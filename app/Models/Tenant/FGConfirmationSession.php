<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class FGConfirmationSession extends Model
{
    protected $connection = 'tenant';
    protected $table = 'fg_confirmation_sessions';

    protected $fillable = [
        'production_order_id',
        'confirmed_qty',
        'rejected_qty',
        'rejection_reason_code',
        'rejection_reason_note',
        'fg_batch_number',
        'fg_warehouse_id',
        'fg_bin_id',
        'completion_status',
        'confirmed_by',
    ];

    protected $casts = [
        'confirmed_qty' => 'decimal:3',
        'rejected_qty'  => 'decimal:3',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
