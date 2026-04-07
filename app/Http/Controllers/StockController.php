<?php

namespace App\Http\Controllers;

use App\Services\StockQueryService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockController — Read-only stock query API.
 *
 * All endpoints are GET-only. Mutations go through their
 * respective domain controllers (GRN, Putaway, Sales, etc.)
 * which call StockService internally.
 *
 * API Routes:
 *   GET /api/v1/stock/available/{materialId}
 *   GET /api/v1/stock/snapshot/{materialId}
 *   GET /api/v1/stock/history/{materialId}
 *   GET /api/v1/stock/warehouse/{warehouseId}
 *   GET /api/v1/stock/bucket/{materialId}/{bucket}
 */
class StockController extends Controller
{
    public function __construct(
        protected StockQueryService $stockQueryService,
        protected StockService      $stockService
    ) {}

    /**
     * Get available stock (ATP) for a material.
     *
     * GET /api/v1/stock/available/{materialId}?warehouse_id=1&bin_id=5
     *
     * Response example:
     * {
     *   "success": true,
     *   "data": { "material_id": 12, "warehouse_id": 1, "available_qty": 450.000 }
     * }
     */
    public function available(int $materialId, Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->integer('warehouse_id', 0);
            $binId       = $request->has('bin_id') ? $request->integer('bin_id') : null;

            if (!$warehouseId) {
                return response()->json([
                    'success' => false,
                    'message' => 'warehouse_id is required',
                ], 422);
            }

            $qty = $this->stockQueryService->getAvailableStock($materialId, $warehouseId, $binId);

            return response()->json([
                'success' => true,
                'data'    => [
                    'material_id'   => $materialId,
                    'warehouse_id'  => $warehouseId,
                    'bin_id'        => $binId,
                    'available_qty' => $qty,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[StockController] available() failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get full stock snapshot (all buckets) for a material.
     *
     * GET /api/v1/stock/snapshot/{materialId}?warehouse_id=1
     *
     * Response includes: on_hand, available, qc_hold, putaway_pending, reserved, blocked, by_bin
     */
    public function snapshot(int $materialId, Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->has('warehouse_id') ? $request->integer('warehouse_id') : null;
            $snapshot    = $this->stockQueryService->getFullStockSnapshot($materialId, $warehouseId);

            return response()->json([
                'success' => true,
                'data'    => array_merge(['material_id' => $materialId], $snapshot),
            ]);
        } catch (\Exception $e) {
            Log::error('[StockController] snapshot() failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transaction history / audit trail for a material.
     *
     * GET /api/v1/stock/history/{materialId}?warehouse_id=1&limit=50
     *
     * Returns every ledger entry for this material, most recent first.
     */
    public function history(int $materialId, Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->has('warehouse_id') ? $request->integer('warehouse_id') : null;
            $limit       = min($request->integer('limit', 50), 200); // cap at 200

            $transactions = $this->stockQueryService->getTransactionHistory($materialId, $warehouseId, $limit);

            return response()->json([
                'success' => true,
                'data'    => $transactions,
                'meta'    => [
                    'material_id' => $materialId,
                    'count'       => $transactions->count(),
                    'limit'       => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[StockController] history() failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get stock summary for an entire warehouse.
     *
     * GET /api/v1/stock/warehouse/{warehouseId}
     *
     * Returns all materials with stock in this warehouse, grouped by bucket.
     */
    public function warehouseSummary(int $warehouseId): JsonResponse
    {
        try {
            $summary = $this->stockQueryService->getWarehouseStockSummary($warehouseId);

            return response()->json([
                'success'      => true,
                'warehouse_id' => $warehouseId,
                'data'         => $summary,
                'meta'         => ['material_count' => count($summary)],
            ]);
        } catch (\Exception $e) {
            Log::error('[StockController] warehouseSummary() failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get stock for a material in a specific bucket.
     *
     * GET /api/v1/stock/bucket/{materialId}/{bucket}?warehouse_id=1
     *
     * Valid buckets: QC_HOLD, PUTAWAY_PENDING, AVAILABLE, RESERVED, BLOCKED, CONSUMED, SHIPPED
     */
    public function byBucket(int $materialId, string $bucket, Request $request): JsonResponse
    {
        $validBuckets = ['QC_HOLD', 'PUTAWAY_PENDING', 'AVAILABLE', 'RESERVED', 'BLOCKED', 'CONSUMED', 'SHIPPED', 'RETURNED'];

        if (!in_array(strtoupper($bucket), $validBuckets)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid bucket. Valid values: " . implode(', ', $validBuckets),
            ], 422);
        }

        try {
            $warehouseId = $request->has('warehouse_id') ? $request->integer('warehouse_id') : null;
            $bucketData  = $this->stockQueryService->getStockByBucket($materialId, strtoupper($bucket), $warehouseId);

            return response()->json([
                'success' => true,
                'data'    => $bucketData,
                'meta'    => ['material_id' => $materialId, 'bucket' => strtoupper($bucket)],
            ]);
        } catch (\Exception $e) {
            Log::error('[StockController] byBucket() failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Manually adjust stock (add or subtract).
     * 
     * POST /api/v1/stock/adjust
     * Body: {
     *   "material_id": 3,
     *   "warehouse_id": 1,
     *   "bin_id": null,
     *   "qty": 500,
     *   "type": "add", // or "subtract"
     *   "batch_number": "BATCH-TEST-001",
     *   "reason": "Manual adjustment for testing"
     * }
     */
    public function adjust(Request $request): JsonResponse
    {
        try {
            // Validate required fields (basic validation)
            $validated = $request->validate([
                'material_id' => 'required|integer|min:1',
                'warehouse_id' => 'required|integer|min:1',
                'bin_id' => 'nullable|integer|min:1',
                'qty' => 'required|numeric|min:0.001',
                'type' => 'required|in:add,subtract',
                'batch_number' => 'nullable|string|max:50',
                'reason' => 'nullable|string|max:255',
            ]);

            $materialId = $validated['material_id'];
            $warehouseId = $validated['warehouse_id'];
            $binId = $validated['bin_id'] ?? null;
            $qty = (float) $validated['qty'];
            $type = $validated['type'];
            $batchNumber = $validated['batch_number'] ?? null;
            $reason = $validated['reason'] ?? 'Manual stock adjustment';

            // Get material details from TENANT database for UOM and cost
            $material = DB::connection('tenant')
                ->table('material_master')
                ->where('id', $materialId)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found in tenant database',
                ], 404);
            }

            // Validate warehouse exists in tenant database
            $warehouse = DB::connection('tenant')
                ->table('warehouse_master')
                ->where('id', $warehouseId)
                ->first();

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found in tenant database',
                ], 404);
            }

            // Validate bin if provided
            if ($binId) {
                $bin = DB::connection('tenant')
                    ->table('bin_locations')
                    ->where('id', $binId)
                    ->first();

                if (!$bin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bin location not found in tenant database',
                    ], 404);
                }
            }

            $qtyChange = $type === 'add' ? $qty : -$qty;
            $userId = auth()->id() ?? 1; // Default to admin if not authenticated

            $stockService = app(StockService::class);

            $txn = $stockService->post(
                item: [
                    'material_id' => $materialId,
                    'uom_id' => $material->uom_id,
                    'warehouse_id' => $warehouseId,
                    'batch_number' => $batchNumber,
                ],
                bucket: 'AVAILABLE',
                qtyChange: $qtyChange,
                transactionType: 'STOCK_ADJUSTMENT',
                referenceType: 'ManualAdjustment',
                referenceId: 999,
                referenceNumber: 'MANUAL/' . date('YmdHis'),
                userId: $userId,
                binId: $binId,
                unitCost: $material->standard_cost,
                remarks: $reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => [
                    'transaction_id' => $txn->id,
                    'material_id' => $materialId,
                    'qty_change' => $qtyChange,
                    'bucket' => 'AVAILABLE',
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[StockController] adjust() failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
