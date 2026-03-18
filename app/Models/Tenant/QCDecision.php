<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCDecision extends Model
{
    protected $connection = 'tenant';
    protected $table = 'usage_decisions';

    protected $fillable = [
        'lot_id',
        'decision',
        'accepted_qty',
        'rejected_qty',
        'override_approved_by',
        'override_reason',
        'coa_file_path',
        'remarks',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
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
     * Get the decision maker
     */
    public function decisionMaker()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Check if decision is accepted
     */
    public function isAccepted(): bool
    {
        return $this->decision === 'ACCEPTED';
    }

    /**
     * Check if decision is rejected
     */
    public function isRejected(): bool
    {
        return $this->decision === 'REJECTED';
    }

    /**
     * Check if decision is conditional
     */
    public function isConditional(): bool
    {
        return $this->decision === 'CONDITIONAL';
    }
}
