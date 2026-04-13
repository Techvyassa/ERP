<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class FGReceipt extends Model
{
    protected $connection = 'tenant';
    protected $table = 'fg_receipts';

    protected $fillable = [
        'batch_run_id',
        'product_id',
        'planned_qty',
        'received_qty',
        'rejected_qty',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'rejected_qty' => 'decimal:3',
    ];

    public function batchRun()
    {
        return $this->belongsTo(ProductionBatchRun::class, 'batch_run_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
