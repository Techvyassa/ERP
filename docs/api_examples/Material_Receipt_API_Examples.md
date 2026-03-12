# Material Receipt (MR) API Examples

## Overview
Material Receipt is the stage where physical goods transfer from the truck to the warehouse floor. The warehouse team formally takes ownership of the materials and performs physical verification.

## Base URL
```
http://127.0.0.1:8000/api/v1/material-receipts
```

## Authentication
All requests require JWT token in Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Test Data
- org_slug: `amit-tech-solutions-pvt-ltd`
- user_id: 1 (ADMIN role)
- Gate Entry ID: Use an existing GE in MOVED_TO_DOCK status
- PO ID: Use an existing approved PO

---

## 1. Create Material Receipt

### Normal Receipt (No Variance)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Normal receipt - all quantities match PO",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 100,
        "uom_id": 1,
        "batch_number": "BATCH-001",
        "manufacturing_date": "2026-03-01",
        "expiry_date": "2027-03-01",
        "provisional_bin_id": 1
      }
    ]
  }'
```

### Short Delivery (Within Tolerance)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Short delivery within tolerance",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 97,
        "uom_id": 1,
        "batch_number": "BATCH-002"
      }
    ]
  }'
```
**Expected Result**: shortage_qty = 3, shortage_flag = FALSE (if PO tolerance >= 3%)

### Short Delivery (Beyond Tolerance)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Short delivery beyond tolerance - requires approval",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 90,
        "uom_id": 1,
        "batch_number": "BATCH-003"
      }
    ]
  }'
```
**Expected Result**: shortage_qty = 10, shortage_flag = TRUE (if PO tolerance < 10%)

### Excess Delivery (Beyond Tolerance)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Excess delivery - vendor sent more than ordered",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 110,
        "uom_id": 1,
        "batch_number": "BATCH-004"
      }
    ]
  }'
```
**Expected Result**: excess_qty = 10, excess_flag = TRUE (if PO tolerance < 10%)

### Damaged Material
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Damage found during unloading",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 100,
        "rejected_on_arrival": 5,
        "uom_id": 1,
        "batch_number": "BATCH-005",
        "damage_found": true,
        "damage_remarks": "5 boxes crushed during transport - packaging failure"
      }
    ]
  }'
```

---

## 2. List All Material Receipts

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### With Pagination
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts?page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Status
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts?status=IN_PROGRESS" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 3. Get Single Material Receipt

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 4. Update Material Receipt

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/material-receipts/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "remarks": "Updated remarks - additional damage found",
    "line_items": [
      {
        "id": 1,
        "received_qty": 95,
        "rejected_on_arrival": 5,
        "damage_found": true,
        "damage_remarks": "Additional damage discovered during detailed inspection"
      }
    ]
  }'
```

---

## 5. Get Material Receipts by Gate Entry

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts/by-ge/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 6. Get Material Receipts by Purchase Order

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts/by-po/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 7. Get Material Receipts Pending GRN

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/material-receipts/pending-grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 8. Start Unloading

Records the start time of unloading operation.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/material-receipts/1/start-unloading" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## 9. Complete Unloading

Marks unloading as complete and changes status to COMPLETED.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/material-receipts/1/complete" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## Status Flow

```
IN_PROGRESS (unloading ongoing)
    ↓
COMPLETED (unloading finished, all line items recorded)
    ↓
PENDING_GRN (awaiting GRN creation)
    ↓
GRN_POSTED (GRN created, inventory updated)
```

---

## Response Examples

### Success Response (Create MR)
```json
{
  "success": true,
  "message": "Material receipt created successfully",
  "data": {
    "id": 1,
    "mr_number": "MR-2603-0001",
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11T14:00:00.000000Z",
    "unloading_completed_at": null,
    "status": "IN_PROGRESS",
    "remarks": "Normal receipt - all quantities match PO",
    "created_by": 1,
    "created_at": "2026-03-11T14:05:00.000000Z",
    "line_items": [
      {
        "id": 1,
        "mr_id": 1,
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 100.00,
        "shortage_qty": 0.00,
        "excess_qty": 0.00,
        "rejected_on_arrival": 0.00,
        "shortage_flag": false,
        "excess_flag": false,
        "batch_number": "BATCH-001",
        "manufacturing_date": "2026-03-01",
        "expiry_date": "2027-03-01",
        "provisional_bin_id": 1,
        "damage_found": false,
        "internal_barcode": "MR2603000100001"
      }
    ]
  }
}
```

### Error Response (Gate Entry Not in Correct Status)
```json
{
  "success": false,
  "message": "Gate entry must be in MOVED_TO_DOCK status",
  "errors": null
}
```

### Error Response (Validation Failed)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "ge_id": ["The selected ge id is invalid."],
    "line_items.0.received_qty": ["The received qty must be greater than 0."]
  }
}
```

---

## Key Features

1. **Automatic Variance Calculation**: System calculates shortage/excess automatically
2. **Tolerance Checking**: Compares variance against PO line tolerances and sets flags
3. **Damage Tracking**: Records damage with photos and remarks
4. **Batch Traceability**: Captures vendor batch numbers and dates
5. **Provisional Storage**: Assigns temporary bin locations
6. **Internal Barcoding**: Generates system barcodes for tracking (format: MR{YYMM}{MR_ID}{LINE_NUM})
7. **Dock Turnaround Tracking**: Records unloading start/end times
8. **3-Way Match Preparation**: Sets up data for PO-MR-Invoice matching

---

## Business Rules

1. Gate Entry must be in MOVED_TO_DOCK status
2. Purchase Order must be in APPROVED, OPEN, or PARTIAL status
3. Material Receipt can only be edited when status is IN_PROGRESS
4. Variance calculation: Variance = Received Qty − PO Qty
5. Shortage flag set when shortage exceeds under_delivery_tolerance
6. Excess flag set when excess exceeds over_delivery_tolerance
7. After MR completion, stock status is "In-Quality" / "Restricted Stock"
8. Stock is NOT available for consumption until QC approval

---

## Testing Checklist

- [ ] Create MR with normal receipt (no variance)
- [ ] Create MR with shortage within tolerance
- [ ] Create MR with shortage beyond tolerance (flag should be TRUE)
- [ ] Create MR with excess within tolerance
- [ ] Create MR with excess beyond tolerance (flag should be TRUE)
- [ ] Create MR with damaged material
- [ ] Create MR with batch traceability data
- [ ] Start unloading timer
- [ ] Complete unloading
- [ ] List all MRs with pagination
- [ ] Filter MRs by status
- [ ] Get MRs by Gate Entry
- [ ] Get MRs by Purchase Order
- [ ] Get MRs pending GRN
- [ ] Update MR line items
- [ ] Verify internal barcode generation
- [ ] Verify variance calculation logic
- [ ] Verify tolerance checking logic
