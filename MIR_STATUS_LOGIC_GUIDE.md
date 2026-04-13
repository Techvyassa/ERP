# MIR Status Logic Guide - Corrected

## Overview

This document clarifies the Material Issue Request (MIR) status logic, particularly the distinction between **APPROVED** and **PARTIALLY_ISSUED** statuses.

## Key Issue Fixed

**Problem**: When all lines are approved but NO items have been issued yet, the MIR header status should be **APPROVED**, not **PARTIALLY_ISSUED**.

**Solution**: The status derivation logic now correctly checks if items have been issued before transitioning to PARTIALLY_ISSUED.

---

## MIR Header Status Definitions

### PENDING
- **Condition**: All lines are PENDING
- **Meaning**: MIR has been raised and sent to Store. Store has not yet acted on any line.
- **Next Action**: Store reviews and approves lines

### APPROVED ✓ (FIXED)
- **Condition**: All lines are APPROVED AND no items have been issued yet (issued_qty = 0 for all lines)
- **Meaning**: Store has confirmed availability for all materials, but hasn't started picking yet
- **Key Point**: This is the state AFTER all lines are individually approved but BEFORE any picking begins
- **Next Action**: Store starts issuing materials

### PARTIALLY_ISSUED
- **Condition**: At least one line has items issued (issued_qty > 0), but not all lines are FULLY_PICKED
- **Meaning**: Store has started picking materials. Some lines may be:
  - PARTIALLY_PICKED (0 < issued_qty < required_qty)
  - FULLY_PICKED (issued_qty = required_qty)
  - APPROVED (issued_qty = 0, but other lines have items)
- **Next Action**: Store continues issuing remaining materials

### FULLY_ISSUED
- **Condition**: All lines are FULLY_PICKED (issued_qty = required_qty for all lines)
- **Meaning**: All required materials have been issued by Store
- **Next Action**: Production floor receiving step

### REJECTED
- **Condition**: One or more lines have been REJECTED
- **Meaning**: Store has rejected one or more materials (e.g., stock unavailable, quality hold)
- **Impact**: Blocks the entire MIR — no other lines can proceed
- **Next Action**: Resolve rejection by raising substitute lines or new MIR

### CLOSED
- **Condition**: Production has confirmed receipt of all materials at the workstation
- **Meaning**: Materials have been physically received and verified at production floor
- **Next Action**: Batch run can now start

---

## Status Derivation Algorithm

```php
public function deriveHeaderStatus(): string
{
    $lines = $this->lines()->get();

    if ($lines->isEmpty()) {
        return 'PENDING';
    }

    $statuses = $lines->pluck('status')->toArray();
    $uniqueStatuses = array_unique($statuses);

    // Rule 1: If any line is REJECTED, header is REJECTED
    if (in_array('REJECTED', $statuses)) {
        return 'REJECTED';
    }

    // Rule 2: If all lines are PENDING
    if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'PENDING') {
        return 'PENDING';
    }

    // Rule 3: If all lines are APPROVED (none picked yet)
    if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'APPROVED') {
        return 'APPROVED';  // ✓ FIXED: This is the key fix
    }

    // Rule 4: If all lines are FULLY_PICKED
    if (count($uniqueStatuses) === 1 && $uniqueStatuses[0] === 'FULLY_PICKED') {
        return 'FULLY_ISSUED';
    }

    // Rule 5: If at least one line is PARTIALLY_PICKED or FULLY_PICKED, but not all FULLY_PICKED
    $pickedCount = $lines->whereIn('status', ['PARTIALLY_PICKED', 'FULLY_PICKED'])->count();
    if ($pickedCount > 0 && $pickedCount < $lines->count()) {
        return 'PARTIALLY_ISSUED';
    }

    // Rule 6: If all lines are FULLY_PICKED (double check)
    if ($lines->where('status', 'FULLY_PICKED')->count() === $lines->count()) {
        return 'FULLY_ISSUED';
    }

    return 'PARTIALLY_ISSUED';
}
```

---

## MIR Line Status Definitions

### PENDING
- Store has not yet acted on this line
- **Transitions to**: APPROVED or REJECTED

