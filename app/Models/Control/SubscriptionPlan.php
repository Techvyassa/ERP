<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $connection = 'control';
    protected $table = 'subscription_plans';
    protected $primaryKey = 'plan_id';
    
    protected $fillable = [
        'plan_code',
        'plan_name',
        'description',
        'billing_cycle',
        'price_amount',
        'currency_code',
        'max_users',
        'max_warehouses',
        'max_materials',
        'storage_gb',
        'api_rate_limit_day',
        'modules_included',
        'is_active',
        'is_public',
    ];
    
    protected $casts = [
        'price_amount' => 'decimal:2',
        'max_users' => 'integer',
        'max_warehouses' => 'integer',
        'max_materials' => 'integer',
        'storage_gb' => 'integer',
        'api_rate_limit_day' => 'integer',
        'modules_included' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get all subscriptions using this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(OrgSubscription::class, 'plan_id', 'plan_id');
    }
    
    /**
     * Scope to filter only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope to filter only public plans
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
    
    /**
     * Check if a module is included in this plan
     */
    public function hasModule(string $moduleCode): bool
    {
        return in_array($moduleCode, $this->modules_included ?? []);
    }
    
    /**
     * Get the billing cycle duration in days
     */
    public function getBillingCycleDays(): int
    {
        return match($this->billing_cycle) {
            'MONTHLY' => 30,
            'QUARTERLY' => 90,
            'ANNUAL' => 365,
            default => 30,
        };
    }
}
