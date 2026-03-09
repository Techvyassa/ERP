# Tenant Master Tables - Migration Summary

## Overview
Created 19 migration files for tenant master tables covering Organization, Inventory, Tax, Vendor, and BOM modules.

## Migration Files Created

### Organization Masters (6 tables)
1. **2024_01_01_000001_create_department_master_table.php**
   - Hierarchical department structure with cost center mapping
   - Self-referencing parent_dept_id for hierarchy

2. **2024_01_01_000002_create_role_master_table.php**
   - System roles (ADMIN, BUYER, QC_INSP, etc.)
   - Replaces hardcoded role strings

3. **2024_01_01_000003_create_role_permissions_table.php**
   - Module-level access control per role
   - Permissions: view, create, edit, approve, delete
   - Unique constraint on (role_id, module_code)

4. **2024_01_01_000004_update_users_table.php**
   - Updates existing users table
   - Adds dept_id foreign key
   - Adds full_name computed column
   - Links role_id to role_master

5. **2024_01_01_000005_create_zone_master_table.php**
   - Geographic zones (NORTH, SOUTH, EAST, WEST)

6. **2024_01_01_000006_create_approval_matrix_master_table.php**
   - Configurable approval thresholds
   - Document types: PR, PO, PAYMENT, DN
   - Multi-level approval with amount ranges

### Inventory Masters (5 tables)
7. **2024_01_01_000007_create_uom_master_table.php**
   - Units of Measurement with conversion factors
   - Self-referencing base_uom_id for conversions

8. **2024_01_01_000008_create_hsn_codes_table.php**
   - HSN codes for tax classification
   - Links to default GST slab

9. **2024_01_01_000009_create_gst_taxes_table.php**
   - GST rate slabs (CGST, SGST, IGST, UGST)
   - Effective date ranges for rate history
   - Adds foreign key to hsn_codes

10. **2024_01_01_000010_create_currency_master_table.php**
    - Multi-currency support
    - Exchange rates vs base currency (INR)
    - is_base_currency flag

11. **2024_01_01_000011_create_warehouse_master_table.php**
    - Physical storage locations
    - Types: RM, FG, PKG, REJECTION, WIP
    - Incharge user assignment

12. **2024_01_01_000012_create_material_master_table.php**
    - **CRITICAL TABLE** - Raw materials, packaging, consumables
    - QC rules, reorder levels, safety stock
    - Batch tracking, valuation methods (FIFO/AVG/STD)
    - Multiple UOM support (stock vs purchase)

13. **2024_01_01_000013_create_product_master_table.php**
    - **CRITICAL TABLE** - Finished goods
    - Removed raw_materials JSON column
    - Links to BOM for material composition

14. **2024_01_01_000014_create_bin_locations_table.php**
    - Rack/Shelf/Bin structure within warehouses
    - Physical slot locations (R01-S02-B03)

### Vendor Masters (3 tables)
15. **2024_01_01_000015_create_vendor_master_table.php**
    - **CRITICAL TABLE** - Supplier registry
    - GSTIN, PAN, MSME category
    - Payment terms, credit days
    - Banking details, approval status
    - Vendor rating and blacklist flag

16. **2024_01_01_000016_create_vendor_contacts_table.php**
    - Multiple contacts per vendor
    - Contact types: SALES, FINANCE, LOGISTICS, GM
    - Primary contact flag

17. **2024_01_01_000017_create_vendor_material_map_table.php**
    - Approved Vendor List (AVL)
    - Vendor-specific pricing, MOQ, lead time
    - Preferred vendor flag
    - Unique constraint on (vendor_id, material_id)

### BOM Masters (2 tables)
18. **2024_01_01_000018_create_bom_header_table.php**
    - **CRITICAL TABLE** - Bill of Materials header
    - Version management with effective dates
    - Status: DRAFT, ACTIVE, OBSOLETE
    - Unique constraint on (product_id, version)

19. **2024_01_01_000019_create_bom_detail_table.php**
    - **CRITICAL TABLE** - BOM component lines
    - Replaces raw_materials JSON in product_master
    - Scrap percentage and effective quantity
    - Substitute material support

## Key Features

### Foreign Key Relationships
- All tables properly linked with foreign keys
- Cascade deletes where appropriate
- Set null for soft references

### Indexes
- Primary keys on all tables
- Unique constraints on codes (dept_code, role_code, etc.)
- Foreign key indexes for performance
- Composite indexes for common queries

