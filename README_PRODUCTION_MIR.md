# Production Order & Material Issue Request (MIR) System

## Overview

This document describes the complete production order and Material Issue Request (MIR) system implementation, including batch runs, two-level MIR status tracking, and production floor receiving gates.

## Files Updated

### Route Files
- **`routes/api.php`** — Updated with new API endpoints for batch runs, MIR lines, and production floor receiving
- **`routes/web.php`** — Updated with new web routes for production portal views

### Documentation Files Created
1. **`PRODUCTION_MIR_API_GUIDE.md`** — Complete API documentation with examples
2. **`PRODUCTION_MIR_UPDATES_SUMMARY.md`** — Summary of all changes made
3. **`IMPLEMENTATION_CHECKLIST.md`** — Step-by-step implementation guide
4. **`QUICK_REFERENCE.md`** — Quick reference for endpoints and workflows
5. **`README_PRODUCTION_MIR.md`** — This file

## Key Features

### 1. Production Orders
- Raised against a specific product and BOM version
- User enters `production_qty` — total FG units to produce
- System calculates total material requirement: `total_required = base_qty × production_qty`
- Status flow: `DRAFT → RELEASED → IN_PROGRESS → CLOSED`

### 2. Batch Runs (Independent Execution Units)
- Each batch run is a separate physical execution of the recipe
- User-defined `run_qty` (flexible, not tied to reference batch size)
- Each run has: `planned_date`, `actual_start_at`, `actual_end_at`, `status`
- Status flow: `PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED`
- MIR auto-generated when batch run moves to `IN_PROGRESS`

### 3. Material Issue Requests (MIR)
- Auto-generated per batch run
- Covers all raw materials: `required_qty = base_qty × (1 + scrap_percent/100) × run_qty`
- Two-level tracking:
  - **Line Level**: Individual material tracking (PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED)
  - **Header Level**: Overall MIR status (auto-derived from line statuses)

### 4. Partial Issuance
- Store can issue materials in multiple transactions per line
- Each transaction logged in `mir_issue_transactions` table
- Line status updates automatically based on cumulative issued qty

### 5. Production Floor Receiving Gate
- Hard gate: Batch run cannot start until `receiving_status = RECEIVED`
- Prevents false starts and mid-run material shortages
- Provides traceability checkpoint with confirmed receipt timestamp
- Supports quantity discrepancy notes (non-blocking)

### 6. Finished Goods Receipt
- Records actual production output per batch run
- Calculates yield: `yield_actual_pct = accepted_qty / planned_qty × 100`
- Unique lot number per receipt for traceability
- Tracks variance for management reporting

## API Endpoints

### Production Orders
```
GET    /api/v1/production-orders
POST   /api/v1/production-orders
GET    /api/v1/production-orders/{id}
PATCH  /api/v1/production-orders/{id}/release
PATCH  /api/v1/production-orders/{id}/close
```

### Batch Runs
```
GET    /api/v1/batch-runs
POST   /api/v1/batch-runs
GET    /api/v1/batch-runs/{id}
GET    /api/v1/batch-runs/{id}/materials
GET    /api/v1/batch-runs/{id}/mir
PATCH  /api/v1/batch-runs/{id}/start
PATCH  /api/v1/batch-runs/{id}/complete
```

### Material Issue Requests
```
GET    /api/v1/material-issue-requests
GET    /api/v1/material-issue-requests/{id}
GET    /api/v1/material-issue-requests/{id}/lines
PATCH  /api/v1/material-issue-requests/{id}/approve
PATCH  /api/v1/material-issue-requests/{id}/reject
```

### MIR Lines
```
GET    /api/v1/mir-lines/{id}
PATCH  /api/v1/mir-lines/{id}/approve
PATCH  /api/v1/mir-lines/{id}/reject
POST   /api/v1/mir-lines/{id}/issue
```

### Production Floor Receiving
```
GET    /api/v1/batch-runs/{batchRunId}/receiving
PATCH  /api/v1/batch-runs/{batchRunId}/receiving/confirm
```

### Finished Goods
```
POST   /api/v1/fg-receipts
GET    /api/v1/fg-receipts/{id}
```

## Status Flows

### Production Order
```
DRAFT → RELEASED → IN_PROGRESS → CLOSED
```

### Batch Run
```
PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED
```

### MIR Header (Auto-Derived)
```
PENDING → APPROVED → PARTIALLY_ISSUED → FULLY_ISSUED → CLOSED
                                    ↓
                                REJECTED
```

### MIR Line
```
PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED
   ↓                                          ↑
   └──────────────────────────────────────────┘
            (multiple issue transactions)

PENDING → REJECTED
```

### Production Floor Receiving
```
PENDING_RECEIPT → RECEIVED
```

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
# Response: Line status → FULLY_PICKED
# MIR header status → FULLY_ISSUED (if all lines done)
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

## Implementation Steps

### Phase 1: Database Migrations
1. Update `production_batch_runs` table with receiving fields
2. Update `material_issue_requests` table with new statuses
3. Update `mir_line_items` table with new statuses
4. Create `mir_issue_transactions` table for audit trail

