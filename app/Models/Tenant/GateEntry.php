<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GateEntry extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'gate_entries';

    protected $fillable = [
        'ge_number',
        'po_id',
        'asn_id',
        'vendor_id',
        'vehicle_number',
        'transporter_name',
        'driver_name',
        'driver_phone',
        'challan_number',
        'vendor_invoice_number',
        'eway_bill_number',
        'eway_bill_expiry',
        'material_type',
        'gross_weight_kg',
        'arrived_at',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'eway_bill_expiry' => 'date',
        'gross_weight_kg' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the purchase order
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the ASN
     */
    public function asn()
    {
        return $this->belongsTo(ASN::class, 'asn_id');
    }

    /**
     * Get the vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the creator (security guard)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the verification record
     */
    public function verification()
    {
        return $this->hasOne(GateVerification::class, 'ge_id');
    }

    /**
     * Get the auto-created GRN
     */
    public function grn()
    {
        return $this->hasOne(\App\Models\Tenant\GRN::class, 'ge_id');
    }

    /**
     * Generate GE number
     * Format: GE-YYMM-NNNN
     */
    public static function generateGENumber(): string
    {
        $prefix = 'GE-' . now()->format('ym') . '-';
        
        $lastGE = self::where('ge_number', 'like', $prefix . '%')
            ->orderBy('ge_number', 'desc')
            ->first();
        
        if ($lastGE) {
            $lastNumber = (int) substr($lastGE->ge_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope: Pending (GRN not yet created)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: Pending verification (alias for backward compat)
     */
    public function scopePendingVerification($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: Completed (GRN auto-created)
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Scope: By vendor
     */
    public function scopeByVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: By PO
     */
    public function scopeByPO($query, int $poId)
    {
        return $query->where('po_id', $poId);
    }

    /**
     * Check if gate entry can have a GRN created (status = PENDING)
     */
    public function canCreateGRN(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if gate entry can be marked complete (transitions to COMPLETED after GRN created)
     */
    public function canComplete(): bool
    {
        return $this->status === 'PENDING';
    }
}
