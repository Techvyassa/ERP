<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BOMDetail extends Model
{
    protected $connection = 'tenant';
    protected $table = 'bom_detail';
    public $timestamps = false;

    protected $fillable = [
        'bom_id',
        'material_id',
        'qty_required',
        'uom_id',
        'scrap_percent',
        'effective_qty',
        'substitute_material_id',
        'is_critical',
        'line_no',
        'remarks',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'qty_required' => 'decimal:4',
        'scrap_percent' => 'decimal:2',
        'effective_qty' => 'decimal:4',
        'line_no' => 'integer'
    ];

    // Relationships
    public function bomHeader()
    {
        return $this->belongsTo(BOMHeader::class, 'bom_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    public function substituteMaterial()
    {
        return $this->belongsTo(Material::class, 'substitute_material_id');
    }

    // Scopes
    public function scopeByBOM($query, $bomId)
    {
        return $query->where('bom_id', $bomId);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeNonCritical($query)
    {
        return $query->where('is_critical', false);
    }
}
