# ASN (Advance Shipping Notice) Implementation Plan

## Overview
Implementation of the Advance Shipping Notice module to provide early visibility of incoming shipments, minimize receiving delays, and improve warehouse planning.

---

## 1. Database Schema

### 1.1 ASN Header Table (`asn_header`)

```sql
CREATE TABLE asn_header (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asn_number VARCHAR(50) UNIQUE NOT NULL,
    po_id BIGINT UNSIGNED NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    
    -- Shipment Details
    shipment_date DATETIME NOT NULL,
    estimated_arrival DATETIME NOT NULL,
    actual_arrival DATETIME NULL,
    
    -- Addresses
    ship_from_address TEXT,
    ship_to_address TEXT,
    
    -- Carrier Information
    carrier_name VARCHAR(100),
    tracking_number VARCHAR(100),
    vehicle_number VARCHAR(50),
    driver_name VARCHAR(100),
    driver_phone VARCHAR(20),
    
    -- References
    customer_reference VARCHAR(100),
    
    -- Status
    asn_status ENUM('DRAFT', 'SENT', 'IN_TRANSIT', 'ARRIVED', 'RECEIVED', 'CANCELLED') DEFAULT 'DRAFT',
    
    -- Metadata
    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (po_id) REFERENCES po_header(id),
    FOREIGN KEY (vendor_id) REFERENCES vendor_master(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouse_master(id),
    
    INDEX idx_asn_number (asn_number),
    INDEX idx_po_id (po_id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_asn_status (asn_status),
    INDEX idx_shipment_date (shipment_date),
    INDEX idx_estimated_arrival (estimated_arrival)
);
```

### 1.2 ASN Line Items Table (`asn_line_items`)

```sql
CREATE TABLE asn_line_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asn_id BIGINT UNSIGNED NOT NULL,
    po_line_item_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,
    
    -- Packaging Details
    pallet_id VARCHAR(50),
    sscc VARCHAR(50),
    
    -- Quantity Details
    shipped_qty DECIMAL(15,3) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    
    -- Batch/Lot Information
    batch_number VARCHAR(50),
    lot_number VARCHAR(50),
    manufacturing_date DATE,
    expiry_date DATE,
    
    -- Weight Details
    gross_weight DECIMAL(15,3),
    net_weight DECIMAL(15,3),
    weight_uom VARCHAR(10) DEFAULT 'KG',
    
    -- Dimensions
    length DECIMAL(10,2),
    width DECIMAL(10,2),
    height DECIMAL(10,2),
    dimension_uom VARCHAR(10) DEFAULT 'CM',
    
    -- Status
    line_status ENUM('PENDING', 'RECEIVED', 'PARTIAL', 'CANCELLED') DEFAULT 'PENDING',
    received_qty DECIMAL(15,3) DEFAULT 0,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asn_id) REFERENCES asn_header(id) ON DELETE CASCADE,
    FOREIGN KEY (po_line_item_id) REFERENCES po_line_items(id),
    FOREIGN KEY (material_id) REFERENCES material_master(id),
    FOREIGN KEY (uom_id) REFERENCES uom_master(id),
    
    INDEX idx_asn_id (asn_id),
    INDEX idx_material_id (material_id),
    INDEX idx_batch_number (batch_number),
    INDEX idx_pallet_id (pallet_id)
);
```

### 1.3 ASN Documents Table (`asn_documents`)

```sql
CREATE TABLE asn_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asn_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('PACKING_LIST', 'INVOICE', 'CERTIFICATE', 'OTHER') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by BIGINT UNSIGNED,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asn_id) REFERENCES asn_header(id) ON DELETE CASCADE,
    INDEX idx_asn_id (asn_id)
);
```

---

## 2. API Endpoints

### 2.1 ASN Management

```
GET    /api/v1/asn                    - List all ASNs (with filters)
GET    /api/v1/asn/{id}               - Get ASN details
POST   /api/v1/asn                    - Create new ASN
PUT    /api/v1/asn/{id}               - Update ASN
DELETE /api/v1/asn/{id}               - Cancel ASN
```

### 2.2 ASN Status Management

