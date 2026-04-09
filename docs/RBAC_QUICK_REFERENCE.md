# RBAC Quick Reference Card

## 🎯 Role Mapping Table

| Old Role(s) | → | New Role | Type |
|-------------|---|----------|------|
| PROC_EXE, PROC_MGR | → | **PROCUREMENT** | Department |
| SECURITY_GUARD, SECURITY_SUPVR | → | **SECURITY** | Department |
| STOREKEEPER, STORE_MGR | → | **STORE** | Department |
| QC_TECH, QC_MGR | → | **QC** | Department |
| AP_CLERK | → | **USER** | Global |
| FIN_MGR, CFO | → | **MANAGER** | Global |
| PPC_USER | → | **VIEWER** | Global |
| SALES_EXE, SALES_MGR | → | **SALES** | Department |
| CUST_EXE | → | **CUSTOMER** | Department |
| MAINT_TECH, MAINT_MGR | → | **MAINTENANCE** | Department |
| ADMIN | → | **ADMIN** | Global (unchanged) |
| PRODUCTION | → | **PRODUCTION** | Department (unchanged) |

---

## 🚀 Quick Commands

### **Before Migration**
```bash
# 1. Analyze current state (specify tenant database)
mysql -u root -p your_tenant_db_name < scripts/rbac_current_state_analysis.sql

# 2. Backup database
mysqldump -u root -p your_tenant_db_name > backup_$(date +%Y%m%d).sql

# 3. List available tenants
php artisan rbac:migrate-simplified

# 4. Dry run (preview changes)
php artisan rbac:migrate-simplified --tenant-db=your_tenant_db_name --dry-run
```

### **Execute Migration**
```bash
# Interactive (with confirmation)
php artisan rbac:migrate-simplified --tenant-db=your_tenant_db_name

# Non-interactive (for scripts/automation)
php artisan rbac:migrate-simplified --tenant-db=your_tenant_db_name --force
```

### **Set Default Tenant in .env (Optional)**
```bash
# Add to your .env file:
TENANT_DB_DATABASE=your_tenant_db_name

# Then you can run without --tenant-db flag:
php artisan rbac:migrate-simplified --dry-run
```

### **After Migration**
```bash
# Clear cache
php artisan cache:clear

# Verify roles
php artisan tinker
# Then run:
# DB::connection('tenant')->table('role_master')->pluck('role_code')->toArray();
```

---

## 📊 Permission Model

### **ADMIN (Global)**
- Scope: `global`
- All modules: Full access (view, create, edit, approve, delete)
- Cross-department: ✅ Yes

### **MANAGER (Global)**
- Scope: `department`
- Own department modules: view, create, edit, approve
- ADMIN module: view only
- Delete: ❌ No

### **USER (Global)**
- Scope: `department`
- Own department modules: view, create, edit
- ADMIN module: view only
- Approve/Delete: ❌ No

### **VIEWER (Global)**
- Scope: `department`
- All modules: view only
- Create/Edit/Approve/Delete: ❌ No

### **DEPARTMENT ROLES (SECURITY, STORE, QC, etc.)**
- Scope: `department`
- Own module: view, create, edit, approve
- Other modules: view only
- Delete: ❌ No

---

## 🔗 Department-Role Mappings

| Department | Valid Roles |
|------------|-------------|
| **ROOT** | ADMIN, MANAGER, USER, VIEWER |
| **SECURITY** | SECURITY |
| **STORE** | STORE |
| **QC** | QC |
| **PROCUREMENT** | PROCUREMENT |
| **PROD** | PRODUCTION |
| **SALES** | SALES |
| **CUSTOMER** | CUSTOMER |
| **MAINT** | MAINTENANCE |

---

## 🛡️ Safety Features

✅ **Transaction-wrapped:** Auto-rollback on failure  
✅ **Non-destructive:** Old roles deactivated, not deleted  
✅ **Dry-run mode:** Preview before applying  
✅ **Validation:** Checks dept-role compatibility  
✅ **Backup recommended:** Always backup first  

---

## 📝 Files Created

1. **Migration Command:** `app/Console/Commands/MigrateToSimplifiedRBAC.php`
2. **Migration Guide:** `docs/RBAC_MIGRATION_GUIDE.md`
3. **Analysis SQL:** `scripts/rbac_current_state_analysis.sql`
4. **Quick Reference:** This file

---

## ⚠️ Important Notes

1. **Old roles are NOT deleted** - they're deactivated (`is_active = 0`)
2. **Users keep their department** - only `role_id` changes
3. **Permissions are regenerated** - based on new logic
4. **Cache must be cleared** - after migration
5. **Test thoroughly** - before deploying to production

---

## 🔧 Troubleshooting Quick Fixes

**Problem:** User can't access their module  
**Fix:** Check role assignment and regenerate permissions
```bash
php artisan db:seed --class=Database\\Seeders\\Tenant\\DefaultRolePermissionSeeder
```

**Problem:** "Role not valid for department" error  
**Fix:** Update dept_role_map
```bash
php artisan rbac:migrate-simplified --force
```

**Problem:** Permissions not working  
**Fix:** Clear cache
```bash
php artisan cache:clear
```

---

**Need Help?** See full guide: `docs/RBAC_MIGRATION_GUIDE.md`
