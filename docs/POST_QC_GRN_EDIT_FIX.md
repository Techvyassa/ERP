# Post-QC GRN Edit Bug Fix Summary

## Issue Description

When attempting to edit a GRN after QC decision (post-QC edit), the API returned a 500 Internal Server Error:

```
Failed to update GRN after QC: Cannot post QC decision for GRN line 13: 
QC_HOLD stock is 0 but 50 is required. This GRN likely predates the stock ledger feature 
and needs stock backfill before QC can proceed.
```

**Request Details:**
- **Endpoint:** `PATCH /api/v1/grn/{id}/post-qc`
- **Status:** 500 Internal Server Error
- **Use Case:** After final QC decision, Store/QC departments need to adjust accepted/rejected quantities

## Root Cause Analysis

The issue was in the `GRNService::applyQCDecision()` method which treated **post-QC edits** the same as **initial QC decisions**:

### Problem Flow:
1. **Initial QC Decision:**
   - Stock moves: `QC_HOLD` → `PUTAWAY_PENDING` (accepted) or `BLOCKED` (rejected)
   - Validation checks if `QC_HOLD` has sufficient stock ✅

2. **Post-QC Edit (Second Update):**
   - Stock is already in `PUTAWAY_PENDING` or `BLOCKED` buckets
   - `QC_HOLD` bucket has **zero stock** for this batch
   - Original code still validated against `QC_HOLD` ❌
   - Validation fails with error: "QC_HOLD stock is 0"

## Solution Implemented

### Changes Made to `/app/Services/GRNService.php`

#### 1. **Detect Post-QC Edit Scenario** (Line 399)
```php
$isPostQCEdit = ($lineItem->accepted_qty > 0 || $lineItem->rejected_qty > 0);
```

#### 2. **Conditional Validation** (Lines 401-411)
Only validate `QC_HOLD` stock for initial QC decisions:
```php
if (!$isPostQCEdit && ($acceptedQty > 0 || $rejectedQty > 0)) {
    // Validate QC_HOLD stock exists
    $requiredQcHoldQty = round($acceptedQty + $rejectedQty, 3);
    $qcHoldQty = $this->getMaterialBucketQty($lineItem, $warehouseId, 'QC_HOLD');
    
    if ($qcHoldQty < $requiredQcHoldQty) {
        throw new \Exception(...);
    }
}
```

#### 3. **Reverse Previous Stock Movements** (Lines 413-489)
For post-QC edits, reverse the previous stock movements before applying new ones:

**Step 1: Reverse Previous Accepted Qty**
```php
// PUTAWAY_PENDING → QC_HOLD
if ($oldAcceptedQty > 0) {
    app(StockService::class)->transfer(
        [...],
        'PUTAWAY_PENDING',  // from
        'QC_HOLD',          // to
        $oldAcceptedQty,
        'STOCK_ADJUSTMENT', // Use existing ENUM value
        ...
    );
}
```

**Step 2: Reverse Previous Rejected Qty**
```php
// BLOCKED → QC_HOLD
if ($oldRejectedQty > 0) {
    app(StockService::class)->transfer(
        [...],
        'BLOCKED',         // from
        'QC_HOLD',         // to
        $oldRejectedQty,
        'STOCK_ADJUSTMENT', // Use existing ENUM value
        ...
    );
}
```

#### 4. **Apply New Stock Movements** (Lines 491-541)
After reversal, apply the updated quantities normally:
```php
// QC_HOLD → PUTAWAY_PENDING (new accepted qty)
if ($acceptedQty > 0) {
    app(StockService::class)->transfer(...);
}

// QC_HOLD → BLOCKED (new rejected qty)
if ($rejectedQty > 0) {
    app(StockService::class)->transfer(...);
}
```

## How It Works

