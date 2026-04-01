<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_orders';

    protected $fillable = [
        'order_no', 'product_id', 'bom_id', 'target_qty',
        'planned_date', 'status', 'created_by',
        // Execution fields
        'actual_start_at', 'actual_end_at',
        'actual_qty', 'rejected_qty', 'rework_qty',
        'yield_percent',
        'fg_bin_id', 'fg_warehouse_id', 'fg_batch_number',
        'confirmed_by', 'confirmed_at',
        // Cumulative session totals
        'confirmed_qty_total', 'rejected_qty_total',
    ];

    protected $casts = [
        'target_qty'          => 'decimal:3',
        'planned_date'        => 'date',
        'actual_qty'          => 'decimal:3',
        'rejected_qty'        => 'decimal:3',
        'rework_qty'          => 'decimal:3',
        'yield_percent'       => 'decimal:2',
        'confirmed_qty_total' => 'decimal:3',
        'rejected_qty_total'  => 'decimal:3',
        'actual_start_at'     => 'datetime',
        'actual_end_at'       => 'datetime',
        'confirmed_at'        => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bom()
    {
        return $this->belongsTo(BOMHeader::class, 'bom_id');
    }

    public function mir()
    {
        return $this->hasOne(MaterialIssueRequest::class, 'production_order_id');
    }

    public function fgBin()
    {
        return $this->belongsTo(BinLocation::class, 'fg_bin_id');
    }

    public function fgWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'fg_warehouse_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function inspectionLots()
    {
        return $this->hasMany(InspectionLot::class, 'production_order_id');
    }

    public function fgSessions()
    {
        return $this->hasMany(FGConfirmationSession::class, 'production_order_id');
    }

    /**
     * Check if order can be started
     */
    public function canStart(): bool
    {
        return $this->status === 'DRAFT' && $this->mir?->status === 'APPROVED';
    }

    /**
     * Check if FG can be confirmed
     */
    public function canConfirmFG(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Calculate yield percentage
     */
    public function calculateYield(): ?float
    {
        if (!$this->target_qty || $this->target_qty <= 0) {
            return null;
        }
        return round(($this->actual_qty / $this->target_qty) * 100, 2);
    }
}
