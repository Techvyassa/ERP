<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HSNCode extends Model
{
    protected $connection = 'tenant';
    protected $table = 'hsn_codes';
    public $timestamps = false;

    protected $fillable = [
        'hsn_code',
        'description',
        'default_gst_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function defaultGst()
    {
        return $this->belongsTo(GSTTax::class, 'default_gst_id');
    }

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
