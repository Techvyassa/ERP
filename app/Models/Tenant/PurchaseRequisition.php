<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'purchase_requisitions';

    protected $fillable = [
        'pr_number',
        'requested_by',
        'department_id',
        'cost_center_code',
        'pr_date',
        'required_date',
        'priority',
        'budget_code',
        'suggested_vendor_id',
        'status',
        'justification',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'pr_date'      => 'date',
        'required_date' => 'date',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function suggestedVendor()
    {
        return $this->belongsTo(Vendor::class, 'suggested_vendor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems()
    {
        return $this->hasMany(PrLineItem::class, 'pr_id')->orderBy('line_number');
    }

    /**
     * Generate next PR number: PR-YYMM-XXXXX
     */
    public static function generatePrNumber(): string
    {
        $prefix = 'PR-' . date('ym') . '-';
        $last = static::withTrashed()
            ->where('pr_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('pr_number');

        $next = $last ? (int) substr($last, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
