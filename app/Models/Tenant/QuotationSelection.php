<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationSelection extends Model
{
    protected $connection = 'tenant';
    protected $table = 'quotation_selections';

    protected $fillable = [
        'pr_number',
        'vendor_id',
        'quotation_id',
        'selected_price',
        'selected_delivery_date',
        'selection_reason',
        'status',
        'selected_by',
        'selected_at',
    ];

    protected $casts = [
        'selected_price' => 'decimal:2',
        'selected_delivery_date' => 'date',
        'selected_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(VendorQuotation::class, 'quotation_id');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }
}
