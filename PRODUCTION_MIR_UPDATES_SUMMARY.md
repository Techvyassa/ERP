# Production Order & MIR Routes Update Summary

## Overview
Updated the production order and Material Issue Request (MIR) flow to implement the complete manufacturing workflow with batch runs, two-level MIR status tracking, and production floor receiving gates.

## Changes Made

### 1. API Routes (`routes/api.php`)

#### Production Orders
- `GET /api/v1/production-orders` — List all production orders
- `POST /api/v1/production-orders` — Create new production order
- `GET /api/v1/production-orders/{id}` — Get production order details
- `PATCH /api/v1/production-orders/{id}/release` — Release order (DRAFT → RELEASED)
- `PATCH /api/v1/production-orders/{id}/close` — Close order (IN_PROGRESS → CLOSED)
- `GET /api/v1/production-orders/{id}/fg-sessions` — Get FG confirmation sessions
- `GET /api/v1/production-orders/{id}/variance` — Get production variance report

#### Batch Runs (NEW)
- `GET /api/v1/batch-runs` — List batch runs with filtering
- `POST /api/v1/batch-runs` — Create batch run under production order
- `GET /api/v1/batch-runs/{id}` — Get batch run details
- `PATCH /api/v1/batch-runs/{id}/start` — Start batch run (PENDING → MIR_RAISED → IN_PROGRESS)
- `PATCH /api/v1/batch-runs/{id}/complete` — Complete batch run (IN_PROGRESS → COMPLETED)
- `GET /api/v1/batch-runs/{id}/materials` — Get required materials for batch run
- `GET /api/v1/batch-runs/{id}/mir` — Get associated MIR

#### Material Issue Requests (MIR)
- `GET /api/v1/material-issue-requests` — List MIRs with filtering
- `GET /api/v1/material-issue-requests/{id}` — Get MIR details
- `GET /api/v1/material-issue-requests/{id}/lines` — Get all MIR line items
- `PATCH /api/v1/material-issue-requests/{id}/approve` — Approve MIR (PENDING → APPROVED)
- `PATCH /api/v1/material-issue-requests/{id}/reject` — Reject MIR (PENDING → REJECTED)

#### MIR Line Items (NEW)
- `GET /api/v1/mir-lines/{id}` — Get line item details
- `PATCH /api/v1/mir-lines/{id}/approve` — Approve line (PENDING → APPROVED)
- `PATCH /api/v1/mir-lines/{id}/reject` — Reject line (PENDING → REJECTED)
- `POST /api/v1/mir-lines/{id}/issue` — Issue material (partial or full)

#### Production Floor Receiving (NEW)
- `GET /api/v1/batch-runs/{batchRunId}/receiving` — Get receiving status
- `PATCH /api/v1/batch-runs/{batchRunId}/receiving/confirm` — Confirm receipt (PENDING_RECEIPT → RECEIVED)

#### Finished Goods Receipt (NEW)
- `POST /api/v1/fg-receipts` — Create FG receipt for completed batch run
- `GET /api/v1/fg-receipts/{id}` — Get FG receipt details

#### Packing Orders
- Kept existing endpoints (no changes)

### 2. Web Routes (`routes/web.php`)

#### Production Portal
- `/org/{org_slug}/production/dashboard` — Production dashboard
- `/org/{org_slug}/production/orders` — List production orders
- `/org/{org_slug}/production/orders/{id}` — View production order details (NEW)
- `/org/{org_slug}/production/batch-runs` — List batch runs (NEW)
- `/org/{org_slug}/production/batch-runs/{id}` — View batch run details (NEW)
- `/org/{org_slug}/production/batch-runs/{id}/receiving` — Production floor receiving (NEW)
- `/org/{org_slug}/production/material-issue-requests` — List MIRs (NEW)
- `/org/{org_slug}/production/material-issue-requests/{id}` — View MIR details (NEW)
- `/org/{org_slug}/production/packing` — Packing orders
- `/org/{org_slug}/production/fg-confirmation` — FG confirmation
- `/org/{org_slug}/production/mir` — MIR index (legacy, kept for compatibility)

#### Warehouse Portal
- `/org/{org_slug}/warehouse/mir` — List MIRs (warehouse view)
- `/org/{org_slug}/warehouse/mir/{id}` — View MIR details (warehouse view)

## Key Features Implemented

### 1. Batch Runs
- Independent execution units per production order
- User-defined `run_qty` (flexible, not tied to reference batch size)
- Automatic MIR generation when batch run moves to IN_PROGRESS
- Status tracking: PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED

### 2. Two-Level MIR Status Tracking
- **Line Level**: Individual material tracking (PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED → REJECTED)
- **Header Level**: Derived automatically from line statuses
  - PENDING: All lines PENDING
  - APPROVED: All lines APPROVED
  - PARTIALLY_ISSUED: Mix of states, at least one picked
  - FULLY_ISSUED: All lines FULLY_PICKED
  - REJECTED: One or more lines REJECTED
  - CLOSED: Production confirms receipt

