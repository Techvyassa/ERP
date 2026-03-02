<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;

class ActiveSubscription extends Model
{
    protected $connection = 'control';
    protected $table = 'active_subscriptions';
    protected $primaryKey = 'org_id';
    public $incrementing = false;
    public $timestamps = false;
    
    protected $fillable = [
        'org_id',
        'subscription_id',
        'plan_id',
        'plan_code',
        'subscription_status',
        'period_end_date',
        'modules_allowed',
        'max_users',
        'tenant_db_name',
        'is_in_trial',
    ];
    
    protected $casts = [
        'org_id' => 'integer',
        'subscription_id' => 'integer',
        'plan_id' => 'integer',
        'period_end_date' => 'date',
        'modules_allowed' => 'array',
        'max_users' => 'integer',
        'is_in_trial' => 'boolean',
        'refreshed_at' => 'datetime',
    ];
    
    /**
     * Get the organization that owns this active subscription
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'org_id');
    }
    
    /**
     * Get the subscription record
     */
    public function subscription()
    {
        return $this->belongsTo(OrgSubscription::class, 'subscription_id', 'subscription_id');
    }
    
    /**
     * Get the subscription plan
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }
    
    /**
     * Check if a module is allowed
     */
    public function hasModule(string $moduleCode): bool
    {
        return in_array($moduleCode, $this->modules_allowed ?? []);
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
        return $this->subscription_status === 'TRIAL' || $this->is_in_trial;
    }
    
    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->subscription_status === 'PAST_DUE';
    }
    
    /**
     * Check if subscription period has ended
     */
    public function isPeriodEnded(): bool
    {
        return now()->greaterThan($this->period_end_date);
    }
}
