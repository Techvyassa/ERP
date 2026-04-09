# RBAC Migration Guide: Specialized → Simplified Structure

## 📋 Overview

This guide explains how to safely migrate from the **old specialized role structure** to the **new simplified departmental RBAC** without breaking any existing data.

---

## 🎯 Migration Goals

### **Before (Old Structure):**
```
20+ Specialized Roles:
- PROC_EXE, PROC_MGR
- SECURITY_GUARD, SECURITY_SUPVR
- STOREKEEPER, STORE_MGR
- QC_TECH, QC_MGR
- AP_CLERK, FIN_MGR, CFO
- PPC_USER
- SALES_EXE, SALES_MGR
- CUST_EXE
- MAINT_TECH, MAINT_MGR
- ADMIN, PRODUCTION
```

### **After (New Structure):**
```
12 Simplified Roles:
- ADMIN (Global)
- MANAGER (Global)
- USER (Global)
- VIEWER (Global)
- SECURITY (Department)
- STORE (Department)
- QC (Department)
- PROCUREMENT (Department)
- PRODUCTION (Department)
- SALES (Department)
- CUSTOMER (Department)
- MAINTENANCE (Department)
```

---

## ⚠️ Pre-Migration Checklist

### **1. Backup Your Database**

```bash
# For each tenant database:
mysqldump -u root -p tenant_db_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Or backup all tenant databases:
mysqldump -u root -p --databases tenant1 tenant2 tenant3 > backup_all_tenants.sql
```

### **2. Document Current State**

Run this query to see current role distribution:

```sql
SELECT 
    rm.role_code,
    rm.role_name,
    COUNT(u.id) as active_users
FROM role_master rm
LEFT JOIN users u ON rm.id = u.role_id AND u.is_active = 1
GROUP BY rm.id, rm.role_code, rm.role_name
ORDER BY active_users DESC;
```

### **3. Test in Staging First**

Always test the migration on a staging environment with a copy of production data.

---

## 🚀 Migration Steps

### **Step 1: Run Dry Run (Preview Changes)**

```bash
php artisan rbac:migrate-simplified --dry-run
```

**What this does:**
- Shows current role statistics
- Lists all proposed user migrations
- Does NOT make any changes

**Expected Output:**
```
📊 CURRENT STATE:
+----------------+------------+
| Role Code      | User Count |
+----------------+------------+
| ADMIN          | 2          |
| PROC_EXE       | 5          |
| STOREKEEPER    | 3          |
| QC_TECH        | 2          |
+----------------+------------+

📋 PROPOSED MIGRATIONS:
+-------------------------------+-----------------+
| Migration Path                | Affected Users  |
+-------------------------------+-----------------+
| PROC_EXE → PROCUREMENT        | 5 users         |
| STOREKEEPER → STORE           | 3 users         |
| QC_TECH → QC                  | 2 users         |
+-------------------------------+-----------------+
```

### **Step 2: Review the Migration Plan**

Verify the following mappings are correct for your organization:

| Old Role | → | New Role | Impact |
|----------|---|----------|--------|
| PROC_EXE | → | PROCUREMENT | Same permissions, simpler name |
| PROC_MGR | → | PROCUREMENT | Manager approval moved to MANAGER role |
| SECURITY_GUARD | → | SECURITY | Consolidated |
| SECURITY_SUPVR | → | SECURITY | Consolidated |
| STOREKEEPER | → | STORE | Same permissions, simpler name |
| STORE_MGR | → | STORE | Manager approval moved to MANAGER role |
| QC_TECH | → | QC | Same permissions, simpler name |
| QC_MGR | → | QC | Manager approval moved to MANAGER role |
| AP_CLERK | → | USER | Standard user access |
| FIN_MGR | → | MANAGER | Department manager |
| CFO | → | MANAGER | Department manager |
| PPC_USER | → | VIEWER | Read-only access |
| SALES_EXE | → | SALES | Same permissions |
| SALES_MGR | → | SALES | Manager approval moved to MANAGER role |
| CUST_EXE | → | CUSTOMER | Same permissions |
| MAINT_TECH | → | MAINTENANCE | Same permissions |
| MAINT_MGR | → | MAINTENANCE | Manager approval moved to MANAGER role |

### **Step 3: Execute Migration**

```bash
# With confirmation prompt:
php artisan rbac:migrate-simplified

# Skip confirmation (for automation):
php artisan rbac:migrate-simplified --force
```

