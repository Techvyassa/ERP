<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class MIRIssueTransaction extends Model
{
    protected $connection = 'tenant';
    protected $table = 'mir_issue_transactions';
    public $timestamps = false;

    protected $fillable = [
        'mir_line_id',
        'issued_qty',
        'issued_by',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'issued_qty' => 'decimal:4',
        'issued_at' => 'datetime',
    ];

    public function mirLine()
    {
        return $this->belongsTo(MIRLineItem::class, 'mir_line_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
