<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ApprovalMatrix extends Model
{
    protected $connection = 'tenant';

    protected $table = 'approval_matrix_master';
    public $timestamps = false;

    protected $fillable = [
        'document_type',
        'level',
        'min_amount',
        'max_amount',
        'approver_role_id',
        'sla_hours',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'sla_hours' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2'
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('document_type', $module);
    }

    public function scopeByTransactionType($query, $type)
    {
        return $query->where('document_type', $type);
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