```
PATCH  /api/v1/asn/{id}/send          - Mark ASN as sent
PATCH  /api/v1/asn/{id}/in-transit    - Mark ASN as in transit
PATCH  /api/v1/asn/{id}/arrived       - Mark ASN as arrived at warehouse
PATCH  /api/v1/asn/{id}/receive       - Mark ASN as received (links to GRN)
```

### 2.3 ASN Line Items

```
GET    /api/v1/asn/{id}/items         - Get ASN line items
POST   /api/v1/asn/{id}/items         - Add line item to ASN
PUT    /api/v1/asn/{id}/items/{itemId} - Update line item
DELETE /api/v1/asn/{id}/items/{itemId} - Remove line item
```

### 2.4 ASN Documents

```
GET    /api/v1/asn/{id}/documents     - List ASN documents
POST   /api/v1/asn/{id}/documents     - Upload document
DELETE /api/v1/asn/{id}/documents/{docId} - Delete document
```

### 2.5 ASN Lookup & Search

```
GET    /api/v1/asn/by-po/{poId}       - Get ASNs for a PO
GET    /api/v1/asn/by-vendor/{vendorId} - Get ASNs from vendor
GET    /api/v1/asn/arriving-today     - Get ASNs arriving today
GET    /api/v1/asn/overdue            - Get overdue ASNs
```

---

## 3. Request/Response Models

### 3.1 Create ASN Request

```json
{
  "po_id": 1,
  "vendor_id": 1,
  "warehouse_id": 1,
  "shipment_date": "2024-10-25T09:00:00",
  "estimated_arrival": "2024-10-26T14:00:00",
  "ship_from_address": "Vendor warehouse location",
  "ship_to_address": "Plant / Warehouse Dock",
  "carrier_name": "BlueDart Logistics",
  "tracking_number": "BL-449201",
  "vehicle_number": "MH-04-EY-1234",
  "driver_name": "John Doe",
  "driver_phone": "+91-9876543210",
  "customer_reference": "PROJ-2024-001",
  "line_items": [
    {
      "po_line_item_id": 1,
      "material_id": 1,
      "pallet_id": "PLT-001",
      "sscc": "00012345678901234567",
      "shipped_qty": 500,
      "uom_id": 1,
      "batch_number": "BATCH-OCT-23",
      "lot_number": "LOT-2024-001",
      "manufacturing_date": "2024-10-01",
      "expiry_date": "2025-10-01",
      "gross_weight": 520,
      "net_weight": 500,
      "weight_uom": "KG"
    }
  ]
}
```

### 3.2 ASN Response

```json
{
  "success": true,
  "data": {
    "asn": {
      "id": 1,
      "asn_number": "ASN-2024-001",
      "po_number": "PO-2023-005",
      "vendor_name": "Spice Traders Pvt Ltd",
      "warehouse_name": "Main Warehouse",
      "shipment_date": "2024-10-25T09:00:00",
      "estimated_arrival": "2024-10-26T14:00:00",
      "actual_arrival": null,
      "carrier_name": "BlueDart Logistics",
      "tracking_number": "BL-449201",
      "vehicle_number": "MH-04-EY-1234",
      "asn_status": "SENT",
      "line_items": [
        {
          "id": 1,
          "material_code": "RM-STEEL-001",
          "material_name": "Cold Rolled Steel Coil",
          "shipped_qty": 500,
          "uom": "KG",
          "batch_number": "BATCH-OCT-23",
          "pallet_id": "PLT-001",
          "line_status": "PENDING"
        }
      ],
      "created_at": "2024-10-25T08:00:00",
      "updated_at": "2024-10-25T08:00:00"
    }
  },
  "message": "ASN created successfully"
}
```

---

## 4. Business Logic & Validations

### 4.1 ASN Creation Validations

- PO must exist and be in APPROVED status
- Vendor must match the PO vendor
- Warehouse must be valid and active
- Estimated arrival must be after shipment date
- Line items must reference valid PO line items
- Shipped quantity cannot exceed remaining PO quantity
- Material IDs must match PO line items

### 4.2 ASN Status Workflow

