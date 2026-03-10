<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'purchase_orders';
    
    protected $fillable = [
        'po_number',
        'vendor_id',
        'currency_id',
        'billing_address',
        'ship_to_address',
        'payment_terms',
        'credit_days',
        'delivery_terms',
        'subtotal',
        'discount_amount',
        'freight_charges',
        'tax_amount',
        'grand_total',
        'po_date',
        'expected_delivery',
        'valid_until',
        'status',
        'terms_conditions',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'freight_charges' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Generate a new unique PO Number
     */
    public static function generatePoNumber()
    {
        // PO-YYMM-XXXX pattern
        $prefix = 'PO-' . date('ym') . '-';
        $lastPo = self::where('po_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastPo) {
            return $prefix . '0001';
        }

        $lastNumber = (int) str_replace($prefix, '', $lastPo->po_number);
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $newNumber;
    }

    /**
     * Get the vendor associated with the PO
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the currency associated with the PO
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the user who created the PO
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the PO
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the line items for the PO
     */
    public function lineItems()
    {
        return $this->hasMany(PoLineItem::class, 'po_id');
    }
}
