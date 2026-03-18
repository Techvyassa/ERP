<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BOMHeader extends Model
{
    use SoftDeletes;

    protected $table = 'bom_header';

    protected $fillable = [
        'bom_code',
        'bom_version',
        'product_id',
        'bom_description',
        'effective_from',
        'effective_to',
        'batch_size',
        'is_active',
        'is_approved',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'batch_size' => 'decimal:2'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bomDetails()
    {
        return $this->hasMany(BOMDetail::class, 'bom_header_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
