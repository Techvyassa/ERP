# ASN Migration Review & Recommendations

## Current State Analysis

### Existing Migrations
- `2024_01_01_000022_create_asn_headers_table.php`
- `2024_01_01_000023_create_asn_line_items_table.php`

---

## Comparison with Implementation Plan

### ✅ Fields Present in Both

#### asn_headers
- ✓ asn_number
- ✓ po_id
- ✓ vendor_id
- ✓ ship_date (as shipment_date in plan)
- ✓ eta (as estimated_arrival in plan)
- ✓ carrier_name
- ✓ tracking_number
- ✓ vehicle_number
- ✓ ship_from_address
- ✓ ship_to_address
- ✓ status
- ✓ created_by
- ✓ timestamps

#### asn_line_items
- ✓ asn_id
- ✓ po_line_id (as po_line_item_id in plan)
- ✓ material_id
- ✓ shipped_qty
- ✓ uom_id
- ✓ batch_number
- ✓ manufacturing_date
- ✓ expiry_date
- ✓ pallet_id
- ✓ gross_weight
- ✓ net_weight

---

## ⚠️ Missing Fields

### asn_headers Table

1. **warehouse_id** (CRITICAL)
   - Purpose: Identifies which warehouse will receive the shipment
   - Type: `unsignedBigInteger`
   - FK: warehouse_master
   - Required for multi-warehouse operations

2. **actual_arrival** (IMPORTANT)
   - Purpose: Records when shipment actually arrived
   - Type: `dateTime()->nullable()`
   - Used for delivery performance tracking

3. **driver_name** (NICE TO HAVE)
   - Purpose: Driver identification
   - Type: `string(100)->nullable()`

4. **driver_phone** (NICE TO HAVE)
   - Purpose: Contact driver during transit
   - Type: `string(20)->nullable()`

5. **customer_reference** (NICE TO HAVE)
   - Purpose: Internal project/department code
   - Type: `string(100)->nullable()`

6. **updated_by** (AUDIT)
   - Purpose: Track who last modified the record
   - Type: `unsignedBigInteger()->nullable()`
   - FK: users

### asn_line_items Table

1. **sscc** (IMPORTANT)
   - Purpose: Serial Shipping Container Code (GS1 standard)
   - Type: `string(50)->nullable()`
   - Used for barcode scanning

2. **lot_number** (IMPORTANT)
   - Purpose: Separate from batch_number for dual tracking
   - Type: `string(50)->nullable()`

3. **line_status** (CRITICAL)
   - Purpose: Track individual line item status
   - Type: `enum('PENDING', 'RECEIVED', 'PARTIAL', 'CANCELLED')`
   - Default: 'PENDING'

4. **received_qty** (CRITICAL)
   - Purpose: Track how much was actually received vs shipped
   - Type: `decimal(15,3)->default(0)`
   - Used for variance analysis

5. **weight_uom** (NICE TO HAVE)
   - Purpose: Unit for weight measurements
   - Type: `string(10)->default('KG')`

6. **Dimensions** (NICE TO HAVE)
   - length: `decimal(10,2)->nullable()`
   - width: `decimal(10,2)->nullable()`
   - height: `decimal(10,2)->nullable()`
   - dimension_uom: `string(10)->default('CM')`

7. **material_description** (NICE TO HAVE)
   - Purpose: Vendor's description of material
   - Type: `string(300)->nullable()`

---

## 🔄 Status Enum Differences

### Current Migration
```php
enum('status', ['SENT', 'ACKNOWLEDGED', 'ARRIVED', 'CANCELLED'])
```

### Implementation Plan
```php
enum('status', ['DRAFT', 'SENT', 'IN_TRANSIT', 'ARRIVED', 'RECEIVED', 'CANCELLED'])
```

### Recommendation
Use the implementation plan version for better workflow tracking:
- **DRAFT**: ASN being prepared (not yet sent to warehouse)
- **SENT**: ASN sent to warehouse team
- **IN_TRANSIT**: Shipment confirmed on the way
- **ARRIVED**: Shipment at dock/warehouse
- **RECEIVED**: GRN created and linked
- **CANCELLED**: ASN cancelled

