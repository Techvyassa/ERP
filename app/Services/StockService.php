<?php

namespace App\Services;

use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockService — The Central Stock Calculation Engine.
 *
 * All inventory movements in the system must go through this service.
 * It is responsible for:
 * 1. Writing an immutable row to inventory_transactions (the Ledger).
 * 2. Updating the corresponding row(s) in stock_balances (the Read-Model / Cache).
 *
 * ARCHITECTURE PATTERN: Ledger → Balance
 * - post()      → Adds qty to a bucket (inflow / outflow in one bucket)
 * - transfer()  → Moves qty between two buckets atomically (most common operation)
 *
 * USAGE:
 *   // At GRN receipt (dock arrival):
 *   StockService::post($params, 'QC_HOLD', +qty, 'GRN_RECEIPT', ...);
 *
 *   // After QC passes:
 *   StockService::transfer($params, 'QC_HOLD', 'PUTAWAY_PENDING', qty, 'QC_PASS', ...);
 *
 *   // After putaway confirmed:
 *   StockService::transfer($params, 'PUTAWAY_PENDING', $finalBinId, 'AVAILABLE', $qty, 'PUTAWAY_COMPLETE', ...);
 */
class StockService
{
    /**
     * Post a single-bucket stock change (inflow or outflow).
     *
     * Use this when stock enters or leaves in a single bucket without
     * simultaneously moving out of another.
     *
     * @param array  $item             Must contain: material_id|product_id, uom_id, warehouse_id, batch_number (nullable)
     * @param string $bucket           Target bucket (must match inventory_transactions.bucket enum)
     * @param float  $qtyChange        Signed quantity: positive = inflow, negative = outflow
     * @param string $transactionType  Must match inventory_transactions.transaction_type enum
     * @param string $referenceType    Polymorphic source: 'GRN', 'PutawayTask', 'SalesOrder', etc.
     * @param int    $referenceId      PK of source document
     * @param string $referenceNumber  Human-readable reference (GRN number, task number, etc.)
     * @param int    $userId           Who triggered this action
     * @param int|null $binId         Specific bin (null = warehouse-level)
     * @param float|null $unitCost    Cost per unit for valuation
     * @param string|null $remarks    Optional notes
     * @return InventoryTransaction
     */
    public function post(
        array  $item,
        string $bucket,
        float  $qtyChange,
        string $transactionType,
        string $referenceType,
        int    $referenceId,
        string $referenceNumber,
        int    $userId,
        ?int   $binId = null,
        ?float $unitCost = null,
        ?string $remarks = null
    ): InventoryTransaction {
        return DB::connection('tenant')->transaction(function () use (
            $item, $bucket, $qtyChange, $transactionType,
            $referenceType, $referenceId, $referenceNumber,
            $userId, $binId, $unitCost, $remarks
        ) {
            $totalCost = ($unitCost !== null && $qtyChange !== 0.0)
                ? round(abs($qtyChange) * $unitCost, 2)
                : null;

            // 1. Write immutable ledger row
            $txn = InventoryTransaction::create([
                'material_id'      => $item['material_id'] ?? null,
                'product_id'       => $item['product_id'] ?? null,
                'batch_number'     => $item['batch_number'] ?? null,
                'bucket'           => $bucket,
                'qty_change'       => $qtyChange,
                'uom_id'           => $item['uom_id'],
                'warehouse_id'     => $item['warehouse_id'],
                'bin_id'           => $binId,
                'transaction_type' => $transactionType,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'reference_number' => $referenceNumber,
                'unit_cost'        => $unitCost,
                'total_cost'       => $totalCost,
                'created_by'       => $userId,
                'remarks'          => $remarks,
            ]);

            // 2. Update the read-model balance
            $this->updateBalance($item, $bucket, $qtyChange, $item['uom_id'], $item['warehouse_id'], $binId, $unitCost);

            Log::info('[StockService] Transaction posted', [
                'txn_id'    => $txn->id,
                'type'      => $transactionType,
                'bucket'    => $bucket,
                'qty'       => $qtyChange,
                'reference' => "{$referenceType}#{$referenceId} ({$referenceNumber})",
            ]);

            return $txn;
        });
    }