### APPROVED
- Store has confirmed availability and approved this material line for issue
- **Condition**: issued_qty = 0 (no items issued yet)
- **Transitions to**: PARTIALLY_PICKED (when issued_qty > 0) or FULLY_PICKED (when issued_qty = required_qty)

### PARTIALLY_PICKED
- Store has issued some quantity but not the full required_qty
- **Condition**: 0 < issued_qty < required_qty
- **Transitions to**: PARTIALLY_PICKED (more partial issues) or FULLY_PICKED (remaining qty issued)

### FULLY_PICKED
- Store has issued the complete required_qty for this line
- **Condition**: issued_qty = required_qty
- **Transitions to**: None (line is closed)

### REJECTED
- Store has rejected this material line (e.g., stock unavailable, quality hold)
- **Transitions to**: PENDING (if new substitute line raised)

---

## Line Status Update Logic

```php
public function updateStatus(): void
{
    if ($this->status === 'REJECTED') {
        return; // Don't change rejected status
    }

    if ($this->issued_qty <= 0) {
        // If no qty issued yet, keep as APPROVED (if it was approved)
        if ($this->status !== 'PENDING') {
            $this->status = 'APPROVED';
        }
    } elseif ($this->issued_qty >= $this->required_qty) {
        // Full quantity issued
        $this->status = 'FULLY_PICKED';
    } else {
        // Partial quantity issued
        $this->status = 'PARTIALLY_PICKED';
    }

    $this->last_issued_at = now();
    $this->save();
}
```

---

## Example Workflow — 3 Material Lines

### Step 1: MIR Raised
```
Line 1 (Chilli): PENDING, issued_qty = 0
Line 2 (Turmeric): PENDING, issued_qty = 0
Line 3 (Salt): PENDING, issued_qty = 0
→ MIR Header Status: PENDING
```

### Step 2: Store Approves All Lines
```
Line 1 (Chilli): APPROVED, issued_qty = 0
Line 2 (Turmeric): APPROVED, issued_qty = 0
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: APPROVED ✓ (KEY FIX: All approved, no items issued)
```

### Step 3: Store Issues Chilli Partially
```
Line 1 (Chilli): PARTIALLY_PICKED, issued_qty = 5.0 (required = 10.0)
Line 2 (Turmeric): APPROVED, issued_qty = 0
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: PARTIALLY_ISSUED (at least one line has items issued)
```

### Step 4: Store Fully Issues Chilli
```
Line 1 (Chilli): FULLY_PICKED, issued_qty = 10.0 (required = 10.0)
Line 2 (Turmeric): APPROVED, issued_qty = 0
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: PARTIALLY_ISSUED (not all lines fully picked)
```

### Step 5: Store Issues Turmeric & Salt Fully
```
Line 1 (Chilli): FULLY_PICKED, issued_qty = 10.0
Line 2 (Turmeric): FULLY_PICKED, issued_qty = 8.0
Line 3 (Salt): FULLY_PICKED, issued_qty = 5.0
→ MIR Header Status: FULLY_ISSUED (all lines fully picked)
```

### Step 6: Production Confirms Receipt
```
Line 1 (Chilli): FULLY_PICKED, issued_qty = 10.0
Line 2 (Turmeric): FULLY_PICKED, issued_qty = 8.0
Line 3 (Salt): FULLY_PICKED, issued_qty = 5.0
→ MIR Header Status: CLOSED (production confirmed receipt)
```

---

## Rejection Scenario

### Step 1: Store Rejects Turmeric
```
Line 1 (Chilli): APPROVED, issued_qty = 0
Line 2 (Turmeric): REJECTED, issued_qty = 0, reason = "Quality hold"
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: REJECTED (blocks all other lines)
```

### Step 2: Raise Substitute Turmeric Line
```
Line 1 (Chilli): APPROVED, issued_qty = 0
Line 2 (Turmeric - Original): REJECTED, issued_qty = 0
Line 2b (Turmeric - Substitute): PENDING, issued_qty = 0
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: REJECTED (original line still rejected)
```

### Step 3: Approve Substitute Line
```
Line 1 (Chilli): APPROVED, issued_qty = 0
Line 2 (Turmeric - Original): REJECTED, issued_qty = 0
Line 2b (Turmeric - Substitute): APPROVED, issued_qty = 0
Line 3 (Salt): APPROVED, issued_qty = 0
→ MIR Header Status: REJECTED (original line still blocks)
```

