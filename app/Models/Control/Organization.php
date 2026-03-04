<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;
    
    protected $connection = 'control';
    protected $table = 'organizations';
    protected $primaryKey = 'org_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'org_slug',
        'org_name',
        'tenant_db_name',
        'registration_status',
        'primary_email',
        'primary_phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'timezone',
        'currency_code',
        'max_users',
        'profile_completion',
        'profile_completion_percentage',
    ];
    
    protected $casts = [
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'created_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'profile_completion' => 'array',
    ];
    
    /**
     * Get all subscriptions for this organization
     */
    public function subscriptions()
    {
        return $this->hasMany(OrgSubscription::class, 'org_id', 'org_id');
    }
    
    /**
     * Get the active subscription for this organization
     */
    public function activeSubscription()
    {
        return $this->hasOne(ActiveSubscription::class, 'org_id', 'org_id');
    }
    
    /**
     * Get all feature controls for this organization
     */
    public function featureControls()
    {
        return $this->hasMany(FeatureControl::class, 'org_id', 'org_id');
    }
    
    /**
     * Get all payment records for this organization
     */
    public function paymentRecords()
    {
        return $this->hasMany(PaymentRecord::class, 'org_id', 'org_id');
    }
    
    /**
     * Scope to filter only active organizations
     */
    public function scopeActive($query)
    {
        return $query->where('registration_status', 'ACTIVE');
    }
    
    /**
     * Scope to filter only pending organizations
     */
    public function scopePending($query)
    {
        return $query->where('registration_status', 'PENDING');
    }
    
    /**
     * Scope to filter only suspended organizations
     */
    public function scopeSuspended($query)
    {
        return $query->where('registration_status', 'SUSPENDED');
    }
    
    /**
     * Check if organization is active
     */
    public function isActive(): bool
    {
        return $this->registration_status === 'ACTIVE';
    }
    
    /**
     * Check if organization is suspended
     */
    public function isSuspended(): bool
    {
        return $this->registration_status === 'SUSPENDED';
    }
    
    /**
     * Check if organization is terminated
     */
    public function isTerminated(): bool
    {
        return $this->registration_status === 'TERMINATED';
    }
}
