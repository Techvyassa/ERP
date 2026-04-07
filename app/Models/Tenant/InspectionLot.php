<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InspectionLot extends Model
{
    protected $connection = 'tenant';
    protected $table = 'inspection_lots';

    protected $fillable = [
        'lot_number',
        'source_type',
        'grn_id',
        'grn_line_id',
        'production_order_id',
        'material_id',
        'product_id',
        'warehouse_id',
        'bin_id',
        'batch_number',
        'lot_qty',
        'sample_size',
        'sampling_method',
        'assigned_to',
        'due_by',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'lot_qty' => 'decimal:3',
        'sample_size' => 'decimal:3',
        'due_by' => 'datetime',
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

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /**
     * Get the GRN line item
     */
    public function grnLine()
    {
        return $this->belongsTo(GRNLineItem::class, 'grn_line_id');
    }

    /**
     * Get the GRN line item (alias for grnLine)
     */
    public function grnLineItem()
    {
        return $this->grnLine();
    }

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bin()
    {
        return $this->belongsTo(BinLocation::class, 'bin_id');
    }

    /**
     * Get assigned QC technician
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get assigned QC technician (alias for assignedUser)
     */
    public function assignedTechnician()
    {
        return $this->assignedUser();
    }

    /**
     * Get QC results
     */
    public function qcResults()
    {
        return $this->hasMany(QCResult::class, 'lot_id');
    }

    /**
     * Get QC decision
     */
    public function qcDecision()
    {
        return $this->hasOne(QCDecision::class, 'lot_id');
    }

    /**
     * Get QC results (alias for qcResults)
     */
    public function testResults()
    {
        return $this->qcResults();
    }

    /**
     * Get QC decision (alias for qcDecision)
     */
    public function usageDecision()
    {
        return $this->qcDecision();
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

    public function scopeByProductionOrder($query, int $productionOrderId)
    {
        return $query->where('production_order_id', $productionOrderId);
    }
}
