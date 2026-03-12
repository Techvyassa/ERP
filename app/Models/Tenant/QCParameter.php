<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCParameter extends Model
{
    protected $connection = 'tenant';
    protected $table = 'qc_parameters_master';

    protected $fillable = [
        'material_id',
        'parameter_name',
        'parameter_category',
        'standard_min',
        'standard_max',
        'standard_value',
        'unit_of_measurement',
        'test_method',
        'is_critical',
        'display_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active parameters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By material
     */
    public function scopeByMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }
}
