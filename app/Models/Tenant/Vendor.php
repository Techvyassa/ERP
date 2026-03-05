<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_master';

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'gstin',
        'pan',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'primary_contact_person',
        'primary_phone',
        'primary_email',
        'payment_terms',
        'credit_limit',
        'credit_days',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'credit_days' => 'integer'
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }
}
