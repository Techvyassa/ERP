# Production Order & MIR API Guide

## Overview

This guide documents the complete API flow for production orders, batch runs, and Material Issue Requests (MIR) based on the manufacturing workflow specification.

## Key Concepts

### Production Order
- Raised against a specific product and BOM version
- User enters `production_qty` — total FG units to produce
- System calculates total material requirement: `total_required = base_qty × production_qty`
- Status flow: `DRAFT → RELEASED → IN_PROGRESS → CLOSED`

### Batch Runs
- Independent execution units under a production order
- Each run has its own `run_qty` (user-defined, flexible)
- Sum of all `run_qty` should equal `production_qty` (but system allows flexibility)
- Each run has: `planned_date`, `actual_start_at`, `actual_end_at`, `status`
- Status flow: `PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED`
- MIR is auto-generated when batch run moves to `IN_PROGRESS`

### Material Issue Request (MIR)
- Auto-generated per batch run when run moves to `IN_PROGRESS`
- Covers all raw materials needed: `required_qty = base_qty × (1 + scrap_percent/100) × run_qty`
- Two-level tracking:
  - **MIR Line Status**: Individual material tracking (PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED)
  - **MIR Header Status**: Overall MIR status (derived from line statuses)

### Production Floor Receiving
- Confirms materials physically arrived at workstation
- Status: `PENDING_RECEIPT → RECEIVED`
- **Hard gate**: Batch run cannot start until `receiving_status = RECEIVED`

---

## API Endpoints

### Production Orders

#### List Production Orders
```
GET /api/v1/production-orders
```
**Response**: List of production orders with status, product, BOM, and quantities

#### Create Production Order
```
POST /api/v1/production-orders
Content-Type: application/json

{
  "product_id": 123,
  "bom_id": 456,
  "production_qty": 100,
  "planned_date": "2026-04-20"
}
```
**Response**: Created production order (status: DRAFT)

#### Get Production Order Details
```
GET /api/v1/production-orders/{id}
```
**Response**: Full production order with related batch runs and MIR

#### Release Production Order
```
PATCH /api/v1/production-orders/{id}/release
```
**Transition**: `DRAFT → RELEASED`
**Response**: Updated production order

#### Close Production Order
```
PATCH /api/v1/production-orders/{id}/close
```
**Transition**: `IN_PROGRESS → CLOSED`
**Response**: Updated production order with final yield calculations

---

### Batch Runs

#### List Batch Runs
```
GET /api/v1/batch-runs
```
**Query Parameters**:
- `production_order_id`: Filter by production order
- `status`: Filter by status (PENDING, MIR_RAISED, IN_PROGRESS, COMPLETED)

**Response**: List of batch runs

#### Create Batch Run
```
POST /api/v1/batch-runs
Content-Type: application/json

{
  "production_order_id": 123,
  "run_qty": 25,
  "planned_date": "2026-04-20"
}
```
**Response**: Created batch run (status: PENDING)

#### Get Batch Run Details
```
GET /api/v1/batch-runs/{id}
```
**Response**: Batch run with materials, MIR, and receiving status

#### Get Batch Run Materials
```
GET /api/v1/batch-runs/{id}/materials
```
**Response**: List of materials required for this batch run with calculated quantities

#### Get Associated MIR
```
GET /api/v1/batch-runs/{id}/mir
```
**Response**: MIR details for this batch run (if exists)

#### Start Batch Run
```
PATCH /api/v1/batch-runs/{id}/start
```
**Preconditions**:
- Batch run status must be `PENDING`
- MIR must be auto-generated and status must be `FULLY_ISSUED`
- Production floor receiving must be `RECEIVED`

**Transition**: `PENDING → MIR_RAISED → IN_PROGRESS`
**Response**: Updated batch run with `actual_start_at` timestamp

#### Complete Batch Run
```
PATCH /api/v1/batch-runs/{id}/complete
```
**Preconditions**:
- Batch run status must be `IN_PROGRESS`

**Transition**: `IN_PROGRESS → COMPLETED`
**Response**: Updated batch run with `actual_end_at` timestamp

