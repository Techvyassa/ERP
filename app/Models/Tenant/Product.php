<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'tenant';
    protected $table = 'product_master';

    protected $fillable = [
        'product_code',
        'product_name',
        'product_category',
        'pack_size',
        'pack_uom_id',
        'hsn_code_id',
        'standard_cost',
        'mrp',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'pack_size' => 'decimal:3',
        'standard_cost' => 'decimal:4',
        'mrp' => 'decimal:2'
    ];

    // Relationships
    public function packUom()
    {
        return $this->belongsTo(UOM::class, 'pack_uom_id');
    }

    public function hsnCode()
    {
        return $this->belongsTo(HSNCode::class, 'hsn_code_id');
    }

    public function bomHeaders()
    {
        return $this->hasMany(BOMHeader::class, 'product_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('product_category', $category);
    }
}
