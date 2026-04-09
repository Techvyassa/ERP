-- ============================================
-- RBAC Current State Analysis Script
-- Run this BEFORE migration to understand your data
-- ============================================

-- 1. Check all roles and their user counts
SELECT 
    'ROLES AND USER DISTRIBUTION' as section,
    rm.role_code,
    rm.role_name,
    rm.is_active,
    rm.is_system_role,
    COUNT(u.id) as active_user_count
FROM role_master rm
LEFT JOIN users u ON rm.id = u.role_id AND u.is_active = 1
GROUP BY rm.id, rm.role_code, rm.role_name, rm.is_active, rm.is_system_role
ORDER BY active_user_count DESC, rm.role_code;

-- 2. Check all departments
SELECT 
    'DEPARTMENTS' as section,
    dept_code,
    dept_name,
    is_active,
    parent_dept_id,
    cost_center_code
FROM department_master
ORDER BY dept_code;

-- 3. Check dept-role mappings
SELECT 
    'DEPARTMENT-ROLE MAPPINGS' as section,
    dm.dept_code,
    dm.dept_name,
    rm.role_code,
    rm.role_name,
    drm.created_at as mapping_created
FROM dept_role_map drm
JOIN department_master dm ON drm.dept_id = dm.id
JOIN role_master rm ON drm.role_id = rm.id
ORDER BY dm.dept_code, rm.role_code;

-- 4. Check users with old specialized roles
SELECT 
    'USERS WITH OLD SPECIALIZED ROLES' as section,
    u.id,
    u.employee_code,
    u.email,
    u.first_name,
    u.last_name,
    u.is_active as user_active,
    rm.role_code,
    rm.role_name,
    dm.dept_code,
    dm.dept_name
FROM users u
JOIN role_master rm ON u.role_id = rm.id
LEFT JOIN department_master dm ON u.dept_id = dm.id
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
AND u.is_active = 1
ORDER BY rm.role_code, u.email;

-- 5. Check users with new simplified roles
SELECT 
    'USERS WITH NEW SIMPLIFIED ROLES' as section,
    u.id,
    u.employee_code,
    u.email,
    u.first_name,
    u.last_name,
    u.is_active as user_active,
    rm.role_code,
    rm.role_name,
    dm.dept_code,
    dm.dept_name
FROM users u
JOIN role_master rm ON u.role_id = rm.id
LEFT JOIN department_master dm ON u.dept_id = dm.id
WHERE rm.role_code IN (
    'ADMIN', 'MANAGER', 'USER', 'VIEWER',
    'SECURITY', 'STORE', 'QC', 'PROCUREMENT',
    'PRODUCTION', 'SALES', 'CUSTOMER', 'MAINTENANCE'
)
AND u.is_active = 1
ORDER BY rm.role_code, u.email;

-- 6. Check permission coverage
SELECT 
    'ROLE PERMISSIONS COVERAGE' as section,
    rm.role_code,
    COUNT(rp.id) as module_count,
    SUM(rp.can_view = 1) as modules_with_view,
    SUM(rp.can_create = 1) as modules_with_create,
    SUM(rp.can_edit = 1) as modules_with_edit,
    SUM(rp.can_approve = 1) as modules_with_approve,
    SUM(rp.can_delete = 1) as modules_with_delete
FROM role_master rm
LEFT JOIN role_permissions rp ON rm.id = rp.role_id
WHERE rm.is_active = 1
GROUP BY rm.id, rm.role_code
ORDER BY rm.role_code;

-- 7. Check for users without valid dept-role mapping
SELECT 
    'USERS WITH INVALID DEPT-ROLE MAPPING' as section,
    u.id,
    u.email,
    dm.dept_code,
    rm.role_code,
    'Invalid: Role not mapped to department' as issue
FROM users u
JOIN department_master dm ON u.dept_id = dm.id
JOIN role_master rm ON u.role_id = rm.id
WHERE u.is_active = 1
AND NOT EXISTS (
    SELECT 1 
    FROM dept_role_map drm 
    WHERE drm.dept_id = u.dept_id 
    AND drm.role_id = u.role_id
)
ORDER BY u.email;

-- 8. Check scope and cross-department settings
SELECT 
    'SCOPE AND CROSS-DEPARTMENT SETTINGS' as section,
    rm.role_code,
    rp.module_code,
    rp.scope,
    rp.view_cross_department,
    rp.can_view,
    rp.can_create,
    rp.can_edit,
    rp.can_approve,
    rp.can_delete
