# Production Planning Module - Implementation Guide

## Overview
Added a comprehensive Production Planning section to the Production Dashboard with Forecast and Gap Analysis capabilities.

## What Was Implemented

### 1. Database Tables (Migration: `2026_04_24_000001_create_production_planning_tables.php`)

#### `production_forecasts`
Stores demand forecasting data based on sales orders and historical patterns.
- **Fields:**
  - `product_id` - Product being forecasted
  - `forecast_date` - Date for the forecast
  - `forecasted_qty` - Predicted demand quantity
  - `actual_demand_qty` - Actual demand from confirmed sales orders
  - `current_stock` - Current stock level
  - `planned_production` - Planned production quantity
  - `source` - Source of forecast (MANUAL, SALES_ORDER, HISTORICAL, SYSTEM)
  - `remarks` - Additional notes

#### `production_gap_analysis`
Analyzes the gap between demand and available capacity/stock.
- **Fields:**
  - `product_id` - Product being analyzed
  - `analysis_date` - Date of analysis
  - `demand_qty` - Total demand (from SO + forecast)
  - `available_stock` - Current available stock
  - `planned_production_qty` - Planned production from orders
  - `gap_qty` - Gap quantity (negative = shortage, positive = surplus)
  - `gap_status` - Status (SURPLUS, BALANCED, SHORTAGE, CRITICAL)
  - `capacity_utilization` - Percentage of capacity used
  - `recommendations` - System-generated recommendations

#### `production_capacity`
Tracks production capacity planning (for future enhancement).
- **Fields:**
  - `product_id` - Product (null = overall capacity)
  - `capacity_date` - Date for capacity
  - `daily_capacity` - Maximum daily production capacity
  - `utilized_capacity` - Currently utilized capacity
  - `available_capacity` - Remaining available capacity
  - `shift` - Shift type (SINGLE, DOUBLE, TRIPLE)

### 2. Models Created

- **`ProductionForecast`** - `app/Models/Tenant/ProductionForecast.php`
- **`ProductionGapAnalysis`** - `app/Models/Tenant/ProductionGapAnalysis.php`
- **`ProductionCapacity`** - `app/Models/Tenant/ProductionCapacity.php`

All models include proper relationships with Product and User models.

### 3. Controller (`ProductionPlanningController.php`)

**Dependencies:**
- Injects `StockQueryService` for proper stock data retrieval
- Uses bucket-based inventory system (AVAILABLE stock only)

#### API Endpoints:

**GET `/api/v1/production-planning/forecast`**
- Get forecast data for a date range
- Parameters: `start_date`, `end_date`

**POST `/api/v1/production-planning/forecast/generate`**
- Generate forecast based on sales orders and historical data
- Parameters: `start_date`, `end_date`, `product_ids` (optional)

**GET `/api/v1/production-planning/gap-analysis`**
- Get gap analysis data for a specific date
- Parameters: `analysis_date`

**POST `/api/v1/production-planning/gap-analysis/run`**
- Run gap analysis for products
- Parameters: `analysis_date`, `product_ids` (optional)

**GET `/api/v1/production-planning/summary`**
- Get planning summary for dashboard
- Returns gap summary and forecast accuracy

### 4. Dashboard UI Updates

Added a new "Production Planning" section to `/production/dashboard` with:

#### Features:
1. **Forecast Summary Card**
   - Shows forecast accuracy percentage
   - Displays 7-day and 30-day forecasts

2. **Gap Analysis Summary**
   - Visual cards showing:
     - Critical items (red)
     - Shortage items (amber)
     - Balanced items (green)
     - Surplus items (blue)

3. **Gap Analysis Details Table**
   - Product-wise breakdown showing:
     - Demand quantity
     - Available stock
     - Planned production
     - Gap quantity (color-coded)
     - Status badge

4. **Action Buttons**
   - "Generate Forecast" - Creates forecast for next 30 days
   - "Run Analysis" - Performs gap analysis for today

### 5. Routes Added (`routes/api.php`)

```php
Route::prefix('production-planning')->group(function () {
    Route::get('/forecast', [ProductionPlanningController::class, 'getForecastData']);
    Route::post('/forecast/generate', [ProductionPlanningController::class, 'generateForecast']);
    Route::get('/gap-analysis', [ProductionPlanningController::class, 'getGapAnalysis']);
    Route::post('/gap-analysis/run', [ProductionPlanningController::class, 'runGapAnalysis']);
    Route::get('/summary', [ProductionPlanningController::class, 'getPlanningSummary']);
});
```

## How It Works

### Forecast Generation Process:
1. Analyzes confirmed sales orders with delivery dates in the forecast period
2. Retrieves current stock levels from stock ledger
3. Checks planned production orders
4. Calculates forecasted demand
5. Stores forecast data for each product

