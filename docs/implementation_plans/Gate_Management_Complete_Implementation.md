# Gate Management - Complete Implementation Guide

## Overview

Gate Management module handles the complete workflow for incoming shipments:
1. **Gate Entry** - Security guard records vehicle arrival
2. **Gate Verification** - Security supervisor validates documents and approves/rejects
3. **Dock Assignment** - Approved entries move to unloading dock

## Database Schema

### gate_entries Table
```sql
CREATE TABLE gate_entries (
    id BIGINT PRIMARY KEY,
    ge_number VARCHAR(30) UNIQUE,           -- GE-2603-0001
    po_id BIGINT NOT NULL,                  -- FK to purchase_orders
    asn_id BIGINT NULLABLE,                 -- FK to asn_headers
    vendor_id BIGINT NOT NULL,              -- FK to vendors
    vehicle_number VARCHAR(20),             -- MH-04-EY-1234
    transporter_name VARCHAR(100),          -- BlueDart Logistics
    driver_name VARCHAR(100),
    driver_phone VARCHAR(15),
    challan_number VARCHAR(50),             -- Vendor delivery challan
    vendor_invoice_number VARCHAR(50),      -- Vendor invoice
    eway_bill_number VARCHAR(30),           -- E-Way Bill
    eway_bill_expiry DATE,
    material_type ENUM(...),                -- RAW_MATERIAL, PACKAGING, etc.
    gross_weight_kg DECIMAL(10,3),          -- Loaded truck weight
    arrived_at DATETIME,
    status ENUM('PENDING_VERIFICATION', 'VERIFIED', 'REJECTED', 'MOVED_TO_DOCK'),
    remarks TEXT,
    created_by BIGINT,                      -- FK to users (Security Guard)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### gate_verifications Table
```sql
CREATE TABLE gate_verifications (
    id BIGINT PRIMARY KEY,
    ge_id BIGINT UNIQUE NOT NULL,           -- FK to gate_entries (one-to-one)
    
    -- Document Checks
    challan_verified BOOLEAN DEFAULT false,
    invoice_verified BOOLEAN DEFAULT false,
    eway_bill_valid BOOLEAN DEFAULT false,
    po_status_valid BOOLEAN DEFAULT false,
    
    -- Physical Inspection
    seal_number VARCHAR(50),
    seal_intact BOOLEAN NULLABLE,
    external_damage BOOLEAN DEFAULT false,
    
    -- Weight Verification
    tare_weight_kg DECIMAL(10,3),           -- Empty truck weight
    net_weight_kg DECIMAL(10,3),            -- Calculated: gross - tare
    weight_variance_flag BOOLEAN DEFAULT false,
    
    -- Dock Assignment
    dock_assigned VARCHAR(30),              -- DOCK-01, DOCK-02, etc.
    
    -- Outcome
    approval_status ENUM('PENDING', 'APPROVED', 'REJECTED'),
    rejection_reason TEXT,
    security_remarks TEXT,
    
    -- Audit
    verified_by BIGINT,                     -- FK to users (Security Supervisor)
    verified_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Models

### GateEntry Model
```php
class GateEntry extends Model {
    // Relationships
    public function purchaseOrder() { }
    public function asn() { }
    public function vendor() { }
    public function creator() { }
    public function verification() { }
    
    // Methods
    public static function generateGENumber(): string { }
    public function canVerify(): bool { }
    public function canMoveToDock(): bool { }
    
    // Scopes
    public function scopePendingVerification($query) { }
    public function scopeVerified($query) { }
    public function scopeByVendor($query, int $vendorId) { }
    public function scopeByPO($query, int $poId) { }
}
```

### GateVerification Model
```php
class GateVerification extends Model {
    // Relationships
    public function gateEntry() { }
    public function verifier() { }
    
    // Methods
    public function allDocumentsVerified(): bool { }
    public function physicalInspectionPassed(): bool { }
    public function weightWithinTolerance(): bool { }
    public function canApprove(): bool { }
}
```

## API Endpoints

### Gate Entry Management
```
GET    /api/v1/gate-entries                          # List all entries
GET    /api/v1/gate-entries/{id}                     # Get single entry
POST   /api/v1/gate-entries                          # Create new entry
GET    /api/v1/gate-entries/pending-verifications    # Get pending verifications
GET    /api/v1/gate-entries/by-vendor/{vendorId}    # Get entries by vendor
GET    /api/v1/gate-entries/by-po/{poId}            # Get entries by PO
POST   /api/v1/gate-entries/{id}/verify             # Create verification
PATCH  /api/v1/gate-entries/{id}/move-to-dock       # Move to dock
```

### Middleware
- All endpoints require: `check.module.permission:GATE`
- Roles: STOREKEEPER, STORE_MGR, ADMIN

## Complete Workflow Example

### 1. Security Guard Creates Gate Entry
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

**Response**: Gate Entry created with status `PENDING_VERIFICATION`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ge_number": "GE-2603-0001",
    "status": "PENDING_VERIFICATION",
    "gross_weight_kg": "1000.000",
    "arrived_at": "2026-03-11T10:30:00.000000Z"
  }
}
```

### 2. Security Supervisor Reviews Pending Entries
```bash
curl --location 'http://127.0.0.1:8000/api/v1/gate-entries/pending-verifications' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