```
DRAFT → SENT → IN_TRANSIT → ARRIVED → RECEIVED
         ↓         ↓           ↓
      CANCELLED CANCELLED  CANCELLED
```

### 4.3 Notifications & Alerts

**On ASN Creation (SENT):**
- Notify warehouse receiving team
- Notify procurement team
- Add to warehouse dashboard

**On IN_TRANSIT:**
- Update warehouse planning board
- Send ETA reminder 4 hours before arrival

**On ARRIVED:**
- Alert warehouse team for immediate action
- Create pending GRN task

**On OVERDUE (ETA passed):**
- Send alert to procurement and warehouse
- Flag for follow-up

---

## 5. Controllers & Services

### 5.1 ASNController

```php
class ASNController extends Controller
{
    public function index(Request $request): JsonResponse
    public function show(int $id): JsonResponse
    public function store(StoreASNRequest $request): JsonResponse
    public function update(UpdateASNRequest $request, int $id): JsonResponse
    public function destroy(int $id): JsonResponse
    
    // Status transitions
    public function send(int $id): JsonResponse
    public function markInTransit(int $id): JsonResponse
    public function markArrived(int $id): JsonResponse
    public function receive(int $id): JsonResponse
    
    // Lookups
    public function getByPO(int $poId): JsonResponse
    public function getByVendor(int $vendorId): JsonResponse
    public function getArrivingToday(): JsonResponse
    public function getOverdue(): JsonResponse
}
```

### 5.2 ASNService

```php
class ASNService
{
    public function createASN(array $data): ASN
    public function updateASN(int $id, array $data): ASN
    public function cancelASN(int $id): bool
    public function changeStatus(int $id, string $status): ASN
    public function validateASNData(array $data): bool
    public function checkPOAvailability(int $poId, array $items): bool
    public function generateASNNumber(): string
    public function notifyWarehouse(ASN $asn): void
    public function linkToGRN(int $asnId, int $grnId): void
}
```

---

## 6. Models

### 6.1 ASN Model

```php
class ASN extends Model
{
    protected $table = 'asn_header';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'asn_number', 'po_id', 'vendor_id', 'warehouse_id',
        'shipment_date', 'estimated_arrival', 'actual_arrival',
        'ship_from_address', 'ship_to_address',
        'carrier_name', 'tracking_number', 'vehicle_number',
        'driver_name', 'driver_phone', 'customer_reference',
        'asn_status', 'created_by', 'updated_by'
    ];
    
    protected $casts = [
        'shipment_date' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_arrival' => 'datetime',
    ];
    
    // Relationships
    public function purchaseOrder()
    public function vendor()
    public function warehouse()
    public function lineItems()
    public function documents()
    public function grn()
    
    // Scopes
    public function scopeArrivingToday($query)
    public function scopeOverdue($query)
    public function scopeByStatus($query, $status)
    
    // Methods
    public function canEdit(): bool
    public function canCancel(): bool
    public function isOverdue(): bool
}
```

### 6.2 ASNLineItem Model

```php
class ASNLineItem extends Model
{
    protected $table = 'asn_line_items';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'asn_id', 'po_line_item_id', 'material_id',
        'pallet_id', 'sscc', 'shipped_qty', 'uom_id',
        'batch_number', 'lot_number', 'manufacturing_date', 'expiry_date',
        'gross_weight', 'net_weight', 'weight_uom',
        'length', 'width', 'height', 'dimension_uom',
        'line_status', 'received_qty'
    ];
    
    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'shipped_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
    ];
    
    // Relationships
    public function asn()
    public function poLineItem()
    public function material()
    public function uom()
}
```

---

## 7. Permissions & RBAC

### 7.1 Module Code
`ASN` - Advance Shipping Notice

### 7.2 Role Permissions

| Role          | View | Create | Edit | Approve | Delete |
|---------------|------|--------|------|---------|--------|
| PROC_EXE      | ✓    | ✓      | ✓    | ✗       | ✗      |
| PROC_MGR      | ✓    | ✓      | ✓    | ✓       | ✓      |
| STOREKEEPER   | ✓    | ✗      | ✗    | ✗       | ✗      |
| STORE_MGR     | ✓    | ✗      | ✓    | ✓       | ✗      |
| ADMIN         | ✓    | ✓      | ✓    | ✓       | ✓      |

