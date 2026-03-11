# Gate Verification API Guide

## Overview

Gate Verification is the **"second eye" check** performed by Security Supervisors. It validates that incoming shipments are authorized, properly documented, and physically safe before moving to the unloading dock.

## Workflow

```
Gate Entry Created (PENDING_VERIFICATION)
    ↓
Supervisor Reviews Entry
    ↓
Supervisor Performs Verification:
    - Document checks (challan, invoice, e-way bill, PO status)
    - Physical inspection (seal, damage)
    - Weight verification (tare weight, net weight)
    ↓
Supervisor Approves/Rejects
    ↓
If APPROVED: Status → VERIFIED
If REJECTED: Status → REJECTED
    ↓
If VERIFIED: Can move to dock
```

## API Endpoints

### 1. Get Pending Verifications
**Endpoint**: `GET /api/v1/gate-entries/pending-verifications`

**Purpose**: Security Supervisor views all gate entries awaiting verification

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "ge_number": "GE-2603-0001",
        "po_id": 1,
        "asn_id": null,
        "vendor_id": 1,
        "vehicle_number": "MH-04-EY-1234",
        "transporter_name": "BlueDart Logistics",
        "driver_name": "John Doe",
        "driver_phone": "+91-9876543210",
        "challan_number": "CH-001",
        "vendor_invoice_number": "INV-001",
        "eway_bill_number": "EWB-001",
        "eway_bill_expiry": "2026-03-20",
        "material_type": "RAW_MATERIAL",
        "gross_weight_kg": "1000.000",
        "arrived_at": "2026-03-11T10:30:00.000000Z",
        "status": "PENDING_VERIFICATION",
        "remarks": "Arrived on time",
        "created_by": 1,
        "created_at": "2026-03-11T10:30:00.000000Z",
        "updated_at": "2026-03-11T10:30:00.000000Z",
        "purchase_order": { ... },
        "vendor": { ... },
        "creator": { ... }
      }
    ],
    "total": 5,
    "per_page": 20
  },
  "message": "Pending verifications retrieved successfully"
}
```

### 2. Get Single Gate Entry
**Endpoint**: `GET /api/v1/gate-entries/{id}`

**Purpose**: View complete gate entry details including verification status

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ge_number": "GE-2603-0001",
    "po_id": 1,
    "vendor_id": 1,
    "vehicle_number": "MH-04-EY-1234",
    "status": "PENDING_VERIFICATION",
    "gross_weight_kg": "1000.000",
    "arrived_at": "2026-03-11T10:30:00.000000Z",
    "verification": null  // null if not yet verified
  },
  "message": "Gate entry retrieved successfully"
}
```

### 3. Create Gate Verification (Approve/Reject)
**Endpoint**: `POST /api/v1/gate-entries/{id}/verify`

**Purpose**: Security Supervisor submits verification checklist and approves/rejects entry

**Request Body**:
```json
{
  "challan_verified": true,
  "invoice_verified": true,
  "eway_bill_valid": true,
  "po_status_valid": true,
  "seal_number": "SEAL-001",
  "seal_intact": true,
  "external_damage": false,
  "tare_weight_kg": 500,
  "weight_variance_flag": false,
  "dock_assigned": "DOCK-01",
  "approval_status": "APPROVED",
  "security_remarks": "All checks passed. Seal intact, no damage observed."
}
```

**Field Descriptions**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `challan_verified` | boolean | Yes | Delivery challan matches gate entry |
| `invoice_verified` | boolean | Yes | Vendor invoice matches system PO |
| `eway_bill_valid` | boolean | Yes | E-Way Bill is valid and not expired |
| `po_status_valid` | boolean | Yes | PO is in OPEN status (not cancelled/closed) |
| `seal_number` | string | No | Container/truck seal ID (e.g., "SEAL-001") |
| `seal_intact` | boolean | No | Whether seal was found unbroken |
| `external_damage` | boolean | Yes | Any visible packaging/container damage |
| `tare_weight_kg` | decimal | No | Empty truck weight after unloading |
| `weight_variance_flag` | boolean | Yes | TRUE if net weight deviates beyond tolerance |
| `dock_assigned` | string | No | Unloading bay number (e.g., "DOCK-01") |
| `approval_status` | enum | Yes | PENDING, APPROVED, or REJECTED |
| `rejection_reason` | string | Conditional | Required if approval_status = REJECTED |
| `security_remarks` | string | No | Supervisor observations |

