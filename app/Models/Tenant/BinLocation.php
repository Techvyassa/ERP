<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BinLocation extends Model
{
    protected $connection = 'tenant';
    protected $table = 'bin_locations';
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'bin_code',
        'aisle',
        'rack',
        'shelf',
        'max_weight_kg',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_weight_kg' => 'decimal:2'
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
