# Putaway & Store Posting API Examples

## Overview
Putaway manages the final physical movement of materials from staging area to permanent warehouse storage, making stock available for production.

## Base URL
```
http://127.0.0.1:8000/api/v1/putaway
```

## Authentication
All requests require JWT token:
```
Authorization: Bearer <your_jwt_token>
```

## Test Data
- org_slug: `amit-tech-solutions-pvt-ltd`
- user_id: 1 (ADMIN role)
- GRN Line ID: Use an existing GRN line with UNRESTRICTED stock status

---

## 1. List All Putaway Tasks

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Status
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway?status=PENDING" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Operator
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway?assigned_to=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Material
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway?material_id=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 2. Get Single Putaway Task

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 3. Create Putaway Task

### Manual Strategy (Operator Selects Bin)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_line_id": 1,
    "material_id": 1,
    "batch_number": "BATCH-001",
    "quantity": 100,
    "uom_id": 1,
    "source_bin_id": 1,
    "destination_bin_id": 5,
    "strategy": "MANUAL",
    "assigned_to": 1,
    "remarks": "Putaway for GRN-2603-0001"
  }'
```

### Fixed Bin Strategy (Pre-assigned Location)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_line_id": 1,
    "material_id": 1,
    "quantity": 100,
    "uom_id": 1,
    "strategy": "FIXED_BIN",
    "assigned_to": 1
  }'
```

### Empty Bin Strategy (Find Nearest Empty Space)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_line_id": 1,
    "material_id": 1,
    "quantity": 100,
    "uom_id": 1,
    "strategy": "EMPTY_BIN"
  }'
```

### FIFO Strategy (First-In-First-Out)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_line_id": 1,
    "material_id": 1,
    "quantity": 100,
    "uom_id": 1,
    "strategy": "FIFO"
  }'
```

### FEFO Strategy (First-Expired-First-Out)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/putaway" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_line_id": 1,
    "material_id": 1,
    "batch_number": "BATCH-001",
    "quantity": 100,
    "uom_id": 1,
    "strategy": "FEFO"
  }'
```

---

## 4. Update Putaway Task

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/putaway/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "destination_bin_id": 10,
    "assigned_to": 2,
    "remarks": "Changed destination bin to RACK-B2-L3"
  }'
```

---

## 5. Start Putaway

Moves task from PENDING to IN_PROGRESS status.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/putaway/1/start" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## 6. Complete Putaway

Moves task from IN_PROGRESS to COMPLETED status with scan confirmation.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/putaway/1/complete" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "bin_scan_confirmed": "RACK-A1-L2",
    "item_scan_confirmed": "MAT-001-BATCH-001",
    "remarks": "Material placed successfully in designated bin"
  }'
```

---

## 7. Cancel Putaway

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/putaway/1/cancel" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "reason": "Material damaged during movement - requires re-inspection"
  }'
```

---

## 8. Get Pending Putaway Tasks

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway/pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 9. Get In-Progress Putaway Tasks

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway/in-progress" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 10. Get Completed Putaway Tasks

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/putaway/completed" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Status Flow

```
PENDING (task created, awaiting operator)
    ↓
IN_PROGRESS (operator started physical movement)
    ↓
COMPLETED (confirmed with bin & item scans)
```

---

## Response Examples

### Success Response (Create Task)
```json
{
  "success": true,
  "message": "Putaway task created successfully",
  "data": {
    "id": 1,
    "task_number": "PT-2603/0001",
    "grn_line_id": 1,
    "material_id": 1,
    "batch_number": "BATCH-001",
    "quantity": 100.000,
    "uom_id": 1,
    "source_bin_id": 1,
    "destination_bin_id": 5,
    "strategy": "MANUAL",
    "status": "PENDING",
    "assigned_to": 1,
    "created_at": "2026-03-12T10:00:00.000000Z"
  }
}
```

### Success Response (Complete Putaway)
```json
{
  "success": true,
  "message": "Putaway completed successfully",
  "data": {
    "id": 1,
    "task_number": "PT-2603/0001",
    "status": "COMPLETED",
    "bin_scan_confirmed": "RACK-A1-L2",
    "item_scan_confirmed": "MAT-001-BATCH-001",
    "completed_at": "2026-03-12T11:30:00.000000Z",
    "completed_by": 1,
    "destination_bin": {
      "id": 5,
      "bin_code": "RACK-A1-L2",
      "warehouse_id": 1,
      "zone": "A",
      "rack": "A1",
      "level": "L2"
    }
  }
}
```

---

## Putaway Strategies

| Strategy | Description | Use Case |
|----------|-------------|----------|
| MANUAL | Operator selects bin | Experienced staff, special handling |
| FIXED_BIN | Pre-assigned location | High-volume items, dedicated storage |
| EMPTY_BIN | Nearest empty space | Optimize volume, flexible storage |
| FIFO | First-In-First-Out | Non-perishable items, rotation |
| FEFO | First-Expired-First-Out | Perishable items, expiry tracking |

---

## Key Features

1. **Strategy-Based Bin Selection**: Automatic or manual bin determination
2. **Barcode Scanning**: Bin and item scan confirmation required
3. **Stock Status Update**: Updates GRN line with final bin location
4. **Operator Tracking**: Tracks who started and completed putaway
5. **Timestamp Recording**: Tracks when material became available
6. **Audit Trail**: Complete digital trail from gate to rack
7. **Batch Traceability**: Links to batch numbers from GRN

---

## Business Rules

1. Putaway task auto-created when QC usage decision = ACCEPTED
2. Task must be in PENDING status to start
3. Task must be in IN_PROGRESS status to complete
4. Both bin and item scans required for completion
5. Completion updates GRN line item with final bin location
6. Stock becomes available for production after completion
7. Operator must be assigned before starting putaway

---

## Barcode Scanning

### Bin Scan Format
- Scan the bin location barcode (e.g., "RACK-A1-L2")
- Must match the destination bin assigned to the task

### Item Scan Format
- Scan the material barcode or QR code
- Format: Material Code + Batch Number (e.g., "MAT-001-BATCH-001")
- Confirms correct material placement

---

## Testing Checklist

- [ ] Create putaway task with MANUAL strategy
- [ ] Create putaway task with FIXED_BIN strategy
- [ ] Create putaway task with EMPTY_BIN strategy
- [ ] Create putaway task with FIFO strategy
- [ ] Create putaway task with FEFO strategy
- [ ] List all putaway tasks with filters
- [ ] Start putaway (PENDING → IN_PROGRESS)
- [ ] Complete putaway with scan confirmation
- [ ] Cancel putaway task
- [ ] Get pending tasks
- [ ] Get in-progress tasks
- [ ] Get completed tasks
- [ ] Verify bin location update in GRN line
- [ ] Verify stock availability after completion
- [ ] Verify operator tracking
- [ ] Verify timestamp recording

---

## Integration Points

1. **From QC**: Putaway task auto-created when usage decision = ACCEPTED
2. **To Inventory**: Stock becomes available for production after completion
3. **To WMS**: Bin location updated for picking operations
4. **To Production**: Material visible as "Available" for Material Requisitions
