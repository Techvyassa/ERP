<?php

namespace App\Services;

use App\Models\Tenant\StockBalance;
use App\Models\Tenant\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * StockQueryService — Standardized read-only API for stock data.
 *
 * Provides pre-built, named query methods so controllers
 * never write raw SQL for stock questions.
 *
 * All methods hit stock_balances (O(1) or O(bins)) — they do NOT
 * aggregate inventory_transactions (O(N)).
 */
class StockQueryService
{
    public function __construct(protected StockService $stockService)
    {
    }

    /**
     * getAvailableStock — ATP (Available to Promise) check.
     * Returns the net qty that can be sold or issued to production.
     *
     * Formula: SUM(qty_on_hand - qty_reserved) WHERE bucket = 'AVAILABLE'
     *
     * @param int      $materialId
     * @param int      $warehouseId
     * @param int|null $binId        Narrow to a specific bin
     * @return float
     */
    public function getAvailableStock(int $materialId, int $warehouseId, ?int $binId = null): float
    {
        return $this->stockService->getAvailableStock($materialId, $warehouseId, $binId);
    }

    /**
     * getFullStockSnapshot — Dashboard-level breakdown per bucket and per bin.
     *
     * Returns:
     *   on_hand        = total physical units inside four walls
     *   available      = available - reserved (can be sold/issued)
     *   qc_hold        = at receiving dock, awaiting inspection
     *   putaway_pending= QC passed, on forklift / staging, not yet shelved
     *   reserved       = committed to orders, not yet dispatched
     *   blocked        = QC rejected, cannot be used
     *
     * @param int      $materialId
     * @param int|null $warehouseId
     */
    public function getFullStockSnapshot(int $materialId, ?int $warehouseId = null): array
    {
        return $this->stockService->getStockSnapshot($materialId, $warehouseId);
    }

    /**
     * getStockByBucket — Return all balances for a material in a specific bucket.
     * Useful for warehouse manager views (e.g., "Show me all QC_HOLD stock").
     */
    public function getStockByBucket(int $materialId, string $bucket, ?int $warehouseId = null): array
    {
        $query = StockBalance::forMaterial($materialId)
            ->inBucket($bucket)
            ->withStock()
            ->with(['bin', 'warehouse', 'uom']);

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->get()->toArray();
    }

    /**
     * getWarehouseStockSummary — All materials with stock in a given warehouse.
     * Groups by material and bucket for a warehouse-level dashboard.
     *
     * @param int $warehouseId
     * @return array
     */
    public function getWarehouseStockSummary(int $warehouseId): array
    {
        return StockBalance::inWarehouse($warehouseId)
            ->withStock()
            ->with(['material', 'uom'])
            ->get()
            ->groupBy('material_id')
            ->map(function ($rows, $materialId) {
                $first = $rows->first();
                return [
                    'material_id'   => $materialId,
                    'material_code' => $first->material?->material_code,
                    'material_name' => $first->material?->material_name,
                    'uom'           => $first->uom?->uom_code,
                    'on_hand'       => $rows->sum(fn($r) => (float) $r->qty_on_hand),
                    'available'     => $rows->where('bucket', 'AVAILABLE')->sum(fn($r) => $r->available_qty),
                    'qc_hold'       => $rows->where('bucket', 'QC_HOLD')->sum(fn($r) => (float) $r->qty_on_hand),
                    'putaway_pending'=> $rows->where('bucket', 'PUTAWAY_PENDING')->sum(fn($r) => (float) $r->qty_on_hand),
                    'blocked'       => $rows->where('bucket', 'BLOCKED')->sum(fn($r) => (float) $r->qty_on_hand),
                    'reserved'      => $rows->sum(fn($r) => (float) $r->qty_reserved),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * getTransactionHistory — Full audit trail for a material.
     * Use this when a warehouse manager asks "why is my stock low?"
     *
     * @param int      $materialId
     * @param int|null $warehouseId
     * @param int      $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransactionHistory(int $materialId, ?int $warehouseId = null, int $limit = 50)
    {
        $query = InventoryTransaction::forMaterial($materialId)
            ->with(['bin', 'warehouse', 'creator'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->get();
    }

    /**
     * checkSufficientStock — Guard used before production issue or sales reservation.
     *
     * @param int      $materialId
     * @param int      $warehouseId
     * @param float    $requiredQty
     * @throws \Exception if insufficient stock
     */
    public function checkSufficientStock(int $materialId, int $warehouseId, float $requiredQty): void
    {
        $available = $this->getAvailableStock($materialId, $warehouseId);
        if ($available < $requiredQty) {
            throw new \Exception(
                "Insufficient stock for material #{$materialId}. " .
                "Required: {$requiredQty}, Available: {$available}"
            );
        }
    }
}
