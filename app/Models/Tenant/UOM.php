<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UOM extends Model
{
    use SoftDeletes;

    protected $table = 'uom_master';

    protected $fillable = [
        'uom_code',
        'uom_name',
        'uom_description',
        'base_unit',
        'conversion_factor',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:4'
    ];

    // Relationships
    public function materials()
    {
        return $this->hasMany(Material::class, 'uom_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
