# RBAC — Department User Management Implementation Plan

## Background

The ERP already has the foundational DB tables (`department_master`, `role_master`, `role_permissions`, `users`) and a working [CheckModulePermission](file:///c:/xampp/htdocs/ERP/ERP/app/Http/Middleware/CheckModulePermission.php#27-241) middleware. The architecture follows:

```
User
  └── Department (e.g. Procurement, Finance, Quality)
         └── Role (e.g. PROC_EXE, PROC_MGR, QC_TECH)
                └── Permissions (can_create, can_approve, can_view …)
```

**What's missing:**
1. A `dept_role_map` table to lock which roles are valid per department
2. Seeders for all 13 roles, departments, and the full permission matrix
3. Login response doesn't return `role_code`, `dept_code`, or a permissions payload
4. No `/auth/me` "who-am-I" endpoint (needed by any frontend to route the correct dashboard)
5. User creation does not validate that the chosen role actually belongs to the chosen department
6. No `/departments/{id}/roles` dropdown API for the admin user-creation UI

---

## Proposed Changes

### Component 1 — Database: `dept_role_map` table

#### [NEW] `2024_01_01_000005_create_dept_role_map_table.php`

New migration in `database/migrations/tenant/`. This pivot table enforces that only pre-approved roles can be assigned within a department. Prevents, e.g., assigning a `FIN_MGR` role to the Procurement department.

```
dept_role_map
  id (PK)
  dept_id  → department_master.id  (cascade delete)
  role_id  → role_master.id        (cascade delete)
  UNIQUE(dept_id, role_id)
```

---

### Component 2 — Database: RBAC Seeder

#### [NEW] `database/seeders/RbacSeeder.php`

Seeds all departments, roles, dept_role_map entries, and the full permission matrix from section 9 in one idempotent seeder (uses `updateOrCreate` so it is safe to re-run).

**Departments seeded:**

| Code | Name |
|------|------|
| PROC | Procurement |
| SEC | Security |
| STORE | Warehouse / Store |
| QC | Quality |
| FIN | Finance |
| PPC | PPC |
| ADMIN | IT / Admin |

**Roles seeded (13 roles from Section 9.1):**

| role_code | dept_code | Description |
|---|---|---|
| PROC_EXE | PROC | Creates POs, manages vendors |
| PROC_MGR | PROC | Approves POs and PO Amendments |
| SECURITY_GUARD | SEC | Creates Gate Entries |
| SECURITY_SUPVR | SEC | Performs Gate Verification |
| STOREKEEPER | STORE | Creates MR, GRN, Putaway |
| STORE_MGR | STORE | Approves GRN; manages bins |
| QC_TECH | QC | Records test results |
| QC_MGR | QC | Issues Usage Decision |
| AP_CLERK | FIN | Registers and verifies invoices |
| FIN_MGR | FIN | Approves payment proposals |
| CFO | FIN | Approves high-value payments |
| PPC_USER | PPC | Read-only access to stock & GRN |
| ADMIN | ADMIN | Full system access |

**Permission Matrix used for `role_permissions`:**

| Module Code | Mapped Actions |
|---|---|
| `PO` | Create PO, Approve PO |
| `GATE_ENTRY` | Create/Verify Gate Entry |
| `MR_GRN` | Create MR / GRN, Confirm Putaway |
| `QC` | Record QC Results, Usage Decision |
| `INVOICE` | Invoice Entry |
| `PAYMENT` | Approve Payment |
| `STOCK` | View Stock |
| `REPORTS` | Reports (All) |

---

### Component 3 — Auth: Enhanced Login Response

#### [MODIFY] `AuthController.php`

Add `role_code`, `role_name`, `dept_code`, `dept_name`, and `dashboard_route` to the `user` block in the login response. The `dashboard_route` tells the frontend which dashboard path to load.

```json
"user": {
  "user_id": 1,
  "email": "john@example.com",
  "employee_code": "EMP001",
  "first_name": "John",
  "last_name": "Doe",
  "dept_id": 1,
  "dept_code": "PROC",
  "dept_name": "Procurement",
  "role_id": 2,
  "role_code": "PROC_MGR",
  "role_name": "Procurement Manager",
  "dashboard_route": "/dashboard/procurement"
}
```

**Dashboard route map (used server-side to build `dashboard_route`):**

| role_code | dashboard_route |
|---|---|
| PROC_EXE | `/dashboard/procurement` |
| PROC_MGR | `/dashboard/procurement` |
| SECURITY_GUARD | `/dashboard/security` |
| SECURITY_SUPVR | `/dashboard/security` |
| STOREKEEPER | `/dashboard/warehouse` |
| STORE_MGR | `/dashboard/warehouse` |
| QC_TECH | `/dashboard/quality` |
| QC_MGR | `/dashboard/quality` |
| AP_CLERK | `/dashboard/finance` |
| FIN_MGR | `/dashboard/finance` |
| CFO | `/dashboard/finance` |
| PPC_USER | `/dashboard/ppc` |
| ADMIN | `/dashboard/admin` |

---

### Component 4 — Auth: `/me` Endpoint

#### [MODIFY] `AuthController.php`

```
GET /api/v1/auth/me
Authorization: Bearer <token>
```

Returns:
- Full user profile (with dept + role names + codes)
- `permissions` — keyed by module_code, so the frontend knows what to show/hide per screen
- `dashboard_route` — same as login response

```json
{
  "user": { ... },
  "permissions": {
    "PO":   { "can_view": true, "can_create": true, "can_approve": true, ... },
    "STOCK":{ "can_view": true, ... }
  },
  "dashboard_route": "/dashboard/procurement"
}
```

---

### Component 5 — User Creation: dept_role_map Validation

#### [MODIFY] `UserController.php` — `store()` and `update()`

Before saving, validate that `role_id` exists in `dept_role_map` for the given `dept_id`:

```php
$valid = DeptRoleMap::where('dept_id', $deptId)
                    ->where('role_id', $roleId)
                    ->exists();
if (!$valid) {
    return 422 error: "Role not valid for selected department"
}
```

#### [NEW] `DeptRoleMap.php` (Model in `app/Models/Tenant/`)

Simple Eloquent model for the pivot table with `belongsTo` relationships to `Department` and `Role`.

---

### Component 6 — Roles-by-Department Dropdown API

#### [MODIFY] `DepartmentController.php`

Add new sub-resource action:
```
GET /api/v1/departments/{id}/roles
```

Returns only the roles that are mapped to this department (via `dept_role_map`). Used by the admin user-creation form to populate the Role dropdown after a Department is chosen.

---

### Component 7 — Routes

#### [MODIFY] `routes/api.php` or `routes/tenant.php`

Register the two new routes:
```
GET  /api/v1/auth/me
GET  /api/v1/departments/{id}/roles
```

---

## Verification Plan

### Automated Tests (Run in `c:\xampp\htdocs\ERP\ERP`)

```bash
# 1. Run the seeder
php artisan db:seed --class=RbacSeeder

# 2. Check seeding result
php artisan tinker --execute="echo App\Models\Tenant\Role::count() . ' roles, ' . App\Models\Tenant\Department::count() . ' departments, ' . App\Models\Tenant\RolePermission::count() . ' permissions';"
```

Expected output: `13 roles, 7 departments, NN permissions`

### API Tests (using curl or Postman)

**Test 1 — Login returns dept/role context:**
```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"proc_manager@example.com","password":"password","org_slug":"your-org"}' \
  | jq '.data.user | {role_code, dept_code, dashboard_route}'
```
Expected: `"role_code":"PROC_MGR"`, `"dept_code":"PROC"`, `"dashboard_route":"/dashboard/procurement"`

**Test 2 — /me returns permissions:**
```bash
curl -s http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <token>" \
  | jq '.data.permissions'
```
Expected: JSON object keyed by module codes with true/false flags.

**Test 3 — dept_role_map validation on user creation:**
```bash
# Attempt to assign a Finance role (FIN_MGR) to Procurement dept → expect 422
curl -s -X POST http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer <admin_token>" \
  -H "Content-Type: application/json" \
  -d '{"employee_code":"T001","email":"t@t.com","password":"password123","first_name":"Test","last_name":"User","dept_id":1,"role_id":10}'
```
Expected: HTTP 422 with `"ROLE_DEPT_MISMATCH"` error code.

**Test 4 — Roles dropdown by department:**
```bash
curl -s http://localhost:8000/api/v1/departments/1/roles \
  -H "Authorization: Bearer <token>" \
  | jq '.[].role_code'
```
Expected: Only `PROC_EXE` and `PROC_MGR` (for Procurement dept id=1).

### Manual Verification

1. Create a test user via the API with `dept_id` = Procurement and `role_id` = PROC_EXE
2. Login as that user and confirm:
   - `dashboard_route` = `/dashboard/procurement`
   - `dept_code` = `PROC`
   - `role_code` = `PROC_EXE`
3. Call `/me` with the token and confirm the `permissions.PO.can_create = true`
4. Try to access a Finance-only endpoint (e.g., approve payment) with the PROC_EXE token → expect HTTP 403
