<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class VendorMaterialMap extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vendor_material_map';
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'material_id',
        'vendor_material_code',
        'last_purchase_price',
        'lead_time_days',
        'min_order_qty',
        'is_preferred',
        'is_active',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'min_order_qty' => 'decimal:3',
        'last_purchase_price' => 'decimal:4',
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
