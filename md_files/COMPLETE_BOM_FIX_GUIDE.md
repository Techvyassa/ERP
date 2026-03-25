# Complete BOM Permission Fix Guide

## Problem
"Insufficient permissions" alert appears when accessing the BOM Header page at `/org/techvyassa/bom-header`.

## Root Causes
1. BOM module permissions were not seeded in the database
2. Permission cache is storing old data without BOM permissions

## Complete Solution

### Step 1: Verify Current State

Run the diagnostic script to see what permissions exist:

```bash
php check_bom_permissions.php
```

This will show:
- Which roles have BOM permissions
- Which users can access BOM
- What specific permissions each role has

### Step 2: Add BOM Permissions

Run the migration command to add BOM permissions to all roles:

```bash
php artisan tenant:add-bom-permissions techvyassa
```

Expected output:
```
Adding BOM permissions for organization: TechVyassa
  ✓ Added BOM permissions for ADMIN
  ✓ Added BOM permissions for PROC_EXE
  ✓ Added BOM permissions for PPC_USER
  ...

✅ BOM permissions processed successfully!
   Added: X
   Updated: Y
```

### Step 3: Clear Permission Cache

**Option A: Using the diagnostic script**
```bash
php clear_permission_cache.php
```

**Option B: Using Laravel cache command**
```bash
php artisan cache:clear
```

**Option C: Clear specific user cache**
```bash
php artisan cache:clear-permissions
```

### Step 4: Verify the Fix

1. **Check database directly:**
```sql
USE erp_techvyassa;

SELECT 
    rm.role_code, 
    rp.module_code, 
    rp.can_view, 
    rp.can_create, 
    rp.can_edit, 
    rp.can_approve, 
    rp.can_delete
FROM role_permissions rp
JOIN role_master rm ON rp.role_id = rm.id
WHERE rp.module_code = 'BOM'
ORDER BY rm.role_code;
```

2. **Test in browser:**
   - Navigate to: `http://127.0.0.1:8000/org/techvyassa/bom-header`
   - The page should load without the "Insufficient permissions" alert
   - You should see the BOM list (even if empty)

### Step 5: Refresh Browser

- Clear browser cache or do a hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
- If using cookies for JWT, you might need to log out and log back in

## Expected Permission Matrix

After running the fix, these permissions should exist:

| Role | View | Create | Edit | Approve | Delete |
|------|------|--------|------|---------|--------|
| ADMIN | ✓ | ✓ | ✓ | ✓ | ✓ |
| PPC_USER | ✓ | ✓ | ✓ | ✗ | ✗ |
| PROC_EXE | ✓ | ✗ | ✗ | ✗ | ✗ |
| PROC_MGR | ✓ | ✗ | ✗ | ✗ | ✗ |
| All Others | ✗ | ✗ | ✗ | ✗ | ✗ |

## Troubleshooting

### Issue: Command not found
```bash
php artisan tenant:add-bom-permissions techvyassa
# Error: Command "tenant:add-bom-permissions" is not defined
```

**Solution:** The command file was created but Laravel hasn't discovered it yet.
```bash
php artisan clear-compiled
composer dump-autoload
php artisan config:clear
```

### Issue: Still getting "Insufficient permissions" after running commands

**Possible causes:**

1. **Cache not cleared properly**
   ```bash
   # Try all cache clearing methods
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   
   # If using Redis
   redis-cli FLUSHALL
   ```

2. **Wrong user logged in**
   - Check which user you're logged in as
   - Verify that user's role has BOM permissions
   - Try logging out and back in

3. **JWT token has old permissions cached**
   - Log out completely
   - Clear browser cookies
   - Log back in to get a fresh token

4. **Database connection issue**
   - Verify you're connected to the correct tenant database
   - Check `organizations` table in control DB for correct `tenant_db_name`

### Issue: Permissions exist but still denied

**Check middleware order in routes/api.php:**

The BOM routes should have this middleware chain:
```php
Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])
    ->group(function () {
        Route::middleware(['check.module.permission:BOM'])
            ->prefix('bom-headers')
            ->group(function () {
                // BOM routes
            });
    });
```

### Issue: Need to add permissions for multiple tenants

Create a script to loop through all tenants:

```bash
# Get all organization slugs
php artisan tinker
>>> App\Models\Control\Organization::pluck('org_slug')->each(function($slug) {
...     Artisan::call('tenant:add-bom-permissions', ['org_slug' => $slug]);
...     echo "Processed: $slug\n";
... });
```

## Manual Database Fix (Last Resort)

If the command doesn't work, you can manually insert permissions:

```sql
USE erp_techvyassa;

-- Add BOM permission for ADMIN role
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete, created_by)
SELECT id, 'BOM', 1, 1, 1, 1, 1, NULL
FROM role_master
WHERE role_code = 'ADMIN'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 1, can_delete = 1;

-- Add BOM permission for PPC_USER role
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete, created_by)
SELECT id, 'BOM', 1, 1, 1, 0, 0, NULL
FROM role_master
WHERE role_code = 'PPC_USER'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 0, can_delete = 0;

-- Add BOM permission for PROC_EXE role
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete, created_by)
SELECT id, 'BOM', 1, 0, 0, 0, 0, NULL
FROM role_master
WHERE role_code = 'PROC_EXE'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 0, can_edit = 0, can_approve = 0, can_delete = 0;

-- Add BOM permission for PROC_MGR role
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete, created_by)
SELECT id, 'BOM', 1, 0, 0, 0, 0, NULL
FROM role_master
WHERE role_code = 'PROC_MGR'
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 0, can_edit = 0, can_approve = 0, can_delete = 0;

-- Verify
SELECT rm.role_code, rp.* 
FROM role_permissions rp
JOIN role_master rm ON rp.role_id = rm.id
WHERE rp.module_code = 'BOM';
```

After manual insert, clear cache:
```bash
php artisan cache:clear
```

## Prevention for Future Modules

When adding new modules, remember to:

1. Add module code to `DefaultRolePermissionSeeder::MODULES`
2. Add module permissions to `RbacSeeder` permission matrix
3. Run seeders for new tenants
4. Create migration command for existing tenants
5. Clear permission cache after updates

## Files Created/Modified

### Created:
- `app/Console/Commands/AddBOMPermissions.php` - Migration command
- `check_bom_permissions.php` - Diagnostic script
- `clear_permission_cache.php` - Cache clearing script

### Modified:
- `database/seeders/Tenant/DefaultRolePermissionSeeder.php` - Added BOM to modules
- `database/seeders/Tenant/RbacSeeder.php` - Added BOM permissions to all roles

## Quick Reference Commands

```bash
# Diagnostic
php check_bom_permissions.php

# Fix permissions
php artisan tenant:add-bom-permissions techvyassa

# Clear cache
php artisan cache:clear
php clear_permission_cache.php

# Verify
php artisan tinker
>>> DB::connection('tenant')->table('role_permissions')->where('module_code', 'BOM')->count()
```

## Success Indicators

✅ Diagnostic script shows BOM permissions for all roles
✅ Command reports "Added" or "Updated" for each role
✅ Cache clear confirms cleared entries
✅ Browser shows BOM page without alert
✅ API call to `/api/v1/bom-headers` returns 200 (not 403)

## Still Having Issues?

If you've tried everything and still getting the error:

1. Share the output of `php check_bom_permissions.php`
2. Share the output of `php artisan tenant:add-bom-permissions techvyassa`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Check browser console for any JavaScript errors
5. Verify JWT token payload (decode at jwt.io)
