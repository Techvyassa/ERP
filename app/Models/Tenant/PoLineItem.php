<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PoLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'po_line_items';
    
    protected $fillable = [
        'po_id',
        'line_number',
        'material_id',
        'material_description',
        'ordered_qty',
        'uom_id',
        'unit_price',
        'discount_pct',
        'line_total',
        'gst_tax_id',
        'tax_amount',
        'scheduled_delivery',
        'under_delivery_tolerance',
        'over_delivery_tolerance',
        'received_qty',
        'receipt_status',
    ];

    protected $casts = [
        'scheduled_delivery' => 'date',
        'ordered_qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_pct' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'under_delivery_tolerance' => 'decimal:2',
        'over_delivery_tolerance' => 'decimal:2',
        'received_qty' => 'decimal:3',
    ];

    /**
     * Get the purchase order this line item belongs to
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the material associated with the line item
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get the unit of measure associated with the line item
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Get the GST tax applied to the line item
     */
    public function gstTax()
    {
        return $this->belongsTo(GSTTax::class, 'gst_tax_id');
    }
}
