<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorMaterialMap extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_material_map';

    protected $fillable = [
        'vendor_id',
        'material_id',
        'vendor_material_code',
        'vendor_material_name',
        'lead_time_days',
        'moq',
        'unit_price',
        'currency_id',
        'is_preferred',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'moq' => 'decimal:2',
        'unit_price' => 'decimal:2'
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('material_id', $materialId);
    }
}
