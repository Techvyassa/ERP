<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PackingOrder extends Model
{
    protected $connection = 'tenant';
    protected $table = 'packing_orders';

    protected $fillable = [
        'packing_order_no',
        'production_order_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function cartons()
    {
        return $this->hasMany(Carton::class, 'packing_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
