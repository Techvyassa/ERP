<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialReceipt extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'material_receipts';

    protected $fillable = [
        'mr_number',
        'ge_id',
        'po_id',
        'vendor_id',
        'unloading_started_at',
        'unloading_completed_at',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'unloading_started_at' => 'datetime',
        'unloading_completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the gate entry
     */
    public function gateEntry()
    {
        return $this->belongsTo(GateEntry::class, 'ge_id');
    }

    /**
     * Get the purchase order
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the line items
     */
    public function lineItems()
    {
        return $this->hasMany(MRLineItem::class, 'mr_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Generate MR number
     * Format: MR-YYMM-NNNN
     */
    public static function generateMRNumber(): string
    {
        $prefix = 'MR-' . now()->format('ym') . '-';
        
        $lastMR = self::where('mr_number', 'like', $prefix . '%')
            ->orderBy('mr_number', 'desc')
            ->first();
        
        if ($lastMR) {
            $lastNumber = (int) substr($lastMR->mr_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if MR can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_PROGRESS', 'COMPLETED']);
    }

    /**
     * Check if MR can start unloading
     */
    public function canStartUnloading(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if MR can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Scope: Pending MRs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Calculate total received quantity
     */
    public function calculateTotalReceived(): float
    {
        return $this->lineItems()->sum('received_qty');
    }

    /**
     * Check if MR has shortages
     */
    public function hasShortages(): bool
    {
        return $this->lineItems()->where('shortage_flag', true)->exists();
    }

    /**
     * Check if MR has excess
     */
    public function hasExcess(): bool
    {
        return $this->lineItems()->where('excess_flag', true)->exists();
    }

    /**
     * Scope: In progress MRs
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'IN_PROGRESS');
    }

    /**
     * Scope: Pending GRN
     */
    public function scopePendingGRN($query)
    {
        return $query->where('status', 'PENDING_GRN');
    }

    /**
     * Scope: By gate entry
     */
    public function scopeByGateEntry($query, int $geId)
    {
        return $query->where('ge_id', $geId);
    }

    /**
     * Scope: By PO
     */
    public function scopeByPO($query, int $poId)
    {
        return $query->where('po_id', $poId);
    }
}