---

### Material Issue Requests (MIR)

#### List MIRs
```
GET /api/v1/material-issue-requests
```
**Query Parameters**:
- `batch_run_id`: Filter by batch run
- `status`: Filter by status (PENDING, APPROVED, PARTIALLY_ISSUED, FULLY_ISSUED, REJECTED, CLOSED)

**Response**: List of MIRs with header status and line count

#### Get MIR Details
```
GET /api/v1/material-issue-requests/{id}
```
**Response**: MIR with all line items, statuses, and timestamps

#### Get MIR Lines
```
GET /api/v1/material-issue-requests/{id}/lines
```
**Response**: All line items with individual statuses and issued quantities

#### Approve MIR (Header Level)
```
PATCH /api/v1/material-issue-requests/{id}/approve
```
**Preconditions**:
- MIR status must be `PENDING`
- All lines must be individually approved first

**Transition**: `PENDING → APPROVED`
**Response**: Updated MIR with `approved_at` timestamp

#### Reject MIR (Header Level)
```
PATCH /api/v1/material-issue-requests/{id}/reject
Content-Type: application/json

{
  "rejection_reason": "Stock unavailable for critical materials"
}
```
**Transition**: `PENDING → REJECTED`
**Response**: Updated MIR with rejection reason

---

### MIR Line Items

#### Get MIR Line Details
```
GET /api/v1/mir-lines/{id}
```
**Response**: Line item with material, required qty, issued qty, and status

#### Approve MIR Line
```
PATCH /api/v1/mir-lines/{id}/approve
```
**Preconditions**:
- Line status must be `PENDING`
- Store confirms material availability

**Transition**: `PENDING → APPROVED`
**Response**: Updated line item

#### Reject MIR Line
```
PATCH /api/v1/mir-lines/{id}/reject
Content-Type: application/json

{
  "rejection_reason": "Quality hold on batch"
}
```
**Transition**: `PENDING → REJECTED`
**Response**: Updated line item with rejection reason

#### Issue Material (Partial or Full)
```
POST /api/v1/mir-lines/{id}/issue
Content-Type: application/json

{
  "issued_qty": 10.5,
  "notes": "Issued from bin B-123, lot #ABC-2026"
}
```
**Preconditions**:
- Line status must be `APPROVED` or `PARTIALLY_PICKED`
- `issued_qty` must be > 0
- Total `issued_qty` cannot exceed `required_qty`

**Transition**:
- If `issued_qty < required_qty`: `APPROVED → PARTIALLY_PICKED` (or stays PARTIALLY_PICKED)
- If `issued_qty = required_qty`: `APPROVED/PARTIALLY_PICKED → FULLY_PICKED`

**Response**: Updated line item with new issued qty and status

**Side Effects**:
- Creates entry in `mir_issue_transactions` table
- Recalculates MIR header status automatically
- If all lines become `FULLY_PICKED`, MIR header becomes `FULLY_ISSUED`

---

### Production Floor Receiving

#### Get Receiving Status
```
GET /api/v1/batch-runs/{batchRunId}/receiving
```
**Response**: 
```json
{
  "batch_run_id": 123,
  "receiving_status": "PENDING_RECEIPT",
  "mir_id": 456,
  "mir_status": "FULLY_ISSUED",
  "materials": [
    {
      "material_id": 789,
      "material_name": "Chilli Powder",
      "required_qty": 10.5,
      "issued_qty": 10.5,
      "status": "FULLY_PICKED"
    }
  ]
}
```

#### Confirm Receipt at Production Floor
```
PATCH /api/v1/batch-runs/{batchRunId}/receiving/confirm
Content-Type: application/json

{
  "receiving_notes": "All materials received in good condition"
}
```
**Preconditions**:
- MIR header status must be `FULLY_ISSUED`
- Batch run receiving status must be `PENDING_RECEIPT`

**Transition**: `PENDING_RECEIPT → RECEIVED`
**Response**: Updated batch run with `received_at` timestamp and `received_by` user

**Side Effects**:
- MIR header status becomes `CLOSED`
- Batch run is now unlocked to move to `IN_PROGRESS`

