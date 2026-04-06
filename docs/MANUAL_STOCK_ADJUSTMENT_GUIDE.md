# Manual Stock Adjustment Guide

## Problem Fixed ✅

**Issue:** The stock adjustment API was trying to query `material_master` from the **control database** (`ERP_saas_control`) instead of the **tenant database**.

**Error Message:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'ERP_saas_control.material_master' doesn't exist
```

**Root Cause:**
- Laravel's validation rule `exists:material_master,id` uses the default connection (control DB)
- Multi-tenant systems require explicit tenant connection for tenant-specific tables

**Solution Applied:**
- Removed `exists:` validation rules that use default connection
- Added manual validation using `DB::connection('tenant')` 
- All lookups now explicitly use the tenant database connection

---

## How to Add Manual Stock for Testing

### Method 1: API Endpoint (Recommended for Automation) ✅

**Endpoint:** `POST /api/v1/stock/adjust`

**Headers:**
```
Content-Type: application/json
Cookie: auth_token=YOUR_JWT_TOKEN; refresh_token=YOUR_REFRESH_TOKEN
```

**Request Body:**
```json
{
    "material_id": 3,
    "warehouse_id": 1,
    "qty": 500,
    "type": "add",
    "batch_number": "BATCH-JEERA-001",
    "reason": "Testing stock adjustment"
}
```

**cURL Example:**
```bash
curl --location 'http://127.0.0.1:8000/api/v1/stock/adjust' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; refresh_token=c9b8908df2df17a0cc4df1ff162dcf159e620447490b51204b4aa174a0a8409e' \
--data '{
    "material_id": 3,
    "warehouse_id": 1,
    "qty": 500,
    "type": "add",
    "batch_number": "BATCH-JEERA-001",
    "reason": "Testing stock adjustment"
}'
```

**Success Response:**
```json
{
    "success": true,
    "message": "Stock adjusted successfully",
    "data": {
        "transaction_id": 1,
        "material_id": 3,
        "qty_change": 500,
        "bucket": "AVAILABLE"
    }
}
```

**Validation:**
- `material_id`: Required, must be ≥ 1
- `warehouse_id`: Required, must be ≥ 1
- `bin_id`: Optional, must be ≥ 1 if provided
- `qty`: Required, must be ≥ 0.001
- `type`: Required, either "add" or "subtract"
- `batch_number`: Optional, max 50 characters
- `reason`: Optional, max 255 characters

---

### Method 2: PHP CLI Script (For Bulk Testing)

**File:** `scripts/add_test_stock.php`

**Configuration:**
Edit the CONFIGURATION section in the script:

```php
$materialId = 3; // RM-0001 - Cumin Seeds (Jeera)
$warehouseId = 1; // Your default warehouse
$binId = null; // NULL for warehouse-level
$uomId = 1; // From material_master.uom_id
$qtyToAdd = 500.0; // Quantity to add
$batchNumber = 'BATCH-JEERA-' . date('Ymd');
$userId = 1; // Admin user ID
$unitCost = 350.0; // Standard cost from material_master
$action = 'add'; // 'add' or 'subtract'
$reason = 'Manual stock addition for testing';
```

**Run the Script:**
```bash
cd c:\xampp\htdocs\ERP\ERP
php scripts/add_test_stock.php
```

**Expected Output:**
```
==============================================
MANUAL STOCK ADJUSTMENT
==============================================
Material ID: 3
Warehouse ID: 1
Bin ID: NULL (warehouse-level)
Quantity: 500 (ADD)
Batch Number: BATCH-JEERA-20260403
Unit Cost: ₹350
Reason: Manual stock addition for testing
==============================================

✓ Material found: Cumin Seeds (Jeera) (RM-0001)
✓ Warehouse found: Raw Material Warehouse

Processing stock adjustment...

✅ SUCCESS!

Transaction Details:
-------------------
Transaction ID: 1
Material ID: 3
Batch Number: BATCH-JEERA-20260403
Bucket: AVAILABLE
Qty Change: 500
Transaction Type: STOCK_ADJUSTMENT
Reference: MANUAL/20260403120000
Remarks: Manual stock addition for testing

Updated Stock Balance:
---------------------
Material ID: 3
Batch Number: BATCH-JEERA-20260403
Warehouse ID: 1
Bucket: AVAILABLE
Qty On Hand: 500
Total Value: ₹175000
Last Updated: 2026-04-03 12:00:00

