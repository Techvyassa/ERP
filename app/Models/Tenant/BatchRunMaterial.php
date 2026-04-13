<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BatchRunMaterial extends Model
{
    protected $connection = 'tenant';
    protected $table = 'batch_run_materials';

    protected $fillable = [
        'batch_run_id',
        'material_id',
        'required_qty',
        'issued_qty',
        'actual_consumed_qty',
    ];

    protected $casts = [
        'required_qty' => 'decimal:4',
        'issued_qty' => 'decimal:4',
        'actual_consumed_qty' => 'decimal:4',
    ];

    public function batchRun()
    {
        return $this->belongsTo(ProductionBatchRun::class, 'batch_run_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
