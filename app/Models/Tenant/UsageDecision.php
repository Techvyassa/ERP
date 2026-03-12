<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class UsageDecision extends Model
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
        'accepted_qty' => 'decimal:3',
        'rejected_qty' => 'decimal:3',
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
     * Get the QC manager who decided
     */
    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Get the override approver
     */
    public function overrideApprover()
    {
        return $this->belongsTo(User::class, 'override_approved_by');
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
        return $this->decision === 'CONDITIONALLY_ACCEPTED';
    }

    /**
     * Check if rework is required
     */
    public function isReworkRequired(): bool
    {
        return $this->decision === 'REWORK_REQUIRED';
    }
}