### 7.3 Update RbacSeeder

Add ASN module permissions to the permission matrix in `database/seeders/RbacSeeder.php`.

---

## 8. Frontend Integration Points

### 8.1 Dashboard Widgets

- **Arriving Today**: List of ASNs expected today
- **Overdue ASNs**: ASNs past ETA without arrival confirmation
- **In Transit**: Current shipments on the way

### 8.2 Warehouse Planning Board

- Visual timeline of expected arrivals
- Dock scheduling based on ASN ETAs
- Resource allocation planning

### 8.3 ASN Management Screen

- Create/Edit ASN form
- ASN list with filters (status, date range, vendor, PO)
- ASN detail view with line items
- Status tracking timeline
- Document upload/view

---

## 9. Integration with Existing Modules

### 9.1 Purchase Order Integration

- Link ASN to PO
- Validate shipped quantities against PO
- Update PO status when ASN is created
- Show ASN status on PO detail page

### 9.2 GRN Integration

- Pre-populate GRN from ASN data
- Link GRN to ASN
- Update ASN status when GRN is created
- Compare ASN quantities with actual received quantities

### 9.3 Vendor Portal (Future)

- Allow vendors to create ASNs
- Real-time tracking updates
- Document upload by vendor

---

## 10. Implementation Phases

### Phase 1: Core ASN Module (Week 1-2)
- Database migrations
- ASN models
- Basic CRUD operations
- ASN number generation

### Phase 2: Business Logic (Week 3)
- Status workflow
- Validations
- PO integration
- Quantity checks

### Phase 3: Notifications & Alerts (Week 4)
- Email notifications
- Dashboard alerts
- Overdue detection
- ETA reminders

### Phase 4: GRN Integration (Week 5)
- Link ASN to GRN
- Pre-populate GRN from ASN
- Quantity reconciliation
- Status synchronization

### Phase 5: Reporting & Analytics (Week 6)
- ASN reports
- Delivery performance metrics
- Vendor on-time delivery tracking
- Warehouse receiving efficiency

---

## 11. Testing Checklist

### Unit Tests
- [ ] ASN creation with valid data
- [ ] ASN validation rules
- [ ] Status transitions
- [ ] Quantity validations
- [ ] ASN number generation

### Integration Tests
- [ ] ASN to PO linking
- [ ] ASN to GRN linking
- [ ] Notification triggers
- [ ] Permission checks

### API Tests
- [ ] Create ASN endpoint
- [ ] Update ASN endpoint
- [ ] Status change endpoints
- [ ] List/filter endpoints
- [ ] Document upload

---

## 12. Sample curl Commands

### Create ASN
```bash
curl --location 'http://127.0.0.1:8000/api/v1/asn' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{
  "po_id": 1,
  "vendor_id": 1,
  "warehouse_id": 1,
  "shipment_date": "2024-10-25T09:00:00",
  "estimated_arrival": "2024-10-26T14:00:00",
  "carrier_name": "BlueDart Logistics",
  "tracking_number": "BL-449201",
  "vehicle_number": "MH-04-EY-1234",
  "line_items": [
    {
      "po_line_item_id": 1,
      "material_id": 1,
      "shipped_qty": 500,
      "uom_id": 1,
      "batch_number": "BATCH-OCT-23"
    }
  ]
}'
```

### Get ASNs Arriving Today
```bash
curl --location 'http://127.0.0.1:8000/api/v1/asn/arriving-today' \
--header 'Cookie: auth_token=YOUR_TOKEN'
```

### Mark ASN as Arrived
```bash
curl --location 'http://127.0.0.1:8000/api/v1/asn/1/arrived' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{
  "actual_arrival": "2024-10-26T13:45:00"
}'
```

---

## 13. Next Steps

1. Review and approve implementation plan
2. Create database migrations
3. Implement models and relationships
4. Build API endpoints
5. Add RBAC permissions
6. Implement notification system
7. Integrate with PO and GRN modules
8. Build frontend screens
9. Testing and QA
10. Documentation and training

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-10  
**Status:** Draft - Pending Review
