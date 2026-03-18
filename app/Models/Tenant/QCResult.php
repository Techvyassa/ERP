<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCResult extends Model
{
    protected $connection = 'tenant';
    protected $table = 'inspection_results';

    protected $fillable = [
        'lot_id',
        'parameter_name',
        'standard_min',
        'standard_max',
        'standard_value',
        'observed_value',
        'unit_of_measurement',
        'is_pass',
        'remarks',
    ];

    protected $casts = [
        'observed_value' => 'decimal:4',
        'is_pass' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the inspection lot
     */
    public function inspectionLot()
    {
        return $this->belongsTo(InspectionLot::class, 'lot_id');
    }

    /**
     * Check if result is within standard
     */
    public function isWithinStandard(): bool
    {
        if (!$this->standard_min || !$this->standard_max) {
            return true;
        }
        return $this->observed_value >= $this->standard_min && $this->observed_value <= $this->standard_max;
    }
}