==============================================
Stock adjustment completed successfully!
==============================================
```

---

### Method 3: Direct SQL (Quickest for One-Time Setup)

**For Cumin Seeds (RM-0001, ID: 3):**
```sql
-- Step 1: Insert inventory transaction
INSERT INTO inventory_transactions (
    material_id, product_id, batch_number, bucket, qty_change, uom_id, 
    warehouse_id, bin_id, transaction_type, reference_type, reference_id, 
    reference_number, unit_cost, total_cost, created_by, remarks, 
    created_at, updated_at
) VALUES (
    3, NULL, 'BATCH-JEERA-001', 'AVAILABLE', 500.0, 1, 
    1, NULL, 'STOCK_ADJUSTMENT', 'ManualTest', 999, 
    'MANUAL/20260403120000', 350.0, 175000.0, 1, 
    'Manual stock addition for testing - Cumin Seeds',
    NOW(), NOW()
);

-- Step 2: Update stock balance (upsert)
INSERT INTO stock_balances (
    material_id, product_id, batch_number, uom_id, warehouse_id, bin_id,
    bucket, qty_on_hand, total_value, created_at, updated_at
) VALUES (
    3, NULL, 'BATCH-JEERA-001', 1, 1, NULL,
    'AVAILABLE', 500.0, 175000.0, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE 
    qty_on_hand = qty_on_hand + 500.0,
    total_value = total_value + 175000.0,
    updated_at = NOW();
```

**For Black Pepper (RM-0002, ID: 8):**
```sql
INSERT INTO inventory_transactions (...) VALUES (
    8, NULL, 'BATCH-PEPPER-001', 'AVAILABLE', 300.0, 1, 
    1, NULL, 'STOCK_ADJUSTMENT', 'ManualTest', 999, 
    'MANUAL/20260403120001', 650.0, 195000.0, 1, 
    'Manual stock addition for testing - Black Pepper',
    NOW(), NOW()
);
```

---

## Code Changes Made

### File: `app/Http/Controllers/StockController.php`

**Changes:**
1. Added `use Illuminate\Support\Facades\DB;` import
2. Replaced `exists:` validation with manual tenant DB lookups
3. Added explicit validation for material, warehouse, and bin
4. Improved error messages to indicate tenant database context

**Before:**
```php
$request->validate([
    'material_id' => 'required|integer|exists:material_master,id',
    'warehouse_id' => 'required|integer|exists:warehouse_master,id',
    'bin_id' => 'nullable|integer|exists:bin_locations,id',
    // ...
]);
```

**After:**
```php
// Validate required fields (basic validation)
$validated = $request->validate([
    'material_id' => 'required|integer|min:1',
    'warehouse_id' => 'required|integer|min:1',
    'bin_id' => 'nullable|integer|min:1',
    // ...
]);

// Get material details from TENANT database for UOM and cost
$material = DB::connection('tenant')
    ->table('material_master')
    ->where('id', $materialId)
    ->first();

if (!$material) {
    return response()->json([
        'success' => false,
        'message' => 'Material not found in tenant database',
    ], 404);
}

// Validate warehouse exists in tenant database
$warehouse = DB::connection('tenant')
    ->table('warehouse_master')
    ->where('id', $warehouseId)
    ->first();

if (!$warehouse) {
    return response()->json([
        'success' => false,
        'message' => 'Warehouse not found in tenant database',
    ], 404);
}
```

---

## Testing Checklist

### Pre-requisites:
- [ ] Tenant organization is active (e.g., `an-tech-solutions-pvt-ltd`)
- [ ] Materials exist in tenant database (`material_master`)
- [ ] Warehouses exist in tenant database (`warehouse_master`)
- [ ] User has valid JWT token

### Test Cases:

#### ✅ Test 1: Add Stock via API
```bash
curl -X POST http://127.0.0.1:8000/api/v1/stock/adjust \
  -H "Content-Type: application/json" \
  -H "Cookie: auth_token=YOUR_TOKEN" \
  -d '{
    "material_id": 3,
    "warehouse_id": 1,
    "qty": 500,
    "type": "add",
    "batch_number": "BATCH-TEST-001",
    "reason": "API test"
  }'
```
**Expected:** Success response with transaction_id

#### ✅ Test 2: Verify Stock Balance
```bash
curl http://127.0.0.1:8000/api/v1/stock/snapshot/3?warehouse_id=1 \
  -H "Cookie: auth_token=YOUR_TOKEN"