### 3. Security Supervisor Verifies and Approves
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

**Result**: 
- Gate Entry status → `VERIFIED`
- Gate Verification created with `approval_status: APPROVED`
- Dock assigned: `DOCK-01`

### 4. Warehouse Moves Entry to Dock
```bash
curl --location --request PATCH 'http://127.0.0.1:8000/api/v1/gate-entries/1/move-to-dock' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

**Result**: Gate Entry status → `MOVED_TO_DOCK`

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
- [ ] **Weight Variance**: Flag if net weight deviates beyond tolerance

## Status Flow

```
PENDING_VERIFICATION
    ↓
    POST /gate-entries/{id}/verify
    ├─→ approval_status: APPROVED
    │       ↓
    │   Gate Entry Status: VERIFIED
    │       ↓
    │   PATCH /gate-entries/{id}/move-to-dock
    │       ↓
    │   Gate Entry Status: MOVED_TO_DOCK
    │
    └─→ approval_status: REJECTED
            ↓
        Gate Entry Status: REJECTED
```

## Installation Steps

### 1. Run Migrations
```bash
php artisan tenant:migrate amit-tech-solutions-pvt-ltd
```

This will create:
- `gate_entries` table
- `gate_verifications` table

### 2. Add GATE Module Permissions to RbacSeeder
Update `database/seeders/RbacSeeder.php`:

```php
// In the permissions array
'GATE' => [
    'view' => true,
    'create' => true,
    'edit' => true,
    'approve' => true,
    'delete' => false,
],
```

### 3. Assign Permissions to Roles
```php
// STOREKEEPER: Can view and create gate entries
'STOREKEEPER' => [
    'GATE' => ['view' => true, 'create' => true, 'edit' => false, 'approve' => false, 'delete' => false],
],

// STORE_MGR: Can view, create, and verify gate entries
'STORE_MGR' => [
    'GATE' => ['view' => true, 'create' => true, 'edit' => true, 'approve' => true, 'delete' => false],
],

// ADMIN: Full access
'ADMIN' => [
    'GATE' => ['view' => true, 'create' => true, 'edit' => true, 'approve' => true, 'delete' => true],
],
```

### 4. Run Permission Command
```bash
php artisan add:gate-permissions amit-tech-solutions-pvt-ltd
```

## Files Created

1. **Models**:
   - `app/Models/Tenant/GateEntry.php`
   - `app/Models/Tenant/GateVerification.php`

2. **Controllers**:
   - `app/Http/Controllers/GateEntryController.php`

3. **Services**:
   - `app/Services/GateEntryService.php`

4. **Form Requests**:
   - `app/Http/Requests/Tenant/StoreGateEntryRequest.php`
   - `app/Http/Requests/Tenant/StoreGateVerificationRequest.php`

5. **Migrations**:
   - `database/migrations/tenant/2024_01_01_000024_create_gate_entries_table.php`
   - `database/migrations/tenant/2024_01_01_000025_create_gate_verifications_table.php`

6. **Routes**:
   - Added to `routes/api.php` under `/api/v1/gate-entries`

## Testing Checklist

- [ ] Create gate entry with all fields
- [ ] List pending verifications
- [ ] Approve gate entry with all checks
- [ ] Reject gate entry with reason
- [ ] Move approved entry to dock
- [ ] Verify weight calculation (gross - tare = net)
- [ ] Test with missing optional fields
- [ ] Test permission checks
- [ ] Test status transitions

## Next Steps

1. Run migrations
2. Add GATE permissions to RbacSeeder
3. Create AddGatePermissions command
4. Test all endpoints
5. Integrate with warehouse dashboard
6. Create GRN (Goods Receipt Note) module for receiving
