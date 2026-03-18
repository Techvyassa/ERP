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
     * Get UOM
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Get source bin
     */
    public function sourceBin()
    {
        return $this->belongsTo(BinLocation::class, 'source_bin_id');
    }

    /**
     * Get destination bin
     */
    public function destinationBin()
    {
        return $this->belongsTo(BinLocation::class, 'destination_bin_id');
    }

    /**
     * Get assigned user
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get putaway lines
     */
    public function putawayLines()
    {
        return $this->hasMany(PutawayLine::class, 'putaway_task_id');
    }

    /**
     * Get completer
     */
    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get assigned operator (alias for assignedUser)
     */
    public function assignedOperator()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get completed by operator (alias for completer)
     */
    public function completedByOperator()
    {
        return $this->belongsTo(User::class, 'completed_by');
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
        return !in_array($this->status, ['COMPLETED', 'CANCELLED']);
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
}
