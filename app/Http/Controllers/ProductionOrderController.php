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
use App\Services\StockService;
use App\Services\QCService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductionOrderController extends Controller
{
    public function __construct(protected StockService $stockService) {}

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
            $query = ProductionOrder::with(['product', 'bom', 'mir']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('order_no', 'like', "%{$s}%")
                        ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%")
                            ->orWhere('product_code', 'like', "%{$s}%"));
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $orders = $query->orderByDesc('created_at')->get()->map(fn($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'product_id' => $o->product_id,
                'product_name' => $o->product?->product_name,
                'product_code' => $o->product?->product_code,
                'target_qty' => $o->target_qty,
                'uom' => $o->bom?->outputUom?->uom_code,
                'planned_date' => $o->planned_date?->format('Y-m-d'),
                'status' => $o->status,
                'mir_status' => $o->mir?->status,
                'mir_id' => $o->mir?->id,
                'created_at' => $o->created_at?->format('Y-m-d H:i'),
            ]);

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
            'rm_lines.*.uom' => 'nullable|string',
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

            // Get the BOM header to calculate scaling factor
            $bomHeader = BOMHeader::findOrFail($request->input('bom_id'));
            $batchSize = (float) $bomHeader->batch_size;
            $targetQty = (float) $request->input('target_qty');
            $scaleFactor = $batchSize > 0 ? $targetQty / $batchSize : 1;

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
                'data' => ['order' => $order],
                'message' => 'Production order retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, 'Production order not found', 404);
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

            $pendingLines = $order->mir->lines()->where('scan_status', '!=', 'ISSUED')->count();
            if ($pendingLines > 0) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'MIR_NOT_FULLY_ISSUED', 'details' => ['pending_lines' => $pendingLines]],
                    'message' => "MIR has {$pendingLines} lines not yet issued. All materials must be scanned before starting production.",
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
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/production-orders/{id}/confirm-fg
     * Confirm finished goods quantity, post PRODUCTION_RECEIPT stock transaction.
     */
    public function confirmFG(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $validator = Validator::make($request->all(), [
                'actual_qty' => 'required|numeric|min:0.001',
                'rejected_qty' => 'nullable|numeric|min:0|default:0',
                'rework_qty' => 'nullable|numeric|min:0|default:0',
                'fg_bin_id' => 'nullable|integer',
                'fg_warehouse_id' => 'nullable|integer',
                'fg_batch_number' => 'nullable|string|max:50',
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

            $actualQty = (float) $request->input('actual_qty');
            $rejectedQty = (float) $request->input('rejected_qty', 0);
            $reworkQty = (float) $request->input('rework_qty', 0);
            $targetQty = (float) $order->target_qty;

            // Validate total doesn't exceed target (with small tolerance)
            $totalConfirmed = $actualQty + $rejectedQty + $reworkQty;
            if ($totalConfirmed > $targetQty * 1.01) { // 1% tolerance
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'QTY_EXCEEDS_TARGET', 'details' => [
                        'total' => $totalConfirmed,
                        'target' => $targetQty,
                        'tolerance' => '1%',
                    ]],
                    'message' => "Total confirmed qty ({$totalConfirmed}) exceeds target ({$targetQty}) by more than 1%.",
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $userId = (int) $request->input('auth_user_id', 0);
            $warehouseId = $request->input('fg_warehouse_id', $order->mir?->lines->first()?->warehouse_id ?? 1);
            $binId = $request->input('fg_bin_id');
            $batchNumber = $request->input('fg_batch_number', 'FG-' . now()->format('ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
            $qcRequired = (bool) ($order->product?->requires_fg_qc ?? false) || $request->boolean('qc_required');
            $fgBucket = $qcRequired ? 'QC_HOLD' : 'AVAILABLE';

            // Calculate yield percentage
            $yieldPercent = $targetQty > 0 ? round(($actualQty / $targetQty) * 100, 2) : 0;

            $inspectionLot = null;

            DB::connection('tenant')->transaction(function () use ($order, $actualQty, $rejectedQty, $reworkQty, $yieldPercent, $warehouseId, $binId, $batchNumber, $userId, $qcRequired, $fgBucket, &$inspectionLot) {
                // 1. Post FG to stock (PRODUCTION_RECEIPT)
                $this->stockService->post(
                    item: [
                        'product_id' => $order->product_id,
                        'uom_id' => $order->bom?->output_uom_id,
                        'warehouse_id' => $warehouseId,
                        'batch_number' => $batchNumber,
                    ],
                    bucket: $fgBucket,
                    qtyChange: +$actualQty,
                    transactionType: 'PRODUCTION_RECEIPT',
                    referenceType: 'ProductionOrder',
                    referenceId: $order->id,
                    referenceNumber: $order->order_no,
                    userId: $userId,
                    binId: $binId,
                    remarks: "FG receipt for {$order->order_no}"
                );

                // 2. Post scrap to BLOCKED bucket if rejected_qty > 0
                if ($rejectedQty > 0) {
                    $this->stockService->post(
                        item: [
                            'product_id' => $order->product_id,
                            'uom_id' => $order->bom?->output_uom_id,
                            'warehouse_id' => $warehouseId,
                            'batch_number' => $batchNumber,
                        ],
                        bucket: 'BLOCKED',
                        qtyChange: +$rejectedQty,
                        transactionType: 'STOCK_ADJUSTMENT',
                        referenceType: 'ProductionOrder',
                        referenceId: $order->id,
                        referenceNumber: $order->order_no,
                        userId: $userId,
                        binId: $binId,
                        remarks: "Scrap from {$order->order_no}"
                    );
                }

                // 3. Update production order
                $order->update([
                    'status' => 'COMPLETED',
                    'actual_end_at' => now(),
                    'actual_qty' => $actualQty,
                    'rejected_qty' => $rejectedQty,
                    'rework_qty' => $reworkQty,
                    'yield_percent' => $yieldPercent,
                    'fg_bin_id' => $binId,
                    'fg_warehouse_id' => $warehouseId,
                    'fg_batch_number' => $batchNumber,
                    'confirmed_by' => $userId,
                    'confirmed_at' => now(),
                ]);

                Log::info('[ProductionOrder] FG confirmed', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'actual_qty' => $actualQty,
                    'rejected_qty' => $rejectedQty,
                    'yield_percent' => $yieldPercent,
                    'qc_required' => $qcRequired,
                ]);

                if ($qcRequired && $actualQty > 0) {
                    $inspectionLot = app(QCService::class)->createInspectionLotForProduction($order->fresh(['bom']), $actualQty, $userId);
                }
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_no' => $order->order_no,
                        'status' => $order->status,
                        'actual_qty' => $actualQty,
                        'rejected_qty' => $rejectedQty,
                        'rework_qty' => $reworkQty,
                        'yield_percent' => $yieldPercent,
                        'fg_batch_number' => $batchNumber,
                        'fg_bucket' => $fgBucket,
                        'qc_required' => $qcRequired,
                        'inspection_lot_id' => $inspectionLot?->id,
                        'confirmed_at' => now()->toIso8601String(),
                    ],
                ],
                'message' => $qcRequired
                    ? 'FG confirmed, moved to QC hold, and inspection lot created successfully.'
                    : 'FG confirmed and stock posted successfully.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
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
            $batchSize = (float) ($order->bom?->batch_size ?? 1);
            $scaleFactor = $batchSize > 0 ? $actualQty / $batchSize : 0;

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
