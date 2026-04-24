<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionGapAnalysis extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_gap_analysis';

    protected $fillable = [
        'product_id',
        'analysis_date',
        'demand_qty',
        'available_stock',
        'planned_production_qty',
        'gap_qty',
        'gap_status',
        'capacity_utilization',
        'recommendations',
        'created_by',
    ];

    protected $casts = [
        'analysis_date'          => 'date',
        'demand_qty'             => 'decimal:3',
        'available_stock'        => 'decimal:3',
        'planned_production_qty' => 'decimal:3',
        'gap_qty'                => 'decimal:3',
        'capacity_utilization'   => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine gap status based on gap quantity
     */
    public static function determineGapStatus(float $gapQty, float $demandQty): string
    {
        if ($demandQty == 0) {
            return 'BALANCED';
        }

        $gapPercentage = ($gapQty / $demandQty) * 100;

        if ($gapPercentage < -20) {
            return 'CRITICAL'; // More than 20% shortage
        } elseif ($gapPercentage < 0) {
            return 'SHORTAGE'; // Any shortage
        } elseif ($gapPercentage > 20) {
            return 'SURPLUS'; // More than 20% surplus
        } else {
            return 'BALANCED'; // Within acceptable range
        }
    }
}
