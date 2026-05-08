<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant\SalesOrder;
use App\Models\Tenant\SalesOrderLineItem;
use App\Models\Tenant\SoPickLine;
use App\Models\Tenant\SoBoxLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\StockQueryService;
use App\Services\StockService;

class SalesOrderController extends Controller
{
    public function __construct(
        protected StockQueryService $stockQueryService,
        protected StockService $stockService
    ) {}

    // GET /api/v1/sales-orders
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'createdBy', 'lineItems.product'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('search')) {
            $query->where('so_number', 'like', '%' . $request->search . '%');
        }
        
        // Date range filtering for forecast calculations
        if ($request->filled('start_date')) {
            $query->whereDate('so_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('so_date', '<=', $request->end_date);
        }

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    // GET /api/v1/sales-orders/{id}
    public function show($id)
    {
        $so = SalesOrder::with([
            'customer',
            'lineItems.product',
            'lineItems.uom',
            'createdBy',
            'confirmedBy',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $so]);
    }

    // ── Security Portal endpoints ─────────────────────────────────────────

    // GET /api/v1/security/outward/packed
    public function securityPackedList()
    {
        $orders = SalesOrder::with(['customer', 'lineItems.product'])
            ->where('status', 'PACKED')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    // GET /api/v1/security/outward/dispatched
    public function securityDispatchedList()
    {
        $orders = SalesOrder::with(['customer', 'lineItems.product'])
            ->where('status', 'DISPATCHED')
            ->orderBy('dispatched_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    // GET /api/v1/security/outward/{id}
    public function securityShowSO($id)
    {
        $so = SalesOrder::with([
            'customer',
            'lineItems.product',
            'lineItems.uom',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $so]);
    }

    // POST /api/v1/sales-orders
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'            => 'required|integer',
            'required_delivery_date' => 'required|date|after_or_equal:today',
            'payment_terms'          => 'nullable|string|max:30',
            'billing_address'        => 'nullable|string|max:500',
            'shipping_address'       => 'nullable|string|max:500',
            'remarks'                => 'nullable|string',
            'line_items'             => 'required|array|min:1',
            'line_items.*.product_id'      => 'required|integer',
            'line_items.*.qty'             => 'required|numeric|min:0.001',
            'line_items.*.uom_id'          => 'required|integer',
            'line_items.*.unit_price'      => 'nullable|numeric|min:0',
            'line_items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $subtotal = 0;
            $lineData = [];

            foreach ($request->line_items as $item) {
                $unitPrice = $item['unit_price'] ?? 0;
                $discPct   = $item['discount_percent'] ?? 0;
                $lineTotal = $item['qty'] * $unitPrice * (1 - $discPct / 100);
                $subtotal += $lineTotal;

                $lineData[] = [
                    'product_id'       => $item['product_id'],
                    'qty'              => $item['qty'],
                    'uom_id'           => $item['uom_id'],
                    'unit_price'       => $unitPrice,
                    'discount_percent' => $discPct,
                    'line_total'       => round($lineTotal, 2),
                    'availability'     => 'PENDING',
                    'available_qty'    => 0,
                ];
            }

            $so = SalesOrder::create([
                'so_number'              => SalesOrder::generateSoNumber(),
                'customer_id'            => $request->customer_id,
                'billing_address'        => $request->billing_address,
                'shipping_address'       => $request->shipping_address,
                'so_date'                => now()->toDateString(),
                'required_delivery_date' => $request->required_delivery_date,
                'payment_terms'          => $request->payment_terms ?? 'NET30',
                'subtotal'               => round($subtotal, 2),
                'discount_amount'        => 0,
                'tax_amount'             => 0,
                'grand_total'            => round($subtotal, 2),
                'status'                 => 'DRAFT',
                'stock_status'           => 'PENDING',
                'remarks'                => $request->remarks,
                'created_by'             => $request->input('auth_user_id'),
            ]);

            foreach ($lineData as $line) {
                $line['so_id'] = $so->id;
                SalesOrderLineItem::create($line);
            }

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales Order created successfully.',
                'data'    => $so->load(['customer', 'lineItems.product', 'lineItems.uom']),
            ], 201);
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // PATCH /api/v1/sales-orders/{id}/confirm
    public function confirm(Request $request, $id)
    {
        $so = SalesOrder::findOrFail($id);

        if ($so->status !== 'DRAFT') {
            return response()->json(['success' => false, 'message' => 'Only DRAFT orders can be confirmed.'], 422);
        }

        $so->update([
            'status'       => 'CONFIRMED',
            'confirmed_by' => $request->input('auth_user_id'),
            'confirmed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Sales Order confirmed.', 'data' => $so]);
    }

    // PATCH /api/v1/sales-orders/{id}/check-stock
    public function checkStock(Request $request, $id)
    {
        $so = SalesOrder::with('lineItems')->findOrFail($id);

        if (!in_array($so->status, ['CONFIRMED', 'DRAFT'])) {
            return response()->json(['success' => false, 'message' => 'Stock check not applicable for this status.'], 422);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $overallAvailable = true;
            $anyPartial = false;

            foreach ($so->lineItems as $line) {
                $stockQty = $this->stockQueryService->getAvailableProductStock((int) $line->product_id);

                if ($stockQty <= 0) {
                    $availability = 'UNAVAILABLE';
                    $overallAvailable = false;
                } elseif ($stockQty >= $line->qty) {
                    $availability = 'AVAILABLE';
                } else {
                    $availability = 'PARTIAL';
                    $anyPartial = true;
                    $overallAvailable = false;
                }

                $line->update([
                    'available_qty' => min($stockQty, $line->qty),
                    'availability'  => $availability,
                ]);
            }

            $stockStatus = $overallAvailable ? 'AVAILABLE' : ($anyPartial ? 'PARTIAL' : 'UNAVAILABLE');

            $so->update([
                'status'       => 'STOCK_CHECKED',
                'stock_status' => $stockStatus,
            ]);

            $message = $stockStatus === 'AVAILABLE'
                ? 'Stock check complete. FG stock is available. You can generate the picklist now.'
                : 'Stock check complete. Insufficient stock found. Please review or create a PR.';

            DB::connection('tenant')->commit();

            return response()->json([
                'success'      => true,
                'message'      => $message,
                'stock_status' => $stockStatus,
                'data'         => $so->load('lineItems.product'),
            ]);
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // PATCH /api/v1/sales-orders/{id}/cancel
    public function cancel(Request $request, $id)
    {
        $so = SalesOrder::findOrFail($id);

        if (in_array($so->status, ['DISPATCHED', 'DELIVERED', 'CANCELLED'])) {
            return response()->json(['success' => false, 'message' => 'Cannot cancel this order.'], 422);
        }

        // Release any reserved stock
        if (in_array($so->status, ['PICKING', 'PACKED'])) {
            $actorId = $this->resolveStockActorId($request, $so);

            foreach ($so->lineItems as $line) {
                $this->stockService->releaseProductReservation(
                    [
                        'product_id' => (int) $line->product_id,
                        'uom_id' => (int) $line->uom_id,
                    ],
                    (float) $line->qty,
                    'SalesOrder',
                    (int) $so->id,
                    (string) $so->so_number,
                    $actorId
                );
            }
        }

        $so->update(['status' => 'CANCELLED']);

        return response()->json(['success' => true, 'message' => 'Sales Order cancelled.', 'data' => $so]);
    }

    // POST /api/v1/sales-orders/{id}/generate-picklist
    public function generatePicklist(Request $request, $id)
    {
        $so = SalesOrder::with('lineItems.product')->findOrFail($id);

        if ($so->status !== 'STOCK_CHECKED' || $so->stock_status !== 'AVAILABLE') {
            return response()->json(['success' => false, 'message' => 'Picklist can only be generated for STOCK_CHECKED orders with AVAILABLE stock.'], 422);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $actorId = $this->resolveStockActorId($request, $so);

            foreach ($so->lineItems as $line) {
                $this->stockService->reserveProduct(
                    [
                        'product_id' => (int) $line->product_id,
                        'uom_id' => (int) $line->uom_id,
                    ],
                    (float) $line->qty,
                    'SalesOrder',
                    (int) $so->id,
                    (string) $so->so_number,
                    $actorId
                );
            }

            $so->update(['status' => 'PICKING', 'updated_at' => now()]);

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Picklist generated and dispatched to HHT. Stock reserved.',
                'data'    => $so->load('lineItems.product'),
            ]);
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // PATCH /api/v1/sales-orders/{id}/dispatch
    public function markPacked(Request $request, $id)
    {
        $so = SalesOrder::with('lineItems')->findOrFail($id);

        if ($so->status !== 'PICKING') {
            return response()->json(['success' => false, 'message' => 'Only PICKING orders can be marked as packed.'], 422);
        }

        // Validate pick_lines payload
        $validator = Validator::make($request->all(), [
            'pick_lines'              => 'required|array|min:1',
            'pick_lines.*.pallet_no'  => 'required|string|max:60',
            'pick_lines.*.bin_code'   => 'required|string|max:30',
            'pick_lines.*.bin_id'     => 'nullable|integer',
            'pick_lines.*.item_id'    => 'required|integer',
            'pick_lines.*.qty'        => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Verify all submitted item_ids belong to this SO's line items
        $validProductIds = $so->lineItems->pluck('product_id')->map(fn($v) => (int) $v)->toArray();
        foreach ($request->pick_lines as $line) {
            if (!in_array((int) $line['item_id'], $validProductIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ID ' . $line['item_id'] . ' does not belong to this sales order picklist.',
                ], 422);
            }
        }

        // Resolve actor — auth_user_id is injected by ValidateJWT middleware
        $actorId = $request->input('auth_user_id') ?? null;

        DB::connection('tenant')->beginTransaction();
        try {
            // Store pick lines
            foreach ($request->pick_lines as $line) {
                SoPickLine::create([
                    'so_id'      => $so->id,
                    'pallet_no'  => $line['pallet_no'],
                    'bin_id'     => $line['bin_id'] ?? null,
                    'bin_code'   => $line['bin_code'],
                    'product_id' => (int) $line['item_id'],
                    'qty'        => (float) $line['qty'],
                    'picked_by'  => $actorId,
                ]);
            }

            $so->update(['status' => 'PACKED', 'updated_at' => now()]);

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Order marked as PACKED. Ready for dispatch.',
                'data'    => $so->fresh(['customer', 'lineItems.product', 'pickLines.product']),
            ]);
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // PATCH /api/v1/sales-orders/{id}/mark-packing-complete
    public function markPackingComplete(Request $request, $id)
    {
        $so = SalesOrder::with('lineItems.product')->findOrFail($id);

        if ($so->status !== 'PACKED') {
            return response()->json(['success' => false, 'message' => 'Only PACKED orders can have packing completed.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'box_lines'             => 'required|array|min:1',
            'box_lines.*.box_no'    => 'required|string|max:60',
            'box_lines.*.item_id'   => 'required|integer',
            'box_lines.*.item_code' => 'nullable|string|max:50',
            'box_lines.*.item_name' => 'nullable|string|max:200',
            'box_lines.*.qty'       => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Verify all submitted item_ids belong to this SO's line items
        $validProductIds = $so->lineItems->pluck('product_id')->map(fn($v) => (int) $v)->toArray();
        foreach ($request->box_lines as $line) {
            if (!in_array((int) $line['item_id'], $validProductIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ID ' . $line['item_id'] . ' does not belong to this sales order.',
                ], 422);
            }
        }

        $actorId = $request->input('auth_user_id') ?? null;

        // Build enriched packing_data — embed product details for security portal
        $productMap = $so->lineItems->keyBy('product_id');
        $packingData = collect($request->box_lines)->map(function ($line) use ($productMap, $actorId) {
            $li = $productMap->get((int) $line['item_id']);
            return [
                'box_no'     => $line['box_no'],
                'item_id'    => (int) $line['item_id'],
                'item_code'  => $line['item_code'] ?? ($li?->product?->product_code ?? ''),
                'item_name'  => $line['item_name'] ?? ($li?->product?->product_name ?? ''),
                'qty'        => (float) $line['qty'],
                'packed_by'  => $actorId,
                'packed_at'  => now()->toIso8601String(),
            ];
        })->values()->toArray();

        $so->update(['packing_data' => $packingData]);

        return response()->json([
            'success' => true,
            'message' => 'Packing complete. Ready for security dispatch.',
            'data'    => $so->fresh(['customer', 'lineItems.product', 'lineItems.uom']),
        ]);
    }

    public function dispatch(Request $request, $id)
    {
        $so = SalesOrder::with('lineItems')->findOrFail($id);

        if (!in_array($so->status, ['PICKING', 'PACKED'])) {
            return response()->json(['success' => false, 'message' => 'Only PICKING or PACKED orders can be dispatched.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_number'          => 'required|string|max:30',
            'driver_name'             => 'required|string|max:100',
            'logistics_partner'       => 'nullable|string|max:100',
            'expected_delivery_date'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $actorId = $this->resolveStockActorId($request, $so);

            foreach ($so->lineItems as $line) {
                $this->stockService->shipReservedProduct(
                    [
                        'product_id' => (int) $line->product_id,
                        'uom_id' => (int) $line->uom_id,
                    ],
                    (float) $line->qty,
                    'SalesOrder',
                    (int) $so->id,
                    (string) $so->so_number,
                    $actorId
                );
            }

            $so->update([
                'status'            => 'DISPATCHED',
                'vehicle_number'    => $request->vehicle_number,
                'driver_name'       => $request->driver_name,
                'logistics_partner' => $request->logistics_partner,
                'dispatched_at'     => now(),
                'dispatched_by'     => $request->input('auth_user_id'),
                'updated_at'        => now(),
            ]);

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Dispatch confirmed. Delivery Challan & E-Way Bill generated. FG stock updated.',
                'data'    => $so->fresh(['customer', 'lineItems.product']),
            ]);
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/v1/sales-orders/dashboard-stats
    public function dashboardStats()
    {
        $today = now()->toDateString();

        $stats = [
            'total_open'          => SalesOrder::whereIn('status', ['CONFIRMED', 'STOCK_CHECKED', 'PICKING', 'PACKED'])->count(),
            'due_today'           => SalesOrder::whereDate('required_delivery_date', $today)
                ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
            'stock_available'     => SalesOrder::where('stock_status', 'AVAILABLE')->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
            'stock_partial'       => SalesOrder::where('stock_status', 'PARTIAL')->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
            'pending_stock_check' => SalesOrder::whereIn('status', ['DRAFT', 'CONFIRMED'])->count(),
            'pending_picklist'    => SalesOrder::where('status', 'STOCK_CHECKED')->where('stock_status', 'AVAILABLE')->count(),
            'in_picking'          => SalesOrder::where('status', 'PICKING')->count(),
            'pending_dispatch'    => SalesOrder::whereIn('status', ['PACKED'])->count(),
            'dispatched_today'    => SalesOrder::whereDate('dispatched_at', $today)->count(),
        ];

        $recent = SalesOrder::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'so_number', 'customer_id', 'required_delivery_date', 'grand_total', 'status', 'stock_status', 'created_at']);

        return response()->json(['success' => true, 'data' => ['stats' => $stats, 'recent_orders' => $recent]]);
    }

    // GET /api/v1/sales-orders/fg-stock
    public function fgStock()
    {
        $fgStock = collect($this->stockQueryService->getGlobalStockSummary())
            ->where('item_type', 'Product')
            ->values()
            ->map(fn(array $item) => [
                'product_id'   => $item['item_id'],
                'product_code' => $item['item_code'],
                'product_name' => $item['item_name'],
                'uom_code'     => $item['uom'],
                'available'    => (float) ($item['available'] ?? 0),
                'qc_hold'      => (float) ($item['qc_hold'] ?? 0),
                'reserved'     => (float) ($item['reserved'] ?? 0),
                'total'        => (float) ($item['on_hand'] ?? 0),
            ])
            ->sortByDesc('available')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $fgStock,
        ]);
    }

    private function resolveStockActorId(Request $request, SalesOrder $so): int
    {
        $actorId = $request->input('auth_user_id')
            ?? $so->confirmed_by
            ?? $so->created_by
            ?? $so->dispatched_by;

        if (!$actorId) {
            throw new \RuntimeException('A valid user is required to post stock movements for this sales order.');
        }

        return (int) $actorId;
    }
}
