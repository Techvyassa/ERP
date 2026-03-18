<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCResult extends Model
{
    protected $connection = 'tenant';
    protected $table = 'qc_results';

    protected $fillable = [
        'inspection_lot_id',
        'qc_parameter_id',
        'observed_value',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'observed_value' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the inspection lot
     */
    public function inspectionLot()
    {
        return $this->belongsTo(InspectionLot::class, 'inspection_lot_id');
    }

    /**
     * Get the QC parameter
     */
    public function qcParameter()
    {
        return $this->belongsTo(QCParameter::class, 'qc_parameter_id');
    }

    /**
     * Get the recorder
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Check if result is within standard
     */
    public function isWithinStandard(): bool
    {
        return $this->qcParameter->isWithinStandard($this->observed_value);
    }
}
