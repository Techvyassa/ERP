# BOM Permission Fix

## Problem
The BOM Header page shows "Insufficient permissions" alert because the BOM module permissions were not seeded in the database.

## Root Cause
The `BOM` module was missing from the permission seeders:
- `database/seeders/Tenant/DefaultRolePermissionSeeder.php`
- `database/seeders/Tenant/RbacSeeder.php`

## Solution Applied

### 1. Updated DefaultRolePermissionSeeder
Added `'BOM'` to the MODULES constant so it will be included when seeding default permissions for new tenants.

**File:** `database/seeders/Tenant/DefaultRolePermissionSeeder.php`

```php
private const MODULES = [
    // ... existing modules ...
    'BOM',          // Bill of Materials
];
```

### 2. Updated RbacSeeder
Added BOM permissions to all role definitions in the permission matrix:

**File:** `database/seeders/Tenant/RbacSeeder.php`

- **ADMIN**: Full access (view, create, edit, approve, delete)
- **PPC_USER**: View, Create, Edit access
- **PROC_EXE**: View only access
- **PROC_MGR**: View only access
- **All other roles**: No access

### 3. Created Migration Command
Created a command to add BOM permissions to existing tenants without re-running all seeders.

**File:** `app/Console/Commands/AddBOMPermissions.php`

## How to Fix Existing Tenants

### Option 1: Run the Migration Command (Recommended)

For a specific tenant:
```bash
php artisan tenant:add-bom-permissions techvyassa
```

This command will:
- Add BOM permissions to all roles in the tenant database
- Update existing BOM permissions if they already exist
- Show a summary of added/updated permissions

### Option 2: Re-run the RBAC Seeder

If you want to refresh all permissions:
```bash
php artisan db:seed --class=Database\\Seeders\\Tenant\\RbacSeeder
```

**Warning:** This will update all role permissions, not just BOM.

### Option 3: Manual Database Insert

Connect to the tenant database and run:

```sql
-- For ADMIN role (full access)
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
SELECT id, 'BOM', 1, 1, 1, 1, 1
FROM role_master
WHERE role_code = 'ADMIN'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 1, can_delete = 1;

-- For PPC_USER role (view, create, edit)
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
SELECT id, 'BOM', 1, 1, 1, 0, 0
FROM role_master
WHERE role_code = 'PPC_USER'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 0, can_delete = 0;

-- For PROC_EXE and PROC_MGR roles (view only)
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
SELECT id, 'BOM', 1, 0, 0, 0, 0
FROM role_master
WHERE role_code IN ('PROC_EXE', 'PROC_MGR')
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 0, can_edit = 0, can_approve = 0, can_delete = 0;
```

## Verification

After applying the fix, verify the permissions:

1. **Check database:**
```sql
SELECT rm.role_code, rp.module_code, rp.can_view, rp.can_create, rp.can_edit, rp.can_approve, rp.can_delete
FROM role_permissions rp
JOIN role_master rm ON rp.role_id = rm.id
WHERE rp.module_code = 'BOM'
ORDER BY rm.role_code;
```

2. **Test in browser:**
   - Navigate to: `http://127.0.0.1:8000/org/techvyassa/bom-header`
   - The "Insufficient permissions" alert should no longer appear
   - The BOM list should load successfully

## For New Tenants

New tenants created after this fix will automatically have BOM permissions seeded when they are provisioned, as the seeders have been updated.

## Files Modified

1. `database/seeders/Tenant/DefaultRolePermissionSeeder.php` - Added BOM to modules list
2. `database/seeders/Tenant/RbacSeeder.php` - Added BOM permissions to all roles
3. `app/Console/Commands/AddBOMPermissions.php` - Created migration command

## Permission Matrix for BOM Module

| Role | View | Create | Edit | Approve | Delete |
|------|------|--------|------|---------|--------|
| ADMIN | ✓ | ✓ | ✓ | ✓ | ✓ |
| PPC_USER | ✓ | ✓ | ✓ | ✗ | ✗ |
| PROC_EXE | ✓ | ✗ | ✗ | ✗ | ✗ |
| PROC_MGR | ✓ | ✗ | ✗ | ✗ | ✗ |
| Others | ✗ | ✗ | ✗ | ✗ | ✗ |