**Response (Approved)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ge_id": 1,
    "challan_verified": true,
    "invoice_verified": true,
    "eway_bill_valid": true,
    "po_status_valid": true,
    "seal_number": "SEAL-001",
    "seal_intact": true,
    "external_damage": false,
    "tare_weight_kg": "500.000",
    "net_weight_kg": "500.000",
    "weight_variance_flag": false,
    "dock_assigned": "DOCK-01",
    "approval_status": "APPROVED",
    "rejection_reason": null,
    "security_remarks": "All checks passed. Seal intact, no damage observed.",
    "verified_by": 1,
    "verified_at": "2026-03-11T10:45:00.000000Z",
    "created_at": "2026-03-11T10:45:00.000000Z",
    "updated_at": "2026-03-11T10:45:00.000000Z",
    "gate_entry": {
      "id": 1,
      "ge_number": "GE-2603-0001",
      "status": "VERIFIED"  // Updated from PENDING_VERIFICATION
    },
    "verifier": { ... }
  },
  "message": "Gate entry verified successfully"
}
```

**Response (Rejected)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ge_id": 1,
    "approval_status": "REJECTED",
    "rejection_reason": "E-Way Bill expired. Vendor must provide updated documentation.",
    "security_remarks": "Rejected due to invalid e-way bill",
    "verified_by": 1,
    "verified_at": "2026-03-11T10:45:00.000000Z",
    "gate_entry": {
      "id": 1,
      "ge_number": "GE-2603-0001",
      "status": "REJECTED"  // Updated from PENDING_VERIFICATION
    }
  },
  "message": "Gate entry verified successfully"
}
```

### 4. Move to Dock
**Endpoint**: `PATCH /api/v1/gate-entries/{id}/move-to-dock`

**Purpose**: After verification, move approved entry to unloading dock

**Prerequisites**: Gate entry must be in VERIFIED status

**Request Body**: Empty

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ge_number": "GE-2603-0001",
    "status": "MOVED_TO_DOCK",
    "verification": {
      "dock_assigned": "DOCK-01",
      "approval_status": "APPROVED"
    }
  },
  "message": "Gate entry moved to dock successfully"
}
```

## Verification Checklist

### Document Verification
- [ ] **Challan Verified**: Physical delivery challan matches GE record
- [ ] **Invoice Verified**: Vendor invoice matches system PO
- [ ] **E-Way Bill Valid**: E-Way Bill is valid and not expired
- [ ] **PO Status Valid**: PO is in OPEN status (not cancelled/closed)

### Physical Inspection
- [ ] **Seal Number**: Record container/truck seal ID
- [ ] **Seal Intact**: Verify seal is unbroken
- [ ] **External Damage**: Check for visible packaging/container damage

### Weight Verification
- [ ] **Tare Weight**: Record empty truck weight after unloading
- [ ] **Net Weight**: System calculates (Gross - Tare)
- [ ] **Weight Variance**: Flag if net weight deviates beyond tolerance (±0.5%)

## Helper Methods in Model

```php
// Check if all documents are verified
$verification->allDocumentsVerified(); // Returns boolean

// Check if physical inspection passed
$verification->physicalInspectionPassed(); // Returns boolean

// Check if weight is within tolerance
$verification->weightWithinTolerance(); // Returns boolean

