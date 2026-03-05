<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BOMDetail extends Model
{
    use SoftDeletes;

    protected $table = 'bom_detail';

    protected $fillable = [
        'bom_header_id',
        'material_id',
        'sequence_no',
        'quantity_required',
        'uom_id',
        'wastage_percentage',
        'is_optional',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'quantity_required' => 'decimal:4',
        'wastage_percentage' => 'decimal:2',
        'sequence_no' => 'integer'
    ];

    // Relationships
    public function bomHeader()
    {
        return $this->belongsTo(BOMHeader::class, 'bom_header_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    // Scopes
    public function scopeByBOM($query, $bomHeaderId)
    {
        return $query->where('bom_header_id', $bomHeaderId);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_optional', false);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }
}