**What this does:**
1. ✅ Creates new simplified roles (if not exist)
2. ✅ Migrates users from old roles to new roles
3. ✅ Updates department-role mappings
4. ✅ Regenerates permission matrix
5. ✅ Deactivates old roles (doesn't delete them)
6. ✅ Wrapped in transaction (auto-rollback on failure)

### **Step 4: Verify Migration**

```bash
# Check role distribution
php artisan tinker
```

```php
// In tinker:
DB::connection('tenant')->table('role_master')
    ->select('role_code', 'is_active')
    ->orderBy('role_code')
    ->get();

// Check user assignments
DB::connection('tenant')->table('users')
    ->join('role_master', 'users.role_id', '=', 'role_master.id')
    ->select('users.email', 'role_master.role_code')
    ->where('users.is_active', true)
    ->get();
```

### **Step 5: Test Key Functionalities**

Test these critical paths:

1. **Login Flow:**
   ```bash
   # Test admin login
   curl -X POST http://your-domain/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@example.com","password":"password"}'
   ```

2. **Permission Check:**
   ```bash
   # Test /auth/me endpoint
   curl -X GET http://your-domain/api/v1/auth/me \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Department Access:**
   ```bash
   # Test department endpoints
   curl -X GET http://your-domain/api/v1/departments \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

4. **Role-Based Routing:**
   - Login as different users
   - Verify they see correct dashboard
   - Verify menu items match permissions

---

## 🔧 Manual Migration (Alternative)

If you prefer manual control, follow these SQL steps:

### **1. Create New Roles**

```sql
-- Insert simplified roles (safe to re-run)
INSERT INTO role_master (role_code, role_name, description, is_active, is_system_role, created_at)
VALUES 
    ('ADMIN', 'System Administrator', 'Full system access across all modules', 1, 1, NOW()),
    ('MANAGER', 'Manager', 'Department-level management and approvals', 1, 1, NOW()),
    ('USER', 'User', 'Standard operational access', 1, 1, NOW()),
    ('VIEWER', 'Viewer', 'Read-only access', 1, 1, NOW()),
    ('SECURITY', 'Security', 'Gate entry, visitor, and asset security', 1, 1, NOW()),
    ('STORE', 'Store', 'Inventory and warehouse operations', 1, 1, NOW()),
    ('QC', 'Quality Control', 'Quality inspection and compliance', 1, 1, NOW()),
    ('PROCUREMENT', 'Procurement', 'Vendor management and purchasing operations', 1, 1, NOW()),
    ('PRODUCTION', 'Production', 'Production planning and shop floor operations', 1, 1, NOW()),
    ('SALES', 'Sales', 'Sales, orders, and customer handling', 1, 1, NOW()),
    ('CUSTOMER', 'Customer', 'Customer portal and self-service access', 1, 1, NOW()),
    ('MAINTENANCE', 'Maintenance', 'Equipment maintenance and repair operations', 1, 1, NOW())
ON DUPLICATE KEY UPDATE 
    role_name = VALUES(role_name),
    description = VALUES(description),
    is_active = 1;
```

### **2. Migrate Users**

```sql
-- Example: Migrate PROC_EXE users to PROCUREMENT
UPDATE users u
JOIN role_master rm_old ON u.role_id = rm_old.id
JOIN role_master rm_new ON rm_new.role_code = 'PROCUREMENT'
SET u.role_id = rm_new.id
WHERE rm_old.role_code = 'PROC_EXE'
  AND u.is_active = 1;

-- Repeat for each role mapping...
```

### **3. Update Dept-Role Map**

```sql
-- Map global roles to ROOT department
INSERT INTO dept_role_map (dept_id, role_id, created_at)
SELECT d.id, r.id, NOW()
FROM department_master d
CROSS JOIN role_master r
WHERE d.dept_code = 'ROOT'
  AND r.role_code IN ('ADMIN', 'MANAGER', 'USER', 'VIEWER')
ON DUPLICATE KEY UPDATE created_at = NOW();

-- Map department-specific roles
INSERT INTO dept_role_map (dept_id, role_id, created_at)
SELECT d.id, r.id, NOW()
FROM department_master d
JOIN role_master r ON d.dept_code = r.role_code
WHERE d.dept_code IN ('SECURITY', 'STORE', 'QC', 'PROCUREMENT', 'SALES', 'CUSTOMER')
   OR (d.dept_code = 'PROD' AND r.role_code = 'PRODUCTION')
   OR (d.dept_code = 'MAINT' AND r.role_code = 'MAINTENANCE')
ON DUPLICATE KEY UPDATE created_at = NOW();
```

### **4. Regenerate Permissions**

```bash
php artisan db:seed --class=Database\\Seeders\\Tenant\\DefaultRolePermissionSeeder
```

### **5. Deactivate Old Roles**

```sql
-- Deactivate old specialized roles (only if no active users)
UPDATE role_master rm
SET rm.is_active = 0
WHERE rm.role_code IN (
    'PROC_EXE', 'PROC_MGR',
    'SECURITY_GUARD', 'SECURITY_SUPVR',
    'STOREKEEPER', 'STORE_MGR',
    'QC_TECH', 'QC_MGR',
    'AP_CLERK', 'FIN_MGR', 'CFO',
    'PPC_USER',
    'SALES_EXE', 'SALES_MGR',
    'CUST_EXE',
    'MAINT_TECH', 'MAINT_MGR'
)
AND NOT EXISTS (
    SELECT 1 FROM users u 
    WHERE u.role_id = rm.id 
    AND u.is_active = 1
);
```

---

## 🛡️ Rollback Plan

If something goes wrong:

### **Option 1: Restore from Backup**

```bash
mysql -u root -p tenant_db_name < backup_20260409_143022.sql
```

### **Option 2: Reactivate Old Roles**

```sql
-- Reactivate all old roles
UPDATE role_master
SET is_active = 1
WHERE role_code IN (
    'PROC_EXE', 'PROC_MGR',
    'SECURITY_GUARD', 'SECURITY_SUPVR',
    'STOREKEEPER', 'STORE_MGR',
    'QC_TECH', 'QC_MGR',
    'AP_CLERK', 'FIN_MGR', 'CFO',
    'PPC_USER',
    'SALES_EXE', 'SALES_MGR',
    'CUST_EXE',
    'MAINT_TECH', 'MAINT_MGR'
);
```

### **Option 3: Reverse User Migrations**

```sql
-- Example: Move PROCUREMENT users back to PROC_EXE
UPDATE users u
JOIN role_master rm_current ON u.role_id = rm_current.id
JOIN role_master rm_old ON rm_old.role_code = 'PROC_EXE'
SET u.role_id = rm_old.id
WHERE rm_current.role_code = 'PROCUREMENT'
  AND u.is_active = 1;
```

---

## 📊 Post-Migration Validation

### **1. Verify Role Counts**

```sql
SELECT 
    rm.role_code,
    rm.role_name,
    COUNT(u.id) as user_count,
    rm.is_active
FROM role_master rm
LEFT JOIN users u ON rm.id = u.role_id AND u.is_active = 1
GROUP BY rm.id
ORDER BY rm.role_code;
```

### **2. Verify Permissions**

```sql
-- Check ADMIN has full access
SELECT module_code, can_view, can_create, can_edit, can_approve, can_delete
FROM role_permissions
WHERE role_id = (SELECT id FROM role_master WHERE role_code = 'ADMIN')
ORDER BY module_code;
```

### **3. Test API Endpoints**

```bash
# Test with different user roles
curl -X GET http://your-domain/api/v1/departments \
  -H "Authorization: Bearer ADMIN_TOKEN"

curl -X GET http://your-domain/api/v1/grn \
  -H "Authorization: Bearer STORE_TOKEN"

curl -X GET http://your-domain/api/v1/qc \
  -H "Authorization: Bearer QC_TOKEN"
```

### **4. Check Middleware Protection**

```php
// Test in tinker:
$rbacService = app(\App\Contracts\RBACPermissionService::class);
$rbacService->hasPermission($userId, 'STORE');
$rbacService->hasPermission($userId, 'QC');
```

---

## 🎓 Understanding the Changes

### **What Changed:**

1. **Role Consolidation:**
   - Multiple specialized roles → Single departmental role
   - Example: `STOREKEEPER` + `STORE_MGR` → `STORE` + `MANAGER`

2. **Permission Model:**
   - Old: Role-specific permission matrix
   - New: Generic logic based on role type (ADMIN/MANAGER/USER/VIEWER/DEPARTMENT)

3. **Approval Workflow:**
   - Old: Department managers had custom roles (PROC_MGR, STORE_MGR, etc.)
   - New: All department managers use `MANAGER` role

### **What Stayed the Same:**

1. **Database Structure:**
   - Tables unchanged
   - Foreign keys intact
   - No schema migrations needed

2. **User Data:**
   - No user records deleted
   - No user credentials changed
   - Only `role_id` updated

3. **Department Structure:**
   - All departments preserved
   - Hierarchy maintained
   - Dept-role mappings updated

---

## 📝 Troubleshooting

### **Issue: "Role not found for department" Error**

**Solution:**
```bash
# Rebuild dept_role_map
php artisan rbac:migrate-simplified --force
```

### **Issue: User Can't Access Their Module**

**Solution:**
```sql
-- Check user's role
SELECT u.email, rm.role_code 
FROM users u
JOIN role_master rm ON u.role_id = rm.id
WHERE u.email = 'user@example.com';

-- Check role permissions
SELECT module_code, can_view, can_create, can_edit, can_approve
FROM role_permissions
WHERE role_id = (SELECT role_id FROM users WHERE email = 'user@example.com');

-- Regenerate permissions if needed
-- Run: php artisan db:seed --class=Database\\Seeders\\Tenant\\DefaultRolePermissionSeeder
```

### **Issue: Permission Cache Not Cleared**

**Solution:**
```bash
# Clear all RBAC cache
php artisan cache:clear

# Or clear specific user cache
php artisan tinker
```

```php
Cache::forget("rbac:user:{$userId}:permissions");
```

---

## ✅ Success Criteria

Migration is successful when:

- [ ] All active users have valid role assignments
- [ ] Old specialized roles are deactivated (not deleted)
- [ ] New simplified roles exist and are active
- [ ] Permission matrix is regenerated for all roles
- [ ] Users can login and access their modules
- [ ] Middleware correctly enforces permissions
- [ ] No data loss occurred
- [ ] API endpoints return correct responses

---

## 📞 Support

If you encounter issues:

1. Check logs: `storage/logs/laravel.log`
2. Review migration output
3. Verify database backups exist
4. Test in staging environment first
5. Contact your system administrator

---

**Last Updated:** April 9, 2026  
**Version:** 1.0  
**Tested On:** Laravel 11, PHP 8.2
