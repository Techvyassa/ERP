<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionCapacity extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_capacity';

    protected $fillable = [
        'product_id',
        'capacity_date',
        'daily_capacity',
        'utilized_capacity',
        'available_capacity',
        'shift',
        'remarks',
    ];

    protected $casts = [
        'capacity_date'       => 'date',
        'daily_capacity'      => 'decimal:3',
        'utilized_capacity'   => 'decimal:3',
        'available_capacity'  => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
