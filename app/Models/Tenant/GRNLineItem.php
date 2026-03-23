<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class GRNLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'grn_line_items';

    protected $fillable = [
        'grn_id',
        'mr_line_id',
        'po_line_id',
        'material_id',
        'accepted_qty',
        'rejected_qty',
        'return_qty',
        'return_remarks',
        'uom_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'unit_price',
        'tax_rate',
        'line_value',
        'tax_amount',
        'warehouse_bin_id',
        'stock_status',
    ];

    protected $casts = [
        'accepted_qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'line_value' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
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
     * Get the MR line item
     */
    public function mrLineItem()
    {
        return $this->belongsTo(MRLineItem::class, 'mr_line_id');
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
     * Get the warehouse bin
     */
    public function warehouseBin()
    {
        return $this->belongsTo(BinLocation::class, 'warehouse_bin_id');
    }

    /**
     * Calculate line value
     */
    public function calculateLineValue(): float
    {
        return round($this->accepted_qty * $this->unit_price, 2);
    }

    /**
     * Calculate tax amount
     */
    public function calculateTaxAmount(): float
    {
        return round($this->line_value * ($this->tax_rate / 100), 2);
    }

    /**
     * Check if stock is restricted
     */
    public function isRestricted(): bool
    {
        return $this->stock_status === 'RESTRICTED';
    }

    /**
     * Check if stock is unrestricted
     */
    public function isUnrestricted(): bool
    {
        return $this->stock_status === 'UNRESTRICTED';
    }

    /**
     * Check if stock is blocked
     */
    public function isBlocked(): bool
    {
        return $this->stock_status === 'BLOCKED';
    }
}
