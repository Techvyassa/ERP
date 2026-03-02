<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Exception;

class PaymentRecord extends Model
{
    protected $connection = 'control';
    protected $table = 'payment_records';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'org_id',
        'subscription_id',
        'payment_reference',
        'payment_type',
        'payment_status',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'gateway_name',
        'gateway_payment_id',
        'gateway_response',
        'payment_date',
    ];
    
    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
    ];
    
    /**
     * Boot the model and add immutability guards
     */
    protected static function boot()
    {
        parent::boot();
        
        // Prevent updates to payment records (immutable ledger)
        static::updating(function ($model) {
            throw new Exception('Payment records are immutable and cannot be updated');
        });
        
        // Prevent deletion of payment records (immutable ledger)
        static::deleting(function ($model) {
            throw new Exception('Payment records are immutable and cannot be deleted');
        });
    }
    
    /**
     * Get the organization that owns this payment record
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'org_id');
    }
    
    /**
     * Get the subscription associated with this payment
     */
    public function subscription()
    {
        return $this->belongsTo(OrgSubscription::class, 'subscription_id', 'subscription_id');
    }
    
    /**
     * Scope to filter successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('payment_status', 'SUCCESS');
    }
    
    /**
     * Scope to filter pending payments
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'PENDING');
    }
    
    /**
     * Scope to filter failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'FAILED');
    }
    
    /**
     * Scope to filter by payment type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('payment_type', $type);
    }
    
    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->payment_status === 'SUCCESS';
    }
    
    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'PENDING';
    }
    
    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->payment_status === 'FAILED';
    }
    
    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->payment_status, ['REFUNDED', 'PARTIALLY_REFUNDED']);
    }
    
    /**
     * Get total tax amount
     */
    public function getTotalTaxAttribute(): float
    {
        return (float) ($this->cgst_amount + $this->sgst_amount + $this->igst_amount);
    }
}
