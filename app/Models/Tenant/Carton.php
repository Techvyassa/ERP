<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Carton extends Model
{
    protected $connection = 'tenant';
    protected $table = 'cartons';

    protected $fillable = [
        'carton_barcode',
        'packing_order_id',
        'carton_type',
        'parent_carton_id',
        'status',
        'calculated_weight',
        'actual_weight',
        'sealed_at',
        'labelled_at',
    ];

    protected $casts = [
        'calculated_weight' => 'decimal:3',
        'actual_weight' => 'decimal:3',
        'sealed_at' => 'datetime',
        'labelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function packingOrder()
    {
        return $this->belongsTo(PackingOrder::class, 'packing_order_id');
    }

    public function parentCarton()
    {
        return $this->belongsTo(self::class, 'parent_carton_id');
    }

    public function childCartons()
    {
        return $this->hasMany(self::class, 'parent_carton_id');
    }

    public function items()
    {
        return $this->hasMany(CartonItem::class, 'carton_id');
    }
}
