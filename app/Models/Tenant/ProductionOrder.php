<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_orders';

    protected $fillable = [
        'order_no', 'product_id', 'bom_id', 'target_qty',
        'planned_date', 'status', 'created_by',
    ];

    protected $casts = [
        'target_qty'   => 'decimal:3',
        'planned_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bom()
    {
        return $this->belongsTo(BOMHeader::class, 'bom_id');
    }

    public function mir()
    {
        return $this->hasOne(MaterialIssueRequest::class, 'production_order_id');
    }
}
