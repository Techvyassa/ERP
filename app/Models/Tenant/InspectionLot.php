<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InspectionLot extends Model
{
    protected $connection = 'tenant';
    protected $table = 'inspection_lots';

    protected $fillable = [
        'lot_number',
        'grn_id',
        'grn_line_id',
        'material_id',
        'lot_qty',
        'sample_size',
        'sampling_method',
        'assigned_to',
        'due_by',
        'status',
        'remarks',
    ];

    protected $casts = [
        'lot_qty' => 'decimal:3',
        'sample_size' => 'decimal:3',
        'due_by' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the GRN header
     */
    public function grn()
    {
        return $this->belongsTo(GRN::class, 'grn_id');
    }

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
     * Get the assigned QC technician
     */
    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the test results
     */
    public function testResults()
    {
        return $this->hasMany(InspectionResult::class, 'lot_id');
    }

    /**
     * Get the usage decision
     */
    public function usageDecision()
    {
        return $this->hasOne(UsageDecision::class, 'lot_id');
    }

    /**
     * Generate lot number: IL-YY-NNNN
     */
    public static function generateLotNumber(): string
    {
        $year = now()->format('y');
        $month = now()->format('m');
        
        $lastLot = self::where('lot_number', 'like', "IL-{$year}{$month}/%")
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastLot 
            ? intval(substr($lastLot->lot_number, -4)) + 1
            : 1;
        
        return sprintf('IL-%s%s/%04d', $year, $month, $nextNumber);
    }

    /**
     * Check if lot can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_PROGRESS']);
    }

    /**
     * Check if lot can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Check if decision can be made
     */
    public function canMakeDecision(): bool
    {
        return $this->status === 'COMPLETED';
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

    /**
     * Scope: By Material
     */
    public function scopeByMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Scope: By Technician
     */
    public function scopeByTechnician($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}