### Gap Analysis Process:
1. Collects demand from sales orders and forecasts
2. Retrieves available stock from stock ledger
3. Sums planned production from production orders
4. Calculates gap: `(Available Stock + Planned Production) - Total Demand`
5. Determines gap status:
   - **CRITICAL**: More than 20% shortage
   - **SHORTAGE**: Any shortage (0-20%)
   - **BALANCED**: Within acceptable range (±20%)
   - **SURPLUS**: More than 20% surplus
6. Generates recommendations based on status

## Data Sources

The system integrates with existing tables:
- **`sales_orders`** & **`sales_order_line_items`** - For demand data
- **`production_orders`** - For planned production
- **`stock_balances`** - For current stock levels (via StockQueryService)
- **`product_master`** - For product information

**Note:** The system uses `StockQueryService` to query stock data, which properly handles the `stock_balances` table with bucket-based inventory management (AVAILABLE, QC_HOLD, RESERVED, etc.).

## Usage Instructions

### For Users:
1. Navigate to `/org/{org_slug}/production/dashboard`
2. View the "Production Planning" section
3. Click "Generate Forecast" to create forecasts for the next 30 days
4. Click "Run Analysis" to perform gap analysis for today
5. Review the gap analysis table to identify:
   - Products with shortages (plan more production)
   - Products with surplus (reduce production)
   - Critical items requiring immediate attention

### For Developers:
1. Migration already run on 5 tenant databases successfully
2. API endpoints are protected by existing authentication middleware
3. Frontend uses Alpine.js for reactivity
4. All calculations are performed server-side for accuracy

## Gap Status Interpretation

| Status | Color | Meaning | Action Required |
|--------|-------|---------|-----------------|
| CRITICAL | Red | >20% shortage | Urgent: Expedite production or find alternatives |
| SHORTAGE | Amber | 0-20% shortage | Plan additional production orders |
| BALANCED | Green | Within ±20% | Continue monitoring |
| SURPLUS | Blue | >20% surplus | Consider reducing production or reallocating capacity |

## Future Enhancements

1. **Historical Trend Analysis**: Use past production data for better forecasting
2. **Machine Learning**: Implement ML-based demand prediction
3. **Capacity Planning**: Utilize the `production_capacity` table for advanced planning
4. **Multi-period Forecasting**: Support weekly/monthly forecasts
5. **Alert System**: Automatic notifications for critical shortages
6. **Export Reports**: PDF/Excel export of gap analysis
7. **What-if Analysis**: Scenario planning tools

## Testing

To test the implementation:

1. **Create Sales Orders** with future delivery dates
2. **Create Production Orders** with planned dates
3. **Generate Forecast**: Click the button on dashboard
4. **Run Gap Analysis**: Click the button on dashboard
5. **Review Results**: Check the gap analysis table

## Migration Status

✅ Successfully migrated on 5 tenant databases:
- Soft-tech-mayuri
- An Tech Solutions Pvt Ltd
- vishu's org pvt ltd
- Techvyassa Third pvt ltd
- Quantum Leap Labs Pvt ltd

⚠️ 3 tenants had pre-existing column conflicts (unrelated to this feature)

## Files Modified/Created

### Created:
- `database/migrations/tenant/2026_04_24_000001_create_production_planning_tables.php`
- `app/Models/Tenant/ProductionForecast.php`
- `app/Models/Tenant/ProductionGapAnalysis.php`
- `app/Models/Tenant/ProductionCapacity.php`
- `app/Http/Controllers/ProductionPlanningController.php`

### Modified:
- `routes/api.php` - Added production planning routes
- `resources/views/tenant/production/dashboard.blade.php` - Added UI section and JavaScript

## API Response Examples

### Gap Analysis Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 5,
      "product_code": "FG-001",
      "product_name": "Widget A",
      "demand_qty": 1000.000,
      "available_stock": 200.000,
      "planned_production_qty": 500.000,
      "gap_qty": -300.000,
      "gap_status": "SHORTAGE",
      "capacity_utilization": 75.00,
      "recommendations": "Shortage of 300 units detected. Plan additional production orders to meet demand."
    }
  ]
}
```

### Planning Summary Response:
```json
{
  "success": true,
  "data": {
    "gap_summary": {
      "critical": 2,
      "shortage": 5,
      "balanced": 10,
      "surplus": 3
    },
    "forecast_accuracy": 87.5
  }
}
```

## Notes

- All calculations use decimal precision (12,3) for quantities
- Dates are stored in DATE format for efficient querying
- Gap analysis can be run for any date (past or future)
- Forecasts support multiple sources for flexibility
- System automatically generates recommendations based on gap status
- Capacity utilization requires capacity data to be configured

## Support

For issues or questions, refer to:
- Controller: `ProductionPlanningController.php`
- Models: `app/Models/Tenant/Production*.php`
- Migration: `database/migrations/tenant/2026_04_24_000001_create_production_planning_tables.php`