---

### Finished Goods Receipt

#### Create FG Receipt
```
POST /api/v1/fg-receipts
Content-Type: application/json

{
  "batch_run_id": 123,
  "received_qty": 98,
  "rejected_qty": 2,
  "lot_number": "LOT-2026-04-001"
}
```
**Preconditions**:
- Batch run status must be `COMPLETED`
- `received_qty` must be > 0
- `rejected_qty` must be >= 0

**Response**: Created FG receipt with calculated `accepted_qty` and `yield_actual_pct`

#### Get FG Receipt Details
```
GET /api/v1/fg-receipts/{id}
```
**Response**: FG receipt with all quantities and yield calculations

---

## Status Flow Diagrams

### Production Order
```
DRAFT
  ↓ (release)
RELEASED
  ↓ (batch runs start)
IN_PROGRESS
  ↓ (all batch runs complete)
CLOSED
```

### Batch Run
```
PENDING
  ↓ (MIR auto-generated, receiving confirmed)
MIR_RAISED
  ↓ (start)
IN_PROGRESS
  ↓ (complete)
COMPLETED
```

### MIR Header (Derived from Line Statuses)
```
PENDING (all lines PENDING)
  ↓ (all lines approved)
APPROVED (all lines APPROVED)
  ↓ (store starts picking)
PARTIALLY_ISSUED (mix of states, at least one picked)
  ↓ (all lines fully picked)
FULLY_ISSUED (all lines FULLY_PICKED)
  ↓ (production confirms receipt)
CLOSED
```

### MIR Line
```
PENDING
  ├→ APPROVED (store confirms availability)
  └→ REJECTED (store rejects)

APPROVED
  ├→ PARTIALLY_PICKED (partial issue)
  └→ FULLY_PICKED (full issue)

PARTIALLY_PICKED
  ├→ PARTIALLY_PICKED (more partial issues)
  └→ FULLY_PICKED (remaining qty issued)

REJECTED
  └→ PENDING (new substitute line raised)
```

### Production Floor Receiving
```
PENDING_RECEIPT (MIR FULLY_ISSUED)
  ↓ (operator confirms)
RECEIVED (unlocks batch run to start)
```

---

## Key Business Rules

1. **Hard Gate**: Production cannot start until `receiving_status = RECEIVED`
2. **Partial Issuance**: Store can issue materials in multiple transactions per line
3. **Line Independence**: Each material line is tracked independently
4. **Header Derivation**: MIR header status is never set manually — always derived from line statuses
5. **Rejection Blocking**: One rejected line blocks entire MIR (header = REJECTED)
6. **Quantity Flexibility**: Sum of batch run quantities can exceed or fall short of production order quantity
7. **Audit Trail**: Every pick transaction is logged in `mir_issue_transactions`
8. **Discrepancy Handling**: Quantity discrepancies at receiving are non-blocking but logged

---

## Example Workflow

### Step 1: Create Production Order
```bash
POST /api/v1/production-orders
{
  "product_id": 1,
  "bom_id": 1,
  "production_qty": 100,
  "planned_date": "2026-04-20"
}
# Response: PO created with status DRAFT
```

### Step 2: Release Production Order
```bash
PATCH /api/v1/production-orders/1/release
# Response: PO status → RELEASED
```

### Step 3: Create Batch Run
```bash
POST /api/v1/batch-runs
{
  "production_order_id": 1,
  "run_qty": 50,
  "planned_date": "2026-04-20"
}
# Response: Batch run created with status PENDING
# System auto-generates MIR with status PENDING
```

### Step 4: Store Approves MIR Lines
```bash
PATCH /api/v1/mir-lines/1/approve
# Response: Line status → APPROVED

PATCH /api/v1/mir-lines/2/approve
# Response: Line status → APPROVED
```

