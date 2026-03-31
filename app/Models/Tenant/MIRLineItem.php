<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MIRLineItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'mir_line_items';
    public $timestamps = false;

    protected $fillable = ['mir_id', 'material_id', 'required_qty', 'issued_qty', 'uom_id',
                           'bin_barcode', 'material_barcode', 'scan_status', 'bin_id', 'warehouse_id', 'scanned_at'];

    protected $casts = ['required_qty' => 'decimal:3', 'issued_qty' => 'decimal:3'];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }
}
