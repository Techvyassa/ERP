# MIR UI Components Guide

## Overview

This guide provides specifications for UI components needed to display and manage Material Issue Requests (MIR) in the production and warehouse portals.

---

## 1. MIR List View

### Location
- **Production Portal**: `/org/{org_slug}/production/material-issue-requests`
- **Warehouse Portal**: `/org/{org_slug}/warehouse/mir`

### Components

#### MIR List Table
```
Columns:
├─ MIR No. (mir_no)
├─ Batch Run ID
├─ Status (with color coding)
├─ Lines Count
├─ Fully Picked Count / Total Lines
├─ Created At
├─ Approved At
└─ Actions (View, Edit)

Color Coding:
├─ PENDING: Gray
├─ APPROVED: Blue
├─ PARTIALLY_ISSUED: Orange
├─ FULLY_ISSUED: Green
├─ REJECTED: Red
└─ CLOSED: Dark Green
```

#### Filters
```
├─ Status Filter (dropdown)
├─ Batch Run ID (search)
├─ Production Order ID (search)
├─ Date Range (from/to)
└─ Search (mir_no)
```

#### Pagination
```
├─ Per Page: 20 items
├─ Page Navigation
└─ Total Count
```

---

## 2. MIR Detail View

### Location
- **Production Portal**: `/org/{org_slug}/production/material-issue-requests/{id}`
- **Warehouse Portal**: `/org/{org_slug}/warehouse/mir/{id}`

### Header Section

```
┌─────────────────────────────────────────────────────┐
│ MIR #MIR-2026-001                                   │
│ Status: APPROVED                                    │
│ Batch Run: BR-001 | Production Order: PO-001       │
│ Created: 2026-04-20 08:00 | Approved: 2026-04-20   │
└─────────────────────────────────────────────────────┘
```

### Summary Section

```
┌─────────────────────────────────────────────────────┐
│ SUMMARY                                             │
├─────────────────────────────────────────────────────┤
│ Total Lines: 3                                      │
│ Pending: 0 | Approved: 3 | Partially Picked: 0    │
│ Fully Picked: 0 | Rejected: 0                      │
│ Progress: 0/3 lines fully picked (0%)              │
└─────────────────────────────────────────────────────┘
```

### MIR Lines Table

```
Columns:
├─ Material Code
├─ Material Name
├─ Required Qty
├─ Issued Qty
├─ Remaining Qty
├─ UOM
├─ Status (with badge)
├─ Last Issued At
└─ Actions (View, Approve, Reject, Issue)

Status Badges:
├─ PENDING: Gray
├─ APPROVED: Blue
├─ PARTIALLY_PICKED: Orange
├─ FULLY_PICKED: Green
├─ REJECTED: Red
```

### Line Detail Modal

#### When Clicking "View" on a Line

```
┌─────────────────────────────────────────────────────┐
│ Material: Chilli Powder (MAT-001)                   │
├─────────────────────────────────────────────────────┤
│ Required Qty: 10.0 kg                               │
│ Issued Qty: 5.0 kg                                  │
│ Remaining Qty: 5.0 kg                               │
│ Status: PARTIALLY_PICKED                            │
│ Last Issued: 2026-04-20 09:30                       │
├─────────────────────────────────────────────────────┤
│ TRANSACTION HISTORY                                 │
├─────────────────────────────────────────────────────┤
│ Transaction 1: 5.0 kg @ 08:00 by Store Keeper A    │
│   Notes: From bin B-123                             │
│ Transaction 2: 3.0 kg @ 09:30 by Store Keeper B    │
│   Notes: From bin B-124                             │
└─────────────────────────────────────────────────────┘
```

---

## 3. Approve Line Component

### Trigger
- Button: "Approve" on each line in PENDING status

### Dialog

```
┌─────────────────────────────────────────────────────┐
│ Approve Material Line                               │
├─────────────────────────────────────────────────────┤
│ Material: Chilli Powder (MAT-001)                   │
│ Required Qty: 10.0 kg                               │
│ Current Status: PENDING                             │
│                                                     │
│ [Confirm] [Cancel]                                  │
└─────────────────────────────────────────────────────┘
```

### API Call
```
PATCH /api/v1/mir-lines/{id}/approve
```

### Success Response
```
Line status updated to APPROVED
MIR header status updated (if applicable)
```

