<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorContact extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_contacts';

    protected $fillable = [
        'vendor_id',
        'contact_person',
        'designation',
        'department',
        'phone',
        'mobile',
        'email',
        'is_primary',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
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
