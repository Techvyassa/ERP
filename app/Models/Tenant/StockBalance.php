<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * StockBalance — Materialized read-model for fast stock queries.
 *
 * One row = (material|product) × batch × bucket × warehouse × bin.
 *
 * RULES:
 * - NEVER write to this table directly from controllers or business code.
 * - Only StockService::post() and StockService::transfer() may mutate this table.
 * - Use StockQueryService or the scopes below for reads.
 */
class StockBalance extends Model
{
    protected $connection = 'tenant';
    protected $table = 'stock_balances';

    protected $fillable = [
        'material_id',
        'product_id',
        'batch_number',
        'bucket',
        'warehouse_id',
        'bin_id',
        'qty_on_hand',
        'qty_reserved',
        'uom_id',
        'avg_cost',
        'last_transaction_at',
    ];

    protected $casts = [
        'qty_on_hand'          => 'decimal:3',
        'qty_reserved'         => 'decimal:3',
        'avg_cost'             => 'decimal:4',
        'last_transaction_at'  => 'datetime',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bin()
    {
        return $this->belongsTo(BinLocation::class, 'bin_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    // -----------------------------------------------------------------
    // Scopes — building blocks for StockQueryService
    // -----------------------------------------------------------------

    public function scopeForMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInBucket($query, string|array $bucket)
    {
        return is_array($bucket)
            ? $query->whereIn('bucket', $bucket)
            : $query->where('bucket', $bucket);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeWithStock($query)
    {
        return $query->where('qty_on_hand', '>', 0);
    }

    // -----------------------------------------------------------------
    // Computed Helpers
    // -----------------------------------------------------------------

    /**
     * Net available qty = on_hand minus reserved
     */
    public function getAvailableQtyAttribute(): float
    {
        return max(0, (float) $this->qty_on_hand - (float) $this->qty_reserved);
    }
}