---

## 4. Reject Line Component

### Trigger
- Button: "Reject" on each line in PENDING status

### Dialog

```
┌─────────────────────────────────────────────────────┐
│ Reject Material Line                                │
├─────────────────────────────────────────────────────┤
│ Material: Chilli Powder (MAT-001)                   │
│ Required Qty: 10.0 kg                               │
│                                                     │
│ Rejection Reason:                                   │
│ ┌─────────────────────────────────────────────────┐ │
│ │ [Text area - max 500 chars]                     │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ [Reject] [Cancel]                                   │
└─────────────────────────────────────────────────────┘
```

### API Call
```
PATCH /api/v1/mir-lines/{id}/reject
{
  "rejection_reason": "Quality hold on batch #XYZ"
}
```

### Success Response
```
Line status updated to REJECTED
MIR header status updated to REJECTED
Warning: MIR is now blocked
```

---

## 5. Issue Material Component

### Trigger
- Button: "Issue" on each line in APPROVED or PARTIALLY_PICKED status

### Dialog

```
┌─────────────────────────────────────────────────────┐
│ Issue Material                                      │
├─────────────────────────────────────────────────────┤
│ Material: Chilli Powder (MAT-001)                   │
│ Required Qty: 10.0 kg                               │
│ Already Issued: 5.0 kg                              │
│ Remaining: 5.0 kg                                   │
│                                                     │
│ Issue Qty:                                          │
│ ┌─────────────────────────────────────────────────┐ │
│ │ [Number input - max 5.0]                        │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ Notes (optional):                                   │
│ ┌─────────────────────────────────────────────────┐ │
│ │ [Text area - e.g., bin location, lot number]   │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ [Issue] [Cancel]                                    │
└─────────────────────────────────────────────────────┘
```

### Validation
- Issue Qty must be > 0
- Issue Qty must be <= Remaining Qty
- Notes optional but recommended

### API Call
```
POST /api/v1/mir-lines/{id}/issue
{
  "issued_qty": 5.0,
  "notes": "From bin B-123, lot #ABC-2026"
}
```

### Success Response
```
Line status updated (PARTIALLY_PICKED or FULLY_PICKED)
MIR header status updated
Transaction created
Show updated line details
```

---

## 6. Approve MIR (Header) Component

### Trigger
- Button: "Approve MIR" on MIR detail view (when status = PENDING)

### Preconditions Check
```
Before showing button:
├─ MIR status must be PENDING
├─ All lines must be APPROVED
└─ No items issued yet (issued_qty = 0 for all)

If preconditions not met:
└─ Show disabled button with tooltip explaining why
```

### Dialog

```
┌─────────────────────────────────────────────────────┐
│ Approve Material Issue Request                      │
├─────────────────────────────────────────────────────┤
│ MIR #MIR-2026-001                                   │
│ All 3 lines are approved and ready for picking      │
│                                                     │
│ [Approve] [Cancel]                                  │
└─────────────────────────────────────────────────────┘
```

### API Call
```
PATCH /api/v1/material-issue-requests/{id}/approve
```

### Success Response
```
MIR status updated to APPROVED
Show success message
Update MIR header display
```

---

## 7. Reject MIR (Header) Component

### Trigger
- Button: "Reject MIR" on MIR detail view (when status = PENDING)

### Dialog

```
┌─────────────────────────────────────────────────────┐
│ Reject Material Issue Request                       │
├─────────────────────────────────────────────────────┤
│ MIR #MIR-2026-001                                   │
│                                                     │
│ Rejection Reason:                                   │
│ ┌─────────────────────────────────────────────────┐ │
│ │ [Text area - max 500 chars]                     │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ [Reject] [Cancel]                                   │
└─────────────────────────────────────────────────────┘
```

### API Call
```
PATCH /api/v1/material-issue-requests/{id}/reject
{
  "rejection_reason": "Insufficient stock for critical materials"
}
```

### Success Response
```
MIR status updated to REJECTED
Show error message
Block batch run from starting
```

---

## 8. Status Progress Indicator

### Display on MIR Detail View

