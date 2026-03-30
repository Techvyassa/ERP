<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CartonItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'carton_items';

    protected $fillable = [
        'carton_id',
        'product_id',
        'product_barcode',
        'qty',
        'uom_id',
        'batch_number',
        'scanned_at',
        'scanned_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function carton()
    {
        return $this->belongsTo(Carton::class, 'carton_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
