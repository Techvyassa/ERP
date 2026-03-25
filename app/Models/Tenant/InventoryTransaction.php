<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * InventoryTransaction — Immutable stock ledger entry.
 *
 * RULES:
 * - NEVER call ->update() on this model. Rows are write-once.
 * - To reverse a transaction, create a new row with the same qty but flipped sign (CANCELLATION type).
 * - All writes go through StockService::post() to ensure stock_balances is updated atomically.
 */
class InventoryTransaction extends Model
{
    protected $connection = 'tenant';
    protected $table = 'inventory_transactions';

    protected $fillable = [
        'material_id',
        'product_id',
        'batch_number',
        'bucket',
        'qty_change',
        'uom_id',
        'warehouse_id',
        'bin_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'unit_cost',
        'total_cost',
        'created_by',
        'remarks',
    ];

    protected $casts = [
        'qty_change'  => 'decimal:3',
        'unit_cost'   => 'decimal:4',
        'total_cost'  => 'decimal:2',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bin()
    {
        return $this->belongsTo(BinLocation::class, 'bin_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeForMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInBucket($query, string $bucket)
    {
        return $query->where('bucket', $bucket);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Determine if this transaction represents an inflow (+) or outflow (-)
     */
    public function isInflow(): bool
    {
        return $this->qty_change > 0;
    }

    public function isOutflow(): bool
    {
        return $this->qty_change < 0;
    }
}
