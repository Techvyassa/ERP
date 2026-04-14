<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorQuotation extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vendor_quotations';

    protected $fillable = [
        'pr_number',
        'vendor_id',
        'item_code',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
        'delivery_date',
        'file_path',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