// Check if verification can be approved
$verification->canApprove(); // Returns boolean
```

## Testing Curl Commands

### Step 1: Create Gate Entry
```bash
curl --location 'http://127.0.0.1:8000/api/v1/gate-entries' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{
  "po_id": 1,
  "vendor_id": 1,
  "vehicle_number": "MH-04-EY-1234",
  "transporter_name": "BlueDart Logistics",
  "driver_name": "John Doe",
  "driver_phone": "+91-9876543210",
  "challan_number": "CH-001",
  "vendor_invoice_number": "INV-001",
  "eway_bill_number": "EWB-001",
  "eway_bill_expiry": "2026-03-20",
  "material_type": "RAW_MATERIAL",
  "gross_weight_kg": 1000,
  "arrived_at": "2026-03-11 10:30:00",
  "remarks": "Arrived on time"
}'
```

### Step 2: Get Pending Verifications
```bash
curl --location 'http://127.0.0.1:8000/api/v1/gate-entries/pending-verifications' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

### Step 3: Approve Gate Entry
```bash
curl --location --request POST 'http://127.0.0.1:8000/api/v1/gate-entries/1/verify' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{
  "challan_verified": true,
  "invoice_verified": true,
  "eway_bill_valid": true,
  "po_status_valid": true,
  "seal_number": "SEAL-001",
  "seal_intact": true,
  "external_damage": false,
  "tare_weight_kg": 500,
  "weight_variance_flag": false,
  "dock_assigned": "DOCK-01",
  "approval_status": "APPROVED",
  "security_remarks": "All checks passed. Seal intact, no damage observed."
}'
```

### Step 4: Reject Gate Entry
```bash
curl --location --request POST 'http://127.0.0.1:8000/api/v1/gate-entries/1/verify' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{
  "challan_verified": false,
  "invoice_verified": false,
  "eway_bill_valid": false,
  "po_status_valid": true,
  "seal_number": "SEAL-001",
  "seal_intact": false,
  "external_damage": true,
  "tare_weight_kg": null,
  "weight_variance_flag": false,
  "dock_assigned": null,
  "approval_status": "REJECTED",
  "rejection_reason": "Seal broken. Container damaged. E-Way Bill expired.",
  "security_remarks": "Multiple issues detected. Reject and notify vendor."
}'
```

### Step 5: Move to Dock
```bash
curl --location --request PATCH 'http://127.0.0.1:8000/api/v1/gate-entries/1/move-to-dock' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

## Status Transitions

```
PENDING_VERIFICATION
    ↓
    POST /gate-entries/{id}/verify
    ├─→ approval_status: APPROVED → Gate Entry Status: VERIFIED
    │       ↓
    │   PATCH /gate-entries/{id}/move-to-dock → MOVED_TO_DOCK
    │
    └─→ approval_status: REJECTED → Gate Entry Status: REJECTED
```

## Key Distinctions

| Aspect | Gate Entry | Gate Verification |
|--------|-----------|-------------------|
| **Created By** | Security Guard | Security Supervisor |
| **Purpose** | Record vehicle arrival | Authorize entry to dock |
| **Status** | PENDING_VERIFICATION | APPROVED/REJECTED |
| **Documents** | Collected | Verified |
| **Physical Check** | Basic (vehicle ID) | Detailed (seal, damage) |
| **Weight** | Gross weight | Tare + Net weight |
| **Outcome** | GE Number generated | Dock assignment |

## Error Handling

### Verification on Non-Pending Entry
```json
{
  "success": false,
  "error": {
    "code": "VERIFICATION_FAILED",
    "details": []
  },
  "message": "Failed to verify gate entry: Gate entry cannot be verified in current status: VERIFIED"
}
```

### Move to Dock on Non-Verified Entry
```json
{
  "success": false,
  "error": {
    "code": "MOVE_TO_DOCK_FAILED",
    "details": []
  },
  "message": "Failed to move gate entry to dock: Gate entry cannot be moved to dock in current status: PENDING_VERIFICATION"
}
```

## Next Steps

1. Run migrations: `php artisan tenant:migrate amit-tech-solutions-pvt-ltd`
2. Add GATE module permissions to RbacSeeder
3. Test all endpoints with provided curl commands
4. Integrate with warehouse dashboard for dock notifications
