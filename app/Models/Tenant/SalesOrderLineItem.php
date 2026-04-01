<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'sales_order_line_items';

    protected $fillable = [
        'so_id', 'product_id', 'qty', 'uom_id',
        'unit_price', 'discount_percent', 'line_total',
        'available_qty', 'availability',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
        'available_qty' => 'decimal:3',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }
}
