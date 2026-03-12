<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InspectionResult extends Model
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
     * Check if test passed
     */
    public function isPass(): bool
    {
        return $this->is_pass === true;
    }

    /**
     * Check if test failed
     */
    public function isFail(): bool
    {
        return $this->is_pass === false;
    }

    /**
     * Check if test is pending
     */
    public function isPending(): bool
    {
        return $this->is_pass === null;
    }
}
