<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'sales_orders';

    protected $fillable = [
        'so_number',
        'customer_id',
        'billing_address',
        'shipping_address',
        'so_date',
        'required_delivery_date',
        'payment_terms',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'status',
        'stock_status',
        'remarks',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'vehicle_number',
        'driver_name',
        'logistics_partner',
        'dispatched_at',
        'dispatched_by',
    ];

    protected $casts = [
        'so_date'                => 'date',
        'required_delivery_date' => 'date',
        'confirmed_at'           => 'datetime',
        'dispatched_at'          => 'datetime',
        'subtotal'               => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'grand_total'            => 'decimal:2',
    ];

    public static function generateSoNumber(): string
    {
        $prefix = 'SO-' . date('ym') . '-';
        $last = self::where('so_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? ((int) str_replace($prefix, '', $last->so_number)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lineItems()
    {
        return $this->hasMany(SalesOrderLineItem::class, 'so_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
