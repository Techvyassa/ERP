# Gate Entry (GE) Implementation Summary

## Overview
Gate Entry module handles the security gate processing for incoming shipments. Security guards verify documents and physical conditions before goods move to the warehouse dock.

## Database Tables

### gate_entries
- **Purpose**: Records each vehicle arrival at the gate
- **Key Fields**:
  - `ge_number`: System-generated unique ID (GE-YYMM-NNNN)
  - `po_id`: Links to Purchase Order
  - `asn_id`: Optional link to ASN (if ASN-based entry)
  - `vendor_id`: Supplier information
  - `vehicle_number`: Truck registration plate
  - `arrived_at`: Actual arrival timestamp
  - `status`: PENDING_VERIFICATION → VERIFIED → MOVED_TO_DOCK / REJECTED
  - `gross_weight_kg`: Loaded truck weight from weighbridge

### gate_verifications
- **Purpose**: Security supervisor verification checklist for each gate entry
- **Key Fields**:
  - `ge_id`: FK to gate_entries (one-to-one)
  - Document checks: challan_verified, invoice_verified, eway_bill_valid, po_status_valid
  - Physical inspection: seal_number, seal_intact, external_damage
  - Weighbridge: tare_weight_kg, net_weight_kg, weight_variance_flag
  - `approval_status`: PENDING → APPROVED / REJECTED
  - `verified_by`: Security supervisor who verified
  - `verified_at`: Verification timestamp

## Models Created

### GateEntry
- Relationships: purchaseOrder, asn, vendor, creator, verification
- Scopes: pendingVerification(), verified(), byVendor(), byPO()
- Methods: canVerify(), canMoveToDock(), generateGENumber()

### GateVerification
- Relationships: gateEntry, verifier
- Methods: allDocumentsVerified(), physicalInspectionPassed(), weightWithinTolerance(), canApprove()

## API Endpoints

### Gate Entry Management
```
GET    /api/v1/gate-entries                    # List all entries (with filters)
GET    /api/v1/gate-entries/{id}               # Get single entry
POST   /api/v1/gate-entries                    # Create new gate entry
GET    /api/v1/gate-entries/pending-verifications  # Get pending verifications
GET    /api/v1/gate-entries/by-vendor/{vendorId}  # Get entries by vendor
GET    /api/v1/gate-entries/by-po/{poId}      # Get entries by PO
POST   /api/v1/gate-entries/{id}/verify       # Create verification & approve/reject
PATCH  /api/v1/gate-entries/{id}/move-to-dock # Move to dock (after verification)
```

### Middleware
- All endpoints require: `check.module.permission:GATE`
- Roles: STOREKEEPER/STORE_MGR (create/verify), ADMIN (all)

## Workflow

### 1. Gate Entry Creation (Security Guard)
- Guard scans QR code or enters ASN/PO number
- System auto-populates vendor, PO details
- Guard enters vehicle info, documents, gross weight
- System generates GE number
- Status: PENDING_VERIFICATION

### 2. Gate Verification (Security Supervisor)
- Supervisor reviews pending entries
- Verifies documents (challan, invoice, e-way bill, PO status)
- Records physical inspection (seal, damage)
- Captures tare weight (empty truck)
- System calculates net weight
- Approves or rejects entry
- If APPROVED: Status → VERIFIED
- If REJECTED: Status → REJECTED

### 3. Move to Dock (Warehouse)
- After verification, entry can be moved to dock
- Status: VERIFIED → MOVED_TO_DOCK
- Warehouse team receives notification

## Status Flow

```
PENDING_VERIFICATION
    ↓
    ├─→ VERIFIED (if approved)
    │       ↓
    │   MOVED_TO_DOCK
    │
    └─→ REJECTED (if rejected)
```

## Form Requests

### StoreGateEntryRequest
- Validates: po_id, vendor_id, vehicle_number, material_type, arrived_at
- Optional: asn_id, transporter_name, driver details, documents, gross_weight_kg

### StoreGateVerificationRequest
- Validates: challan_verified, invoice_verified, eway_bill_valid, po_status_valid
- Validates: external_damage, weight_variance_flag, approval_status
- Conditional: rejection_reason (required if approval_status = REJECTED)

## Service Layer

### GateEntryService
- `createGateEntry()`: Creates gate entry with validation
- `createVerification()`: Creates verification record and updates entry status
- `moveToDock()`: Transitions entry to dock
- `validatePO()`: Ensures PO is in valid status

## Key Features

1. **Document Verification**: Tracks challan, invoice, e-way bill validation
2. **Physical Inspection**: Records seal integrity and damage assessment
3. **Weight Management**: Captures gross/tare/net weights with variance detection
4. **Approval Workflow**: Supervisor approval/rejection with reasons
5. **Audit Trail**: Tracks who created/verified each entry with timestamps
6. **Soft Deletes**: Entries can be soft-deleted for compliance

## Testing Curl Commands

### Create Gate Entry
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

### Get Pending Verifications
```bash
curl --location 'http://127.0.0.1:8000/api/v1/gate-entries/pending-verifications' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

### Create Verification (Approve)
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
  "security_remarks": "All checks passed"
}'
```

### Move to Dock
```bash
curl --location --request PATCH 'http://127.0.0.1:8000/api/v1/gate-entries/1/move-to-dock' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

## Next Steps

1. Run migrations: `php artisan tenant:migrate amit-tech-solutions-pvt-ltd`
2. Add GATE module permissions to RbacSeeder
3. Create GateEntryCommand for adding permissions to tenant database
4. Test all endpoints with provided curl commands
5. Integrate with warehouse dashboard for notifications
