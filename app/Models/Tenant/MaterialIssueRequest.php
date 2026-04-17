<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MaterialIssueRequest extends Model
{
    protected $connection = 'tenant';
    protected $table = 'material_issue_requests';

    protected $fillable = [
        'mir_no',
        'batch_run_id',
        'production_order_id',
        'production_request_id',
        'status',
        'remarks',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'fully_issued_at',
        'closed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'fully_issued_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function productionRequest()
    {
        return $this->belongsTo(ProductionRequest::class, 'production_request_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function batchRun()
    {
        return $this->belongsTo(ProductionBatchRun::class, 'batch_run_id');
    }

    public function lines()
    {
        return $this->hasMany(MIRLineItem::class, 'mir_id');
    }

    /**
     * Derive MIR header status from line statuses
     * PENDING: All lines PENDING
     * APPROVED: All lines APPROVED (none picked yet)
     * PARTIALLY_ISSUED: At least one line PARTIALLY_PICKED or FULLY_PICKED, but not all FULLY_PICKED
     * FULLY_ISSUED: All lines FULLY_PICKED
     * REJECTED: One or more lines REJECTED
     * CLOSED: Production confirmed receipt
     */
    public function deriveHeaderStatus(): string
    {
        $lines = $this->lines()->get();

        if ($lines->isEmpty()) {
            return 'PENDING';
        }

        $statuses = $lines->pluck('status')->toArray();
        $uniqueStatuses = array_unique($statuses);

        // If any line is REJECTED, header is REJECTED
        if (in_array('REJECTED', $statuses)) {
            return 'REJECTED';
        }

        // If all lines are PENDING
        if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'PENDING') {
            return 'PENDING';
        }

        // If all lines are APPROVED (none picked yet)
        if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'APPROVED') {
            return 'APPROVED';
        }

        // If all lines are FULLY_PICKED
        if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'FULLY_PICKED') {
            return 'FULLY_ISSUED';
        }

        // If at least one line is PARTIALLY_PICKED or FULLY_PICKED, but not all FULLY_PICKED
        $pickedCount = $lines->whereIn('status', ['PARTIALLY_PICKED', 'FULLY_PICKED'])->count();
        if ($pickedCount > 0 && $pickedCount < $lines->count()) {
            return 'PARTIALLY_ISSUED';
        }

        // If all lines are FULLY_PICKED (double check)
        if ($lines->where('status', 'FULLY_PICKED')->count() === $lines->count()) {
            return 'FULLY_ISSUED';
        }

        return 'PARTIALLY_ISSUED';
    }

    /**
     * Update header status and timestamps based on derived status
     */
    public function updateHeaderStatus(): void
    {
        $newStatus = $this->deriveHeaderStatus();
        $oldStatus = $this->status;

        $this->status = $newStatus;

        // Set fully_issued_at when transitioning to FULLY_ISSUED
        if ($newStatus === 'FULLY_ISSUED' && $oldStatus !== 'FULLY_ISSUED') {
            $this->fully_issued_at = now();
        }

        $this->save();
    }

    /**
     * Check if all lines are approved
     */
    public function allLinesApproved(): bool
    {
        $totalLines = $this->lines()->count();
        $approvedLines = $this->lines()->whereIn('status', ['APPROVED', 'PARTIALLY_PICKED', 'FULLY_PICKED'])->count();

        return $totalLines > 0 && $totalLines === $approvedLines;
    }

    /**
     * Check if MIR can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'PENDING' && $this->allLinesApproved();
    }

    /**
     * Check if MIR can be rejected
     */
    public function canReject(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Generate unique MIR number
     */
    public static function generateMirNo(): string
    {
        $prefix = 'MIR';
        $date = now()->format('ymd');
        $lastMIR = self::where('mir_no', 'like', "{$prefix}{$date}%")
            ->orderByDesc('mir_no')
            ->first();

        if ($lastMIR) {
            $lastNumber = (int) substr($lastMIR->mir_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