FROM role_master rm
JOIN role_permissions rp ON rm.id = rp.role_id
WHERE rm.role_code IN ('ADMIN', 'MANAGER', 'USER', 'VIEWER')
AND rm.is_active = 1
ORDER BY rm.role_code, rp.module_code;

-- 9. Summary statistics
SELECT 
    'SUMMARY STATISTICS' as section,
    'Total Roles' as metric,
    COUNT(*) as value
FROM role_master
WHERE is_active = 1

UNION ALL

SELECT 
    'SUMMARY STATISTICS' as section,
    'Total Departments' as metric,
    COUNT(*) as value
FROM department_master
WHERE is_active = 1

UNION ALL

SELECT 
    'SUMMARY STATISTICS' as section,
    'Total Active Users' as metric,
    COUNT(*) as value
FROM users
WHERE is_active = 1

UNION ALL

SELECT 
    'SUMMARY STATISTICS' as section,
    'Dept-Role Mappings' as metric,
    COUNT(*) as value
FROM dept_role_map

UNION ALL

SELECT 
    'SUMMARY STATISTICS' as section,
    'Role Permission Records' as metric,
    COUNT(*) as value
FROM role_permissions;

-- 10. Identify potential migration issues
SELECT 
    'POTENTIAL MIGRATION ISSUES' as section,
    u.id,
    u.email,
    rm.role_code as current_role,
    dm.dept_code as current_dept,
    CASE 
        WHEN rm.role_code IN ('PROC_EXE', 'PROC_MGR') THEN 'PROCUREMENT'
        WHEN rm.role_code IN ('SECURITY_GUARD', 'SECURITY_SUPVR') THEN 'SECURITY'
        WHEN rm.role_code IN ('STOREKEEPER', 'STORE_MGR') THEN 'STORE'
        WHEN rm.role_code IN ('QC_TECH', 'QC_MGR') THEN 'QC'
        WHEN rm.role_code IN ('AP_CLERK') THEN 'USER'
        WHEN rm.role_code IN ('FIN_MGR', 'CFO') THEN 'MANAGER'
        WHEN rm.role_code IN ('PPC_USER') THEN 'VIEWER'
        WHEN rm.role_code IN ('SALES_EXE', 'SALES_MGR') THEN 'SALES'
        WHEN rm.role_code IN ('CUST_EXE') THEN 'CUSTOMER'
        WHEN rm.role_code IN ('MAINT_TECH', 'MAINT_MGR') THEN 'MAINTENANCE'
        ELSE rm.role_code
    END as proposed_new_role,
    CASE 
        WHEN rm.role_code IN ('PROC_EXE', 'PROC_MGR') THEN 'Check if PROCUREMENT role mapped to user dept'
        WHEN rm.role_code IN ('SECURITY_GUARD', 'SECURITY_SUPVR') THEN 'Check if SECURITY role mapped to user dept'
        WHEN rm.role_code IN ('STOREKEEPER', 'STORE_MGR') THEN 'Check if STORE role mapped to user dept'
        WHEN rm.role_code IN ('QC_TECH', 'QC_MGR') THEN 'Check if QC role mapped to user dept'
        WHEN rm.role_code IN ('AP_CLERK') THEN 'Will become USER role'
        WHEN rm.role_code IN ('FIN_MGR', 'CFO') THEN 'Will become MANAGER role'
        WHEN rm.role_code IN ('PPC_USER') THEN 'Will become VIEWER role'
        WHEN rm.role_code IN ('SALES_EXE', 'SALES_MGR') THEN 'Check if SALES role mapped to user dept'
        WHEN rm.role_code IN ('CUST_EXE') THEN 'Check if CUSTOMER role mapped to user dept'
        WHEN rm.role_code IN ('MAINT_TECH', 'MAINT_MGR') THEN 'Check if MAINTENANCE role mapped to user dept'
        ELSE 'No change needed'
    END as migration_note
FROM users u
JOIN role_master rm ON u.role_id = rm.id
LEFT JOIN department_master dm ON u.dept_id = dm.id
WHERE u.is_active = 1
AND rm.role_code IN (
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
ORDER BY rm.role_code, u.email;
