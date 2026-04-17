<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BOMHeader extends Model
{
    protected $connection = 'tenant';
    protected $table = 'bom_header';
    public $timestamps = false;

    protected $fillable = [
        'bom_code',
        'version',
        'product_id',
        'batch_size',
        'effective_from',
        'effective_to',
        'bom_status',
        'output_uom_id',
        'remarks',
        'created_by',
        'approved_by'
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function outputUom()
    {
        return $this->belongsTo(UOM::class, 'output_uom_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bomDetails()
    {
        return $this->hasMany(BOMDetail::class, 'bom_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('bom_status', 'ACTIVE');
    }

    public function scopeApproved($query)
    {
        return $query->where('bom_status', 'ACTIVE');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