### Phase 2: Model Updates
1. Update `ProductionBatchRun` model
2. Update `MaterialIssueRequest` model
3. Update `MIRLineItem` model
4. Create `MIRIssueTransaction` model

### Phase 3: Controller Implementation
1. Create `BatchRunController`
2. Create `MIRLineController`
3. Create `BatchRunReceivingController`
4. Create `FGReceiptController`
5. Update `ProductionOrderController`
6. Update `MaterialIssueRequestController`

### Phase 4: Testing
1. Unit tests for each controller
2. Integration tests for workflows
3. Manual testing of complete flow

### Phase 5: Deployment
1. Run migrations
2. Deploy code
3. Verify endpoints
4. Monitor for errors

## Business Rules

1. **Hard Gate**: Production cannot start until `receiving_status = RECEIVED`
2. **Partial Issuance**: Store can issue materials in multiple transactions per line
3. **Line Independence**: Each material line tracked independently
4. **Header Derivation**: MIR header status never set manually — always derived from line statuses
5. **Rejection Blocking**: One rejected line blocks entire MIR (header = REJECTED)
6. **Quantity Flexibility**: Sum of batch run quantities can exceed or fall short of production order quantity
7. **Audit Trail**: Every pick transaction logged in `mir_issue_transactions`
8. **Discrepancy Handling**: Quantity discrepancies at receiving are non-blocking but logged

## Database Tables

### New/Updated Tables
- `production_batch_runs` — Added receiving fields
- `material_issue_requests` — Updated status enum, added timestamps
- `mir_line_items` — Updated status enum, added timestamps
- `mir_issue_transactions` — NEW: Audit trail for pick transactions

### Key Fields
- `production_batch_runs.receiving_status` — PENDING_RECEIPT / RECEIVED
- `production_batch_runs.received_at` — Timestamp when operator confirms
- `production_batch_runs.received_by` — FK to users
- `production_batch_runs.receiving_notes` — Discrepancy notes
- `material_issue_requests.status` — PENDING / APPROVED / PARTIALLY_ISSUED / FULLY_ISSUED / REJECTED / CLOSED
- `material_issue_requests.fully_issued_at` — Timestamp when all lines FULLY_PICKED
- `material_issue_requests.closed_at` — Timestamp when production confirms receipt
- `mir_line_items.status` — PENDING / APPROVED / PARTIALLY_PICKED / FULLY_PICKED / REJECTED
- `mir_line_items.last_issued_at` — Timestamp of most recent issue
- `mir_line_items.rejected_reason` — NULLABLE

## Controllers to Implement

1. **BatchRunController** — Manage batch runs
   - `index()`, `store()`, `show()`, `start()`, `complete()`, `materials()`, `mir()`

2. **MIRLineController** — Manage individual MIR lines
   - `show()`, `approve()`, `reject()`, `issue()`

3. **BatchRunReceivingController** — Handle production floor receiving
   - `show()`, `confirm()`

4. **FGReceiptController** — Handle finished goods receipts
   - `store()`, `show()`

5. **ProductionOrderController** — Update with new endpoints
   - `release()`, `close()`

6. **MaterialIssueRequestController** — Update with new endpoints
   - `approve()`, `reject()`, `lines()`

## Web Routes

### Production Portal
- `/org/{org_slug}/production/dashboard` — Dashboard
- `/org/{org_slug}/production/orders` — List orders
- `/org/{org_slug}/production/orders/{id}` — View order
- `/org/{org_slug}/production/batch-runs` — List batch runs
- `/org/{org_slug}/production/batch-runs/{id}` — View batch run
- `/org/{org_slug}/production/batch-runs/{id}/receiving` — Receiving confirmation
- `/org/{org_slug}/production/material-issue-requests` — List MIRs
- `/org/{org_slug}/production/material-issue-requests/{id}` — View MIR

### Warehouse Portal
- `/org/{org_slug}/warehouse/mir` — List MIRs (warehouse view)
- `/org/{org_slug}/warehouse/mir/{id}` — View MIR (warehouse view)

## Documentation Files

1. **PRODUCTION_MIR_API_GUIDE.md** — Complete API documentation with examples and error scenarios
2. **PRODUCTION_MIR_UPDATES_SUMMARY.md** — Summary of all changes and features
3. **IMPLEMENTATION_CHECKLIST.md** — Step-by-step implementation guide with code examples
4. **QUICK_REFERENCE.md** — Quick reference for endpoints, workflows, and common errors
5. **README_PRODUCTION_MIR.md** — This file

## Next Steps

1. Review the API guide and implementation checklist
2. Create database migrations
3. Update models
4. Implement controllers
5. Write tests
6. Deploy to staging
7. Test complete workflow
8. Deploy to production
9. Monitor and optimize

## Support

For questions or issues:
1. Check the API guide for endpoint documentation
2. Check the quick reference for common errors
3. Check the implementation checklist for code examples
4. Review the status flow diagrams for workflow understanding

## Version History

- **v1.0** (April 2026) — Initial implementation with batch runs, two-level MIR tracking, and production floor receiving
