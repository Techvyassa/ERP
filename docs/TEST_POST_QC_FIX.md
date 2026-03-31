# Quick Test Guide: Post-QC GRN Edit Fix

## Prerequisites
- Laravel application running
- Tenant database with at least one GRN that has completed QC
- User with appropriate permissions (Store/QC department)

## Test Scenario 1: API Test (Recommended)

### Step 1: Find a Post-QC GRN
```sql
-- Run in tenant database
SELECT id, grn_number, status 
FROM grn_headers 
WHERE status IN ('PUTAWAY_IN_PROGRESS', 'ACCEPTED', 'REJECTED', 'PARTIALLY_ACCEPTED')
ORDER BY created_at DESC 
LIMIT 1;
```

### Step 2: Get Line Items
```sql
SELECT id, material_id, accepted_qty, rejected_qty, stock_status
FROM grn_line_items
WHERE grn_id = {GRN_ID_FROM_STEP_1};
```

### Step 3: Make API Request
```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/grn/{GRN_ID}/post-qc" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {YOUR_TOKEN}" \
  -d '{
    "line_items": [
      {
        "id": {LINE_ITEM_ID},
        "accepted_qty": 45,
        "rejected_qty": 5,
        "return_qty": 0,
        "return_remarks": ""
      }
    ],
    "remarks": "Testing post-QC edit fix"
  }'
```

### Expected Response (Success)
```json
{
  "success": true,
  "message": "GRN updated successfully after QC",
  "data": {
    "id": 17,
    "grn_number": "GRN/25-26/0017",
    "status": "PARTIALLY_ACCEPTED",
    "line_items": [...]
  }
}
```

## Test Scenario 2: Using Test Script

### Run the Test
```bash
cd c:\xampp\htdocs\ERP\ERP
php artisan tinker --execute=test_post_qc_fix.php
```

### Expected Output
```
=== Testing Post-QC GRN Edit Fix ===

Found GRN: GRN/25-26/0017 (ID: 17)
Status: PARTIALLY_ACCEPTED

Line Item ID: 13
  - Material ID: 5
  - Accepted Qty: 50.00
  - Rejected Qty: 0.00
  - Stock Status: PUTAWAY_PENDING

Testing post-QC edit...
  Line 13: Changing from (50.00, 0.00) to (45.00, 5.00)

Applying QC decision update...

✅ SUCCESS! GRN updated successfully.
New Status: PARTIALLY_ACCEPTED

Updated Line Items:
  Line Item ID: 13
    - Accepted Qty: 45.00
    - Rejected Qty: 5.00
    - Stock Status: QC_HOLD

=== Test Completed Successfully ===
```

## Test Scenario 3: Frontend Test (If UI exists)

### Steps:
1. Navigate to GRN list page
2. Find a GRN with status "ACCEPTED", "REJECTED", or "PARTIALLY_ACCEPTED"
3. Click the "Update after QC" button (edit_note icon)
4. Modify quantities in the modal
5. Click "Save"
6. Verify success message and updated data

## Verification Checks

### Database Verification
After successful test, verify stock movements:

```sql
-- Check inventory transactions for the GRN
SELECT 
    transaction_type,
    bucket,
    qty_change,
    remarks,
    created_at
FROM inventory_transactions
WHERE reference_type = 'GRN' 
  AND reference_id = {GRN_ID}
ORDER BY created_at DESC;
```

You should see:
1. `STOCK_ADJUSTMENT` transactions (reversals)
2. New `QC_PASS` and/or `QC_REJECT` transactions

### Stock Balance Verification
```sql
-- Check stock balances
SELECT 
    bucket,
    SUM(qty_on_hand) as total_qty
FROM stock_balances
WHERE material_id = {MATERIAL_ID}
  AND batch_number = '{BATCH_NUMBER}'
  AND warehouse_id = {WAREHOUSE_ID}
GROUP BY bucket;
```

## Common Issues & Solutions

### Issue 1: "GRN cannot be edited in current status"
**Solution:** Ensure GRN status is one of:
- `PUTAWAY_IN_PROGRESS`
- `ACCEPTED`
- `REJECTED`
- `PARTIALLY_ACCEPTED`

### Issue 2: "Failed to reverse previous QC acceptance"
**Possible Causes:**
- Insufficient stock in PUTAWAY_PENDING bucket
- Stock was moved by putaway task
- Batch number mismatch

**Solution:** Check stock levels and ensure no putaway tasks have completed:
```sql
SELECT status, quantity 
FROM putaway_tasks 
WHERE grn_line_id = {LINE_ITEM_ID};
```

### Issue 3: "Warehouse could not be resolved"
**Solution:** Ensure warehouse is set in:
- Warehouse bin master, OR
- Material master default warehouse

## Success Criteria

✅ All tests complete without 500 errors  
✅ Stock movements are correctly reversed and reapplied  
✅ GRN status updates appropriately  
✅ Audit trail shows QC_ADJUSTMENT transactions  
✅ Updated quantities match request payload  

## Rollback Procedure (If Needed)

If issues occur, revert the code changes:

```bash
git checkout HEAD -- app/Services/GRNService.php
```

Then restore from backup if data was affected.

---

**Test Date:** March 31, 2026  
**Test Status:** ⏳ Pending  
**Tested By:** ___________
