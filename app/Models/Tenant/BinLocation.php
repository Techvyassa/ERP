<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BinLocation extends Model
{
    use SoftDeletes;

    protected $table = 'bin_locations';

    protected $fillable = [
        'warehouse_id',
        'bin_code',
        'bin_name',
        'aisle',
        'rack',
        'shelf',
        'bin_type',
        'capacity',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'decimal:2'
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
