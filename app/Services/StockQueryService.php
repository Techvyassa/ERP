<?php

namespace App\Services;

use App\Models\Tenant\StockBalance;
use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\Product;
use App\Models\Tenant\Material;
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
    public function __construct(protected StockService $stockService) {}

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
     * getAvailableProductStock — ATP check for finished goods.
     *
     * Formula: SUM(qty_on_hand - qty_reserved) WHERE bucket = 'AVAILABLE'
     */
    public function getAvailableProductStock(int $productId, ?int $warehouseId = null, ?int $binId = null): float
    {
        $query = StockBalance::query()
            ->forProduct($productId)
            ->inBucket('AVAILABLE')
            ->withStock();

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        if ($binId) {
            $query->where('bin_id', $binId);
        }

        return (float) $query->sum(DB::raw('qty_on_hand - qty_reserved'));
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
     * Groups by material and uom_id for a warehouse-level dashboard.
     * Includes all active products and materials from master tables, even those without stock in this warehouse.
     *
     * @param int $warehouseId
     * @return array
     */
    public function getWarehouseStockSummary(int $warehouseId): array
    {
        // Get stock data for this warehouse grouped by item_id + uom_id
        $stockData = StockBalance::inWarehouse($warehouseId)
            ->withStock()
            ->with(['material', 'product', 'uom'])
            ->get()
            ->groupBy(function ($item) {
                // Ensure consistent key by casting to int
                $itemId = (int) ($item->material_id ?? $item->product_id);
                $uomId = (int) ($item->uom_id ?? 0);
                $type = $item->material_id ? 'M' : 'P';
                return $type . $itemId . '_U' . $uomId;
            })
            ->map(function ($rows) {
                $first = $rows->first();
                $isProduct = (bool) $first->product_id;

                return [
                    'item_id'       => $isProduct ? $first->product_id : $first->material_id,
                    'item_type'     => $isProduct ? 'Product' : 'Material',
                    'item_code'     => $isProduct ? $first->product?->product_code : $first->material?->material_code,
                    'item_name'     => $isProduct ? $first->product?->product_name : $first->material?->material_name,
                    'uom_id'        => (int) $first->uom_id,
                    'uom'           => $first->uom?->uom_code,
                    'on_hand'       => $rows->sum(fn($r) => (float) $r->qty_on_hand),
                    'available'     => $rows->where('bucket', 'AVAILABLE')->sum(fn($r) => $r->available_qty),
                    'qc_hold'       => $rows->where('bucket', 'QC_HOLD')->sum(fn($r) => (float) $r->qty_on_hand),
                    'putaway_pending' => $rows->where('bucket', 'PUTAWAY_PENDING')->sum(fn($r) => (float) $r->qty_on_hand),
                    'blocked'       => $rows->where('bucket', 'BLOCKED')->sum(fn($r) => (float) $r->qty_on_hand),
                    'reserved'      => $rows->sum(fn($r) => (float) $r->qty_reserved)
                        + $rows->where('bucket', 'RESERVED')->sum(fn($r) => (float) $r->qty_on_hand),
                ];
            })
            ->keyBy(function ($item) {
                $type = $item['item_type'] === 'Product' ? 'P' : 'M';
                return $type . $item['item_id'] . '_U' . ($item['uom_id'] ?? 0);
            });

        // Get all active products with their UOMs
        $products = Product::active()
            ->with(['packUom'])
            ->get()
            ->map(function ($product) {
                $uomId = $product->pack_uom_id ?? 0;
                return [
                    'item_id'       => $product->id,
                    'item_type'     => 'Product',
                    'item_code'     => $product->product_code,
                    'item_name'     => $product->product_name,
                    'uom_id'        => (int) $uomId,
                    'uom'           => $product->packUom?->uom_code ?? null,
                ];
            });

        // Get all active materials with their UOMs
        $materials = Material::active()
            ->with(['uom', 'purchaseUom'])
            ->get()
            ->map(function ($material) {
                $uomId = $material->uom_id ?? 0;
                return [
                    'item_id'       => $material->id,
                    'item_type'     => 'Material',
                    'item_code'     => $material->material_code,
                    'item_name'     => $material->material_name,
                    'uom_id'        => (int) $uomId,
                    'uom'           => $material->uom?->uom_code ?? null,
                ];
            });

        // Merge products and materials into a single collection
        $masterData = $products->concat($materials)->keyBy(function ($item) {
            $type = $item['item_type'] === 'Product' ? 'P' : 'M';
            return $type . $item['item_id'] . '_U' . ($item['uom_id'] ?? 0);
        });

        // Merge stock data with master data (stock data takes precedence for quantities)
        $result = $masterData->map(function ($item, $key) use ($stockData) {
            $stockItem = $stockData->get($key);
            
            return [
                'item_id'         => $item['item_id'],
                'item_type'       => $item['item_type'],
                'item_code'       => $item['item_code'],
                'item_name'       => $item['item_name'],
                'uom_id'          => $item['uom_id'],
                'uom'             => $item['uom'],
                'on_hand'         => $stockItem ? (float) $stockItem['on_hand'] : 0,
                'available'       => $stockItem ? (float) $stockItem['available'] : 0,
                'reserved'        => $stockItem ? (float) $stockItem['reserved'] : 0,
                'qc_hold'         => $stockItem ? (float) $stockItem['qc_hold'] : 0,
                'putaway_pending' => $stockItem ? (float) $stockItem['putaway_pending'] : 0,
                'blocked'         => $stockItem ? (float) $stockItem['blocked'] : 0,
                'has_stock'       => $stockItem ? ($stockItem['on_hand'] > 0) : false,
            ];
        })->values()->toArray();

        return $result;
    }

    /**
     * getGlobalStockSummary — Aggregate stock for all materials/products across all warehouses.
     * Groups by item_id + uom_id to avoid mixing different UOMs.
     * Includes all active products and materials from master tables, even those without stock.
     */
    public function getGlobalStockSummary(): array
    {
        // Get stock data grouped by item_id + uom_id
        $stockData = StockBalance::with(['material', 'product', 'uom'])
            ->withStock()
            ->get()
            ->groupBy(function ($item) {
                // Ensure consistent key by casting to int
                $itemId = (int) ($item->material_id ?? $item->product_id);
                $uomId = (int) ($item->uom_id ?? 0);
                $type = $item->material_id ? 'M' : 'P';
                return $type . $itemId . '_U' . $uomId;
            })
            ->map(function ($rows) {
                $first = $rows->first();
                $isProduct = (bool) $first->product_id;

                return [
                    'item_id'       => $isProduct ? $first->product_id : $first->material_id,
                    'item_type'     => $isProduct ? 'Product' : 'Material',
                    'item_code'     => $isProduct ? $first->product?->product_code : $first->material?->material_code,
                    'item_name'     => $isProduct ? $first->product?->product_name : $first->material?->material_name,
                    'uom_id'        => (int) $first->uom_id,
                    'uom'           => $first->uom?->uom_code,
                    'on_hand'       => $rows->sum(fn($r) => (float) $r->qty_on_hand),
                    'available'     => $rows->where('bucket', 'AVAILABLE')->sum(fn($r) => $r->available_qty),
                    'qc_hold'       => $rows->where('bucket', 'QC_HOLD')->sum(fn($r) => (float) $r->qty_on_hand),
                    'putaway_pending' => $rows->where('bucket', 'PUTAWAY_PENDING')->sum(fn($r) => (float) $r->qty_on_hand),
                    'blocked'       => $rows->where('bucket', 'BLOCKED')->sum(fn($r) => (float) $r->qty_on_hand),
                    'reserved'      => $rows->sum(fn($r) => (float) $r->qty_reserved)
                        + $rows->where('bucket', 'RESERVED')->sum(fn($r) => (float) $r->qty_on_hand),
                ];
            })
            ->keyBy(function ($item) {
                $type = $item['item_type'] === 'Product' ? 'P' : 'M';
                return $type . $item['item_id'] . '_U' . ($item['uom_id'] ?? 0);
            });

        // Get all active products with their UOMs
        $products = Product::active()
            ->with(['packUom'])
            ->get()
            ->map(function ($product) {
                // Get the primary UOM (from pack_uom_id)
                $uomId = $product->pack_uom_id ?? 0;
                return [
                    'item_id'       => $product->id,
                    'item_type'     => 'Product',
                    'item_code'     => $product->product_code,
                    'item_name'     => $product->product_name,
                    'uom_id'        => (int) $uomId,
                    'uom'           => $product->packUom?->uom_code ?? null,
                ];
            });

        // Get all active materials with their UOMs
        $materials = Material::active()
            ->with(['uom', 'purchaseUom'])
            ->get()
            ->map(function ($material) {
                // Get the primary UOM (from uom_id)
                $uomId = $material->uom_id ?? 0;
                return [
                    'item_id'       => $material->id,
                    'item_type'     => 'Material',
                    'item_code'     => $material->material_code,
                    'item_name'     => $material->material_name,
                    'uom_id'        => (int) $uomId,
                    'uom'           => $material->uom?->uom_code ?? null,
                ];
            });

        // Merge products and materials into a single collection
        $masterData = $products->concat($materials)->keyBy(function ($item) {
            $type = $item['item_type'] === 'Product' ? 'P' : 'M';
            return $type . $item['item_id'] . '_U' . ($item['uom_id'] ?? 0);
        });

        // Merge stock data with master data (stock data takes precedence for quantities)
        $result = $masterData->map(function ($item, $key) use ($stockData) {
            $stockItem = $stockData->get($key);
            
            return [
                'item_id'         => $item['item_id'],
                'item_type'       => $item['item_type'],
                'item_code'       => $item['item_code'],
                'item_name'       => $item['item_name'],
                'uom_id'          => $item['uom_id'],
                'uom'             => $item['uom'],
                'on_hand'         => $stockItem ? (float) $stockItem['on_hand'] : 0,
                'available'       => $stockItem ? (float) $stockItem['available'] : 0,
                'reserved'        => $stockItem ? (float) $stockItem['reserved'] : 0,
                'qc_hold'         => $stockItem ? (float) $stockItem['qc_hold'] : 0,
                'putaway_pending' => $stockItem ? (float) $stockItem['putaway_pending'] : 0,
                'blocked'         => $stockItem ? (float) $stockItem['blocked'] : 0,
                'has_stock'       => $stockItem ? ($stockItem['on_hand'] > 0) : false,
            ];
        })->values()->toArray();

        return $result;
    }

    /**
     * getProductBinAvailability — Available FG stock grouped by bin for picking.
     */
    public function getProductBinAvailability(int $productId, ?int $warehouseId = null): array
    {
        $query = StockBalance::query()
            ->forProduct($productId)
            ->inBucket('AVAILABLE')
            ->withStock()
            ->with(['bin', 'warehouse']);

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query
            ->get()
            ->map(function ($balance) {
                return [
                    'bin_id'         => $balance->bin_id,
                    'bin_code'       => $balance->bin?->bin_code,
                    'warehouse_id'   => $balance->warehouse_id,
                    'warehouse_name' => $balance->warehouse?->warehouse_name,
                    'qty_on_hand'    => (float) $balance->qty_on_hand,
                    'qty_reserved'   => (float) $balance->qty_reserved,
                    'qty_available'  => (float) $balance->available_qty,
                    'batch_number'   => $balance->batch_number,
                    'bucket'         => $balance->bucket,
                ];
            })
            ->filter(fn(array $row) => $row['qty_available'] > 0)
            ->sortByDesc('qty_available')
            ->values()
            ->all();
    }

    /**
     * getMaterialBinAvailability — Available RM stock grouped by bin.
     */
    public function getMaterialBinAvailability(int $materialId, ?int $warehouseId = null): array
    {
        $query = StockBalance::query()
            ->forMaterial($materialId)
            ->inBucket('AVAILABLE')
            ->with(['bin', 'warehouse']);

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query
            ->get()
            ->map(function ($balance) {
                return [
                    'bin_id'         => $balance->bin_id,
                    'bin_code'       => $balance->bin?->bin_code ?? 'No Bin',
                    'warehouse_id'   => $balance->warehouse_id,
                    'warehouse_name' => $balance->warehouse?->warehouse_name,
                    'qty_on_hand'    => (float) $balance->qty_on_hand,
                    'qty_reserved'   => (float) $balance->qty_reserved,
                    'qty_available'  => (float) $balance->available_qty,
                    'batch_number'   => $balance->batch_number,
                    'bucket'         => $balance->bucket,
                ];
            })
            ->sortByDesc('qty_available')
            ->values()
            ->all();
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