### Step 5: Store Issues Materials
```bash
POST /api/v1/mir-lines/1/issue
{
  "issued_qty": 5.25,
  "notes": "From bin B-123"
}
# Response: Line status → PARTIALLY_PICKED

POST /api/v1/mir-lines/1/issue
{
  "issued_qty": 4.75,
  "notes": "From bin B-124"
}
# Response: Line status → FULLY_PICKED (5.25 + 4.75 = 10)
# MIR header status → PARTIALLY_ISSUED (line 1 done, line 2 pending)

POST /api/v1/mir-lines/2/issue
{
  "issued_qty": 8.0,
  "notes": "From bin B-125"
}
# Response: Line status → FULLY_PICKED
# MIR header status → FULLY_ISSUED (all lines done)
```

### Step 6: Production Confirms Receipt
```bash
PATCH /api/v1/batch-runs/1/receiving/confirm
{
  "receiving_notes": "All materials received"
}
# Response: Batch run receiving_status → RECEIVED
# MIR header status → CLOSED
```

### Step 7: Start Batch Run
```bash
PATCH /api/v1/batch-runs/1/start
# Response: Batch run status → IN_PROGRESS
# actual_start_at timestamp recorded
```

### Step 8: Complete Batch Run
```bash
PATCH /api/v1/batch-runs/1/complete
# Response: Batch run status → COMPLETED
# actual_end_at timestamp recorded
```

### Step 9: Record Finished Goods
```bash
POST /api/v1/fg-receipts
{
  "batch_run_id": 1,
  "received_qty": 49,
  "rejected_qty": 1,
  "lot_number": "LOT-2026-04-001"
}
# Response: FG receipt created
# accepted_qty = 48, yield_actual_pct = 96%
```

---

## Error Scenarios

### Scenario 1: Batch Run Cannot Start (MIR Not Fully Issued)
```
PATCH /api/v1/batch-runs/1/start
# Error 422: "Cannot start batch run. MIR status is PARTIALLY_ISSUED, not FULLY_ISSUED"
```

### Scenario 2: Batch Run Cannot Start (Receiving Not Confirmed)
```
PATCH /api/v1/batch-runs/1/start
# Error 422: "Cannot start batch run. Production floor receiving not confirmed"
```

### Scenario 3: MIR Line Rejected
```
PATCH /api/v1/mir-lines/1/reject
{
  "rejection_reason": "Quality hold on batch"
}
# Response: Line status → REJECTED
# MIR header status → REJECTED (blocks all other lines)
```

### Scenario 4: Partial Issue Exceeds Required
```
POST /api/v1/mir-lines/1/issue
{
  "issued_qty": 15.0
}
# Error 422: "Issued qty (15.0) exceeds required qty (10.0)"
```

---

## Database Tables Reference

### material_issue_requests
- `id`: PK
- `batch_run_id`: FK → production_batch_runs
- `status`: PENDING / APPROVED / PARTIALLY_ISSUED / FULLY_ISSUED / REJECTED / CLOSED
- `raised_at`: Timestamp when MIR auto-generated
- `fully_issued_at`: Timestamp when all lines FULLY_PICKED
- `closed_at`: Timestamp when production confirms receipt

### mir_line_items
- `id`: PK
- `mir_id`: FK → material_issue_requests
- `material_id`: FK → materials
- `required_qty`: Calculated at creation
- `issued_qty`: Running total
- `status`: PENDING / APPROVED / PARTIALLY_PICKED / FULLY_PICKED / REJECTED
- `last_issued_at`: Timestamp of most recent issue
- `rejected_reason`: NULLABLE

### mir_issue_transactions
- `id`: PK
- `mir_line_id`: FK → mir_line_items
- `issued_qty`: Qty in this transaction
- `issued_by`: FK → users
- `issued_at`: Timestamp
- `notes`: NULLABLE

### production_batch_runs
- `id`: PK
- `production_order_id`: FK → production_orders
- `run_qty`: User-defined
- `status`: PENDING / MIR_RAISED / IN_PROGRESS / COMPLETED
- `receiving_status`: PENDING_RECEIPT / RECEIVED
- `received_at`: NULLABLE
- `received_by`: FK → users
- `receiving_notes`: NULLABLE
- `actual_start_at`: NULLABLE
- `actual_end_at`: NULLABLE
