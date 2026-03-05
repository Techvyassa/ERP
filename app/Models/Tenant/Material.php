<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $table = 'material_master';

    protected $fillable = [
        'material_code',
        'material_name',
        'material_description',
        'material_type',
        'uom_id',
        'hsn_code_id',
        'reorder_level',
        'reorder_quantity',
        'lead_time_days',
        'standard_cost',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reorder_level' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'lead_time_days' => 'integer'
    ];

    // Relationships
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function hsnCode()
    {
        return $this->belongsTo(HSNCode::class, 'hsn_code_id');
    }

    public function vendorMaps()
    {
        return $this->hasMany(VendorMaterialMap::class, 'material_id');
    }

    public function bomDetails()
    {
        return $this->hasMany(BOMDetail::class, 'material_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('material_type', $type);
    }
}
