<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GSTTax extends Model
{
    use SoftDeletes;

    protected $table = 'gst_taxes';

    protected $fillable = [
        'tax_name',
        'tax_type',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cess_rate',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'cess_rate' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('tax_type', $type);
    }

    // Helper method to get total tax rate
    public function getTotalTaxRate()
    {
        return $this->cgst_rate + $this->sgst_rate + $this->igst_rate + $this->cess_rate;
    }
}
