# Material Receipt (MR) Implementation Plan

## Overview
Material Receipt is the stage where physical goods transfer from the truck to the warehouse floor. The warehouse team formally takes ownership of the materials and performs physical verification.

## Database Schema Review

### material_receipts Table ✅
- **Purpose**: Header record for each unloading session
- **Key Fields**:
  - `mr_number`: System-generated (MR-YYMM-NNNN)
  - `ge_id`: Links to gate_entries
  - `po_id`: Links to purchase_orders
  - `vendor_id`: Links to vendor_master
  - `unloading_started_at`, `unloading_completed_at`: Track dock turnaround
  - `status`: IN_PROGRESS → COMPLETED → PENDING_GRN → GRN_POSTED
  - `created_by`: Storekeeper who created the MR

### mr_line_items Table ✅
- **Purpose**: Line-level details for each material received
- **Key Fields**:
  - `mr_id`: FK to material_receipts
  - `po_line_id`: FK to po_line_items
  - `material_id`: FK to material_master
  - `received_qty`: Actual quantity counted
  - `shortage_qty`: PO qty - received qty (when under-delivered)
  - `excess_qty`: Received qty - PO qty (when over-delivered)
  - `rejected_on_arrival`: Qty rejected due to visible damage
  - `shortage_flag`, `excess_flag`: TRUE when beyond tolerance
  - `batch_number`, `manufacturing_date`, `expiry_date`: Traceability
  - `provisional_bin_id`: Temporary staging location
  - `damage_found`, `damage_remarks`, `damage_photo_path`: Damage tracking
  - `internal_barcode`: System-generated for tracking

## Business Logic

### Shortage & Excess Calculation
```
Variance = Received Qty − PO Qty

If Variance < 0:
    Shortage Qty = abs(Variance)
    Check against under_delivery_tolerance from PO line
    If Shortage > Tolerance: shortage_flag = TRUE

If Variance > 0:
    Excess Qty = Variance
    Check against over_delivery_tolerance from PO line
    If Excess > Tolerance: excess_flag = TRUE
```

### Status Flow
```
IN_PROGRESS (unloading ongoing)
    ↓
COMPLETED (unloading finished, all line items recorded)
    ↓
PENDING_GRN (awaiting GRN creation)
    ↓
GRN_POSTED (GRN created, inventory updated)
```

### Inventory Status After MR
- Stock status: **"In-Quality" / "Restricted Stock"**
- Visible in system but NOT available for consumption
- Awaits QC approval before becoming available

## API Endpoints to Implement

### Material Receipt Management
```
GET    /api/v1/material-receipts                    # List all MRs
GET    /api/v1/material-receipts/{id}               # Get single MR
POST   /api/v1/material-receipts                    # Create new MR
PUT    /api/v1/material-receipts/{id}               # Update MR
GET    /api/v1/material-receipts/by-ge/{geId}       # Get MR by Gate Entry
GET    /api/v1/material-receipts/by-po/{poId}       # Get MRs by PO
GET    /api/v1/material-receipts/pending-grn        # Get MRs pending GRN
PATCH  /api/v1/material-receipts/{id}/complete      # Mark unloading complete
PATCH  /api/v1/material-receipts/{id}/start-unloading  # Start unloading timer
```

### Middleware
- All endpoints require: `check.module.permission:MR_GRN`
- Roles: STOREKEEPER (create/edit), STORE_MGR (approve), ADMIN (all)

## Models to Create

### MaterialReceipt Model
```php
class MaterialReceipt extends Model {
    // Relationships
    public function gateEntry() { }
    public function purchaseOrder() { }
    public function vendor() { }
    public function lineItems() { }
    public function creator() { }
    public function updater() { }
    
    // Methods
    public static function generateMRNumber(): string { }
    public function canEdit(): bool { }
    public function canComplete(): bool { }
    public function calculateTotalReceived(): float { }
    public function hasShortages(): bool { }
    public function hasExcess(): bool { }
    
    // Scopes
    public function scopeInProgress($query) { }
    public function scopePendingGRN($query) { }
    public function scopeByGateEntry($query, int $geId) { }
    public function scopeByPO($query, int $poId) { }
}
```

### MRLineItem Model
```php
class MRLineItem extends Model {
    // Relationships
    public function materialReceipt() { }
    public function poLineItem() { }
    public function material() { }
    public function uom() { }
    public function provisionalBin() { }
    
    // Methods
    public function calculateVariance(): array { }
    public function isShortage(): bool { }
    public function isExcess(): bool { }
    public function isWithinTolerance(): bool { }
    public function generateInternalBarcode(): string { }
}
```

