# Gate Entry Permission Bug - Root Cause Analysis & Fix

## Bug Description
Even ADMIN users were getting `PERMISSION_DENIED` error when trying to access gate entry endpoints:
```json
{
  "success": false,
  "error": {"code": "PERMISSION_DENIED", "details": []},
  "message": "Insufficient permissions"
}
```

## Root Cause

**Module Code Mismatch** between routes and RBAC seeder:

### Routes (api.php)
```php
Route::middleware(['check.module.permission:GATE'])->group(function () {
    // Gate entry endpoints
});
```

### RbacSeeder (database/seeders/RbacSeeder.php)
```php
'ADMIN' => [
    'PO'         => [true, true, true, true, true],
    'GATE_ENTRY' => [true, true, true, true, true],  // ← Uses GATE_ENTRY
    'MR_GRN'     => [true, true, true, true, true],
    // ...
];
```

**The Problem:**
- Routes check for module code: `GATE`
- Database has permissions for module code: `GATE_ENTRY`
- Middleware looks up `GATE` in `role_permissions` table
- No matching record found → Permission denied

## The Fix

Changed the middleware in `routes/api.php` from:
```php
Route::middleware(['check.module.permission:GATE'])->group(function () {
```

To:
```php
Route::middleware(['check.module.permission:GATE_ENTRY'])->group(function () {
```

Now the module code matches what's defined in RbacSeeder.

## How Permission Checking Works

1. **Request arrives** at `/api/v1/gate-entries`
2. **Middleware extracts** module code from route: `GATE_ENTRY`
3. **Middleware queries** `role_permissions` table:
   ```sql
   SELECT * FROM role_permissions 
   WHERE role_id = {user_role_id} 
   AND module_code = 'GATE_ENTRY'
   ```
4. **If record found** with `can_view = true` → Access granted
5. **If no record found** → Permission denied

## Verification

After the fix, the ADMIN user should have:
- `role_id`: 1 (ADMIN)
- `module_code`: GATE_ENTRY
- `can_view`: true
- `can_create`: true
- `can_edit`: true
- `can_approve`: true
- `can_delete`: true

## Testing

Run the curl command again:
```bash
curl --location 'http://127.0.0.1:8000/api/v1/gate-entries' \
--header 'Content-Type: application/json' \
--header 'Cookie: auth_token=YOUR_TOKEN' \
--data '{...}'
```

Expected response: `"success": true` with gate entries list

## Related Module Codes

For consistency, all module codes should match between:
1. **routes/api.php** - Middleware parameter
2. **database/seeders/RbacSeeder.php** - Permission matrix keys

Current module codes:
- `PO` - Purchase Orders
- `GATE_ENTRY` - Gate Entries & Verifications
- `MR_GRN` - Material Receipts & GRN
- `QC` - Quality Control
- `INVOICE` - Vendor Invoices
- `PAYMENT` - Payments
- `STOCK` - Stock / Inventory
- `REPORTS` - Reports
- `ASN` - Advance Shipping Notice

## Prevention

When adding new modules:
1. Define module code in RbacSeeder permission matrix
2. Use **exact same code** in route middleware
3. Add to all role permission definitions
4. Document in this file
