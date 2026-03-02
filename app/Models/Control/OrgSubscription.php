<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;

class OrgSubscription extends Model
{
    protected $connection = 'control';
    protected $table = 'org_subscriptions';
    protected $primaryKey = 'subscription_id';
    
    protected $fillable = [
        'org_id',
        'plan_id',
        'subscription_status',
        'trial_start_date',
        'trial_end_date',
        'current_period_start',
        'current_period_end',
        'next_billing_date',
        'grace_period_until',
        'cancelled_at',
        'cancellation_reason',
    ];
    
    protected $casts = [
        'trial_start_date' => 'date',
        'trial_end_date' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'next_billing_date' => 'date',
        'grace_period_until' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the organization that owns this subscription
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'org_id');
    }
    
    /**
     * Get the subscription plan
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }
    
    /**
     * Get payment records for this subscription
     */
    public function paymentRecords()
    {
        return $this->hasMany(PaymentRecord::class, 'subscription_id', 'subscription_id');
    }
    
    /**
     * Scope to filter active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'ACTIVE');
    }
    
    /**
     * Scope to filter trial subscriptions
     */
    public function scopeTrial($query)
    {
        return $query->where('subscription_status', 'TRIAL');
    }
    
    /**
     * Scope to filter past due subscriptions
     */
    public function scopePastDue($query)
    {
        return $query->where('subscription_status', 'PAST_DUE');
    }
    
    /**
     * Scope to filter cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('subscription_status', 'CANCELLED');
    }
    
    /**
     * Scope to filter expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('subscription_status', 'EXPIRED');
    }
    
    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->subscription_status === 'ACTIVE';
    }
    
    /**
     * Check if subscription is in trial
     */
    public function isTrial(): bool
    {
        return $this->subscription_status === 'TRIAL';
    }
    
    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->subscription_status === 'PAST_DUE';
    }
    
    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->subscription_status === 'CANCELLED';
    }
    
    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->subscription_status === 'EXPIRED';
    }
    
    /**
     * Check if subscription is within grace period
     */
    public function isInGracePeriod(): bool
    {
        return $this->isPastDue() 
            && $this->grace_period_until 
            && now()->lessThan($this->grace_period_until);
    }
}
