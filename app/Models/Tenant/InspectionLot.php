<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InspectionLot extends Model
{
    protected $connection = 'tenant';
    protected $table = 'inspection_lots';

    protected $fillable = [
        'grn_id',
        'material_id',
        'sample_size',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the GRN
     */
    public function grn()
    {
        return $this->belongsTo(GRN::class, 'grn_id');
    }

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get QC results
     */
    public function qcResults()
    {
        return $this->hasMany(QCResult::class, 'inspection_lot_id');
    }

    /**
     * Get QC decision
     */
    public function qcDecision()
    {
        return $this->hasOne(QCDecision::class, 'inspection_lot_id');
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if lot can be started
     */
    public function canStart(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if lot can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Scope: Pending lots
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: In progress lots
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'IN_PROGRESS');
    }

    /**
     * Scope: Completed lots
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Scope: By GRN
     */
    public function scopeByGRN($query, int $grnId)
    {
        return $query->where('grn_id', $grnId);
    }
}