### Data Types
- SERIAL for auto-increment IDs
- VARCHAR with appropriate lengths
- DECIMAL for monetary and quantity values
- BOOLEAN for flags
- TIMESTAMPTZ for timestamps
- TEXT for long descriptions

### Constraints
- NOT NULL on required fields
- DEFAULT values where appropriate
- UNIQUE constraints on business keys
- CHECK constraints via validation

### Computed Columns
- users.full_name: CONCAT(first_name, ' ', last_name)
- bom_detail.effective_qty: qty_required * (1 + scrap_percent / 100)

## Migration Order

Migrations are numbered to ensure proper execution order based on dependencies:

1. Independent tables first (department, role, zone, uom, hsn, gst, currency)
2. Tables with self-references (department, uom)
3. Dependent tables (users, warehouse, material, product)
4. Mapping tables (role_permissions, vendor_material_map)
5. Transaction tables (bom_header, bom_detail)

## Running Migrations

### For New Tenant
```bash
# Run all tenant migrations
php artisan tenant:migrate {org_id}
```

### For Existing Tenant
```bash
# Run specific migration
php artisan tenant:migrate {org_id} --path=database/migrations/tenant/2024_01_01_000001_create_department_master_table.php
```

### Rollback
```bash
# Rollback last batch
php artisan tenant:migrate:rollback {org_id}

# Rollback all
php artisan tenant:migrate:reset {org_id}
```

## Seeding Data

After running migrations, seed default data:

```bash
# Seed default roles
php artisan tenant:seed {org_id} --class=RoleMasterSeeder

# Seed default UOMs
php artisan tenant:seed {org_id} --class=UomMasterSeeder

# Seed default GST rates
php artisan tenant:seed {org_id} --class=GstTaxesSeeder

# Seed base currency (INR)
php artisan tenant:seed {org_id} --class=CurrencyMasterSeeder
```

## Critical Tables

These tables are marked as CRITICAL and require special attention:

1. **material_master** - Core inventory control
2. **product_master** - Finished goods
3. **vendor_master** - Supplier management
4. **bom_header** - Production planning
5. **bom_detail** - Material requirements

## Data Validation Rules

### Department Master
- dept_code: Unique, uppercase, no spaces
- parent_dept_id: Cannot create circular references

### Role Master
- role_code: Unique, uppercase with underscores
- At least one ADMIN role must exist

### Material Master
- material_code: Auto-generated or manual with prefix
- reorder_level <= safety_stock validation
- qc_required = true for RAW materials

### Vendor Master
- GSTIN: 15 characters, format validation
- PAN: 10 characters, format validation
- is_approved must be true before PO creation

### BOM
- Only one ACTIVE BOM per product at a time
- effective_from < effective_to validation
- Version numbers must be sequential

## Performance Considerations

### Indexes Created
- All foreign keys indexed
- Unique constraints on business keys
- Composite indexes on common query patterns
- is_active flags indexed for filtering

### Query Optimization
- Use indexes for filtering (is_active, status fields)
- Avoid SELECT * - specify columns
- Use joins instead of subqueries where possible
- Paginate large result sets

## Security Considerations

### Sensitive Data
- vendor_master.bank_account_no: Should be encrypted
- users.password_hash: Already hashed
- Audit trail via created_by, updated_by

### Soft Deletes
- Use is_active flag instead of hard deletes
- Maintain referential integrity
- Audit trail preserved

## Testing Checklist

- [ ] All migrations run without errors
- [ ] Foreign keys properly created
- [ ] Unique constraints working
- [ ] Default values applied
- [ ] Indexes created
- [ ] Computed columns working
- [ ] Cascade deletes working correctly
- [ ] Rollback works without errors

## Next Steps

1. Create model classes for each table
2. Create seeders for default data
3. Create factories for testing
4. Implement validation rules
5. Create API endpoints
6. Add audit logging
7. Implement soft deletes
8. Create reports and analytics

## Notes

- All timestamps use TIMESTAMPTZ for timezone support
- All monetary values use DECIMAL for precision
- All quantity values use DECIMAL(12,3) for 3 decimal places
- All percentage values use DECIMAL(5,2) for 2 decimal places
- Boolean flags default to appropriate values
- Foreign keys use onDelete actions appropriately

## Support

For issues or questions:
- Check migration file comments
- Review foreign key relationships
- Verify data types match requirements
- Test with sample data before production
