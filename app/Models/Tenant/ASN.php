<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ASN extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'asn_headers';

    protected $fillable = [
        'asn_number',
        'po_id',
        'vendor_id',
        'warehouse_id',
        'ship_date',
        'eta',
        'actual_arrival',
        'carrier_name',
        'tracking_number',
        'vehicle_number',
        'container_id',
        'driver_name',
        'driver_phone',
        'ship_from_address',
        'ship_to_address',
        'customer_reference',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ship_date' => 'datetime',
        'eta' => 'datetime',
        'actual_arrival' => 'datetime',
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
     * Get the vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the warehouse
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the line items
     */
    public function lineItems()
    {
        return $this->hasMany(ASNLineItem::class, 'asn_id');
    }

    /**
     * Get the documents
     */
    public function documents()
    {
        return $this->hasMany(ASNDocument::class, 'asn_id');
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
     * Scope: ASNs arriving today
     */
    public function scopeArrivingToday($query)
    {
        return $query->whereDate('eta', today())
            ->whereIn('status', ['SENT', 'IN_TRANSIT']);
    }

    /**
     * Scope: Overdue ASNs
     */
    public function scopeOverdue($query)
    {
        return $query->where('eta', '<', now())
            ->whereNull('actual_arrival')
            ->whereIn('status', ['SENT', 'IN_TRANSIT']);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
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
     * Check if ASN can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['DRAFT', 'SENT']);
    }

    /**
     * Check if ASN can be cancelled
     */
    public function canCancel(): bool
    {
        return !in_array($this->status, ['RECEIVED', 'CANCELLED']);
    }

    /**
     * Check if ASN is overdue
     */
    public function isOverdue(): bool
    {
        return $this->eta < now() 
            && is_null($this->actual_arrival)
            && in_array($this->status, ['SENT', 'IN_TRANSIT']);
    }

    /**
     * Check if ASN is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    /**
     * Check if ASN has arrived
     */
    public function hasArrived(): bool
    {
        return in_array($this->status, ['ARRIVED', 'RECEIVED']);
    }
}
