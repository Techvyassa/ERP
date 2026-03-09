<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $connection = 'tenant';
    protected $table = 'warehouse_master';
    public $timestamps = false;

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'warehouse_type',
        'address',
        'incharge_user_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function inchargeUser()
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }

    public function binLocations()
    {
        return $this->hasMany(BinLocation::class, 'warehouse_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'default_warehouse_id');
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
