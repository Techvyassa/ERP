<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MRLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'mr_line_items';

    protected $fillable = [
        'mr_id',
        'po_line_id',
        'material_id',
        'received_qty',
        'shortage_qty',
        'excess_qty',
        'rejected_on_arrival',
        'uom_id',
        'shortage_flag',
        'excess_flag',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'provisional_bin_id',
        'damage_found',
        'damage_remarks',
        'damage_photo_path',
        'internal_barcode',
    ];

    protected $casts = [
        'received_qty' => 'decimal:3',
        'shortage_qty' => 'decimal:3',
        'excess_qty' => 'decimal:3',
        'rejected_on_arrival' => 'decimal:3',
        'shortage_flag' => 'boolean',
        'excess_flag' => 'boolean',
        'damage_found' => 'boolean',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the material receipt
     */
    public function materialReceipt()
    {
        return $this->belongsTo(MaterialReceipt::class, 'mr_id');
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
     * Get the provisional bin
     */
    public function provisionalBin()
    {
        return $this->belongsTo(BinLocation::class, 'provisional_bin_id');
    }

    /**
     * Calculate variance
     */
    public function calculateVariance(): array
    {
        $poQty = $this->poLineItem->ordered_qty ?? 0;
        $variance = $this->received_qty - $poQty;
        
        return [
            'variance' => $variance,
            'shortage' => $variance < 0 ? abs($variance) : 0,
            'excess' => $variance > 0 ? $variance : 0,
        ];
    }

    /**
     * Check if shortage
     */
    public function isShortage(): bool
    {
        return $this->shortage_qty > 0;
    }

    /**
     * Check if excess
     */
    public function isExcess(): bool
    {
        return $this->excess_qty > 0;
    }

    /**
     * Check if within tolerance
     */
    public function isWithinTolerance(): bool
    {
        return !$this->shortage_flag && !$this->excess_flag;
    }

    /**
     * Generate internal barcode
     */
    public function generateInternalBarcode(): string
    {
        return 'MR-' . $this->mr_id . '-' . $this->id . '-' . now()->format('YmdHis');
    }

    /**
     * Get net accepted quantity (received - rejected)
     */
    public function getNetAcceptedQty(): float
    {
        return $this->received_qty - $this->rejected_on_arrival;
    }
}
