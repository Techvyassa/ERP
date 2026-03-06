# Migration Order Fix - Detailed Explanation

## The Problem

When attempting to register a new organization, the tenant provisioning failed with this error:

```
SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'gst_taxes'
SQL: alter table `hsn_codes` add constraint `hsn_codes_default_gst_id_foreign` 
     foreign key (`default_gst_id`) references `gst_taxes` (`id`) on delete restrict
```

## Root Cause

The migration files were running in the wrong order:

**BEFORE (Incorrect Order)**:
- `2024_01_01_000008_create_hsn_codes_table.php` (ran 8th)
- `2024_01_01_000009_create_gst_taxes_table.php` (ran 9th)

The `hsn_codes` table was being created BEFORE the `gst_taxes` table, but `hsn_codes` has a foreign key constraint that references `gst_taxes.id`:

```php
// In hsn_codes migration
$table->unsignedBigInteger('default_gst_id');
$table->foreign('default_gst_id')->references('id')->on('gst_taxes')->onDelete('restrict');
```

When Laravel tried to create this foreign key, the `gst_taxes` table didn't exist yet, causing the error.

## The Fix

Swapped the migration file names so `gst_taxes` is created before `hsn_codes`:

**AFTER (Correct Order)**:
- `2024_01_01_000008_create_gst_taxes_table.php` (runs 8th) ✅
- `2024_01_01_000009_create_hsn_codes_table.php` (runs 9th) ✅

Now when `hsn_codes` tries to create the foreign key, the `gst_taxes` table already exists.

## Why This Matters

Laravel runs migrations in alphabetical order based on the filename timestamp. The order is critical when tables have foreign key dependencies:

1. **Parent table** (referenced table) must be created first
2. **Child table** (referencing table) must be created second

In this case:
- `gst_taxes` is the **parent** (referenced by `hsn_codes.default_gst_id`)
- `hsn_codes` is the **child** (references `gst_taxes.id`)

## Commands Used to Fix

```bash
# Step 1: Rename hsn_codes to temporary name
mv database/migrations/tenant/2024_01_01_000008_create_hsn_codes_table.php database/migrations/tenant/temp_hsn.php

# Step 2: Rename gst_taxes to 000008 (earlier)
mv database/migrations/tenant/2024_01_01_000009_create_gst_taxes_table.php database/migrations/tenant/2024_01_01_000008_create_gst_taxes_table.php

# Step 3: Rename temp hsn_codes to 000009 (later)
mv database/migrations/tenant/temp_hsn.php database/migrations/tenant/2024_01_01_000009_create_hsn_codes_table.php
```

## Verification

After the fix, the migration order is now correct:

```
1. department_master
2. role_master
3. role_permissions
4. users
5. approval_matrix_master
6. uom_master
7. gst_taxes          ← Created FIRST
8. hsn_codes          ← Created SECOND (can now reference gst_taxes)
9. currency_master
10. warehouse_master
11. material_master
12. product_master
... (rest of migrations)
```

## Testing

To test the fix:
1. Drop the failed tenant database (if it was partially created)
2. Reset the organization status to PENDING
3. Try registration again
4. The migrations should now run successfully

## Lesson Learned

When adding foreign key relationships between tables:
1. Always check the migration order
2. Ensure parent tables are created before child tables
3. Use migration timestamps to control execution order
4. Test with fresh database to catch ordering issues early

## Related Files Modified

- `database/migrations/tenant/2024_01_01_000008_create_gst_taxes_table.php` (renamed from 000009)
- `database/migrations/tenant/2024_01_01_000009_create_hsn_codes_table.php` (renamed from 000008)
- `SCHEMA_FIXES_SUMMARY.md` (updated with migration order fix)
