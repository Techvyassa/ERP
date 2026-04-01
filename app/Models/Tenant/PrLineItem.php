<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PrLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'pr_line_items';

    protected $fillable = [
        'pr_id',
        'line_number',
        'material_id',
        'item_name',
        'description',
        'quantity',
        'uom_id',
        'estimated_unit_price',
        'warehouse_id',
        'purpose',
        'sort_order',
    ];

    protected $casts = [
        'quantity'             => 'decimal:3',
        'estimated_unit_price' => 'decimal:4',
        'estimated_total'      => 'decimal:2',
    ];

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
}
