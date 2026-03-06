<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    protected $connection = 'tenant';
    protected $table = 'currency_master';
    public $timestamps = false;

    protected $fillable = [
        'currency_code',
        'currency_name',
        'symbol',
        'exchange_rate',
        'is_base_currency',
        'is_active',
        'updated_at'
    ];

    protected $casts = [
        'is_base_currency' => 'boolean',
        'is_active' => 'boolean',
        'exchange_rate' => 'decimal:6'
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
