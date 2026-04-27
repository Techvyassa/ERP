# Bug Fix: Stock Query in Production Planning

## Issue
The Production Planning module was failing with error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'erp_*.stock_ledger' doesn't exist
```

## Root Cause
The `ProductionPlanningController` was directly querying a non-existent `stock_ledger` table instead of using the proper stock management system.

## Solution
Updated the controller to use `StockQueryService` which properly queries the `stock_balances` table with bucket-based inventory management.

## Changes Made

### 1. Added Dependency Injection
```php
public function __construct(protected StockQueryService $stockQueryService)
{
}
```

### 2. Updated Stock Queries

**Before (Incorrect):**
```php
$currentStock = DB::connection('tenant')
    ->table('stock_ledger')
    ->where('item_id', $productId)
    ->where('item_type', 'Product')
    ->sum('available_qty');
```

**After (Correct):**
```php
$currentStock = $this->stockQueryService->getAvailableProductStock($productId);
```

## Why This Works

The `StockQueryService` provides:
1. **Proper Table Access**: Queries `stock_balances` table which actually exists
2. **Bucket Management**: Handles inventory buckets (AVAILABLE, QC_HOLD, RESERVED, etc.)
3. **Accurate Calculations**: Uses formula `SUM(qty_on_hand - qty_reserved) WHERE bucket = 'AVAILABLE'`
4. **Consistency**: Same method used throughout the application

## Testing
After this fix:
- ✅ Forecast generation works correctly
- ✅ Gap analysis runs successfully
- ✅ Stock data is accurately retrieved
- ✅ No database errors

## Files Modified
- `app/Http/Controllers/ProductionPlanningController.php`
- `PRODUCTION_PLANNING_IMPLEMENTATION.md` (documentation updated)

## Related Services
- `StockQueryService` - Standardized stock query API
- `StockService` - Stock balance management
- `StockBalance` model - Represents stock_balances table

## Stock Balance Table Structure
```
stock_balances:
- material_id / product_id
- warehouse_id
- bin_id
- bucket (AVAILABLE, QC_HOLD, PUTAWAY_PENDING, RESERVED, BLOCKED)
- qty_on_hand
- qty_reserved
- available_qty (computed: qty_on_hand - qty_reserved)
```

## Best Practices
Always use `StockQueryService` for stock queries:
- `getAvailableProductStock()` - For finished goods
- `getAvailableStock()` - For raw materials
- `getFullStockSnapshot()` - For detailed breakdown
- `getGlobalStockSummary()` - For all items across warehouses

Never directly query `stock_balances` or any non-existent `stock_ledger` table.
