<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GRN extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'grn_headers';

    protected $fillable = [
        'grn_number',
        'mr_id',
        'po_id',
        'vendor_id',
        'grn_date',
        'posting_date',
        'total_received_value',
        'total_tax_amount',
        'grand_total',
        'journal_ref',
        'status',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'grn_date' => 'date',
        'posting_date' => 'date',
        'total_received_value' => 'decimal:2',
        'total_tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the material receipt
     */
    public function materialReceipt()
    {
        return $this->belongsTo(MaterialReceipt::class, 'mr_id');
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
        return $this->hasMany(GRNLineItem::class, 'grn_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate GRN number: GRN/YY-YY/NNNN
     */
    public static function generateGRNNumber(): string
    {
        $fiscalYear = now()->month >= 4 
            ? now()->format('y') . '-' . now()->addYear()->format('y')
            : now()->subYear()->format('y') . '-' . now()->format('y');
        
        $lastGRN = self::where('grn_number', 'like', "GRN/{$fiscalYear}/%")
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastGRN 
            ? intval(substr($lastGRN->grn_number, -4)) + 1
            : 1;
        
        return sprintf('GRN/%s/%04d', $fiscalYear, $nextNumber);
    }

    /**
     * Check if GRN can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['PROVISIONAL', 'QC_PENDING']);
    }

    /**
     * Check if GRN can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'PROVISIONAL';
    }

    /**
     * Check if GRN can be cancelled
     */
    public function canCancel(): bool
    {
        return !in_array($this->status, ['ACCEPTED', 'CANCELLED']);
    }

    /**
     * Scope: Provisional GRNs
     */
    public function scopeProvisional($query)
    {
        return $query->where('status', 'PROVISIONAL');
    }

    /**
     * Scope: QC Pending GRNs
     */
    public function scopeQCPending($query)
    {
        return $query->where('status', 'QC_PENDING');
    }

    /**
     * Scope: By Material Receipt
     */
    public function scopeByMR($query, int $mrId)
    {
        return $query->where('mr_id', $mrId);
    }

    /**
     * Scope: By Purchase Order
     */
    public function scopeByPO($query, int $poId)
    {
        return $query->where('po_id', $poId);
    }

    /**
     * Scope: By Vendor
     */
    public function scopeByVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
