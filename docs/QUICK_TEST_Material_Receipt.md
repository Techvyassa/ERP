# Quick Test - Material Receipt APIs

## Prerequisites
1. JWT Token from login
2. Gate Entry in MOVED_TO_DOCK status
3. Purchase Order in APPROVED/OPEN status

---

## Step 1: Create Material Receipt

```bash
curl -X POST "http://localhost/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "ge_id": 1,
    "po_id": 1,
    "vendor_id": 1,
    "unloading_started_at": "2026-03-11 14:00:00",
    "remarks": "Test MR creation",
    "line_items": [
      {
        "po_line_id": 1,
        "material_id": 1,
        "received_qty": 100,
        "uom_id": 1,
        "batch_number": "TEST-BATCH-001"
      }
    ]
  }'
```

---

## Step 2: List All Material Receipts

```bash
curl -X GET "http://localhost/api/v1/material-receipts" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Step 3: Complete Unloading

```bash
curl -X PATCH "http://localhost/api/v1/material-receipts/1/complete" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## Step 4: Get Pending GRN

```bash
curl -X GET "http://localhost/api/v1/material-receipts/pending-grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```
