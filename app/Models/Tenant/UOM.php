<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class UOM extends Model
{
    protected $connection = 'tenant';
    protected $table = 'uom_master';
    public $timestamps = false;

    protected $fillable = [
        'uom_code',
        'uom_name',
        'uom_type',
        'base_uom_id',
        'conversion_factor',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:6'
    ];

    // Relationships
    public function baseUom()
    {
        return $this->belongsTo(UOM::class, 'base_uom_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'uom_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'pack_uom_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
