<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SoBoxLine extends Model
{
    protected $connection = 'tenant';
    protected $table = 'so_box_lines';

    protected $fillable = [
        'so_id',
        'box_no',
        'product_id',
        'qty',
        'packed_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function packedBy()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }
}
