<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionRequest extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_requests';

    protected $fillable = [
        'request_no',
        'product_id',
        'bom_id',
        'target_qty',
        'uom_id',
        'planned_date',
        'status',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
        'mir_id',
        'production_order_id',
    ];

    protected $casts = [
        'target_qty'    => 'decimal:3',
        'planned_date'  => 'date',
        'approved_at'   => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BOMHeader::class, 'bom_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function mir(): BelongsTo
    {
        return $this->belongsTo(MaterialIssueRequest::class, 'mir_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /**
     * Generate unique request number
     */
    public static function generateRequestNo(): string
    {
        $prefix = 'PRQ';
        $date = now()->format('ymd');
        $lastRequest = self::where('request_no', 'like', "{$prefix}{$date}%")
            ->orderByDesc('request_no')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if request can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if request can be converted to MIR
     */
    public function canConvertToMIR(): bool
    {
        return in_array($this->status, ['APPROVED', 'CONVERTED_TO_MIR']);
    }

    /**
     * Check if request can be converted to Production Order
     */
    public function canConvertToProductionOrder(): bool
    {
        return in_array($this->status, ['APPROVED', 'CONVERTED_TO_MIR']);
    }
}