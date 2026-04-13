# Batch Size Column Removal - Summary

## Overview
Removed the `batch_size` column entirely from the `bom_header` table and all related code. The column was not being used in calculations and was causing confusion.

## Changes Made

### 1. Database Migration
**File**: `database/migrations/tenant/2024_01_15_000001_rename_batch_size_to_reference_batch_size.php`
- Changed from renaming `batch_size` to `reference_batch_size`
- Now **drops the `batch_size` column entirely**
- Rollback adds the column back if needed

### 2. BOMHeader Model
**File**: `app/Models/Tenant/BOMHeader.php`
- Removed `reference_batch_size` from `$fillable` array
- Removed `reference_batch_size` from `$casts` array

### 3. BOMHeaderController
**File**: `app/Http/Controllers/BOMHeaderController.php`
- Removed `reference_batch_size` validation rule from `bulkStore()` method
- Removed `reference_batch_size` from CSV import required headers
- Removed `reference_batch_size` from CSV template headers
- Removed `reference_batch_size` from CSV sample rows
- Removed `reference_batch_size` from `update()` method validation
- Removed `reference_batch_size` from `update()` method `$updateData`
- Removed `reference_batch_size` from `createBomRecord()` method validation
- Removed `reference_batch_size` from BOM creation logic

### 4. Production Orders View
**File**: `resources/views/tenant/production/orders/index.blade.php`
- Removed references to `reference_batch_size` in BOM display
- Removed "Standard Batch" display that showed the batch size

## Migration Instructions

Run the migration to drop the `batch_size` column from all tenant databases:

```bash
php artisan tenant:migrate --path=database/migrations/tenant/2024_01_15_000001_rename_batch_size_to_reference_batch_size.php
```

Or run all tenant migrations:

```bash
php artisan tenant:migrate
```

## Impact

- **Calculation Formula**: Unchanged - still uses `base_qty * (1 + scrap_percent/100) * run_qty`
- **API Endpoints**: Will no longer accept `reference_batch_size` in requests
- **UI**: BOM creation/editing forms no longer show batch size field
- **CSV Import**: CSV templates no longer include batch size column
- **Data**: Existing `batch_size` values will be deleted when migration runs

## Verification

After running the migration, verify:
1. BOM headers can be created without batch_size
2. BOM headers can be updated without batch_size
3. Material calculations still work correctly
4. Production orders can be created and MIR generated
5. All tests pass
