# Production & MIR Workflow Diagrams

## 1. Complete Production Workflow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PRODUCTION ORDER WORKFLOW                             │
└─────────────────────────────────────────────────────────────────────────┘

1. CREATE PRODUCTION ORDER
   ├─ Product: Chilli Powder
   ├─ BOM: Recipe v1.0
   ├─ Production Qty: 100 units
   └─ Status: DRAFT

2. RELEASE PRODUCTION ORDER
   └─ Status: RELEASED

3. CREATE BATCH RUN #1
   ├─ Run Qty: 50 units
   ├─ Status: PENDING
   └─ MIR Auto-Generated (Status: PENDING)

4. CREATE BATCH RUN #2
   ├─ Run Qty: 50 units
   ├─ Status: PENDING
   └─ MIR Auto-Generated (Status: PENDING)

5. STORE PROCESSES BATCH RUN #1 MIR
   ├─ Approve all lines (Status: APPROVED)
   ├─ Issue materials (Status: FULLY_PICKED)
   └─ MIR Header Status: FULLY_ISSUED

6. PRODUCTION CONFIRMS RECEIPT (BATCH RUN #1)
   ├─ Receiving Status: RECEIVED
   └─ MIR Header Status: CLOSED

7. START BATCH RUN #1
   ├─ Status: IN_PROGRESS
   ├─ Actual Start: 2026-04-20 08:00:00
   └─ Production begins

8. COMPLETE BATCH RUN #1
   ├─ Status: COMPLETED
   ├─ Actual End: 2026-04-20 12:00:00
   └─ Production finished

9. RECORD FINISHED GOODS (BATCH RUN #1)
   ├─ Received Qty: 49 units
   ├─ Rejected Qty: 1 unit
   ├─ Accepted Qty: 48 units
   ├─ Yield: 96%
   └─ Lot Number: LOT-2026-04-001

10. REPEAT STEPS 5-9 FOR BATCH RUN #2

11. CLOSE PRODUCTION ORDER
    └─ Status: CLOSED
```

## 2. MIR Status Derivation Logic

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    MIR HEADER STATUS DERIVATION                          │
└─────────────────────────────────────────────────────────────────────────┘

MIR Line Statuses:
├─ Line 1 (Chilli Powder): PENDING
├─ Line 2 (Turmeric): PENDING
└─ Line 3 (Salt): PENDING
   → MIR Header Status: PENDING

MIR Line Statuses:
├─ Line 1 (Chilli Powder): APPROVED
├─ Line 2 (Turmeric): APPROVED
└─ Line 3 (Salt): APPROVED
   → MIR Header Status: APPROVED

MIR Line Statuses:
├─ Line 1 (Chilli Powder): FULLY_PICKED
├─ Line 2 (Turmeric): APPROVED
└─ Line 3 (Salt): APPROVED
   → MIR Header Status: PARTIALLY_ISSUED

MIR Line Statuses:
├─ Line 1 (Chilli Powder): FULLY_PICKED
├─ Line 2 (Turmeric): FULLY_PICKED
└─ Line 3 (Salt): FULLY_PICKED
   → MIR Header Status: FULLY_ISSUED

MIR Line Statuses:
├─ Line 1 (Chilli Powder): FULLY_PICKED
├─ Line 2 (Turmeric): REJECTED
└─ Line 3 (Salt): APPROVED
   → MIR Header Status: REJECTED (blocks all other lines)
```

## 3. Partial Issuance Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PARTIAL ISSUANCE FLOW                                 │
└─────────────────────────────────────────────────────────────────────────┘

MIR Line: Chilli Powder
├─ Required Qty: 10.0 kg
├─ Status: APPROVED
└─ Issued Qty: 0.0 kg

TRANSACTION 1: Issue 5.0 kg
├─ Issued Qty: 5.0 kg
├─ Cumulative: 5.0 kg
├─ Status: PARTIALLY_PICKED
└─ Transaction logged in mir_issue_transactions

TRANSACTION 2: Issue 3.0 kg
├─ Issued Qty: 3.0 kg
├─ Cumulative: 8.0 kg
├─ Status: PARTIALLY_PICKED
└─ Transaction logged in mir_issue_transactions

TRANSACTION 3: Issue 2.0 kg
├─ Issued Qty: 2.0 kg
├─ Cumulative: 10.0 kg (= Required Qty)
├─ Status: FULLY_PICKED
└─ Transaction logged in mir_issue_transactions

Audit Trail:
├─ Transaction 1: 5.0 kg @ 08:00 by Store Keeper A
├─ Transaction 2: 3.0 kg @ 09:30 by Store Keeper B
└─ Transaction 3: 2.0 kg @ 10:15 by Store Keeper A
```

## 4. Production Floor Receiving Gate

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PRODUCTION FLOOR RECEIVING GATE                       │
└─────────────────────────────────────────────────────────────────────────┘

SCENARIO 1: Normal Flow
┌─────────────────────────────────────────────────────────────────────────┐
│ MIR Status: FULLY_ISSUED                                                │
│ Batch Run Receiving Status: PENDING_RECEIPT                             │
│ Batch Run Status: PENDING                                               │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
                    Production Operator Confirms Receipt
                    (All materials at workstation)
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ MIR Status: CLOSED                                                      │
│ Batch Run Receiving Status: RECEIVED                                    │
│ Batch Run Status: PENDING (now unlocked to start)                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
                    Batch Run Can Now Start
                    (PATCH /batch-runs/{id}/start)
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ Batch Run Status: IN_PROGRESS                                           │
│ Actual Start: 2026-04-20 08:00:00                                       │
└─────────────────────────────────────────────────────────────────────────┘

SCENARIO 2: Receiving Discrepancy
┌─────────────────────────────────────────────────────────────────────────┐
│ MIR Status: FULLY_ISSUED                                                │
│ Batch Run Receiving Status: PENDING_RECEIPT                             │
│ Expected: Chilli Powder 10.0 kg                                         │
│ Actual: Chilli Powder 9.8 kg (0.2 kg short)                             │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
                    Production Operator Confirms Receipt
                    (Notes discrepancy: "0.2 kg short")
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ MIR Status: CLOSED                                                      │
│ Batch Run Receiving Status: RECEIVED                                    │
│ Receiving Notes: "0.2 kg short on Chilli Powder"                        │
│ Batch Run Status: PENDING (unlocked to start)                           │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
                    Batch Run Proceeds (Non-Blocking)
                    Discrepancy Logged for Investigation
```

## 5. Rejection Scenario

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    REJECTION SCENARIO                                    │
└─────────────────────────────────────────────────────────────────────────┘

MIR Line Statuses:
├─ Line 1 (Chilli Powder): APPROVED
├─ Line 2 (Turmeric): APPROVED
└─ Line 3 (Salt): APPROVED
   → MIR Header Status: APPROVED

Store Rejects Line 2 (Turmeric)
├─ Reason: "Quality hold on batch #XYZ"
├─ Line 2 Status: REJECTED
└─ MIR Header Status: REJECTED (BLOCKS ALL OTHER LINES)

Result:
├─ Line 1 (Chilli Powder): APPROVED (cannot proceed)
├─ Line 2 (Turmeric): REJECTED (blocked)
└─ Line 3 (Salt): APPROVED (cannot proceed)

Resolution Options:
1. Raise New Substitute MIR Line for Turmeric
   ├─ New Line 2: PENDING
   ├─ Store Approves: APPROVED
   ├─ Store Issues: FULLY_PICKED
   └─ MIR Header Status: FULLY_ISSUED (unblocked)

2. Or: Reject Entire MIR and Raise New MIR
   ├─ Original MIR: REJECTED
   └─ New MIR: PENDING (start over)
```

## 6. Batch Run Lifecycle

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    BATCH RUN LIFECYCLE                                   │
└─────────────────────────────────────────────────────────────────────────┘

CREATE BATCH RUN
├─ Status: PENDING
├─ Receiving Status: PENDING_RECEIPT
├─ MIR Auto-Generated: PENDING
└─ Timestamp: created_at

STORE PROCESSES MIR
├─ Approve Lines: MIR Status → APPROVED
├─ Issue Materials: MIR Status → FULLY_ISSUED
└─ Timestamp: fully_issued_at

PRODUCTION CONFIRMS RECEIPT
├─ Receiving Status: RECEIVED
├─ MIR Status: CLOSED
├─ Timestamp: received_at
└─ User: received_by

START BATCH RUN
├─ Status: MIR_RAISED → IN_PROGRESS
├─ Timestamp: actual_start_at
└─ Preconditions: MIR FULLY_ISSUED + Receiving RECEIVED

PRODUCTION EXECUTION
├─ Status: IN_PROGRESS
├─ Duration: actual_start_at to actual_end_at
└─ Materials Consumed: tracked in batch_run_materials

COMPLETE BATCH RUN
├─ Status: COMPLETED
├─ Timestamp: actual_end_at
└─ Ready for FG Receipt

RECORD FINISHED GOODS
├─ Received Qty: 49 units
├─ Rejected Qty: 1 unit
├─ Accepted Qty: 48 units
├─ Yield: 96%
└─ Lot Number: LOT-2026-04-001
```

## 7. API Call Sequence Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    API CALL SEQUENCE                                     │
└─────────────────────────────────────────────────────────────────────────┘

Production Team:
1. POST /api/v1/production-orders
   └─ Create PO (Status: DRAFT)

2. PATCH /api/v1/production-orders/{id}/release
   └─ Release PO (Status: RELEASED)

3. POST /api/v1/batch-runs
   └─ Create Batch Run (Status: PENDING)
   └─ System auto-generates MIR (Status: PENDING)

Store Team:
4. GET /api/v1/material-issue-requests/{id}
   └─ View MIR details

5. PATCH /api/v1/mir-lines/{id}/approve
   └─ Approve each line (Status: APPROVED)

6. POST /api/v1/mir-lines/{id}/issue
   └─ Issue material (Status: PARTIALLY_PICKED or FULLY_PICKED)
   └─ Repeat until all lines FULLY_PICKED
   └─ MIR Header Status: FULLY_ISSUED

Production Team:
7. GET /api/v1/batch-runs/{id}/receiving
   └─ Check receiving status

8. PATCH /api/v1/batch-runs/{id}/receiving/confirm
   └─ Confirm receipt (Receiving Status: RECEIVED)
   └─ MIR Status: CLOSED

9. PATCH /api/v1/batch-runs/{id}/start
   └─ Start batch run (Status: IN_PROGRESS)

10. PATCH /api/v1/batch-runs/{id}/complete
    └─ Complete batch run (Status: COMPLETED)

11. POST /api/v1/fg-receipts
    └─ Record FG receipt (Yield calculated)
```

## 8. Status Transition Matrix

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    STATUS TRANSITION MATRIX                              │
└─────────────────────────────────────────────────────────────────────────┘

PRODUCTION ORDER:
┌──────────┬──────────┬──────────────┬────────┐
│ Current  │ Next     │ Trigger      │ Action │
├──────────┼──────────┼──────────────┼────────┤
│ DRAFT    │ RELEASED │ release()    │ User   │
│ RELEASED │ IN_PROG  │ batch start  │ System │
│ IN_PROG  │ CLOSED   │ close()      │ User   │
└──────────┴──────────┴──────────────┴────────┘

BATCH RUN:
┌──────────┬──────────┬──────────────┬────────┐
│ PENDING  │ MIR_RAIS │ MIR created  │ System │
│ MIR_RAIS │ IN_PROG  │ start()      │ User   │
│ IN_PROG  │ COMPLETE │ complete()   │ User   │
└──────────┴──────────┴──────────────┴────────┘

MIR HEADER (Auto-Derived):
┌──────────┬──────────┬──────────────┬────────┐
│ PENDING  │ APPROVED │ All lines OK │ System │
│ APPROVED │ PART_ISS │ Issue starts │ System │
│ PART_ISS │ FULL_ISS │ All picked   │ System │
│ FULL_ISS │ CLOSED   │ Receipt conf │ System │
│ PENDING  │ REJECTED │ Line reject  │ System │
└──────────┴──────────┴──────────────┴────────┘

MIR LINE:
┌──────────┬──────────┬──────────────┬────────┐
│ PENDING  │ APPROVED │ approve()    │ User   │
│ APPROVED │ PART_PIC │ issue() <100%│ User   │
│ PART_PIC │ PART_PIC │ issue() <100%│ User   │
│ PART_PIC │ FULL_PIC │ issue() =100%│ User   │
│ PENDING  │ REJECTED │ reject()     │ User   │
└──────────┴──────────┴──────────────┴────────┘

RECEIVING:
┌──────────┬──────────┬──────────────┬────────┐
│ PENDING  │ RECEIVED │ confirm()    │ User   │
└──────────┴──────────┴──────────────┴────────┘
```

## 9. Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DATA FLOW DIAGRAM                                     │
└─────────────────────────────────────────────────────────────────────────┘

Production Order
    ├─ product_id
    ├─ bom_id
    ├─ production_qty: 100
    └─ status: DRAFT → RELEASED → IN_PROGRESS → CLOSED
         ↓
    Batch Run #1
    ├─ production_order_id
    ├─ run_qty: 50
    ├─ status: PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED
    ├─ receiving_status: PENDING_RECEIPT → RECEIVED
    └─ actual_start_at, actual_end_at
         ↓
    Material Issue Request
    ├─ batch_run_id
    ├─ status: PENDING → APPROVED → PARTIALLY_ISSUED → FULLY_ISSUED → CLOSED
    ├─ fully_issued_at, closed_at
    └─ lines: [MIR Line 1, MIR Line 2, MIR Line 3]
         ↓
    MIR Line 1 (Chilli Powder)
    ├─ material_id
    ├─ required_qty: 10.0 kg
    ├─ issued_qty: 0.0 → 5.0 → 10.0 kg
    ├─ status: PENDING → APPROVED → PARTIALLY_PICKED → FULLY_PICKED
    └─ transactions: [Transaction 1, Transaction 2]
         ↓
    MIR Issue Transaction 1
    ├─ issued_qty: 5.0 kg
    ├─ issued_by: Store Keeper A
    ├─ issued_at: 2026-04-20 08:00:00
    └─ notes: "From bin B-123"
         ↓
    MIR Issue Transaction 2
    ├─ issued_qty: 5.0 kg
    ├─ issued_by: Store Keeper B
    ├─ issued_at: 2026-04-20 09:30:00
    └─ notes: "From bin B-124"
         ↓
    Finished Goods Receipt
    ├─ batch_run_id
    ├─ planned_qty: 50
    ├─ received_qty: 49
    ├─ rejected_qty: 1
    ├─ accepted_qty: 48
    ├─ yield_actual_pct: 96%
    └─ lot_number: LOT-2026-04-001
```

## 10. Error Handling Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ERROR HANDLING FLOW                                   │
└─────────────────────────────────────────────────────────────────────────┘

ATTEMPT: Start Batch Run
    ├─ Check: Status = PENDING? ✓
    ├─ Check: MIR Status = FULLY_ISSUED? ✗
    └─ Error 422: "MIR not fully issued"
         ↓
    Resolution: Issue all materials first

ATTEMPT: Issue Material
    ├─ Check: Line Status = APPROVED or PARTIALLY_PICKED? ✓
    ├─ Check: Issued Qty > 0? ✓
    ├─ Check: Issued Qty <= Remaining? ✗
    └─ Error 422: "Issued qty exceeds remaining"
         ↓
    Resolution: Reduce issued qty

ATTEMPT: Confirm Receipt
    ├─ Check: MIR Status = FULLY_ISSUED? ✗
    └─ Error 422: "MIR not fully issued"
         ↓
    Resolution: Ensure all lines FULLY_PICKED

ATTEMPT: Approve MIR
    ├─ Check: Status = PENDING? ✓
    ├─ Check: All Lines = APPROVED? ✗
    └─ Error 422: "Not all lines approved"
         ↓
    Resolution: Approve all lines first
```
