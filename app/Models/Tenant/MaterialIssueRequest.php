<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MaterialIssueRequest extends Model
{
    protected $connection = 'tenant';
    protected $table = 'material_issue_requests';

    protected $fillable = [
        'mir_no', 'production_order_id', 'status', 'remarks', 'rejection_reason', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function lines()
    {
        return $this->hasMany(MIRLineItem::class, 'mir_id');
    }
}
