<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Carton;
use App\Models\Tenant\CartonItem;
use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\PackingOrder;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\StockBalance;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PackingOrderController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    private function switchTenantDb(Request $request): void
    {
        $dbName = $request->input('tenant_db_name');
        if (!$dbName) {
            return;
        }

        config(['database.connections.tenant.database' => $dbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function index(Request $request): JsonResponse
    {
        $this->switchTenantDb($request);

        $orders = PackingOrder::with(['productionOrder.product', 'cartons.items'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['packing_orders' => $orders],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->switchTenantDb($request);

        $order = PackingOrder::with([
            'productionOrder.product',
            'productionOrder.bom',
            'productionOrder.inspectionLots.usageDecision',
            'cartons.items.product',
            'cartons.items.uom',
            'cartons.childCartons',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['packing_order' => $order],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'production_order_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $order = ProductionOrder::with(['product', 'inspectionLots.usageDecision'])->findOrFail($request->integer('production_order_id'));

        if ($order->status !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Packing can start only after production is completed.',
            ], 422);
        }

        $fgLot = $order->inspectionLots()
            ->where('source_type', 'PRODUCTION')
            ->with('usageDecision')
            ->latest('id')
            ->first();

        if ($fgLot && $fgLot->status !== 'DECISION_MADE') {
            return response()->json([
                'success' => false,
                'message' => 'FG QC decision is pending for this production order.',
            ], 422);
        }

        $packingOrder = PackingOrder::create([
            'packing_order_no' => 'PKG-' . str_pad((PackingOrder::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT),
            'production_order_id' => $order->id,
            'status' => 'PENDING',
            'created_by' => $request->input('auth_user_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Packing order created successfully.',
            'data' => ['packing_order' => $packingOrder->load('productionOrder.product')],
        ], 201);
    }

    public function createCarton(Request $request, int $id): JsonResponse
    {
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'carton_type' => 'nullable|in:INNER,OUTER,PALLET',
            'carton_barcode' => 'nullable|string|max:100',
            'parent_carton_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $packingOrder = PackingOrder::findOrFail($id);

        // Check if there's already an open carton for this packing order
        $existingOpenCarton = Carton::where('packing_order_id', $id)
            ->where('status', 'OPEN')
            ->first();

        if ($existingOpenCarton) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot open a new carton. Please close or pack the existing open carton first.',
                'error' => [
                    'code' => 'OPEN_CARTON_EXISTS',
                    'details' => [
                        'existing_carton_id' => $existingOpenCarton->id,
                        'existing_carton_barcode' => $existingOpenCarton->carton_barcode,
                    ],
                ],
            ], 409);
        }

        $carton = Carton::create([
            'carton_barcode' => $request->input('carton_barcode', 'PKG-' . now()->format('ymd') . '-' . str_pad((Carton::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT)),
            'packing_order_id' => $packingOrder->id,
            'carton_type' => $request->input('carton_type', 'OUTER'),
            'parent_carton_id' => $request->input('parent_carton_id'),
            'status' => 'OPEN',
        ]);

        if ($packingOrder->status === 'PENDING') {
            $packingOrder->update(['status' => 'IN_PROGRESS']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Carton created successfully.',
            'data' => ['carton' => $carton],
        ], 201);
    }

    public function scanIntoCarton(Request $request, int $id, int $cartonId): JsonResponse
    {
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'product_barcode' => 'required|string|max:100',
            'qty' => 'nullable|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $packingOrder = PackingOrder::with('productionOrder.bom')->findOrFail($id);
        $carton = Carton::where('packing_order_id', $id)->findOrFail($cartonId);

        if ($carton->status !== 'OPEN') {
            return response()->json([
                'success' => false,
                'message' => 'Only OPEN cartons can receive scanned FG.',
            ], 422);
        }

        $order = $packingOrder->productionOrder;
        $product = Product::where('product_code', $request->input('product_barcode'))->first();

        if (!$product || $product->id !== $order->product_id) {
            return response()->json([
                'success' => false,
                'message' => 'Scanned product does not match the production order FG.',
            ], 422);
        }

        $qty = (float) $request->input('qty', 1);
        $batchNumber = $order->fg_batch_number;
        $warehouseId = (int) ($order->fg_warehouse_id ?? 1);
        $binId = $order->fg_bin_id;

        $stockQuery = StockBalance::forProduct($product->id)
            ->inBucket('AVAILABLE')
            ->inWarehouse($warehouseId);

        if ($binId) {
            $stockQuery->where('bin_id', $binId);
        }

        if ($batchNumber) {
            $stockQuery->where('batch_number', $batchNumber);
        }

        $availableQty = (float) $stockQuery->sum('qty_on_hand');

        if ($availableQty < $qty) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient FG stock for packing. Available: {$availableQty}, Required: {$qty}.",
            ], 422);
        }

        // Validate packing limit not exceeded (check across all packages in this order)
        $qcLots = $order->inspectionLots()->where('source_type', 'PRODUCTION')->with('usageDecision')->get();
        $totalAcceptedQty = (float) $qcLots->sum(fn($l) => $l->usageDecision?->accepted_qty ?? 0);
        
        // If no QC lots exist but QC is not required, use actual produced qty as limit
        $packingLimit = $totalAcceptedQty > 0 ? $totalAcceptedQty : (float) $order->actual_qty;

        $alreadyPackedTotal = (float) CartonItem::whereHas('carton', function($q) use ($id) {
            $q->where('packing_order_id', $id);
        })->sum('qty');

        if ($alreadyPackedTotal + $qty > $packingLimit + 0.0001) {
            return response()->json([
                'success' => false,
                'message' => "Cannot scan more than the available quantity. Limit (QC Passed/Produced): {$packingLimit}, Total packed so far: {$alreadyPackedTotal}, Attempting to add: {$qty}.",
            ], 422);
        }

        DB::connection('tenant')->transaction(function () use ($request, $packingOrder, $carton, $product, $order, $qty, $batchNumber, $warehouseId, $binId) {
            $this->stockService->transfer(
                item: [
                    'product_id' => $product->id,
                    'uom_id' => $order->bom?->output_uom_id ?? $product->pack_uom_id,
                    'warehouse_id' => $warehouseId,
                    'batch_number' => $batchNumber,
                ],
                fromBucket: 'AVAILABLE',
                toBucket: 'RESERVED',
                qty: $qty,
                transactionType: 'TRANSFER',
                referenceType: 'Carton',
                referenceId: $carton->id,
                referenceNumber: $carton->carton_barcode,
                userId: (int) ($request->input('auth_user_id') ?? 0),
                fromBinId: $binId,
                toBinId: $binId,
                remarks: "Packed FG for {$packingOrder->packing_order_no}"
            );

            CartonItem::create([
                'carton_id' => $carton->id,
                'product_id' => $product->id,
                'product_barcode' => $request->input('product_barcode'),
                'qty' => $qty,
                'uom_id' => $order->bom?->output_uom_id ?? $product->pack_uom_id,
                'batch_number' => $batchNumber,
                'scanned_at' => now(),
                'scanned_by' => $request->input('auth_user_id'),
            ]);

            $carton->update([
                'calculated_weight' => (float) $carton->items()->sum(DB::raw('qty')) * (float) ($product->pack_size ?? 0),
            ]);

            if ($packingOrder->status === 'PENDING') {
                $packingOrder->update(['status' => 'IN_PROGRESS']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'FG scanned into carton successfully.',
            'data' => ['carton' => $carton->fresh('items.product')],
        ]);
    }

    public function sealCarton(Request $request, int $id, int $cartonId): JsonResponse
    {
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'actual_weight' => 'nullable|numeric|min:0',
            'labelled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $carton = Carton::where('packing_order_id', $id)->with('packingOrder.productionOrder')->findOrFail($cartonId);

        if ($carton->items()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot seal an empty carton.',
            ], 422);
        }

        // Validate packing limit not exceeded (check order total)
        $packingOrder = $carton->packingOrder;
        $order = $packingOrder->productionOrder;
        
        $orderTotalPacked = (float) CartonItem::whereHas('carton', function($q) use ($id) {
            $q->where('packing_order_id', $id);
        })->sum('qty');

        $qcLots = $order->inspectionLots()->where('source_type', 'PRODUCTION')->with('usageDecision')->get();
        $totalAcceptedQty = (float) $qcLots->sum(fn($l) => $l->usageDecision?->accepted_qty ?? 0);
        $packingLimit = $totalAcceptedQty > 0 ? $totalAcceptedQty : (float) ($order->actual_qty ?? 0);

        if ($order && $orderTotalPacked > $packingLimit + 0.0001) {
            return response()->json([
                'success' => false,
                'message' => "Cannot seal package. Total packed quantity across all packages ({$orderTotalPacked}) exceeds available limit ({$packingLimit}).",
            ], 422);
        }

        $labelled = filter_var($request->input('labelled', true), FILTER_VALIDATE_BOOLEAN);

        $carton->update([
            'status' => $labelled ? 'LABELLED' : 'SEALED',
            'actual_weight' => $request->input('actual_weight', $carton->actual_weight),
            'sealed_at' => now(),
            'labelled_at' => $labelled ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carton sealed successfully.',
            'data' => ['carton' => $carton],
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $this->switchTenantDb($request);

        $packingOrder = PackingOrder::with('cartons.items')->findOrFail($id);

        if ($packingOrder->cartons()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'At least one carton is required before completing packing.',
            ], 422);
        }

        $openCartons = $packingOrder->cartons()->where('status', 'OPEN')->count();
        if ($openCartons > 0) {
            return response()->json([
                'success' => false,
                'message' => "There are {$openCartons} open cartons. Seal them before completing packing.",
            ], 422);
        }

        // Validate total packed quantity doesn't exceed target
        $totalPackedQty = 0;
        foreach ($packingOrder->cartons as $carton) {
            $totalPackedQty += (float) $carton->items()->sum('qty');
        }

        $order = $packingOrder->productionOrder;
        
        $orderTotalPacked = (float) CartonItem::whereHas('carton', function($q) use ($id) {
            $q->where('packing_order_id', $id);
        })->sum('qty');

        $qcLots = $order->inspectionLots()->where('source_type', 'PRODUCTION')->with('usageDecision')->get();
        $totalAcceptedQty = (float) $qcLots->sum(fn($l) => $l->usageDecision?->accepted_qty ?? 0);
        $packingLimit = $totalAcceptedQty > 0 ? $totalAcceptedQty : (float) ($order->actual_qty ?? 0);

        if ($order && $orderTotalPacked > $packingLimit + 0.0001) {
            return response()->json([
                'success' => false,
                'message' => "Cannot complete packing. Total packed quantity ({$orderTotalPacked}) exceeds available limit ({$packingLimit}).",
            ], 422);
        }

        $packingOrder->update(['status' => 'COMPLETED']);

        return response()->json([
            'success' => true,
            'message' => 'Packing order completed successfully.',
            'data' => ['packing_order' => $packingOrder->fresh('cartons.items')],
        ]);
    }
}