---

## 📋 Recommended Migration Updates

### Option 1: Create New Migration (Recommended)
Create an additive migration to add missing fields without breaking existing data.

```php
// 2024_03_10_000001_add_missing_fields_to_asn_tables.php

public function up(): void
{
    Schema::connection('tenant')->table('asn_headers', function (Blueprint $table) {
        // Add warehouse_id after vendor_id
        $table->unsignedBigInteger('warehouse_id')
            ->after('vendor_id')
            ->nullable()
            ->comment('FK → warehouse_master');
        
        // Add actual_arrival after eta
        $table->dateTime('actual_arrival')
            ->after('eta')
            ->nullable()
            ->comment('Actual arrival timestamp at warehouse');
        
        // Add driver details after vehicle_number
        $table->string('driver_name', 100)
            ->after('vehicle_number')
            ->nullable()
            ->comment('Driver name for contact');
        
        $table->string('driver_phone', 20)
            ->after('driver_name')
            ->nullable()
            ->comment('Driver contact number');
        
        // Add customer_reference after ship_to_address
        $table->string('customer_reference', 100)
            ->after('ship_to_address')
            ->nullable()
            ->comment('Internal project/department code');
        
        // Add updated_by after created_by
        $table->unsignedBigInteger('updated_by')
            ->after('created_by')
            ->nullable()
            ->comment('FK → users');
        
        // Update status enum
        DB::connection('tenant')->statement("
            ALTER TABLE asn_headers 
            MODIFY COLUMN status ENUM('DRAFT', 'SENT', 'IN_TRANSIT', 'ARRIVED', 'RECEIVED', 'CANCELLED') 
            DEFAULT 'DRAFT'
        ");
        
        // Add foreign keys
        $table->foreign('warehouse_id')
            ->references('id')
            ->on('warehouse_master')
            ->onDelete('restrict');
        
        $table->foreign('updated_by')
            ->references('id')
            ->on('users')
            ->onDelete('set null');
        
        // Add index
        $table->index('warehouse_id');
    });
    
    Schema::connection('tenant')->table('asn_line_items', function (Blueprint $table) {
        // Add sscc after pallet_id
        $table->string('sscc', 50)
            ->after('pallet_id')
            ->nullable()
            ->comment('Serial Shipping Container Code (GS1)');
        
        // Add lot_number after batch_number
        $table->string('lot_number', 50)
            ->after('batch_number')
            ->nullable()
            ->comment('Lot number (separate from batch)');
        
        // Add line_status after net_weight
        $table->enum('line_status', ['PENDING', 'RECEIVED', 'PARTIAL', 'CANCELLED'])
            ->after('net_weight')
            ->default('PENDING')
            ->comment('Individual line item status');
        
        // Add received_qty after line_status
        $table->decimal('received_qty', 15, 3)
            ->after('line_status')
            ->default(0)
            ->comment('Quantity actually received');
        
        // Add weight_uom after net_weight
        $table->string('weight_uom', 10)
            ->after('net_weight')
            ->default('KG')
            ->comment('Unit of measure for weights');
        
        // Add dimensions after weight_uom
        $table->decimal('length', 10, 2)
            ->after('weight_uom')
            ->nullable()
            ->comment('Package length');
        
        $table->decimal('width', 10, 2)
            ->after('length')
            ->nullable()
            ->comment('Package width');
        
        $table->decimal('height', 10, 2)
            ->after('width')
            ->nullable()
            ->comment('Package height');
        
        $table->string('dimension_uom', 10)
            ->after('height')
            ->default('CM')
            ->comment('Unit of measure for dimensions');
        
        // Add material_description after material_id
        $table->string('material_description', 300)
            ->after('material_id')
            ->nullable()
            ->comment('Vendor description of material');
        
        // Add indexes
        $table->index('line_status');
        $table->index('sscc');
    });
}

public function down(): void
{
    Schema::connection('tenant')->table('asn_headers', function (Blueprint $table) {
        $table->dropForeign(['warehouse_id']);
        $table->dropForeign(['updated_by']);
        $table->dropColumn([
            'warehouse_id',
            'actual_arrival',
            'driver_name',
            'driver_phone',
            'customer_reference',
            'updated_by'
        ]);
    });
    
    Schema::connection('tenant')->table('asn_line_items', function (Blueprint $table) {
        $table->dropColumn([
            'sscc',
            'lot_number',
            'line_status',
            'received_qty',
            'weight_uom',
            'length',
            'width',
            'height',
            'dimension_uom',
            'material_description'
        ]);
    });
}
```