**Note**: The original rejected line must be removed or marked as superseded before the MIR can proceed.

---

## API Endpoints

### Approve MIR Line
```
PATCH /api/v1/mir-lines/{id}/approve
```
**Preconditions**:
- Line status must be PENDING
- Store confirms material availability

**Result**: Line status → APPROVED, issued_qty = 0

### Issue Material
```
POST /api/v1/mir-lines/{id}/issue
{
  "issued_qty": 5.0,
  "notes": "From bin B-123"
}
```
**Preconditions**:
- Line status must be APPROVED or PARTIALLY_PICKED
- issued_qty > 0 and <= remaining_qty

**Result**:
- Creates transaction record
- Updates line issued_qty
- Updates line status (PARTIALLY_PICKED or FULLY_PICKED)
- Recalculates MIR header status

### Approve MIR (Header)
```
PATCH /api/v1/material-issue-requests/{id}/approve
```
**Preconditions**:
- MIR status must be PENDING
- All lines must be APPROVED (issued_qty = 0 for all)

**Result**: MIR status → APPROVED

---

## Key Business Rules

1. **APPROVED Status**: All lines approved, NO items issued yet
   - This is a distinct state from PARTIALLY_ISSUED
   - Represents "ready to pick" state

2. **Partial Issuance**: Store can issue in multiple transactions
   - Each transaction creates a record in mir_issue_transactions
   - Line status updates automatically based on cumulative issued_qty

3. **Line Independence**: Each line tracked independently
   - One line can be FULLY_PICKED while another is APPROVED
   - Store can work on lines in any order

4. **Header Derivation**: Never set manually
   - Always calculated from line statuses
   - Updated automatically after each line change

5. **Rejection Blocking**: One rejected line blocks entire MIR
   - Must be resolved before proceeding
   - Substitute lines must be raised

6. **Audit Trail**: Every transaction logged
   - mir_issue_transactions table tracks all picks
   - Includes: qty, user, timestamp, notes

---

## Database Fields

### mir_line_items
- `status`: PENDING / APPROVED / PARTIALLY_PICKED / FULLY_PICKED / REJECTED
- `issued_qty`: Running total of issued quantity
- `required_qty`: Total quantity needed (locked at creation)
- `last_issued_at`: Timestamp of most recent issue
- `rejected_reason`: NULLABLE

### material_issue_requests
- `status`: PENDING / APPROVED / PARTIALLY_ISSUED / FULLY_ISSUED / REJECTED / CLOSED
- `fully_issued_at`: Timestamp when all lines FULLY_PICKED
- `closed_at`: Timestamp when production confirms receipt

### mir_issue_transactions
- `mir_line_id`: FK to mir_line_items
- `issued_qty`: Qty in this transaction
- `issued_by`: FK to users
- `issued_at`: Timestamp
- `notes`: NULLABLE

---

## Testing Scenarios

### Test 1: Approve All Lines (No Items Issued)
1. Create MIR with 3 lines
2. Approve each line individually
3. Verify MIR header status = APPROVED (not PARTIALLY_ISSUED)
4. Verify all lines have issued_qty = 0

### Test 2: Partial Issuance
1. Approve all lines
2. Issue 50% of line 1
3. Verify line 1 status = PARTIALLY_PICKED
4. Verify MIR header status = PARTIALLY_ISSUED
5. Issue remaining 50% of line 1
6. Verify line 1 status = FULLY_PICKED
7. Verify MIR header status = PARTIALLY_ISSUED (other lines not picked)

### Test 3: Full Issuance
1. Approve all lines
2. Issue all quantities for all lines
3. Verify all lines status = FULLY_PICKED
4. Verify MIR header status = FULLY_ISSUED

### Test 4: Rejection
1. Approve all lines
2. Reject line 2
3. Verify line 2 status = REJECTED
4. Verify MIR header status = REJECTED
5. Verify other lines cannot proceed

---

## Migration Notes

If upgrading from old system:
1. Ensure all mir_line_items have `status` field
2. Ensure all material_issue_requests have `fully_issued_at` and `closed_at` fields
3. Recalculate all MIR header statuses using deriveHeaderStatus()
4. Create mir_issue_transactions table for audit trail