```
┌─────────────────────────────────────────────────────┐
│ MIR PROGRESS                                        │
├─────────────────────────────────────────────────────┤
│ PENDING ──→ APPROVED ──→ PARTIALLY_ISSUED ──→ FULLY_ISSUED ──→ CLOSED
│                ✓                                    
│ Current Status: APPROVED                            │
│ Progress: All lines approved, ready for picking     │
└─────────────────────────────────────────────────────┘
```

### Color Coding
```
├─ Completed steps: Green
├─ Current step: Blue
└─ Future steps: Gray
```

---

## 9. Error Messages

### Line Approval Errors
```
"Line cannot be approved. Current status: PARTIALLY_PICKED"
"Line cannot be approved. Current status: REJECTED"
```

### Line Rejection Errors
```
"Line cannot be rejected. Current status: APPROVED"
"Line cannot be rejected. Current status: FULLY_PICKED"
```

### Material Issue Errors
```
"Line cannot be issued. Current status: PENDING"
"Line cannot be issued. Current status: REJECTED"
"Issued qty (15.0) exceeds remaining qty (10.0)"
"Issued qty must be greater than 0"
```

### MIR Approval Errors
```
"MIR cannot be approved. Status must be PENDING and all lines must be approved."
"MIR cannot be approved. 2 lines are still PENDING"
"MIR cannot be approved. Some lines have items issued"
```

---

## 10. Success Messages

### Line Operations
```
"Line approved successfully"
"Line rejected successfully"
"Material issued successfully (5.0 kg)"
```

### MIR Operations
```
"MIR approved successfully"
"MIR rejected successfully"
```

### Status Updates
```
"Line status updated to PARTIALLY_PICKED"
"Line status updated to FULLY_PICKED"
"MIR status updated to PARTIALLY_ISSUED"
"MIR status updated to FULLY_ISSUED"
```

---

## 11. Responsive Design

### Mobile View
```
├─ Stack table columns vertically
├─ Use collapsible sections for details
├─ Full-width buttons
└─ Simplified dialogs
```

### Tablet View
```
├─ 2-column layout for details
├─ Horizontal scrolling for tables
└─ Side-by-side dialogs
```

### Desktop View
```
├─ Full table display
├─ Side panels for details
└─ Modal dialogs
```

---

## 12. Real-Time Updates

### WebSocket Events (Optional)
```
├─ mir:line-approved
├─ mir:line-rejected
├─ mir:material-issued
├─ mir:status-changed
└─ mir:fully-issued
```

### Polling Fallback
```
├─ Poll MIR status every 5 seconds
├─ Update line statuses
└─ Refresh transaction history
```

---

## 13. Accessibility Features

### ARIA Labels
```
├─ Status badges: aria-label="Status: Approved"
├─ Buttons: aria-label="Approve line"
├─ Tables: role="table"
└─ Dialogs: role="dialog"
```

### Keyboard Navigation
```
├─ Tab through buttons
├─ Enter to confirm
├─ Escape to cancel
└─ Arrow keys for table navigation
```

### Color Contrast
```
├─ Status colors meet WCAG AA standards
├─ Text contrast ratio >= 4.5:1
└─ No color-only indicators
```

---

## 14. Data Validation

### Client-Side Validation
```
├─ Issue Qty: Must be numeric, > 0, <= remaining
├─ Rejection Reason: Required, max 500 chars
├─ Notes: Optional, max 500 chars
└─ Show validation errors inline
```

### Server-Side Validation
```
├─ Verify line status before operations
├─ Verify issued_qty calculations
├─ Verify user permissions
└─ Return detailed error messages
```

---

## 15. Performance Considerations

### Lazy Loading
```
├─ Load MIR list with pagination
├─ Load line details on demand
├─ Load transaction history on expand
└─ Cache MIR data for 30 seconds
```

### Optimization
```
├─ Minimize API calls
├─ Use batch operations where possible
├─ Debounce search inputs
└─ Optimize table rendering for large datasets
```

---

## Implementation Checklist

- [ ] MIR List View component
- [ ] MIR Detail View component
- [ ] Approve Line dialog
- [ ] Reject Line dialog
- [ ] Issue Material dialog
- [ ] Approve MIR dialog
- [ ] Reject MIR dialog
- [ ] Status Progress Indicator
- [ ] Error message handling
- [ ] Success message handling
- [ ] Responsive design
- [ ] Accessibility features
- [ ] Data validation
- [ ] Performance optimization
- [ ] Testing (unit, integration, E2E)
