<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HSNCode extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'hsn_codes';

    protected $fillable = [
        'hsn_code',
        'hsn_description',
        'gst_rate',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gst_rate' => 'decimal:2'
    ];

    // Relationships
    public function materials()
    {
        return $this->hasMany(Material::class, 'hsn_code_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'hsn_code_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