### 3. Partial Issuance
- Store can issue materials in multiple transactions per line
- Each transaction logged in `mir_issue_transactions` table
- Line status updates automatically based on cumulative issued qty

### 4. Production Floor Receiving Gate
- Hard gate: Batch run cannot start until `receiving_status = RECEIVED`
- Prevents false starts and mid-run material shortages
- Provides traceability checkpoint with confirmed receipt timestamp
- Supports quantity discrepancy notes (non-blocking)

### 5. Finished Goods Receipt
- Records actual production output per batch run
- Calculates yield: `yield_actual_pct = accepted_qty / planned_qty × 100`
- Unique lot number per receipt for traceability
- Tracks variance for management reporting

## Status Flow Diagrams

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
                                REJECTED (if any line rejected)
```

### MIR Line
```
PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED
   ↓                                          ↑
   └──────────────────────────────────────────┘
                    (multiple issues)

PENDING → REJECTED (if store rejects)
```

### Production Floor Receiving
```
PENDING_RECEIPT → RECEIVED (unlocks batch run to start)
```

## Database Fields Added/Modified

### production_batch_runs
- `receiving_status`: PENDING_RECEIPT / RECEIVED
- `received_at`: Timestamp when operator confirms
- `received_by`: FK to users
- `receiving_notes`: Discrepancy notes

### material_issue_requests
- `status`: Now includes PARTIALLY_ISSUED, FULLY_ISSUED, CLOSED
- `fully_issued_at`: Timestamp when all lines FULLY_PICKED
- `closed_at`: Timestamp when production confirms receipt

### mir_line_items
- `status`: PENDING / APPROVED / PARTIALLY_PICKED / FULLY_PICKED / REJECTED
- `last_issued_at`: Timestamp of most recent issue
- `rejected_reason`: NULLABLE

### mir_issue_transactions (NEW TABLE)
- Audit trail for every pick transaction
- Tracks: mir_line_id, issued_qty, issued_by, issued_at, notes

## Business Rules Enforced

1. **Hard Gate**: Production cannot start until `receiving_status = RECEIVED`
2. **Partial Issuance**: Store can issue in multiple transactions per line
3. **Line Independence**: Each material line tracked independently
4. **Header Derivation**: MIR header status never set manually
5. **Rejection Blocking**: One rejected line blocks entire MIR
6. **Quantity Flexibility**: Batch run quantities can exceed/fall short of production order
7. **Audit Trail**: Every pick transaction logged
8. **Discrepancy Handling**: Non-blocking but logged for investigation

## Controllers Required

The following controllers need to be created/updated:

1. **ProductionOrderController** — Updated with release/close endpoints
2. **BatchRunController** (NEW) — Manage batch runs
3. **MaterialIssueRequestController** — Updated with approve/reject endpoints
4. **MIRLineController** (NEW) — Manage individual MIR lines
5. **BatchRunReceivingController** (NEW) — Handle production floor receiving
6. **FGReceiptController** (NEW) — Handle finished goods receipts

## Migration Requirements

The following database migrations are needed:

1. Add `receiving_status`, `received_at`, `received_by`, `receiving_notes` to `production_batch_runs`
2. Update `material_issue_requests` status enum to include PARTIALLY_ISSUED, FULLY_ISSUED, CLOSED
3. Add `fully_issued_at`, `closed_at` to `material_issue_requests`
4. Update `mir_line_items` status enum and add `last_issued_at`, `rejected_reason`
5. Create `mir_issue_transactions` table for audit trail

## Testing Scenarios

### Happy Path
1. Create production order (DRAFT)
2. Release production order (RELEASED)
3. Create batch run (PENDING)
4. MIR auto-generated (PENDING)
5. Store approves all lines (APPROVED)
6. Store issues all materials (FULLY_PICKED → FULLY_ISSUED)
7. Production confirms receipt (RECEIVED)
8. Start batch run (IN_PROGRESS)
9. Complete batch run (COMPLETED)
10. Record FG receipt

### Partial Issuance
1. Store approves line
2. Store issues 50% of required qty (PARTIALLY_PICKED)
3. Store issues remaining 50% (FULLY_PICKED)
4. MIR header updates to FULLY_ISSUED

### Rejection Scenario
1. Store rejects one line (REJECTED)
2. MIR header becomes REJECTED
3. New substitute line raised
4. Process continues

### Receiving Discrepancy
1. MIR FULLY_ISSUED
2. Production confirms receipt with discrepancy notes
3. Batch run proceeds (non-blocking)
4. Discrepancy logged for investigation

## Backward Compatibility

- Existing production order endpoints remain functional
- Legacy MIR routes kept for compatibility
- New batch run and receiving endpoints are additive
- No breaking changes to existing API contracts