## Form Requests

### StoreMaterialReceiptRequest
```php
Validates:
- ge_id (required, exists in gate_entries)
- po_id (required, exists in purchase_orders)
- vendor_id (required, exists in vendor_master)
- unloading_started_at (optional, datetime)
- remarks (optional, string)
- line_items (required, array, min:1)
  - po_line_id (required, exists)
  - material_id (required, exists)
  - received_qty (required, numeric, gt:0)
  - uom_id (required, exists)
  - rejected_on_arrival (optional, numeric, gte:0)
  - batch_number (optional, string)
  - manufacturing_date (optional, date)
  - expiry_date (optional, date, after:manufacturing_date)
  - provisional_bin_id (optional, exists)
  - damage_found (optional, boolean)
  - damage_remarks (optional, string)
```

### UpdateMaterialReceiptRequest
```php
Validates:
- unloading_completed_at (optional, datetime)
- remarks (optional, string)
- line_items (optional, array)
  - Same as StoreMaterialReceiptRequest
```

## Service Layer

### MaterialReceiptService
```php
class MaterialReceiptService {
    public function createMaterialReceipt(array $data, int $userId): MaterialReceipt
    public function updateMaterialReceipt(int $id, array $data, int $userId): MaterialReceipt
    public function startUnloading(int $id, int $userId): MaterialReceipt
    public function completeUnloading(int $id, int $userId): MaterialReceipt
    
    private function calculateVariances(array $lineItem, $poLineItem): array
    private function checkTolerances($poLineItem, float $shortage, float $excess): array
    private function validateGateEntry(int $geId): void
    private function validatePO(int $poId): void
}
```

## Key Features

1. **Automatic Variance Calculation**: System calculates shortage/excess automatically
2. **Tolerance Checking**: Compares variance against PO line tolerances
3. **Damage Tracking**: Records damage with photos and remarks
4. **Batch Traceability**: Captures vendor batch numbers and dates
5. **Provisional Storage**: Assigns temporary bin locations
6. **Internal Barcoding**: Generates system barcodes for tracking
7. **Dock Turnaround Tracking**: Records unloading start/end times
8. **3-Way Match Preparation**: Sets up data for PO-MR-Invoice matching

## Testing Scenarios

### Normal Receipt (No Variance)
```json
{
  "ge_id": 1,
  "po_id": 1,
  "vendor_id": 1,
  "unloading_started_at": "2026-03-11 14:00:00",
  "line_items": [{
    "po_line_id": 1,
    "material_id": 1,
    "received_qty": 100,
    "uom_id": 1,
    "batch_number": "BATCH-001"
  }]
}
```

### Short Delivery (Within Tolerance)
```json
{
  "line_items": [{
    "po_line_id": 1,
    "material_id": 1,
    "received_qty": 97,  // PO qty = 100, tolerance = 3%
    "uom_id": 1
  }]
}
```
Result: shortage_qty = 3, shortage_flag = FALSE

### Short Delivery (Beyond Tolerance)
```json
{
  "line_items": [{
    "po_line_id": 1,
    "material_id": 1,
    "received_qty": 90,  // PO qty = 100, tolerance = 3%
    "uom_id": 1
  }]
}
```
Result: shortage_qty = 10, shortage_flag = TRUE

### Excess Delivery (Beyond Tolerance)
```json
{
  "line_items": [{
    "po_line_id": 1,
    "material_id": 1,
    "received_qty": 110,  // PO qty = 100, tolerance = 5%
    "uom_id": 1
  }]
}
```
Result: excess_qty = 10, excess_flag = TRUE

### Damaged Material
```json
{
  "line_items": [{
    "po_line_id": 1,
    "material_id": 1,
    "received_qty": 100,
    "rejected_on_arrival": 5,
    "uom_id": 1,
    "damage_found": true,
    "damage_remarks": "5 boxes crushed during transport"
  }]
}
```

## Integration Points

### From Gate Entry
- MR is created after gate entry is verified and moved to dock
- Links to GE for full traceability

### To GRN (Goods Receipt Note)
- After MR is completed, GRN is created
- GRN posts inventory to system
- Updates PO received quantities

### To QC (Quality Control)
- Materials in "In-Quality" status
- QC inspection triggered after GRN
- QC approval required before stock becomes available

## Next Steps

1. Create MaterialReceipt and MRLineItem models
2. Create MaterialReceiptService with variance calculation logic
3. Create MaterialReceiptController with all endpoints
4. Create form request validators
5. Add routes to api.php
6. Run migrations
7. Add MR_GRN permissions to RbacSeeder
8. Test all scenarios (normal, shortage, excess, damage)
