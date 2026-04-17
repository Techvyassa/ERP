<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MIRLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'mir_line_items';
    public $timestamps = false;

    protected $fillable = [
        'mir_id',
        'material_id',
        'required_qty',
        'issued_qty',
        'uom_id',
        'status',
        'last_issued_at',
        'rejected_reason',
        'bin_barcode',
        'material_barcode',
        'scan_status',
        'bin_id',
        'warehouse_id',
        'scanned_at',
    ];

    protected $casts = [
        'required_qty' => 'decimal:4',
        'issued_qty' => 'decimal:4',
        'last_issued_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    public function mir()
    {
        return $this->belongsTo(MaterialIssueRequest::class, 'mir_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function transactions()
    {
        return $this->hasMany(MIRIssueTransaction::class, 'mir_line_id');
    }

    /**
     * Update line status based on issued quantity
     * PENDING: No action taken
     * APPROVED: Store confirmed availability, ready to issue
     * PARTIALLY_PICKED: issued_qty > 0 but < required_qty
     * FULLY_PICKED: issued_qty = required_qty
     * REJECTED: Store rejected this line
     */
    public function updateStatus(): void
    {
        if ($this->status === 'REJECTED') {
            return; // Don't change rejected status
        }

        if ($this->issued_qty <= 0) {
            // If no qty issued yet, keep as APPROVED (if it was approved)
            if ($this->status !== 'PENDING') {
                $this->status = 'APPROVED';
            }
        } elseif ($this->issued_qty >= $this->required_qty) {
            // Full quantity issued
            $this->status = 'FULLY_PICKED';
        } else {
            // Partial quantity issued
            $this->status = 'PARTIALLY_PICKED';
        }

        $this->last_issued_at = now();
        $this->save();
    }

    /**
     * Check if line can be issued
     */
    public function canIssue(): bool
    {
        return in_array($this->status, ['APPROVED', 'PARTIALLY_PICKED']);
    }

    /**
     * Check if line can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if line can be rejected
     */
    public function canReject(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Get remaining quantity to issue
     */
    public function getRemainingQty(): float
    {
        return round(max(0, $this->required_qty - $this->issued_qty), 3);
    }

    /**
     * Check if line is fully picked
     */
    public function isFullyPicked(): bool
    {
        return $this->status === 'FULLY_PICKED' && $this->issued_qty >= $this->required_qty;
    }
}
