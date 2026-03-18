<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCParameter extends Model
{
    protected $connection = 'tenant';
    protected $table = 'qc_parameters_master';

    protected $fillable = [
        'material_id',
        'parameter_code',
        'parameter_name',
        'parameter_category',
        'data_type',
        'tolerance_type',
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
        'display_order' => 'integer',
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
     * Check if observed value is within standard range
     */
    public function isWithinStandard($observedValue): bool
    {
        if ($this->tolerance_type === 'RANGE' && $this->standard_min !== null && $this->standard_max !== null) {
            return $observedValue >= $this->standard_min && $observedValue <= $this->standard_max;
        } elseif ($this->tolerance_type === 'MIN_ONLY' && $this->standard_min !== null) {
            return $observedValue >= $this->standard_min;
        } elseif ($this->tolerance_type === 'MAX_ONLY' && $this->standard_max !== null) {
            return $observedValue <= $this->standard_max;
        } elseif ($this->tolerance_type === 'EXACT' && $this->standard_value !== null) {
            return (string) $observedValue === (string) $this->standard_value;
        }
        return true;
    }
}
