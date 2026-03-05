<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_master';

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'warehouse_type',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'contact_person',
        'contact_phone',
        'contact_email',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function binLocations()
    {
        return $this->hasMany(BinLocation::class, 'warehouse_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('warehouse_type', $type);
    }
}
