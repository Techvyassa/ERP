<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class GateVerification extends Model
{
    protected $connection = 'tenant';
    protected $table = 'gate_verifications';

    protected $fillable = [
        'ge_id',
        'challan_verified',
        'invoice_verified',
        'eway_bill_valid',
        'po_status_valid',
        'seal_number',
        'seal_intact',
        'external_damage',
        'tare_weight_kg',
        'net_weight_kg',
        'weight_variance_flag',
        'dock_assigned',
        'approval_status',
        'rejection_reason',
        'security_remarks',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'challan_verified' => 'boolean',
        'invoice_verified' => 'boolean',
        'eway_bill_valid' => 'boolean',
        'po_status_valid' => 'boolean',
        'seal_intact' => 'boolean',
        'external_damage' => 'boolean',
        'weight_variance_flag' => 'boolean',
        'tare_weight_kg' => 'decimal:3',
        'net_weight_kg' => 'decimal:3',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the gate entry
     */
    public function gateEntry()
    {
        return $this->belongsTo(GateEntry::class, 'ge_id');
    }

    /**
     * Get the verifier (security supervisor)
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if all documents are verified
     */
    public function allDocumentsVerified(): bool
    {
        return $this->challan_verified 
            && $this->invoice_verified 
            && $this->eway_bill_valid 
            && $this->po_status_valid;
    }

    /**
     * Check if physical inspection passed
     */
    public function physicalInspectionPassed(): bool
    {
        return !$this->external_damage 
            && ($this->seal_intact !== false);
    }

    /**
     * Check if weight is within tolerance
     */
    public function weightWithinTolerance(): bool
    {
        return !$this->weight_variance_flag;
    }

    /**
     * Check if verification can be approved
     */
    public function canApprove(): bool
    {
        return $this->approval_status === 'PENDING';
    }
}
