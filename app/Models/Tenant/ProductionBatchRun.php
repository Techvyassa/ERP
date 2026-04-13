<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchRun extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_batch_runs';

    protected $fillable = [
        'production_order_id',
        'run_number',
        'run_qty',
        'planned_date',
        'status',
    ];

    protected $casts = [
        'run_qty' => 'decimal:3',
        'planned_date' => 'date',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function materials()
    {
        return $this->hasMany(BatchRunMaterial::class, 'batch_run_id');
    }

    public function fgReceipt()
    {
        return $this->hasOne(FGReceipt::class, 'batch_run_id');
    }
}
