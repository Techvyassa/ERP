<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vendor_master';

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'gstin',
        'pan_number',
        'msme_category',
        'payment_terms',
        'credit_days',
        'currency_id',
        'delivery_terms',
        'bank_name',
        'bank_account_no',
        'ifsc_code',
        'is_approved',
        'approved_date',
        'approved_by',
        'rating_score',
        'blacklisted',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'blacklisted' => 'boolean',
        'credit_days' => 'integer',
        'rating_score' => 'decimal:2',
        'approved_date' => 'date',
    ];

    // Relationships
    public function contacts()
    {
        return $this->hasMany(VendorContact::class, 'vendor_id');
    }

    public function materialMaps()
    {
        return $this->hasMany(VendorMaterialMap::class, 'vendor_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('blacklisted', false);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }
}
