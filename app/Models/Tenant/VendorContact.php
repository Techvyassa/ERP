<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class VendorContact extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vendor_contacts';
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'contact_name',
        'contact_type',
        'phone',
        'email',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
