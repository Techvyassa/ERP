<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalMatrix extends Model
{
    use SoftDeletes;

    protected $table = 'approval_matrix_master';

    protected $fillable = [
        'module_name',
        'transaction_type',
        'approval_level',
        'role_id',
        'min_amount',
        'max_amount',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_level' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2'
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module_name', $module);
    }

    public function scopeByTransactionType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeForAmount($query, $amount)
    {
        return $query->where('min_amount', '<=', $amount)
                     ->where(function($q) use ($amount) {
                         $q->where('max_amount', '>=', $amount)
                           ->orWhereNull('max_amount');
                     });
    }
}
