<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'currency_master';

    protected $fillable = [
        'currency_code',
        'currency_name',
        'currency_symbol',
        'exchange_rate',
        'is_base_currency',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_base_currency' => 'boolean',
        'is_active' => 'boolean',
        'exchange_rate' => 'decimal:4'
    ];

    // Relationships
    public function vendorMaterialMaps()
    {
        return $this->hasMany(VendorMaterialMap::class, 'currency_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseCurrency($query)
    {
        return $query->where('is_base_currency', true);
    }
}
