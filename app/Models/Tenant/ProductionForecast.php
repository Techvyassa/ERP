<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProductionForecast extends Model
{
    protected $connection = 'tenant';
    protected $table = 'production_forecasts';

    protected $fillable = [
        'product_id',
        'forecast_date',
        'forecast_month',
        'forecasted_qty',
        'actual_demand_qty',
        'previous_month_sales',
        'growth_percentage',
        'calculation_formula',
        'current_stock',
        'planned_production',
        'source',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'forecast_date'        => 'date',
        'forecasted_qty'       => 'decimal:3',
        'actual_demand_qty'    => 'decimal:3',
        'previous_month_sales' => 'decimal:3',
        'growth_percentage'    => 'decimal:2',
        'current_stock'        => 'decimal:3',
        'planned_production'   => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
