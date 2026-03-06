<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GSTTax extends Model
{
    protected $connection = 'tenant';
    protected $table = 'gst_taxes';
    public $timestamps = false;

    protected $fillable = [
        'tax_code',
        'tax_name',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'ugst_rate',
        'effective_from',
        'effective_to',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'ugst_rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date'
    ];

    // Relationships
    public function hsnCodes()
    {
        return $this->hasMany(HSNCode::class, 'default_gst_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('effective_from', '<=', $today)
                     ->where(function($q) use ($today) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', $today);
                     });
    }

    // Helper method to get total tax rate
    public function getTotalTaxRate()
    {
        return $this->cgst_rate + $this->sgst_rate + $this->igst_rate + $this->ugst_rate;
    }
}