### Option 2: Modify Existing Migrations (If Not Yet Run in Production)
If these migrations haven't been run in production, you can modify them directly.

---

## 🗂️ Missing Table: asn_documents

The implementation plan includes a documents table that's completely missing:

```php
// 2024_01_01_000024_create_asn_documents_table.php

public function up(): void
{
    Schema::connection('tenant')->create('asn_documents', function (Blueprint $table) {
        $table->id();
        
        $table->unsignedBigInteger('asn_id')->comment('FK → asn_headers');
        
        $table->enum('document_type', ['PACKING_LIST', 'INVOICE', 'CERTIFICATE', 'OTHER'])
            ->comment('Type of document attached');
        
        $table->string('document_name', 255)->comment('Original filename');
        $table->string('file_path', 500)->comment('Storage path');
        $table->integer('file_size')->nullable()->comment('File size in bytes');
        $table->string('mime_type', 100)->nullable()->comment('File MIME type');
        
        $table->unsignedBigInteger('uploaded_by')->nullable()->comment('FK → users');
        $table->timestamp('uploaded_at')->useCurrent();
        
        // Foreign Keys
        $table->foreign('asn_id')
            ->references('id')
            ->on('asn_headers')
            ->onDelete('cascade');
        
        $table->foreign('uploaded_by')
            ->references('id')
            ->on('users')
            ->onDelete('set null');
        
        // Indexes
        $table->index('asn_id');
        $table->index('document_type');
    });
}

public function down(): void
{
    Schema::connection('tenant')->dropIfExists('asn_documents');
}
```

---

## 🎯 Priority Recommendations

### CRITICAL (Must Have)
1. ✅ Add `warehouse_id` to asn_headers
2. ✅ Add `line_status` to asn_line_items
3. ✅ Add `received_qty` to asn_line_items
4. ✅ Update status enum to include full workflow
5. ✅ Create asn_documents table

### IMPORTANT (Should Have)
6. ✅ Add `actual_arrival` to asn_headers
7. ✅ Add `sscc` to asn_line_items
8. ✅ Add `lot_number` to asn_line_items
9. ✅ Add `updated_by` to asn_headers

### NICE TO HAVE (Could Have)
10. Add driver details (name, phone)
11. Add customer_reference
12. Add dimensions to line items
13. Add weight_uom and dimension_uom

---

## 📊 Table Naming Convention Note

**Current:** `asn_headers` and `asn_line_items`  
**Implementation Plan:** `asn_header` and `asn_line_items`

The current naming is actually better (plural for headers is more consistent with Laravel conventions). Keep the current naming.

---

## ✅ Next Steps

1. **Decide on approach:**
   - If migrations not run in production → Modify existing files
   - If migrations already run → Create additive migration

2. **Create missing migrations:**
   - Add missing fields migration
   - Create asn_documents table

3. **Update implementation plan** to match actual table names

4. **Run migrations:**
   ```bash
   php artisan tenant:migrate --org_slug=your-org-slug
   ```

5. **Verify schema:**
   ```bash
   php artisan tenant:migrate:status --org_slug=your-org-slug
   ```

---

## 🔍 Foreign Key Dependencies Check

Ensure these tables exist before running ASN migrations:
- ✓ purchase_orders (po_header)
- ✓ po_line_items
- ✓ vendor_master
- ✓ material_master
- ✓ uom_master
- ⚠️ warehouse_master (needed for warehouse_id)
- ✓ users

---

**Review Date:** 2026-03-10  
**Status:** Pending Implementation  
**Reviewer:** Kiro AI Assistant