### Initial QC Decision Flow:
```
QC_HOLD (100 units)
    ↓ QC_PASS (80 units)
PUTAWAY_PENDING (80 units)
    ↓ QC_REJECT (20 units)
BLOCKED (20 units)
```

### Post-QC Edit Flow (e.g., changing from 80/20 to 75/25):
```
Step 1: Reverse previous movements
PUTAWAY_PENDING (80) → QC_HOLD (80)
BLOCKED (20) → QC_HOLD (20)
Result: QC_HOLD (100), PUTAWAY_PENDING (0), BLOCKED (0)

Step 2: Apply new movements
QC_HOLD (100) → QC_PASS (75) → PUTAWAY_PENDING (75)
QC_HOLD (100) → QC_REJECT (25) → BLOCKED (25)
Result: QC_HOLD (0), PUTAWAY_PENDING (75), BLOCKED (25)
```

## Additional Improvements

### Error Handling (Lines 420-488)
Added try-catch blocks around reversal operations with detailed logging:
```php
try {
    app(StockService::class)->transfer(...);
} catch (\Exception $e) {
    Log::error('[GRNService] Failed to reverse previous QC pass', [
        'grn_line_id' => $lineItem->id,
        'material_id' => $lineItem->material_id,
        'qty' => $oldAcceptedQty,
        'error' => $e->getMessage(),
    ]);
    throw new \Exception(
        "Failed to reverse previous QC acceptance for line {$lineItem->id}: " . $e->getMessage()
    );
}
```

### Model Support
The `GRN::canEdit()` method already supports post-QC editing:
```php
public function canEdit(): bool
{
    return in_array($this->status, [
        'PROVISIONAL', 
        'QC_PENDING', 
        'PUTAWAY_IN_PROGRESS', 
        'ACCEPTED', 
        'REJECTED', 
        'PARTIALLY_ACCEPTED'
    ]);
}
```

## Testing

### Manual Test Steps:
1. Create a GRN and approve it (status → QC_PENDING)
2. Perform initial QC decision via QCService
3. Attempt post-QC edit via API:
   ```bash
   PATCH http://127.0.0.1:8000/api/v1/grn/17/post-qc
   Content-Type: application/json
   
   {
       "line_items": [
           {
               "id": 13,
               "accepted_qty": 45,
               "rejected_qty": 5,
               "return_qty": 0,
               "return_remarks": ""
           }
       ],
       "remarks": "Updated after QC review"
   }
   ```
4. Expected result: ✅ 200 OK with updated GRN data

### Automated Test Script
Run the test script to verify:
```bash
php artisan tinker --execute=test_post_qc_fix.php
```

## Impact

### Fixed Issues:
✅ Post-QC edits no longer fail with "QC_HOLD stock is 0" error  
✅ Stock movements are properly reversed and reapplied  
✅ Audit trail maintained with `STOCK_ADJUSTMENT` transactions  
✅ Error messages provide clear context for debugging  

### Preserved Functionality:
✅ Initial QC decisions continue to work as before  
✅ Stock validation ensures data integrity  
✅ Putaway tasks are updated correctly  
✅ GRN status transitions remain accurate  

## Files Modified

1. **`/app/Services/GRNService.php`**
   - Method: `applyQCDecision()`
   - Lines: 397-541 (major refactor)
   - Changes: Added post-QC edit detection, reversal logic, and enhanced error handling

## Related Documentation

- Memory: "Post-QC GRN Editing Capability"
- Route: `/routes/api.php` line 379
- Controller: `/app/Http/Controllers/GRNController.php` lines 341-399
- Model: `/app/Models/Tenant/GRN.php` lines 125-130

## Next Steps

1. ✅ Test the fix with existing GRN records
2. Monitor logs for any reversal failures
3. Consider adding automated tests for post-QC scenarios
4. Update user documentation to reflect post-QC edit capability

---

**Fixed Date:** March 31, 2026  
**Severity:** High (blocked critical business functionality)  
**Status:** ✅ Resolved
