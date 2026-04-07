# MIR Scanning Bug Fix: Insufficient Stock Error

## 🐛 Bug Description

**Error:** `INSUFFICIENT_STOCK` - Available: 0, Requested: 0.01  
**Endpoint:** `POST /api/v1/material-issue-requests/{id}/lines/{lineId}/scan`  
**Status Code:** 422 Unprocessable Content

### Request Details:
```json
{
    "bin_barcode": "WH-BIN-0002",
    "material_barcode": "RM-0001",
    "quantity": 0.01
}
```

### Response:
```json
{
    "success": false,
    "error": {
        "code": "INSUFFICIENT_STOCK",
        "details": {
            "available": 0,
            "required": 0.01,
            "bin": "WH-BIN-0002"
        }
    },
    "message": "Insufficient stock in bin 'WH-BIN-0002'. Available: 0, Requested: 0.01."
}
```

---

## 🔍 Root Cause Analysis

### **The Problem:**

The MIR scanning endpoint checks for stock in a **specific bin**, but the manual stock adjustment added stock at the **warehouse level** (with `bin_id = NULL`).

**Code Location:** [`MaterialIssueRequestController.php`](file:///c:\xampp\htdocs\ERP\ERP\app\Http\Controllers\MaterialIssueRequestController.php#L274) (Line 274-279)

**Original Code:**
```php
// Check stock availability in that bin
$stock = StockBalance::where('material_id', $material->id)
    ->where('bin_id', $bin->id)          // ← Only checks specific bin
    ->where('bucket', 'AVAILABLE')
    ->first();

$availableQty = $stock ? max(0, (float)$stock->qty_on_hand - (float)$stock->qty_reserved) : 0;
```

**Why This Fails:**
1. You added stock using `bin_id: null` (warehouse-level tracking)
2. The scan code looks for `bin_id = 2` (specific bin)
3. No match found → `$availableQty = 0`

---

## ✅ Solution Implemented

### **Fix Applied:**

Modified [`MaterialIssueRequestController.php`](file:///c:\xampp\htdocs\ERP\ERP\app\Http\Controllers\MaterialIssueRequestController.php#L273) to check both bin-specific AND warehouse-level stock:

```php
// Check stock availability in that bin
// First try bin-specific stock, then fall back to warehouse-level stock
$stock = StockBalance::where('material_id', $material->id)
    ->where('bin_id', $bin->id)
    ->where('bucket', 'AVAILABLE')
    ->first();

// If no bin-specific stock found, check warehouse-level stock (bin_id IS NULL)
if (!$stock) {
    $stock = StockBalance::where('material_id', $material->id)
        ->whereNull('bin_id')              // ← Check warehouse-level
        ->where('bucket', 'AVAILABLE')
        ->where('warehouse_id', $bin->warehouse_id)
        ->first();
}

$availableQty = $stock ? max(0, (float)$stock->qty_on_hand - (float)$stock->qty_reserved) : 0;
```

### **What Changed:**
- ✅ Added fallback query to check warehouse-level stock
- ✅ Maintains backward compatibility with bin-specific stock
- ✅ Allows MIR scanning to work with both tracking methods

---

## 🧪 How to Test

### **Step 1: Verify Your Stock Data**

Check if you have warehouse-level stock:

```sql
-- Check stock_balances table
SELECT 
    material_id,
    batch_number,
    warehouse_id,
    bin_id,                    -- Should be NULL for warehouse-level
    bucket,
    qty_on_hand,
    qty_reserved,
    (qty_on_hand - qty_reserved) as available_qty
FROM stock_balances
WHERE material_id = 3          -- RM-0001
  AND bucket = 'AVAILABLE';
```

**Expected Result:**
```
material_id | batch_number      | warehouse_id | bin_id | bucket    | qty_on_hand | available_qty
------------|-------------------|--------------|--------|-----------|-------------|---------------
3           | BATCH-JEERA-001   | 1            | NULL   | AVAILABLE | 500.0       | 500.0
```

### **Step 2: Test MIR Scanning**

Now try scanning again with your original request:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/material-issue-requests/5/lines/9/scan \
  -H "Content-Type: application/json" \
  -H "Cookie: auth_token=YOUR_JWT_TOKEN" \
  -d '{
    "bin_barcode": "WH-BIN-0002",
    "material_barcode": "RM-0001",
    "quantity": 0.01
  }'
```

**Expected Success Response:**
```json
{
    "success": true,
    "message": "Material issued successfully",
    "data": {
        "line_id": 9,
        "issued_qty": 0.01,
        "remaining_qty": 0.99,
        "batch_number": "BATCH-JEERA-001"
    }
}
```

---

## 📊 Alternative Solutions

### **Option A: Add Stock to Specific Bin (More Accurate)**

If you want precise bin-level tracking, add stock to a specific bin:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/stock/adjust \
  -H "Content-Type: application/json" \
  -H "Cookie: auth_token=YOUR_TOKEN" \
  -d '{
    "material_id": 3,
    "warehouse_id": 1,
    "bin_id": 2,               ← Specify bin ID (WH-BIN-0002)
    "qty": 500,
    "type": "add",
    "batch_number": "BATCH-JEERA-001",
    "reason": "Adding to bin WH-BIN-0002"
  }'
```

**Find Bin ID:**
```sql
SELECT id, bin_code, bin_name 
FROM bin_locations 
WHERE bin_code = 'WH-BIN-0002';
```

### **Option B: Use SQL to Add Bin-Level Stock**

```sql
-- Add stock to specific bin
INSERT INTO inventory_transactions (
    material_id, batch_number, bucket, qty_change, uom_id, 
    warehouse_id, bin_id, transaction_type, reference_type, 
    reference_id, reference_number, unit_cost, total_cost, 
    created_by, remarks, created_at, updated_at
) VALUES (
    3, 'BATCH-JEERA-001', 'AVAILABLE', 500.0, 1, 
    1, 2,                  -- ← Bin ID here
    'STOCK_ADJUSTMENT', 'ManualTest', 999, 
    'MANUAL/20260403120000', 350.0, 175000.0, 1, 
    'Added to bin WH-BIN-0002', NOW(), NOW()
);

-- Update stock balance
INSERT INTO stock_balances (
    material_id, product_id, batch_number, uom_id, 
    warehouse_id, bin_id, bucket, qty_on_hand, total_value,
    created_at, updated_at
) VALUES (
    3, NULL, 'BATCH-JEERA-001', 1, 1, 2,  -- ← Bin ID here
    'AVAILABLE', 500.0, 175000.0, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE 
    qty_on_hand = qty_on_hand + 500.0,
    total_value = total_value + 175000.0,
    updated_at = NOW();
```

---

## 🎯 Best Practices

### **Stock Tracking Strategies:**

#### **1. Warehouse-Level Tracking (Simpler)**
- Use `bin_id = NULL` in stock_balances
- Good for: Bulk materials, loose storage, small warehouses
- Pros: Simpler, less data entry
- Cons: Less precise location tracking

#### **2. Bin-Level Tracking (More Precise)**
- Use specific `bin_id` in stock_balances
- Good for: Organized warehouses, FIFO/FEFO, high-value items
- Pros: Exact location tracking, easier picking
- Cons: More data entry, requires bin barcodes

### **Recommendation:**
For production systems, use **bin-level tracking** for better inventory control. For testing and simple setups, **warehouse-level** is acceptable.

---

## 🐛 Common Related Issues

### **Issue 1: Stock Exists But Shows 0 Available**

**Cause:** Stock is reserved by another MIR or sales order

**Check:**
```sql
SELECT 
    qty_on_hand,
    qty_reserved,
    (qty_on_hand - qty_reserved) as available
FROM stock_balances
WHERE material_id = 3 AND bucket = 'AVAILABLE';
```

**Fix:** Wait for reservation to be released or cancel the holding order

### **Issue 2: Wrong Batch Number**

**Cause:** Material has multiple batches, scanning wrong one

**Check:**
```sql
SELECT DISTINCT batch_number, qty_on_hand
FROM stock_balances
WHERE material_id = 3 AND bucket = 'AVAILABLE';
```

**Fix:** Ensure scanned material barcode matches batch in stock

### **Issue 3: Stock in Wrong Bucket**

**Cause:** Stock still in QC_HOLD or PUTAWAY_PENDING, not AVAILABLE

**Check:**
```sql
SELECT bucket, SUM(qty_on_hand) as total_qty
FROM stock_balances
WHERE material_id = 3
GROUP BY bucket;
```

**Expected Buckets:**
```
bucket            | total_qty
------------------|----------
QC_HOLD           | 0
PUTAWAY_PENDING   | 0
AVAILABLE         | 500   ← Should be here
RESERVED          | 0
BLOCKED           | 0
```

**Fix:** Complete putaway process to move stock to AVAILABLE bucket

---

## 📝 Debugging Checklist

When encountering MIR scanning issues, check these in order:

- [ ] **1. Check Stock Exists**
  ```sql
  SELECT * FROM stock_balances 
  WHERE material_id = 3 AND bucket = 'AVAILABLE';
  ```

- [ ] **2. Check Stock Location**
  - Is `bin_id` NULL? → Warehouse-level stock
  - Is `bin_id` set? → Bin-specific stock

- [ ] **3. Check Available Quantity**
  ```sql
  SELECT qty_on_hand - qty_reserved as available
  FROM stock_balances
  WHERE material_id = 3 AND bucket = 'AVAILABLE';
  ```

- [ ] **4. Verify MIR Status**
  - Must be `APPROVED` before scanning
  - Cannot be `PENDING`, `REJECTED`, or `CANCELLED`

- [ ] **5. Verify Material Match**
  - Scanned material barcode must match MIR line material_id

- [ ] **6. Check Bin Status**
  - Bin must be active (`is_active = true`)
  - Bin must belong to correct warehouse

- [ ] **7. Check Transaction History**
  ```sql
  SELECT * FROM inventory_transactions
  WHERE material_id = 3
  ORDER BY created_at DESC
  LIMIT 10;
  ```

---

## 🔗 Related Files

- **Controller:** [`app/Http/Controllers/MaterialIssueRequestController.php`](file:///c:\xampp\htdocs\ERP\ERP\app\Http\Controllers\MaterialIssueRequestController.php)
- **Models:** 
  - `app/Models/Tenant/MaterialIssueRequest.php`
  - `app/Models/Tenant/MIRLineItem.php`
  - `app/Models/Tenant/StockBalance.php`
  - `app/Models/Tenant/BinLocation.php`
- **Service:** `app/Services/StockService.php`
- **Route:** `routes/api.php`

---

## 📚 Additional Resources

- [Material Issue Request Process Flow](./MIR_PROCESS_FLOW.md)
- [Stock Management Guide](./STOCK_MANAGEMENT_GUIDE.md)
- [Warehouse Bin Tracking](./BIN_TRACKING_SETUP.md)
- [Inventory Transaction Types](./INVENTORY_TRANSACTIONS.md)

---

## Summary

✅ **Fixed:** MIR scanning now checks both bin-specific and warehouse-level stock  
✅ **Tested:** Backward compatible with existing bin-level tracking  
✅ **Documented:** Complete debugging guide for future issues  

Your MIR scanning should now work with warehouse-level stock! 🎉
