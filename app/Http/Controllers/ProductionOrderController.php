<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\BOMDetail;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\FGConfirmationSession;
use App\Services\StockService;
use App\Services\StockQueryService;
use App\Services\QCService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductionOrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected StockQueryService $stockQueryService
    ) {}

    /**
     * Switch to the tenant DB from request context.
     * Same pattern used across the app (VendorPortalController, TokenService, etc.)
     */
    private function switchTenantDb(Request $request): void
    {
        $dbName = $request->input('tenant_db_name');
        if (!$dbName)
            return;
        config(['database.connections.tenant.database' => $dbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
    /**
     * GET /api/v1/production-orders
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $ordersQuery = ProductionOrder::with(['product', 'bom', 'mir', 'productionRequest']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $ordersQuery->where(function ($q) use ($s) {
                    $q->where('order_no', 'like', "%{$s}%")
                        ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%")
                            ->orWhere('product_code', 'like', "%{$s}%"));
                });
            }

            if ($request->filled('status')) {
                $ordersQuery->where('status', $request->input('status'));
            }

            $orders = $ordersQuery->orderByDesc('created_at')->get()->map(fn($o) => [
                'id' => $o->id,
                'is_request' => false,
                'order_no' => $o->order_no,
                'product_id' => $o->product_id,
                'product_name' => $o->product?->product_name,
                'product_code' => $o->product?->product_code,
                'target_qty' => $o->target_qty,
                'uom' => $o->bom?->outputUom ? [
                    'uom_code' => $o->bom->outputUom->uom_code,
                    'uom_name' => $o->bom->outputUom->uom_name,
                ] : null,
                'planned_date' => $o->planned_date?->format('Y-m-d'),
                'status' => $o->status,
                'mir_status' => $o->mir?->status,
                'mir_id' => $o->mir?->id,
                'mir_no' => $o->mir?->mir_no,
                'request_no' => $o->productionRequest?->request_no,
                'confirmed_qty_total' => $o->confirmed_qty_total ?? 0,
                'rejected_qty_total'  => $o->rejected_qty_total ?? 0,
                'actual_qty' => $o->actual_qty ?? 0,
                'yield_percent' => $o->yield_percent ?? 0,
                'fg_batch_number' => $o->fg_batch_number,
                'actual_start_at' => $o->actual_start_at?->format('Y-m-d H:i'),
                'actual_end_at' => $o->actual_end_at?->format('Y-m-d H:i'),
                'remaining_qty' => max(0, (float)$o->target_qty - (float)($o->confirmed_qty_total ?? 0)),
                'created_at' => $o->created_at?->format('Y-m-d H:i'),
            ]);

            // Add ready-to-start requests if not filtering by order status
            if (!$request->filled('status')) {
                $reqQuery = \App\Models\Tenant\ProductionRequest::with(['product', 'bom.outputUom', 'mir'])
                    ->where('status', '!=', 'COMPLETED')
                    ->whereHas('mir', fn($m) => $m->where('status', 'CLOSED'))
                    ->whereNull('production_order_id');

                if ($request->filled('search')) {
                    $s = $request->input('search');
                    $reqQuery->where(function ($q) use ($s) {
                        $q->where('request_no', 'like', "%{$s}%")
                            ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%")
                                ->orWhere('product_code', 'like', "%{$s}%"));
                    });
                }

                $requests = $reqQuery->orderByDesc('created_at')->get()->map(fn($r) => [
                    'id' => $r->id,
                    'is_request' => true,
                    'order_no' => $r->request_no, // Labeling as request no but in the same column
                    'product_id' => $r->product_id,
                    'product_name' => $r->product?->product_name,
                    'product_code' => $r->product?->product_code,
                    'target_qty' => $r->target_qty,
                    'uom' => $r->bom?->outputUom ? [
                        'uom_code' => $r->bom->outputUom->uom_code,
                        'uom_name' => $r->bom->outputUom->uom_name,
                    ] : null,
                    'planned_date' => $r->planned_date?->format('Y-m-d'),
                    'status' => 'READY', // Custom status for the UI
                    'mir_status' => 'CLOSED',
                    'mir_id' => $r->mir_id,
                    'mir_no' => $r->mir?->mir_no,
                    'request_no' => $r->request_no,
                    'actual_qty' => 0,
                    'yield_percent' => 0,
                    'created_at' => $r->created_at?->format('Y-m-d H:i'),
                ]);

                // Merge and sort again
                $orders = $orders->concat($requests)->sortByDesc('created_at')->values();
            }

            return response()->json([
                'success' => true,
                'data' => ['orders' => $orders],
                'message' => 'Production orders retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage());
        }
    }

    /**
     * GET /api/v1/production-orders/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $activeOrders = ProductionOrder::whereIn('status', ['DRAFT', 'IN_PROGRESS'])->count();
            $pendingMIR = MaterialIssueRequest::where('status', 'PENDING')->count();
            $approvedMIR = MaterialIssueRequest::where('status', 'APPROVED')->count();
            $productsWithBOM = BOMHeader::where('bom_status', 'ACTIVE')->distinct('product_id')->count();

            // Additional details for the dashboard
            $completedLast30Days = ProductionOrder::where('status', 'COMPLETED')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();
            
            $totalFGConfirmedLast30Days = ProductionOrder::where('status', 'COMPLETED')
                ->where('updated_at', '>=', now()->subDays(30))
                ->sum('actual_qty');

            $avgYieldLast30Days = ProductionOrder::where('status', 'COMPLETED')
                ->where('updated_at', '>=', now()->subDays(30))
                ->where('yield_percent', '>', 0)
                ->avg('yield_percent') ?? 0;

            // Use StockQueryService for consistent stock data (same as /api/v1/stock/current)
            $warehouseId = $request->has('warehouse_id') ? $request->integer('warehouse_id') : null;
            $itemType = $request->input('item_type');
            $search = trim((string) $request->input('search', ''));

            $stockRows = $warehouseId
                ? $this->stockQueryService->getWarehouseStockSummary($warehouseId)
                : $this->stockQueryService->getGlobalStockSummary();

            $stockRows = collect($stockRows)
                ->when($itemType, function ($rows) use ($itemType) {
                    return $rows->filter(fn($row) => strcasecmp((string) ($row['item_type'] ?? ''), (string) $itemType) === 0);
                })
                ->when($search !== '', function ($rows) use ($search) {
                    return $rows->filter(function ($row) use ($search) {
                        return str_contains(strtolower((string) ($row['item_code'] ?? '')), strtolower($search))
                            || str_contains(strtolower((string) ($row['item_name'] ?? '')), strtolower($search));
                    });
                })
                ->values();

            // Aggregate by item_id + item_type to avoid duplicates and normalize UOMs
            $fgStock = $stockRows
                ->filter(fn($row) => $row['item_type'] === 'Product')
                ->map(function ($row) {
                    return [
                        'product_id' => $row['item_id'],
                        'product_name' => $row['item_name'],
                        'product_code' => $row['item_code'],
                        'uom_id' => $row['uom_id'] ?? null,
                        'uom_code' => $row['uom'],
                        'total_on_hand' => (float) $row['on_hand'],
                        'buckets' => [
                            'AVAILABLE' => (float) $row['available'],
                            'QC_HOLD' => (float) $row['qc_hold'],
                            'PUTAWAY_PENDING' => (float) $row['putaway_pending'],
                            'BLOCKED' => (float) $row['blocked'],
                            'RESERVED' => (float) $row['reserved'],
                        ],
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'activeOrders' => $activeOrders,
                    'pendingMIR' => $pendingMIR,
                    'approvedMIR' => $approvedMIR,
                    'products' => $productsWithBOM,
                    'completedLast30Days' => $completedLast30Days,
                    'totalFGConfirmedLast30Days' => (float) $totalFGConfirmedLast30Days,
                    'avgYieldLast30Days' => (float) $avgYieldLast30Days,
                    'fgStock' => $fgStock,
                ],
                'message' => 'Production dashboard data retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage());
        }
    }

    /**
     * GET /api/v1/production-orders/for-packing
     * Returns completed orders that are eligible for packing (QC passed if required).
     */
    public function forPacking(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $orders = ProductionOrder::with(['product', 'inspectionLots.usageDecision'])
                ->where('status', 'COMPLETED')
                ->whereDoesntHave('packingOrders')
                ->get()
                ->filter(function ($order) {
                    // If QC was required, ensure decision is made
                    if ($order->product?->requires_fg_qc) {
                        $latestLot = $order->inspectionLots()
                            ->where('source_type', 'PRODUCTION')
                            ->latest('id')
                            ->first();
                        return $latestLot && $latestLot->status === 'DECISION_MADE';
                    }
                    return true;
                })
                ->map(fn($o) => [
                    'id' => $o->id,
                    'order_no' => $o->order_no,
                    'product_name' => $o->product?->product_name,
                    'product_code' => $o->product?->product_code,
                    'fg_batch_number' => $o->fg_batch_number,
                    'actual_qty' => $o->actual_qty ?? 0,
                    'qc_passed_qty' => (float) ($o->inspectionLots->sum(fn($l) => $l->usageDecision?->accepted_qty ?? 0)),
                    'requires_fg_qc' => $o->product?->requires_fg_qc ?? false,
                ]);

            return response()->json([
                'success' => true,
                'data' => ['orders' => $orders],
                'message' => 'Eligible orders for packing retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage());
        }
    }

    /**
     * POST /api/v1/production-orders
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'bom_id' => 'required|integer',
            'target_qty' => 'required|numeric|min:0.001',
            'planned_date' => 'required|date',
            'rm_lines' => 'required|array|min:1',
            'rm_lines.*.material_id' => 'required|integer',
            'rm_lines.*.required_qty' => 'required|numeric|min:0.001',
            'rm_lines.*.uom' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            // Get BOM details with materials to resolve UOMs and calculate effective qty
            $bomDetails = BOMDetail::with('uom')
                ->where('bom_id', $request->input('bom_id'))
                ->get()
                ->keyBy('material_id');

            // Build a map of material_id => uom_id from BOM details
            $materialUomMap = $bomDetails->map(fn($d) => $d->uom_id)->toArray();

            // Get the BOM header and target quantity
            $bomHeader = BOMHeader::findOrFail($request->input('bom_id'));
            $runQty = (float) $request->input('target_qty');
            $scaleFactor = $runQty;

            DB::connection('tenant')->transaction(function () use ($request, &$order, &$mir, $bomDetails, $materialUomMap, $scaleFactor) {
                // Generate order number
                $orderNo = 'PRD-' . str_pad(
                    (ProductionOrder::max('id') ?? 0) + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

                $order = ProductionOrder::create([
                    'order_no' => $orderNo,
                    'product_id' => $request->input('product_id'),
                    'bom_id' => $request->input('bom_id'),
                    'target_qty' => $request->input('target_qty'),
                    'planned_date' => $request->input('planned_date'),
                    'status' => 'DRAFT',
                    'created_by' => $request->input('auth_user_id'),
                ]);

                // Generate MIR number
                $mirNo = 'MIR-' . str_pad(
                    (MaterialIssueRequest::max('id') ?? 0) + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

                $mir = MaterialIssueRequest::create([
                    'mir_no' => $mirNo,
                    'production_order_id' => $order->id,
                    'status' => 'PENDING',
                ]);

                // Resolve UOM ids from BOM detail and create MIR line items
                foreach ($request->input('rm_lines') as $line) {
                    $materialId = $line['material_id'];
                    $bomDetail = $bomDetails->get($materialId);

                    // Use the request quantity when provided so floor-stock adjusted deficits are preserved.
                    $bomEffectiveQty = $bomDetail ? (float) $bomDetail->effective_qty : 0;
                    $bomRequiredQty = $bomEffectiveQty * $scaleFactor;
                    $requestedQty = isset($line['required_qty']) ? (float) $line['required_qty'] : 0;
                    $requiredQty = $requestedQty > 0 ? min($requestedQty, $bomRequiredQty ?: $requestedQty) : $bomRequiredQty;

                    // Get UOM from BOM detail (FIX: was previously null)
                    $uomId = $bomDetail?->uom_id ?? null;

                    if ($requiredQty <= 0) {
                        continue;
                    }

                    $mirLine = MIRLineItem::create([
                        'mir_id' => $mir->id,
                        'material_id' => $materialId,
                        'required_qty' => $requiredQty,
                        'uom_id' => $uomId,
                    ]);

                    // Soft-allocate across available bins until the requested quantity is covered.
                    if ($uomId && $requiredQty > 0) {
                        try {
                            $warehouseId = (int) $request->input('warehouse_id', 1);
                            $remainingToReserve = $requiredQty;

                            $stockBalances = StockBalance::forMaterial($materialId)
                                ->inBucket('AVAILABLE')
                                ->inWarehouse($warehouseId)
                                ->whereRaw('qty_on_hand > qty_reserved')
                                ->orderByDesc('qty_on_hand')
                                ->lockForUpdate()
                                ->get();

                            foreach ($stockBalances as $stockBalance) {
                                if ($remainingToReserve <= 0) {
                                    break;
                                }

                                $availableQty = max(0, (float) $stockBalance->qty_on_hand - (float) $stockBalance->qty_reserved);
                                if ($availableQty <= 0) {
                                    continue;
                                }

                                $reserveQty = min($remainingToReserve, $availableQty);

                                $this->stockService->reserve(
                                    item: [
                                        'material_id' => $materialId,
                                        'uom_id' => $uomId,
                                        'warehouse_id' => $stockBalance->warehouse_id,
                                        'batch_number' => $stockBalance->batch_number,
                                    ],
                                    qty: $reserveQty,
                                    referenceType: 'MaterialIssueRequest',
                                    referenceId: $mir->id,
                                    referenceNumber: $mirNo,
                                    userId: (int) $request->input('auth_user_id', 0),
                                    binId: $stockBalance->bin_id
                                );

                                if (!$mirLine->warehouse_id && $stockBalance->warehouse_id) {
                                    $mirLine->warehouse_id = $stockBalance->warehouse_id;
                                }

                                if (!$mirLine->bin_id && $stockBalance->bin_id) {
                                    $mirLine->bin_id = $stockBalance->bin_id;
                                }

                                $remainingToReserve -= $reserveQty;

                                Log::info('[ProductionOrder] Soft-allocated RM for MIR', [
                                    'mir_id' => $mir->id,
                                    'material_id' => $materialId,
                                    'reserved_qty' => $reserveQty,
                                    'warehouse_id' => $stockBalance->warehouse_id,
                                    'bin_id' => $stockBalance->bin_id,
                                ]);
                            }

                            if ($mirLine->isDirty(['warehouse_id', 'bin_id'])) {
                                $mirLine->save();
                            }

                            if ($remainingToReserve > 0.0001) {
                                Log::warning('[ProductionOrder] MIR line partially reserved', [
                                    'mir_id' => $mir->id,
                                    'material_id' => $materialId,
                                    'required_qty' => $requiredQty,
                                    'unreserved_qty' => $remainingToReserve,
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Log but don't fail the order creation if reservation fails
                            Log::warning('[ProductionOrder] Failed to reserve stock for MIR line', [
                                'mir_id' => $mir->id,
                                'material_id' => $materialId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                if (!$mir->lines()->exists()) {
                    $mir->update([
                        'status' => 'APPROVED',
                        'remarks' => 'No RM issue required for this order.',
                        'approved_by' => $request->input('auth_user_id'),
                        'approved_at' => now(),
                    ]);
                }
            });

            $order->load(['product', 'bom.outputUom', 'mir.lines.material', 'mir.lines.uom']);

            return response()->json([
                'success' => true,
                'data' => ['order' => $order, 'mir' => $mir],
                'message' => 'Production order created and MIR sent to Store',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/production-orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $order = ProductionOrder::with(['product', 'bom.outputUom', 'mir.lines.material', 'mir.lines.uom'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_no' => $order->order_no,
                        'product_id' => $order->product_id,
                        'product' => $order->product ? [
                            'product_name' => $order->product->product_name,
                            'product_code' => $order->product->product_code,
                        ] : null,
                        'bom_id' => $order->bom_id,
                        'bom' => $order->bom ? [
                            'output_uom' => $order->bom->outputUom ? [
                                'uom_code' => $order->bom->outputUom->uom_code,
                                'uom_name' => $order->bom->outputUom->uom_name,
                            ] : null
                        ] : null,
                        'target_qty' => $order->target_qty,
                        'planned_date' => $order->planned_date?->format('Y-m-d'),
                        'status' => $order->status,
                        'mir_status' => $order->mir?->status,
                        'mir_id' => $order->mir?->id,
                        'mir' => $order->mir ? [
                            'id'              => $order->mir->id,
                            'mir_no'          => $order->mir->mir_no,
                            'status'          => $order->mir->status,
                            'fully_issued_at' => $order->mir->fully_issued_at?->toIso8601String(),
                            'lines' => $order->mir->lines->map(fn($l) => [
                                'material_id' => $l->material_id,
                                'material' => $l->material ? [
                                    'material_name' => $l->material->material_name,
                                    'material_code' => $l->material->material_code,
                                ] : null,
                                'required_qty' => $l->required_qty,
                                'uom' => $l->uom ? [
                                    'uom_code' => $l->uom->uom_code,
                                    'uom_name' => $l->uom->uom_name,
                                ] : null,
                            ])->toArray()
                        ] : null,
                        'actual_qty' => $order->actual_qty ?? 0,
                        'rejected_qty' => $order->rejected_qty ?? 0,
                        'yield_percent' => $order->yield_percent ?? 0,
                        'fg_batch_number' => $order->fg_batch_number,
                        'actual_start_at' => $order->actual_start_at?->format('Y-m-d H:i'),
                        'actual_end_at' => $order->actual_end_at?->format('Y-m-d H:i'),
                    ]
                ],
                'message' => 'Production order retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/production-orders/{id}/start
     * Start production — validates all MIR lines are issued, transitions to IN_PROGRESS.
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $order = ProductionOrder::with('mir.lines')->findOrFail($id);

            if ($order->status !== 'DRAFT') {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_STATUS', 'details' => []],
                    'message' => 'Only DRAFT orders can be started.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Check if MIR exists and all lines are issued
            if (!$order->mir) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'MIR_NOT_FOUND', 'details' => []],
                    'message' => 'No MIR found for this production order.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Gate: MIR must be CLOSED (store issued + production confirmed floor receipt)
            if ($order->mir->status !== 'CLOSED') {
                // Give a specific message based on current MIR state
                $mirStatus = $order->mir->status;

                if ($mirStatus === 'PENDING' || $mirStatus === 'APPROVED') {
                    $message = 'Store has not yet issued materials. MIR must be fully issued before starting.';
                } elseif ($mirStatus === 'PARTIALLY_ISSUED') {
                    $pendingLines = $order->mir->lines()->whereNotIn('status', ['FULLY_PICKED'])->count();
                    $message = "Store has partially issued materials ({$pendingLines} line(s) remaining). All materials must be issued first.";
                } elseif ($mirStatus === 'FULLY_ISSUED') {
                    $message = 'Materials issued but production floor receipt not yet confirmed. Please confirm receipt first.';
                } elseif ($mirStatus === 'REJECTED') {
                    $message = 'MIR has been rejected. Please resolve the rejection before starting production.';
                } else {
                    $message = "MIR status is '{$mirStatus}'. Must be CLOSED to start production.";
                }

                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'MIR_NOT_READY', 'details' => ['mir_status' => $mirStatus]],
                    'message' => $message,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $order->update([
                'status' => 'IN_PROGRESS',
                'actual_start_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_no' => $order->order_no,
                        'status' => $order->status,
                        'actual_start_at' => $order->actual_start_at?->toIso8601String(),
                    ],
                ],
                'message' => 'Production started successfully.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/production-orders/{id}/confirm-fg
     * Confirm finished goods for a session. Supports partial completion (batch stays open)
     * or full completion (batch closed). Records variance between BOM expected FG and actual.
     */
    public function confirmFG(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $validator = Validator::make($request->all(), [
                'produced_qty'          => 'nullable|numeric|min:0.001',
                'confirmed_qty'         => 'nullable|numeric|min:0.001',
                'rejected_qty'          => 'nullable|numeric|min:0',
                'rejection_reason_code' => 'nullable|string|max:100',
                'rejection_reason_note' => 'nullable|string',
                'fg_bin_id'             => 'nullable|integer',
                'fg_warehouse_id'       => 'nullable|integer',
                'fg_batch_number'       => 'nullable|string|max:50',
                'qc_required'           => 'nullable|boolean',
                // Legacy field aliases kept for backward compat
                'actual_qty'            => 'nullable|numeric|min:0.001',
                'rework_qty'            => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                    'message' => 'Validation failed',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $order = ProductionOrder::with(['product', 'bom', 'mir.lines'])->findOrFail($id);

            if ($order->status !== 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_STATUS', 'details' => []],
                    'message' => 'Only IN_PROGRESS orders can have FG confirmed.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Support both new field name and legacy alias
            $producedQty = (float) (
                $request->input('produced_qty')
                ?? $request->input('confirmed_qty')
                ?? $request->input('actual_qty', 0)
            );
            $rejectedQty    = (float) $request->input('rejected_qty', 0);
            $reworkQty      = (float) $request->input('rework_qty', 0);
            $targetQty      = (float) $order->target_qty;

            if ($producedQty <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION_ERROR', 'details' => [
                        'produced_qty' => ['Produced qty must be greater than 0.'],
                    ]],
                    'message' => 'Produced qty must be greater than 0.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if ($rejectedQty > 0 || $request->filled('rejection_reason_code') || $request->filled('rejection_reason_note')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'QC_REQUIRED', 'details' => [
                        'rejected_qty' => ['Production confirmation cannot record rejected qty. QC must record approval/rejection decisions.'],
                    ]],
                    'message' => 'Production confirmation records only produced qty. QC must record approval and rejection decisions.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Auto-determine completion status – respect request if present, else auto-calculate
            $alreadyProduced = (float) ($order->confirmed_qty_total ?? 0);
            $remainingToProduce = max(0, $targetQty - $alreadyProduced);
            $newProducedTotal = $alreadyProduced + $producedQty;

            if ($newProducedTotal > $targetQty + 0.001) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'OVER_PRODUCTION_NOT_ALLOWED', 'details' => [
                        'produced_qty' => $producedQty,
                        'already_produced_qty' => $alreadyProduced,
                        'target_qty' => $targetQty,
                        'balance_qty' => $remainingToProduce,
                    ]],
                    'message' => "Produced qty ({$producedQty}) exceeds balance qty ({$remainingToProduce}). Overproduction is not allowed.",
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $balanceQty = max(0, $targetQty - $newProducedTotal);
            $completionStatus = $balanceQty <= 0.001 ? 'COMPLETED' : 'PARTIALLY_COMPLETED';

            $userId      = (int) $request->input('auth_user_id', 0);
            $warehouseId = $request->input('fg_warehouse_id', $order->mir?->lines->first()?->warehouse_id ?? 1);
            $binId       = $request->input('fg_bin_id');
            $batchNumber = $request->input('fg_batch_number', 'FG-' . now()->format('ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
            $fgBucket    = 'QC_HOLD';

            $newRejectedTotal  = (float) ($order->rejected_qty_total ?? 0);
            $yieldPercent      = $targetQty > 0 ? round(($newProducedTotal / $targetQty) * 100, 2) : 0;
            $variance          = $newProducedTotal - $targetQty; // negative = under, positive = over

            $inspectionLot = null;

            DB::connection('tenant')->transaction(function () use (
                $order,
                $producedQty,
                $reworkQty,
                $completionStatus,
                $newProducedTotal,
                $newRejectedTotal,
                $yieldPercent,
                $variance,
                $warehouseId,
                $binId,
                $batchNumber,
                $userId,
                $fgBucket,
                &$inspectionLot
            ) {
                // 1. Post FG to stock (PRODUCTION_RECEIPT)
                $this->stockService->post(
                    item: [
                        'product_id'  => $order->product_id,
                        'uom_id'      => $order->bom?->output_uom_id,
                        'warehouse_id' => $warehouseId,
                        'batch_number' => $batchNumber,
                    ],
                    bucket: $fgBucket,
                    qtyChange: +$producedQty,
                    transactionType: 'PRODUCTION_RECEIPT',
                    referenceType: 'ProductionOrder',
                    referenceId: $order->id,
                    referenceNumber: $order->order_no,
                    userId: $userId,
                    binId: $binId,
                    remarks: "FG receipt for {$order->order_no}"
                );

                // 2. Record this production confirmation session.
                FGConfirmationSession::create([
                    'production_order_id'   => $order->id,
                    'confirmed_qty'         => $producedQty,
                    'rejected_qty'          => 0,
                    'rejection_reason_code' => null,
                    'rejection_reason_note' => null,
                    'fg_batch_number'       => $batchNumber,
                    'fg_warehouse_id'       => $warehouseId,
                    'fg_bin_id'             => $binId,
                    'completion_status'     => $completionStatus,
                    'confirmed_by'          => $userId,
                ]);

                // 4. Update production order — keep IN_PROGRESS if partial, close if completed
                $newStatus = $completionStatus === 'COMPLETED' ? 'COMPLETED' : 'IN_PROGRESS';
                $order->update([
                    'status'               => $newStatus,
                    'actual_end_at'        => $newStatus === 'COMPLETED' ? now() : null,
                    'actual_qty'           => $newProducedTotal,
                    'rejected_qty'         => $newRejectedTotal,
                    'rework_qty'           => (float) ($order->rework_qty ?? 0) + $reworkQty,
                    'yield_percent'        => $yieldPercent,
                    'confirmed_qty_total'  => $newProducedTotal,
                    'rejected_qty_total'   => $newRejectedTotal,
                    'fg_bin_id'            => $binId,
                    'fg_warehouse_id'      => $warehouseId,
                    'fg_batch_number'      => $batchNumber,
                    'confirmed_by'         => $userId,
                    'confirmed_at'         => now(),
                ]);

                Log::info('[ProductionOrder] FG session confirmed', [
                    'order_id'          => $order->id,
                    'order_no'          => $order->order_no,
                    'produced_qty'      => $producedQty,
                    'balance_qty'       => max(0, (float) $order->target_qty - $newProducedTotal),
                    'completion_status' => $completionStatus,
                    'yield_percent'     => $yieldPercent,
                    'fg_bucket'         => $fgBucket,
                ]);

                if ($producedQty > 0) {
                    $inspectionLot = app(QCService::class)->createInspectionLotForProduction($order->fresh(['bom']), $producedQty, $userId);
                }
            });

            $order->refresh();
            $remaining = max(0, $targetQty - $newProducedTotal);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id'                   => $order->id,
                        'order_no'             => $order->order_no,
                        'status'               => $order->status,
                        'target_qty'           => $targetQty,
                        'produced_qty'         => $producedQty,
                        'produced_qty_total'   => $newProducedTotal,
                        'confirmed_qty_total'  => $newProducedTotal,
                        'rejected_qty_total'   => $newRejectedTotal,
                        'balance_qty'          => $remaining,
                        'remaining_qty'        => $remaining,
                        'yield_percent'        => $yieldPercent,
                        'variance'             => round($variance, 3),
                        'fg_batch_number'      => $batchNumber,
                        'fg_bucket'            => $fgBucket,
                        'qc_required'          => true,
                        'inspection_lot_id'    => $inspectionLot?->id,
                        'completion_status'    => $completionStatus,
                        'confirmed_at'         => now()->toIso8601String(),
                    ],
                ],
                'message' => $completionStatus === 'COMPLETED'
                    ? 'Production completed. Produced FG moved to QC pending.'
                    : 'Produced qty recorded. Remaining qty stays in WIP and produced FG moved to QC pending.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/production-orders/{id}/fg-sessions
     * List all FG confirmation sessions for an order.
     */
    public function fgSessions(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $order = ProductionOrder::with(['product', 'bom'])->findOrFail($id);
            $sessions = FGConfirmationSession::where('production_order_id', $id)
                ->with('confirmer')
                ->orderBy('created_at')
                ->get()
                ->map(fn($s) => [
                    'id'                    => $s->id,
                    'confirmed_qty'         => $s->confirmed_qty,
                    'rejected_qty'          => $s->rejected_qty,
                    'rejection_reason_code' => $s->rejection_reason_code,
                    'rejection_reason_note' => $s->rejection_reason_note,
                    'fg_batch_number'       => $s->fg_batch_number,
                    'completion_status'     => $s->completion_status,
                    'confirmed_by_name'     => $s->confirmer ? ($s->confirmer->first_name . ' ' . $s->confirmer->last_name) : null,
                    'created_at'            => $s->created_at?->toIso8601String(),
                ]);

            $targetQty        = (float) $order->target_qty;
            $confirmedTotal   = (float) ($order->confirmed_qty_total ?? 0);
            $rejectedTotal    = (float) ($order->rejected_qty_total ?? 0);
            $remaining        = max(0, $targetQty - $confirmedTotal);
            $yieldPercent     = $targetQty > 0 ? round(($confirmedTotal / $targetQty) * 100, 2) : 0;
            $variance         = round($confirmedTotal - $targetQty, 3);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id'                  => $order->id,
                        'order_no'            => $order->order_no,
                        'product_name'        => $order->product?->product_name,
                        'product_code'        => $order->product?->product_code,
                        'target_qty'          => $targetQty,
                        'uom'                 => $order->bom?->outputUom?->uom_code,
                        'status'              => $order->status,
                        'produced_qty_total'  => $confirmedTotal,
                        'confirmed_qty_total' => $confirmedTotal,
                        'rejected_qty_total'  => $rejectedTotal,
                        'balance_qty'         => $remaining,
                        'remaining_qty'       => $remaining,
                        'yield_percent'       => $yieldPercent,
                        'variance'            => $variance,
                    ],
                    'sessions' => $sessions,
                ],
                'message' => 'FG confirmation sessions retrieved.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * PATCH /api/v1/production-orders/{id}/confirm-receipt
     * Production floor confirms materials received from Store.
     * Closes the MIR and unlocks the production order to start.
     */
    public function confirmReceipt(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $order = ProductionOrder::with(['mir.lines'])->findOrFail($id);

            if (!$order->mir) {
                return response()->json([
                    'success' => false,
                    'message' => 'No MIR found for this production order.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if ($order->mir->status !== 'FULLY_ISSUED') {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot confirm receipt. MIR status is '{$order->mir->status}', must be FULLY_ISSUED.",
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $notes = $request->input('receiving_notes');
            $userId = (int) $request->input('auth_user_id', 0);

            // Close the MIR — marks handover from Store to Production
            $order->mir->update([
                'status'    => 'CLOSED',
                'closed_at' => now(),
            ]);

            Log::info('[ProductionOrder] Floor receipt confirmed', [
                'order_id'  => $order->id,
                'order_no'  => $order->order_no,
                'mir_id'    => $order->mir->id,
                'user_id'   => $userId,
                'notes'     => $notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id'   => $order->id,
                    'order_no'   => $order->order_no,
                    'mir_status' => 'CLOSED',
                    'can_start'  => true,
                ],
                'message' => 'Materials confirmed received. Production can now start.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/production-orders/{id}/variance
     * Get yield report: target vs actual, RM consumed vs BOM expected.
     */
    public function variance(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $order = ProductionOrder::with(['product', 'bom.bomDetails.material', 'mir.lines.material'])
                ->findOrFail($id);

            $targetQty = (float) $order->target_qty;
            $actualQty = (float) ($order->actual_qty ?? 0);
            $scaleFactor = $actualQty;

            // Get actual RM consumed from inventory transactions
            $rmConsumed = InventoryTransaction::where('reference_type', 'MaterialIssueRequest')
                ->where('reference_id', $order->mir?->id)
                ->where('transaction_type', 'PRODUCTION_ISSUE')
                ->select('material_id', DB::connection('tenant')->raw('SUM(-qty_change) as total_consumed'))
                ->groupBy('material_id')
                ->pluck('total_consumed', 'material_id')
                ->toArray();

            // Build variance report per RM line
            $rmLines = $order->bom?->bomDetails->map(function ($bomDetail) use ($scaleFactor, $rmConsumed) {
                $materialId = $bomDetail->material_id;
                $bomRequired = (float) $bomDetail->qty_required;
                $bomEffective = (float) $bomDetail->effective_qty;
                $bomScaledRequired = $bomRequired * $scaleFactor;
                $bomScaledEffective = $bomEffective * $scaleFactor;
                $actuallyConsumed = $rmConsumed[$materialId] ?? 0;
                $variance = $actuallyConsumed - $bomScaledEffective;

                return [
                    'material_id' => $materialId,
                    'material_name' => $bomDetail->material?->material_name,
                    'material_code' => $bomDetail->material?->material_code,
                    'bom_required' => round($bomScaledRequired, 4),
                    'bom_effective' => round($bomScaledEffective, 4),
                    'actually_consumed' => round($actuallyConsumed, 4),
                    'variance' => round($variance, 4),
                    'variance_percent' => $bomScaledEffective > 0 ? round(($variance / $bomScaledEffective) * 100, 2) : 0,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_no' => $order->order_no,
                        'product_name' => $order->product?->product_name,
                        'product_code' => $order->product?->product_code,
                    ],
                    'production' => [
                        'target_qty' => $targetQty,
                        'actual_qty' => $actualQty,
                        'rejected_qty' => (float) ($order->rejected_qty ?? 0),
                        'rework_qty' => (float) ($order->rework_qty ?? 0),
                        'yield_percent' => (float) ($order->yield_percent ?? 0),
                    ],
                    'rm_lines' => $rmLines,
                ],
                'message' => 'Variance report retrieved successfully.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($requestId, 'Production order not found', 404);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    private function error(string $requestId, string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => $status === 404 ? 'NOT_FOUND' : 'ERROR', 'details' => []],
            'message' => $message,
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
