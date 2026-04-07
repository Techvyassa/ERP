<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'customers';

    protected $fillable = [
        'customer_code', 'customer_name', 'contact_person',
        'phone', 'email', 'billing_address', 'shipping_address',
        'gstin', 'payment_terms', 'credit_days', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_days' => 'integer',
    ];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function generateCode(): string
    {
        $prefix = 'CUST-';
        $last = self::where('customer_code', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? ((int) str_replace($prefix, '', $last->customer_code)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
