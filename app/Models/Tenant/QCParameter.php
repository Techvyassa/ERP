<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCParameter extends Model
{
    protected $connection = 'tenant';
    protected $table = 'qc_parameters';

    protected $fillable = [
        'material_id',
        'parameter_name',
        'standard_value_min',
        'standard_value_max',
        'unit',
        'test_method',
    ];

    protected $casts = [
        'standard_value_min' => 'decimal:4',
        'standard_value_max' => 'decimal:4',
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
     * Get QC results for this parameter
     */
    public function qcResults()
    {
        return $this->hasMany(QCResult::class, 'qc_parameter_id');
    }

    /**
     * Check if observed value is within standard range
     */
    public function isWithinStandard($observedValue): bool
    {
        return $observedValue >= $this->standard_value_min && $observedValue <= $this->standard_value_max;
    }
}
