<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ASNLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'asn_line_items';

    protected $fillable = [
        'asn_id',
        'po_line_id',
        'material_id',
        'material_description',
        'pallet_id',
        'sscc',
        'shipped_qty',
        'uom_id',
        'batch_number',
        'lot_number',
        'manufacturing_date',
        'expiry_date',
        'gross_weight',
        'net_weight',
        'weight_uom',
        'length',
        'width',
        'height',
        'dimension_uom',
        'line_status',
        'received_qty',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'shipped_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'net_weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ASN
     */
    public function asn()
    {
        return $this->belongsTo(ASN::class, 'asn_id');
    }

    /**
     * Get the PO line item
     */
    public function poLineItem()
    {
        return $this->belongsTo(PoLineItem::class, 'po_line_id');
    }

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get the UOM
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Check if line item is fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->line_status === 'RECEIVED' 
            && $this->received_qty >= $this->shipped_qty;
    }

    /**
     * Check if line item is partially received
     */
    public function isPartiallyReceived(): bool
    {
        return $this->line_status === 'PARTIAL' 
            && $this->received_qty > 0 
            && $this->received_qty < $this->shipped_qty;
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQty(): float
    {
        return max(0, $this->shipped_qty - $this->received_qty);
    }

    /**
     * Get variance (difference between shipped and received)
     */
    public function getVariance(): float
    {
        return $this->received_qty - $this->shipped_qty;
    }

    /**
     * Get variance percentage
     */
    public function getVariancePercentage(): float
    {
        if ($this->shipped_qty == 0) {
            return 0;
        }
        return ($this->getVariance() / $this->shipped_qty) * 100;
    }
}
