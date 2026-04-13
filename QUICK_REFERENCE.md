# Production & MIR Quick Reference

## API Endpoints Summary

### Production Orders
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/production-orders` | List all production orders |
| POST | `/api/v1/production-orders` | Create new production order |
| GET | `/api/v1/production-orders/{id}` | Get production order details |
| PATCH | `/api/v1/production-orders/{id}/release` | Release order (DRAFT → RELEASED) |
| PATCH | `/api/v1/production-orders/{id}/close` | Close order (IN_PROGRESS → CLOSED) |

### Batch Runs
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/batch-runs` | List batch runs |
| POST | `/api/v1/batch-runs` | Create batch run (auto-generates MIR) |
| GET | `/api/v1/batch-runs/{id}` | Get batch run details |
| GET | `/api/v1/batch-runs/{id}/materials` | Get required materials |
| GET | `/api/v1/batch-runs/{id}/mir` | Get associated MIR |
| PATCH | `/api/v1/batch-runs/{id}/start` | Start batch run (PENDING → IN_PROGRESS) |
| PATCH | `/api/v1/batch-runs/{id}/complete` | Complete batch run (IN_PROGRESS → COMPLETED) |

### Material Issue Requests
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/material-issue-requests` | List MIRs |
| GET | `/api/v1/material-issue-requests/{id}` | Get MIR details |
| GET | `/api/v1/material-issue-requests/{id}/lines` | Get all MIR lines |
| PATCH | `/api/v1/material-issue-requests/{id}/approve` | Approve MIR (PENDING → APPROVED) |
| PATCH | `/api/v1/material-issue-requests/{id}/reject` | Reject MIR (PENDING → REJECTED) |

### MIR Lines
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/mir-lines/{id}` | Get line details |
| PATCH | `/api/v1/mir-lines/{id}/approve` | Approve line (PENDING → APPROVED) |
| PATCH | `/api/v1/mir-lines/{id}/reject` | Reject line (PENDING → REJECTED) |
| POST | `/api/v1/mir-lines/{id}/issue` | Issue material (partial or full) |

### Production Floor Receiving
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/batch-runs/{batchRunId}/receiving` | Get receiving status |
| PATCH | `/api/v1/batch-runs/{batchRunId}/receiving/confirm` | Confirm receipt (PENDING_RECEIPT → RECEIVED) |

### Finished Goods
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/v1/fg-receipts` | Create FG receipt |
| GET | `/api/v1/fg-receipts/{id}` | Get FG receipt details |

---

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

---

## Key Workflows

### 1. Create & Release Production Order
```bash
# Create
POST /api/v1/production-orders
{
  "product_id": 1,
  "bom_id": 1,
  "production_qty": 100,
  "planned_date": "2026-04-20"
}

# Release
PATCH /api/v1/production-orders/1/release
```

### 2. Create Batch Run (Auto-Generates MIR)
```bash
POST /api/v1/batch-runs
{
  "production_order_id": 1,
  "run_qty": 50,
  "planned_date": "2026-04-20"
}
# System auto-generates MIR with status PENDING
```

### 3. Store Approves & Issues Materials
```bash
# Approve line
PATCH /api/v1/mir-lines/1/approve

# Issue partial qty
POST /api/v1/mir-lines/1/issue
{
  "issued_qty": 5.0,
  "notes": "From bin B-123"
}

# Issue remaining qty
POST /api/v1/mir-lines/1/issue
{
  "issued_qty": 5.0,
  "notes": "From bin B-124"
}
# Line status → FULLY_PICKED
# MIR header status → FULLY_ISSUED (if all lines done)
```

### 4. Production Confirms Receipt
```bash
PATCH /api/v1/batch-runs/1/receiving/confirm
{
  "receiving_notes": "All materials received in good condition"
}
# Batch run receiving_status → RECEIVED
# MIR header status → CLOSED
```

### 5. Start & Complete Batch Run
```bash
# Start (only if MIR FULLY_ISSUED and receiving RECEIVED)
PATCH /api/v1/batch-runs/1/start
# Status → IN_PROGRESS, actual_start_at recorded

# Complete
PATCH /api/v1/batch-runs/1/complete
# Status → COMPLETED, actual_end_at recorded
```