    /**
     * Transfer qty between two buckets atomically.
     *
     * This is the primary mechanism for advancing stock through its lifecycle:
     *   QC_HOLD → PUTAWAY_PENDING → AVAILABLE → RESERVED → SHIPPED (products)
     *   QC_HOLD → PUTAWAY_PENDING → AVAILABLE → CONSUMED (materials)
     *
     * Internally, this posts TWO transactions:
     *   1. Outflow from $fromBucket (negative qty)
     *   2. Inflow  to   $toBucket  (positive qty)
     *
     * @param array  $item             Must contain: material_id|product_id, uom_id, warehouse_id, batch_number
     * @param string $fromBucket       Source bucket to deduct from
     * @param string $toBucket         Destination bucket to add to
     * @param float  $qty              Always positive — the method applies signs internally
     * @param string $transactionType  The business reason for this transfer
     * @param string $referenceType    Polymorphic source document type
     * @param int    $referenceId      Source document ID
     * @param string $referenceNumber  Human-readable reference
     * @param int    $userId           Who triggered this
     * @param int|null $fromBinId      Source bin (null = warehouse-level)
     * @param int|null $toBinId        Destination bin (null = warehouse-level)
     * @param float|null $unitCost     Cost per unit for valuation
     * @param string|null $remarks     Optional notes
     * @return array{from: InventoryTransaction, to: InventoryTransaction}
     */
    public function transfer(
        array  $item,
        string $fromBucket,
        string $toBucket,
        float  $qty,
        string $transactionType,
        string $referenceType,
        int    $referenceId,
        string $referenceNumber,
        int    $userId,
        ?int   $fromBinId = null,
        ?int   $toBinId = null,
        ?float $unitCost = null,
        ?string $remarks = null
    ): array {
        return DB::connection('tenant')->transaction(function () use (
            $item, $fromBucket, $toBucket, $qty, $transactionType,
            $referenceType, $referenceId, $referenceNumber,
            $userId, $fromBinId, $toBinId, $unitCost, $remarks
        ) {
            $outflow = $this->post(
                $item,
                $fromBucket,
                -$qty,   // ← negative = deduction
                $transactionType,
                $referenceType,
                $referenceId,
                $referenceNumber,
                $userId,
                $fromBinId,
                $unitCost,
                $remarks ? "OUT ({$remarks})" : "OUT"
            );

            $inflow = $this->post(
                $item,
                $toBucket,
                +$qty,   // ← positive = addition
                $transactionType,
                $referenceType,
                $referenceId,
                $referenceNumber,
                $userId,
                $toBinId,
                $unitCost,
                $remarks ? "IN ({$remarks})" : "IN"
            );

            Log::info('[StockService] Transfer completed', [
                'from_bucket' => $fromBucket,
                'to_bucket'   => $toBucket,
                'qty'         => $qty,
                'reference'   => "{$referenceType}#{$referenceId}",
            ]);

            return ['from' => $outflow, 'to' => $inflow];
        });
    }

    /**
     * Reserve qty within the AVAILABLE bucket for a Sales/Production order.
     * This does NOT move stock between buckets; it increments qty_reserved on the balance row.
     *
     * @param array  $item          material_id|product_id, uom_id, warehouse_id, batch_number
     * @param float  $qty           Quantity to reserve (always positive)
     * @param string $referenceType Polymorphic: 'SalesOrder', 'ProductionOrder'
     * @param int    $referenceId   FK of the order
     * @param string $referenceNumber Human-readable order number
     * @param int    $userId        Who is reserving
     * @param int|null $binId       Specific bin (optional)
     * @throws \Exception When insufficient available stock
     */
    public function reserve(
        array  $item,
        float  $qty,
        string $referenceType,
        int    $referenceId,
        string $referenceNumber,
        int    $userId,
        ?int   $binId = null
    ): InventoryTransaction {
        return DB::connection('tenant')->transaction(function () use (
            $item, $qty, $referenceType, $referenceId, $referenceNumber, $userId, $binId
        ) {
            // Check availability before reserving
            $balanceQuery = StockBalance::forMaterial($item['material_id'] ?? 0)
                ->inBucket('AVAILABLE')
                ->inWarehouse($item['warehouse_id']);

            if ($binId) {
                $balanceQuery->where('bin_id', $binId);
            }
            if (!empty($item['batch_number'])) {
                $balanceQuery->where('batch_number', $item['batch_number']);
            }

            $balance = $balanceQuery->first();
            $available = $balance ? $balance->available_qty : 0;

            if ($available < $qty) {
                throw new \Exception(
                    "Insufficient available stock. Requested: {$qty}, Available: {$available}"
                );
            }

            // Increment reserved on balance (no bucket change, just reserve flag)
            $this->incrementReserved($item, $qty, $item['warehouse_id'], $binId);

            // Post an informational ledger row (qty_change = 0 as it's the same bucket)
            return $this->post(
                $item,
                'RESERVED',
                +$qty,
                'SALES_RESERVE',
                $referenceType,
                $referenceId,
                $referenceNumber,
                $userId,
                $binId,
                null,
                "Reserved for {$referenceType} #{$referenceId}"
            );
        });
    }

