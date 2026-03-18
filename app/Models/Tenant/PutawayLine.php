<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PutawayLine extends Model
{
    protected $connection = 'tenant';
    protected $table = 'putaway_lines';

    protected $fillable = [
        'putaway_task_id',
        'line_number',
        'batch_number',
        'quantity',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the putaway task
     */
    public function putawayTask()
    {
        return $this->belongsTo(PutawayTask::class, 'putaway_task_id');
    }
}
