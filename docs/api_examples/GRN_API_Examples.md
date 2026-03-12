# Goods Receipt Note (GRN) API Examples

## Overview
GRN is the formal book entry that legally acknowledges ownership transfer. It triggers inventory updates and creates financial liability (Accounts Payable).

## Base URL
```
http://127.0.0.1:8000/api/v1/grn
```

## Authentication
All requests require JWT token:
```
Authorization: Bearer <your_jwt_token>
```

## Test Data
- org_slug: `amit-tech-solutions-pvt-ltd`
- user_id: 1 (ADMIN role)
- Material Receipt ID: Use an MR in COMPLETED status

---

## 1. Create GRN from Material Receipt

### Basic GRN Creation
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "mr_id": 1,
    "grn_date": "2026-03-11",
    "posting_date": "2026-03-11",
    "remarks": "GRN created from MR-2603-0001",
    "line_items": [
      {
        "mr_line_id": 1,
        "material_id": 1,
        "accepted_qty": 100,
        "uom_id": 1,
        "unit_price": 150.50,
        "tax_rate": 18,
        "batch_number": "BATCH-001",
        "manufacturing_date": "2026-03-01",
        "expiry_date": "2027-03-01",
        "warehouse_bin_id": 1
      }
    ]
  }'
```

### GRN with Multiple Line Items
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "mr_id": 1,
    "grn_date": "2026-03-11",
    "posting_date": "2026-03-11",
    "remarks": "Multi-line GRN",
    "line_items": [
      {
        "mr_line_id": 1,
        "material_id": 1,
        "accepted_qty": 100,
        "uom_id": 1,
        "unit_price": 150.50,
        "tax_rate": 18
      },
      {
        "mr_line_id": 2,
        "material_id": 2,
        "accepted_qty": 50,
        "uom_id": 2,
        "unit_price": 200.00,
        "tax_rate": 18
      }
    ]
  }'
```

---

## 2. List All GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Status
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn?status=PROVISIONAL" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Date Range
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn?grn_date_from=2026-03-01&grn_date_to=2026-03-31" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 3. Get Single GRN

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 4. Update GRN

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/grn/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "remarks": "Updated remarks - bin location changed",
    "line_items": [
      {
        "id": 1,
        "warehouse_bin_id": 2,
        "batch_number": "BATCH-001-UPDATED"
      }
    ]
  }'
```

---

## 5. Get GRNs by Material Receipt

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/by-mr/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 6. Get GRNs by Purchase Order

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/by-po/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 7. Get GRNs by Vendor

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/by-vendor/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 8. Get Provisional GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/provisional" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 9. Get QC Pending GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/qc-pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 10. Approve GRN

Moves GRN from PROVISIONAL to QC_PENDING status.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/grn/1/approve" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## 11. Cancel GRN

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/grn/1/cancel" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "reason": "Incorrect pricing - need to recreate GRN"
  }'
```

---

## Status Flow

```
PROVISIONAL (created after MR completion)
    ↓
QC_PENDING (approved by Store Manager, awaiting QC inspection)
    ↓
ACCEPTED / PARTIALLY_ACCEPTED / REJECTED (after QC decision)
```

---

## Response Examples

### Success Response (Create GRN)
```json
{
  "success": true,
  "message": "GRN created successfully",
  "data": {
    "id": 1,
    "grn_number": "GRN/25-26/0001",
    "mr_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "grn_date": "2026-03-11",
    "posting_date": "2026-03-11",
    "total_received_value": 15050.00,
    "total_tax_amount": 2709.00,
    "grand_total": 17759.00,
    "status": "PROVISIONAL",
    "created_by": 1,
    "line_items": [
      {
        "id": 1,
        "grn_id": 1,
        "mr_line_id": 1,
        "material_id": 1,
        "accepted_qty": 100.000,
        "uom_id": 1,
        "unit_price": 150.5000,
        "tax_rate": 18.00,
        "line_value": 15050.00,
        "tax_amount": 2709.00,
        "stock_status": "RESTRICTED"
      }
    ]
  }
}
```

---

## Key Features

1. **Legal Evidence**: Officially confirms company accepted goods
2. **Inventory Update**: Increases stock in RESTRICTED status (awaiting QC)
3. **Financial Liability**: Creates Accounts Payable entry
4. **Automatic Calculations**: Line value and tax calculated automatically
5. **Batch Traceability**: Tracks batch numbers and expiry dates
6. **Stock Status**: RESTRICTED → UNRESTRICTED (after QC approval)
7. **Fiscal Year Numbering**: GRN/YY-YY/NNNN format

---

## Business Rules

1. Material Receipt must be in COMPLETED status
2. GRN can only be edited when status is PROVISIONAL or QC_PENDING
3. Approval moves GRN to QC_PENDING and triggers QC inspection
4. Stock is created in RESTRICTED status (not available for production)
5. After QC approval, stock status changes to UNRESTRICTED
6. Cancelling GRN reverts Material Receipt to COMPLETED status
7. Line value = accepted_qty × unit_price
8. Tax amount = line_value × (tax_rate / 100)
9. Grand total = total_received_value + total_tax_amount

---

## Accounting Entry (Auto-generated on GRN save)

```
Debit:  GR/IR Clearing Account (or Inventory Account)
Credit: Accounts Payable (Vendor Liability)
```

The `journal_ref` field stores the accounting entry ID for audit trail.
