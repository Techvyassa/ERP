<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $connection = 'tenant';
    protected $table = 'material_master';

    protected $fillable = [
        'material_code',
        'material_name',
        'material_type',
        'uom_id',
        'purchase_uom_id',
        'hsn_code_id',
        'default_warehouse_id',
        'reorder_level',
        'safety_stock',
        'lead_time_days',
        'shelf_life_days',
        'qc_required',
        'inspection_type',
        'is_batch_tracked',
        'standard_cost',
        'valuation_method',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'qc_required' => 'boolean',
        'is_batch_tracked' => 'boolean',
        'reorder_level' => 'decimal:3',
        'safety_stock' => 'decimal:3',
        'standard_cost' => 'decimal:4',
        'lead_time_days' => 'integer',
        'shelf_life_days' => 'integer'
    ];

    // Relationships
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function purchaseUom()
    {
        return $this->belongsTo(UOM::class, 'purchase_uom_id');
    }

    public function hsnCode()
    {
        return $this->belongsTo(HSNCode::class, 'hsn_code_id');
    }

    public function defaultWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
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
