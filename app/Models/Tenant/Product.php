<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'product_master';

    protected $fillable = [
        'product_code',
        'product_name',
        'product_description',
        'product_category',
        'uom_id',
        'hsn_code_id',
        'standard_cost',
        'selling_price',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'standard_cost' => 'decimal:2',
        'selling_price' => 'decimal:2'
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
