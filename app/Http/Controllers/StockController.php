<?php

namespace App\Http\Controllers;

use App\Services\StockQueryService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