    /**
     * Get the current stock snapshot for a material across all buckets and warehouses.
     * This is the primary method for the "Full Stock Snapshot" API response.
     *
     * @param int $materialId
     * @param int|null $warehouseId  Filter by warehouse (optional)
     * @return array{
     *   on_hand: float,
     *   available: float,
     *   qc_hold: float,
     *   putaway_pending: float,
     *   reserved: float,
     *   blocked: float,
     *   by_bucket: array,
     *   by_bin: array
     * }
     */
    public function getStockSnapshot(int $materialId, ?int $warehouseId = null): array
    {
        $query = StockBalance::forMaterial($materialId)->withStock();

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        $balances = $query->with(['bin', 'warehouse'])->get();

        $snapshot = [
            'on_hand'        => 0.0,
            'available'      => 0.0,
            'qc_hold'        => 0.0,
            'putaway_pending'=> 0.0,
            'reserved'       => 0.0,
            'blocked'        => 0.0,
            'returned'       => 0.0,
            'by_bucket'      => [],
            'by_bin'         => [],
        ];

        foreach ($balances as $balance) {
            $qty = (float) $balance->qty_on_hand;
            $snapshot['on_hand'] += $qty;

            match ($balance->bucket) {
                'AVAILABLE'       => $snapshot['available']       += $balance->available_qty,
                'QC_HOLD'         => $snapshot['qc_hold']         += $qty,
                'PUTAWAY_PENDING' => $snapshot['putaway_pending']  += $qty,
                'RESERVED'        => $snapshot['reserved']         += $qty,
                'BLOCKED'         => $snapshot['blocked']          += $qty,
                'RETURNED'        => $snapshot['returned']         += $qty,
                default           => null,
            };

            // Bucket breakdown
            $snapshot['by_bucket'][$balance->bucket] = ($snapshot['by_bucket'][$balance->bucket] ?? 0) + $qty;

            // Bin-level breakdown
            if ($balance->bin_id) {
                $binCode = $balance->bin?->bin_code ?? "BIN-{$balance->bin_id}";
                $snapshot['by_bin'][$binCode] = [
                    'bin_id'   => $balance->bin_id,
                    'bin_code' => $binCode,
                    'bucket'   => $balance->bucket,
                    'qty'      => $qty,
                    'reserved' => (float) $balance->qty_reserved,
                    'available'=> $balance->available_qty,
                    'warehouse'=> $balance->warehouse?->warehouse_name ?? "WH-{$balance->warehouse_id}",
                ];
            }
        }

        return $snapshot;
    }

    /**
     * Get the simple "available stock" number for a material (the ATP check).
     * O(1) query — suitable for use in order entry validation.
     *
     * @param int      $materialId
     * @param int      $warehouseId
     * @param int|null $binId        Optional specific bin filter
     * @return float
     */
    public function getAvailableStock(int $materialId, int $warehouseId, ?int $binId = null): float
    {
        $query = StockBalance::forMaterial($materialId)
            ->inBucket('AVAILABLE')
            ->inWarehouse($warehouseId);

        if ($binId) {
            $query->where('bin_id', $binId);
        }

        return $query->sum(DB::connection('tenant')->raw('qty_on_hand - qty_reserved'));
    }

    // -----------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------

    /**
     * Upsert the stock_balances row for a given item/bucket/bin combination.
     * Called inside every post() to keep the read-model in sync.
     */
    private function updateBalance(
        array  $item,
        string $bucket,
        float  $qtyChange,
        int    $uomId,
        int    $warehouseId,
        ?int   $binId,
        ?float $unitCost
    ): StockBalance {
        $key = [
            'material_id' => $item['material_id'] ?? null,
            'product_id'  => $item['product_id'] ?? null,
            'batch_number'=> $item['batch_number'] ?? null,
            'bucket'      => $bucket,
            'warehouse_id'=> $warehouseId,
            'bin_id'      => $binId,
        ];

        // Lock the row for update to prevent race conditions in concurrent requests
        $balance = StockBalance::where($key)->lockForUpdate()->first();

        if (!$balance) {
            $balance = StockBalance::create(array_merge($key, [
                'qty_on_hand'  => 0,
                'qty_reserved' => 0,
                'uom_id'       => $uomId,
                'avg_cost'     => $unitCost,
            ]));
        }

        $newQty = (float) $balance->qty_on_hand + $qtyChange;

        // Moving average cost calculation on inflow
        $newAvgCost = $balance->avg_cost;
        if ($unitCost !== null && $qtyChange > 0 && $newQty > 0) {
            $oldValue  = (float) $balance->qty_on_hand * (float) ($balance->avg_cost ?? $unitCost);
            $addValue  = $qtyChange * $unitCost;
            $newAvgCost = ($oldValue + $addValue) / $newQty;
        }

        $balance->update([
            'qty_on_hand'          => max(0, $newQty),
            'avg_cost'             => $newAvgCost,
            'last_transaction_at'  => now(),
        ]);

        return $balance;
    }

    /**
     * Increment the qty_reserved column on an AVAILABLE balance row.
     * Used by reserve() to flag committed stock.
     */
    private function incrementReserved(array $item, float $qty, int $warehouseId, ?int $binId): void
    {
        $key = [
            'material_id' => $item['material_id'] ?? null,
            'product_id'  => $item['product_id'] ?? null,
            'bucket'      => 'AVAILABLE',
            'warehouse_id'=> $warehouseId,
            'bin_id'      => $binId,
        ];

        StockBalance::where($key)->increment('qty_reserved', $qty);
    }
}
