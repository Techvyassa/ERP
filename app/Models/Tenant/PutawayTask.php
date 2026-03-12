<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PutawayTask extends Model
{
    protected $connection = 'tenant';
    protected $table = 'putaway_tasks';

    protected $fillable = [
        'task_number',
        'grn_line_id',
        'material_id',
        'batch_number',
        'quantity',
        'uom_id',
        'source_bin_id',
        'destination_bin_id',
        'strategy',
        'status',
        'bin_scan_confirmed',
        'item_scan_confirmed',
        'completed_at',
        'assigned_to',
        'completed_by',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the GRN line item
     */
    public function grnLineItem()
    {
        return $this->belongsTo(GRNLineItem::class, 'grn_line_id');
    }

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get the UOM
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Get the source bin
     */
    public function sourceBin()
    {
        return $this->belongsTo(BinLocation::class, 'source_bin_id');
    }

    /**
     * Get the destination bin
     */
    public function destinationBin()
    {
        return $this->belongsTo(BinLocation::class, 'destination_bin_id');
    }

    /**
     * Get the assigned operator
     */
    public function assignedOperator()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the operator who completed
     */
    public function completedByOperator()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Generate task number: PT-YYMM-NNNN
     */
    public static function generateTaskNumber(): string
    {
        $year = now()->format('y');
        $month = now()->format('m');
        
        $lastTask = self::where('task_number', 'like', "PT-{$year}{$month}/%")
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastTask 
            ? intval(substr($lastTask->task_number, -4)) + 1
            : 1;
        
        return sprintf('PT-%s%s/%04d', $year, $month, $nextNumber);
    }

    /**
     * Check if task can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_PROGRESS']);
    }

    /**
     * Check if task can be started
     */
    public function canStart(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if task can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Check if task can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_PROGRESS']);
    }

    /**
     * Scope: Pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: In progress tasks
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'IN_PROGRESS');
    }

    /**
     * Scope: Completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Scope: By operator
     */
    public function scopeByOperator($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: By material
     */
    public function scopeByMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Scope: By destination bin
     */
    public function scopeByDestinationBin($query, int $binId)
    {
        return $query->where('destination_bin_id', $binId);
    }
}
