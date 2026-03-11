-- Add ASN module permissions for all roles
-- Run this on the tenant database

-- Get the role IDs first
SET @admin_role_id = (SELECT id FROM role_master WHERE role_code = 'ADMIN' LIMIT 1);
SET @proc_exe_role_id = (SELECT id FROM role_master WHERE role_code = 'PROC_EXE' LIMIT 1);
SET @proc_mgr_role_id = (SELECT id FROM role_master WHERE role_code = 'PROC_MGR' LIMIT 1);
SET @storekeeper_role_id = (SELECT id FROM role_master WHERE role_code = 'STOREKEEPER' LIMIT 1);
SET @store_mgr_role_id = (SELECT id FROM role_master WHERE role_code = 'STORE_MGR' LIMIT 1);

-- ADMIN - Full access
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
VALUES (@admin_role_id, 'ASN', 1, 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 1, can_delete = 1;

-- PROC_EXE - View, Create, Edit
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
VALUES (@proc_exe_role_id, 'ASN', 1, 1, 1, 0, 0)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 0, can_delete = 0;

-- PROC_MGR - Full access
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
VALUES (@proc_mgr_role_id, 'ASN', 1, 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_approve = 1, can_delete = 1;

-- STOREKEEPER - View only
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
VALUES (@storekeeper_role_id, 'ASN', 1, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 0, can_edit = 0, can_approve = 0, can_delete = 0;

-- STORE_MGR - View, Edit, Approve
INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_approve, can_delete)
VALUES (@store_mgr_role_id, 'ASN', 1, 0, 1, 1, 0)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 0, can_edit = 1, can_approve = 1, can_delete = 0;

-- Verify the permissions were added
SELECT 
    r.role_code,
    r.role_name,
    rp.module_code,
    rp.can_view,
    rp.can_create,
    rp.can_edit,
    rp.can_approve,
    rp.can_delete
FROM role_permissions rp
JOIN role_master r ON rp.role_id = r.id
WHERE rp.module_code = 'ASN'
ORDER BY r.role_code;
