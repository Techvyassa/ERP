# Quick Test - GRN APIs

## Prerequisites
1. JWT Token from login
2. Material Receipt in COMPLETED status
3. Purchase Order with pricing information
4. Valid material_id and uom_id from your database


---

## Step 1: Get Valid Material IDs

First, check what materials exist in your database:

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/materials" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

Look for `id` values in the response.

---

## Step 2: Get Valid UOM IDs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/uom" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Step 3: Create GRN with Valid IDs

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "mr_id": 1,
    "grn_date": "2026-03-11",
    "posting_date": "2026-03-11",
    "remarks": "Test GRN creation",
    "line_items": [
      {
        "mr_line_id": 1,
        "material_id": 1,
        "accepted_qty": 100,
        "uom_id": 1,
        "unit_price": 150.50,
        "tax_rate": 18
      }
    ]
  }'
```

**Note**: Replace `material_id` and `uom_id` with actual IDs from your database.

---

## Step 4: List All GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Step 5: Get Provisional GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/provisional" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Step 6: Approve GRN

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/grn/1/approve" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## Step 7: Get QC Pending GRNs

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/grn/qc-pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```