### 6. Record Finished Goods
```bash
POST /api/v1/fg-receipts
{
  "batch_run_id": 1,
  "received_qty": 49,
  "rejected_qty": 1,
  "lot_number": "LOT-2026-04-001"
}
# accepted_qty = 48, yield_actual_pct = 96%
```

---

## Common Error Scenarios

### Error: Cannot Start Batch Run
```
Status 422: "Cannot start batch run. MIR status is PARTIALLY_ISSUED, not FULLY_ISSUED"
→ Solution: Ensure all MIR lines are FULLY_PICKED

Status 422: "Cannot start batch run. Production floor receiving not confirmed"
→ Solution: Confirm receipt at production floor first
```

### Error: Cannot Issue Material
```
Status 422: "Line not ready for issue"
→ Solution: Line must be APPROVED or PARTIALLY_PICKED

Status 422: "Issued qty exceeds remaining"
→ Solution: Reduce issued_qty to not exceed required_qty - issued_qty
```

### Error: Cannot Confirm Receipt
```
Status 422: "MIR not fully issued"
→ Solution: All MIR lines must be FULLY_PICKED first

Status 422: "Invalid receiving status"
→ Solution: Receiving status must be PENDING_RECEIPT
```

---

## Database Tables

### Key Tables
- `production_orders` — Production order header
- `production_batch_runs` — Batch run execution units
- `material_issue_requests` — MIR header
- `mir_line_items` — Individual material lines
- `mir_issue_transactions` — Audit trail of pick transactions
- `fg_receipts` — Finished goods receipts

### Key Fields
- `production_batch_runs.receiving_status` — PENDING_RECEIPT / RECEIVED
- `material_issue_requests.status` — PENDING / APPROVED / PARTIALLY_ISSUED / FULLY_ISSUED / REJECTED / CLOSED
- `mir_line_items.status` — PENDING / APPROVED / PARTIALLY_PICKED / FULLY_PICKED / REJECTED
- `mir_issue_transactions.issued_qty` — Qty issued in each transaction

---

## Business Rules

1. **Hard Gate**: Batch run cannot start until `receiving_status = RECEIVED`
2. **Partial Issuance**: Store can issue in multiple transactions per line
3. **Line Independence**: Each material line tracked independently
4. **Header Derivation**: MIR header status auto-derived from line statuses
5. **Rejection Blocking**: One rejected line blocks entire MIR
6. **Quantity Flexibility**: Batch run quantities can exceed/fall short of production order
7. **Audit Trail**: Every pick transaction logged
8. **Discrepancy Handling**: Non-blocking but logged

---

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

---

## Controllers to Implement

1. **BatchRunController** — Manage batch runs
2. **MIRLineController** — Manage individual MIR lines
3. **BatchRunReceivingController** — Handle production floor receiving
4. **FGReceiptController** — Handle finished goods receipts
5. **ProductionOrderController** — Update with release/close endpoints
6. **MaterialIssueRequestController** — Update with approve/reject endpoints

---

## Testing Checklist

- [ ] Create production order
- [ ] Release production order
- [ ] Create batch run (verify MIR auto-generated)
- [ ] Approve MIR lines
- [ ] Issue materials (partial and full)
- [ ] Verify MIR header status updates
- [ ] Confirm production floor receipt
- [ ] Start batch run
- [ ] Complete batch run
- [ ] Create FG receipt
- [ ] Verify yield calculation
- [ ] Test error scenarios

---

## Performance Considerations

- Index on `batch_run_id` in `material_issue_requests`
- Index on `mir_id` in `mir_line_items`
- Index on `mir_line_id` in `mir_issue_transactions`
- Cache MIR header status derivation if needed
- Batch operations for multiple line approvals

---

## Monitoring & Alerts

- Monitor batch run start failures (receiving not confirmed)
- Monitor MIR rejection rates
- Monitor partial issuance patterns
- Monitor production floor receiving discrepancies
- Monitor FG yield trends

---

## Future Enhancements

- Batch approval of multiple MIR lines
- Automatic MIR line substitution
- Material shortage notifications
- Production floor receiving mobile app
- Advanced yield analytics
- Predictive material requirements