```
**Expected:** Shows AVAILABLE bucket with 500 qty

#### ✅ Test 3: Subtract Stock via API
```bash
curl -X POST http://127.0.0.1:8000/api/v1/stock/adjust \
  -H "Content-Type: application/json" \
  -H "Cookie: auth_token=YOUR_TOKEN" \
  -d '{
    "material_id": 3,
    "warehouse_id": 1,
    "qty": 100,
    "type": "subtract",
    "reason": "Testing subtraction"
  }'
```
**Expected:** AVAILABLE bucket now shows 400 qty

#### ✅ Test 4: Invalid Material ID
```bash
curl -X POST http://127.0.0.1:8000/api/v1/stock/adjust \
  -H "Content-Type: application/json" \
  -H "Cookie: auth_token=YOUR_TOKEN" \
  -d '{
    "material_id": 999,
    "warehouse_id": 1,
    "qty": 100,
    "type": "add"
  }'
```
**Expected:** 404 response - "Material not found in tenant database"

#### ✅ Test 5: Run CLI Script
```bash
php scripts/add_test_stock.php
```
**Expected:** Success message with transaction details

---

## Database Tables Involved

### 1. `inventory_transactions` (Ledger)
Immutable record of every stock movement.

**Key Columns:**
- `material_id`: Links to `material_master.id`
- `bucket`: Stock state (QC_HOLD, AVAILABLE, etc.)
- `qty_change`: Positive = inflow, Negative = outflow
- `transaction_type`: Business reason (STOCK_ADJUSTMENT)
- `reference_type`: Polymorphic source (ManualAdjustment)
- `reference_id`: Source document PK
- `reference_number`: Human-readable reference
- `unit_cost`, `total_cost`: Valuation
- `created_by`: User who triggered it

### 2. `stock_balances` (Read Cache)
Cached summary for fast queries. Updated automatically by StockService.

**Key Columns:**
- `material_id`, `batch_number`, `warehouse_id`, `bin_id`: Unique key
- `bucket`: Stock state
- `qty_on_hand`: Current quantity
- `total_value`: Total valuation (qty × cost)

---

## Stock Flow Reference

### Complete Lifecycle:
```
GRN_RECEIPT → QC_PASS → PUTAWAY_COMPLETE → AVAILABLE
   ↓            ↓            ↓              ↓
QC_HOLD   PUTAWAY_PENDING  AVAILABLE    Ready for Use
```

### Manual Adjustment:
```
STOCK_ADJUSTMENT → AVAILABLE (direct)
```

### Transaction Types ENUM:
- `GRN_RECEIPT`: Stock arrives at dock
- `QC_PASS`: QC approved
- `QC_REJECT`: QC rejected
- `PUTAWAY_COMPLETE`: Shelf placement confirmed
- `SALES_RESERVE`: Committed to sales order
- `SALES_SHIP`: Dispatched
- `PRODUCTION_ISSUE`: Issued to production
- `PRODUCTION_RECEIPT`: Finished goods received
- `RETURN_TO_VENDOR`: RTV completed
- `STOCK_ADJUSTMENT`: Manual correction ✅
- `TRANSFER`: Bin-to-bin transfer
- `CANCELLATION`: Reversal of prior transaction

---

## Troubleshooting

### Error: "Material not found in tenant database"
**Cause:** Material exists in control DB but not tenant DB  
**Fix:** Check which database connection you're using. API should use tenant connection.

### Error: "Warehouse not found"
**Cause:** Invalid warehouse_id  
**Fix:** Verify warehouse exists: `SELECT * FROM warehouse_master WHERE id = 1;`

### Error: Validation failed
**Cause:** Missing required field or invalid format  
**Fix:** Check all required fields are present and numeric

### Error: "Tenant context required"
**Cause:** Missing JWT token or org_slug  
**Fix:** Ensure valid auth_token cookie is sent with request

---

## Related Files

- **Controller:** `app/Http/Controllers/StockController.php`
- **Service:** `app/Services/StockService.php`
- **Route:** `routes/api.php` (line 508)
- **Models:** `app/Models/Tenant/InventoryTransaction.php`, `app/Models/Tenant/StockBalance.php`
- **Script:** `scripts/add_test_stock.php`

---

## Summary

✅ **Fixed:** Multi-tenant database connection issue  
✅ **Added:** API endpoint for manual stock adjustment  
✅ **Created:** CLI script for bulk testing  
✅ **Documented:** Complete guide with examples  

You can now add stock manually for testing without going through the full GRN → QC → Putaway flow!
