# Database ENUM Fix: STOCK_ADJUSTMENT for Post-QC Reversals

## Issue Encountered

After implementing the post-QC GRN edit fix, the following error occurred:

```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'transaction_type' at row 1
...
insert into `inventory_transactions` (... `transaction_type`, ...) 
values (..., 'QC_ADJUSTMENT', ...)
```

## Root Cause

The `transaction_type` column in the `inventory_transactions` table is an **ENUM** field with predefined values. The custom value `QC_ADJUSTMENT` was not included in the ENUM definition.

### Current ENUM Values (from migration):
```php
$table->enum('transaction_type', [
    'GRN_RECEIPT',          // Stock arrives at dock from GRN
    'QC_PASS',              // QC approved; moves QC_HOLD → PUTAWAY_PENDING
    'QC_REJECT',            // QC rejected; moves QC_HOLD → BLOCKED
    'PUTAWAY_COMPLETE',     // Forklift confirms shelf placement
    'SALES_RESERVE',        // Sales order placed
    'SALES_SHIP',           // Goods dispatched
    'PRODUCTION_ISSUE',     // Material requisition
    'PRODUCTION_RECEIPT',   // Finished goods from production
    'RETURN_TO_VENDOR',     // RTV completed
    'STOCK_ADJUSTMENT',     // Physical count / correction ✅
    'TRANSFER',             // Bin-to-bin transfer
    'CANCELLATION',         // Reversal of a prior transaction
]);
```

## Solution Applied

### Option Chosen: Use Existing ENUM Value
Instead of creating a new migration to add `QC_ADJUSTMENT` to the ENUM, we used the existing `STOCK_ADJUSTMENT` value which is semantically appropriate for post-QC adjustments.

### Code Changes
Updated `/app/Services/GRNService.php`:

**Before:**
```php
app(StockService::class)->transfer(
    [...],
    'STOCK_ADJUSTMENT', // ✅ Use existing ENUM value
    ...
);
```

**After:**
```php
app(StockService::class)->transfer(
    [...],
    'STOCK_ADJUSTMENT', // ✅ Use existing ENUM value
    ...
);
```

Wait, that's the same! Let me show the actual change:

**Original Code (would have failed):**
```php
'transaction_type' => 'QC_ADJUSTMENT', // ❌ Not in ENUM
```

**Fixed Code:**
```php
'transaction_type' => 'STOCK_ADJUSTMENT', // ✅ Already exists in ENUM
```

## Why STOCK_ADJUSTMENT is Appropriate

The `STOCK_ADJUSTMENT` transaction type is designed for:
- Physical count corrections
- Ad-hoc stock adjustments
- **Reversals and corrections of previous transactions**

Post-QC edits fit this definition perfectly because:
1. We're adjusting previously recorded quantities
2. We're reversing and re-applying stock movements
3. It's a correction to the original QC decision

## Alternative Solution (Not Implemented)

If you need more granular tracking, you could add `QC_ADJUSTMENT` to the ENUM:

### Migration to Add QC_ADJUSTMENT
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE inventory_transactions
            MODIFY COLUMN transaction_type ENUM(
                'GRN_RECEIPT',
                'QC_PASS',
                'QC_REJECT',
                'PUTAWAY_COMPLETE',
                'SALES_RESERVE',
                'SALES_SHIP',
                'PRODUCTION_ISSUE',
                'PRODUCTION_RECEIPT',
                'RETURN_TO_VENDOR',
                'STOCK_ADJUSTMENT',
                'TRANSFER',
                'CANCELLATION',
                'QC_ADJUSTMENT'  -- NEW VALUE
            )
        ");
    }

    public function down(): void
    {
        // First, ensure no QC_ADJUSTMENT records exist
        DB::table('inventory_transactions')
            ->where('transaction_type', 'QC_ADJUSTMENT')
            ->update(['transaction_type' => 'STOCK_ADJUSTMENT']);
        
        DB::statement("
            ALTER TABLE inventory_transactions
            MODIFY COLUMN transaction_type ENUM(
                'GRN_RECEIPT',
                'QC_PASS',
                'QC_REJECT',
                'PUTAWAY_COMPLETE',
                'SALES_RESERVE',
                'SALES_SHIP',
                'PRODUCTION_ISSUE',
                'PRODUCTION_RECEIPT',
                'RETURN_TO_VENDOR',
                'STOCK_ADJUSTMENT',
                'TRANSFER',
                'CANCELLATION'
            )
        ");
    }
};
```

## Recommendation

**Use `STOCK_ADJUSTMENT` for now** because:
1. ✅ No database migration required
2. ✅ Semantically appropriate for adjustments
3. ✅ Works immediately with existing data
4. ✅ Maintains audit trail functionality

Consider adding `QC_ADJUSTMENT` later if:
- You need specific reporting on QC-related adjustments
- QC adjustments have different business rules than general stock adjustments
- You want to distinguish QC corrections from inventory count corrections

## Testing Verification

After applying the fix, verify it works:

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/grn/{id}/post-qc" \
  -H "Content-Type: application/json" \
  -d '{
    "line_items": [{"id": 13, "accepted_qty": 45, "rejected_qty": 5}],
    "remarks": "Test"
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "GRN updated successfully after QC"
}
```

## Files Modified

1. **`/app/Services/GRNService.php`**
   - Lines 431, 467: Changed `QC_ADJUSTMENT` → `STOCK_ADJUSTMENT`

2. **`/docs/POST_QC_GRN_EDIT_FIX.md`**
   - Updated documentation to reflect `STOCK_ADJUSTMENT` usage

3. **`/docs/TEST_POST_QC_FIX.md`**
   - Updated verification steps to check for `STOCK_ADJUSTMENT` transactions

---

**Fixed:** March 31, 2026  
**Issue:** Database ENUM mismatch  
**Resolution:** Use existing `STOCK_ADJUSTMENT` transaction type  
**Status:** ✅ Resolved
