<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SoPickLine extends Model
{
    protected $connection = 'tenant';
    protected $table = 'so_pick_lines';

    protected $fillable = [
        'so_id',
        'pallet_no',
        'bin_id',
        'bin_code',
        'product_id',
        'qty',
        'picked_by',
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

    public function bin()
    {
        return $this->belongsTo(BinLocation::class, 'bin_id');
    }

    public function pickedBy()
    {
        return $this->belongsTo(User::class, 'picked_by');
    }
}
