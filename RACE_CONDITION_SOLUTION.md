# Race Condition Solution - Final Approach

## Problem Statement

When an organization registers, there's a potential race condition:
1. Organization record is created with `tenant_db_name`
2. Provisioning job is queued (async)
3. User tries to access system immediately
4. Database doesn't exist yet → Error

## Solution Approaches Considered

### ❌ Approach 1: Set tenant_db_name to NULL initially
```php
$organization = Organization::create([
    'tenant_db_name' => null,  // Set after DB creation
    'registration_status' => 'PENDING',
]);
```

**Problems:**
- Violates data integrity (nullable when it should always have a value)
- Requires migration to change column to nullable
- Database name is not predictable
- Complicates queries and validation

### ✅ Approach 2: Set tenant_db_name immediately + Block PENDING access (CHOSEN)
```php
$organization = Organization::create([
    'tenant_db_name' => 'erp_' . $org_slug,  // Set immediately
    'registration_status' => 'PENDING',
]);
```

**Advantages:**
- Maintains data integrity (NOT NULL constraint)
- Database name is predictable and consistent
- No migration needed
- Simple and clean

**Protection:** Middleware blocks PENDING organizations
```php
if ($organization->registration_status === 'PENDING') {
    throw new ApiException(
        'TENANT_PROVISIONING_IN_PROGRESS',
        'Organization is being provisioned. Please try again in a few moments.',
        503
    );
}
```

---

## Implementation Details

### 1. Organization Registration (OrganizationController.php)

```php
public function register(Request $request): JsonResponse
{
    // Create organization with tenant_db_name set immediately
    $organization = Organization::create([
        'org_slug' => $request->input('org_slug'),
        'org_name' => $request->input('org_name'),
        'tenant_db_name' => 'erp_' . $request->input('org_slug'),
        'registration_status' => 'PENDING',
        // ... other fields
    ]);
    
    // Queue provisioning job (async)
    ProvisionTenantJob::dispatch($organization->org_id);
    
    return response()->json([
        'success' => true,
        'data' => [
            'org_id' => $organization->org_id,
            'registration_status' => 'PENDING',
            'tenant_db_name' => $organization->tenant_db_name,
        ],
        'message' => 'Organization registered. Provisioning in progress.'
    ], 201);
}
```

### 2. Tenant Resolution Middleware (ResolveTenant.php)

```php
public function handle(Request $request, Closure $next): Response
{
    $organization = Organization::where('org_slug', $orgSlug)->first();
    
    // Block PENDING organizations
    if ($organization->registration_status === 'PENDING') {
        throw new ApiException(
            'TENANT_PROVISIONING_IN_PROGRESS',
            'Organization is being provisioned. Please try again in a few moments.',
            [],
            503  // Service Unavailable
        );
    }
    
    // Only ACTIVE organizations can proceed
    if ($organization->registration_status !== 'ACTIVE') {
        throw new ApiException('INVALID_TENANT_STATUS', ..., 403);
    }
    
    // Switch to tenant database
    // ...
}
```

### 3. Provisioning Service (TenantProvisioningServiceImpl.php)

```php
public function provisionTenant(int $orgId): ProvisioningResult
{
    $organization = Organization::find($orgId);
    
    // Verify status is PENDING
    if ($organization->registration_status !== 'PENDING') {
        throw new \Exception("Organization must be PENDING");
    }
    
    // Generate database name
    $tenantDbName = "erp_{$organization->org_slug}";
    
    // Verify it matches what's in the database
    if ($organization->tenant_db_name !== $tenantDbName) {
        throw new \Exception("Database name mismatch");
    }
    
    // Create database (idempotent)
    DB::statement("CREATE DATABASE IF NOT EXISTS `{$tenantDbName}`");
    
    // ... rest of provisioning
    
    // Update status to ACTIVE
    $organization->registration_status = 'ACTIVE';
    $organization->activated_at = now();
    $organization->save();
}
```

### 4. Rollback on Failure

```php
public function rollbackProvisioning(int $orgId): void
{
    $organization = Organization::find($orgId);
    
    // Drop database if exists
    DB::statement("DROP DATABASE IF EXISTS `{$organization->tenant_db_name}`");
    
    // Reset to PENDING (keep tenant_db_name for retry)
    $organization->registration_status = 'PENDING';
    $organization->activated_at = null;
    $organization->save();
}
```

---

## User Experience Flow

### Registration Flow

```
1. User submits registration
   ↓
2. Organization created (status: PENDING, tenant_db_name: erp_acme)
   ↓
3. Response: "Registration successful. Provisioning in progress."
   ↓
4. Provisioning job queued
   ↓
5. User tries to login immediately
   ↓
6. Middleware checks status: PENDING
   ↓
7. Response: 503 "Organization is being provisioned. Please try again."
   ↓
8. Provisioning completes (status: ACTIVE)
   ↓
9. User tries to login again
   ↓
10. Success! Access granted
```

### Error Responses

**During Provisioning (PENDING):**
```json
{
  "success": false,
  "error": {
    "code": "TENANT_PROVISIONING_IN_PROGRESS"
  },
  "message": "Organization is being provisioned. Please try again in a few moments.",
  "timestamp": "2024-01-01T00:00:00Z"
}
```
HTTP Status: 503 Service Unavailable

**After Provisioning (ACTIVE):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Login successful"
}
```
HTTP Status: 200 OK

---

## Benefits of This Approach

### 1. Data Integrity
- `tenant_db_name` is NEVER null
- Database name is predictable: `erp_{org_slug}`
- Consistent naming convention enforced

### 2. No Migration Required
- Column remains NOT NULL
- No schema changes needed
- Works with existing database

### 3. Clear Status Tracking
- PENDING = Provisioning in progress
- ACTIVE = Ready to use
- SUSPENDED = Temporarily disabled
- TERMINATED = Permanently closed

### 4. Proper Error Handling
- 503 status code indicates temporary unavailability
- Clear error message guides user
- Retry-friendly (user can try again)

### 5. Idempotent Provisioning
- Can retry provisioning safely
- `CREATE DATABASE IF NOT EXISTS`
- Checks before creating roles/users/etc.

### 6. Automatic Rollback
- Failed provisioning cleans up automatically
- Database dropped
- Status reset to PENDING
- Ready for retry

---

## Testing Scenarios

### Test 1: Normal Registration
```bash
# Register
curl -X POST /api/v1/organizations/register -d {...}
# Response: 201, status: PENDING

# Try to login immediately
curl -X POST /api/v1/auth/login -H "X-Org-Slug: acme" -d {...}
# Response: 503, "Provisioning in progress"

# Wait for provisioning (or run manually)
php artisan queue:work --once

# Try to login again
curl -X POST /api/v1/auth/login -H "X-Org-Slug: acme" -d {...}
# Response: 200, Success!
```

### Test 2: Provisioning Failure + Retry
```bash
# Register
curl -X POST /api/v1/organizations/register -d {...}

# Simulate failure (e.g., database permission error)
# Provisioning fails, rollback runs automatically

# Check status
php artisan tinker
>>> Organization::where('org_slug', 'acme')->first()->registration_status
# "PENDING"

# Retry provisioning
php artisan tenant:provision acme
# Success!
```

### Test 3: Concurrent Requests
```bash
# Register
curl -X POST /api/v1/organizations/register -d {...}

# Multiple concurrent login attempts
for i in {1..5}; do
  curl -X POST /api/v1/auth/login -H "X-Org-Slug: acme" -d {...} &
done

# All should get 503 "Provisioning in progress"
# No "database not found" errors
```

---

## Comparison with Alternative Approaches

| Aspect | Nullable Approach | Immediate Set + Block (Chosen) |
|--------|-------------------|--------------------------------|
| Data Integrity | ❌ Allows NULL | ✅ Always has value |
| Migration Required | ✅ Yes | ❌ No |
| Predictability | ⚠️ Name set later | ✅ Name known immediately |
| Error Handling | ⚠️ Complex | ✅ Simple (503 status) |
| Retry Logic | ⚠️ Must handle NULL | ✅ Straightforward |
| Database Queries | ⚠️ Must check NULL | ✅ Always valid |
| Code Complexity | ⚠️ More checks | ✅ Simpler |

---

## Conclusion

The chosen approach (set `tenant_db_name` immediately + block PENDING access) provides:

✅ **Better data integrity** - No nullable columns
✅ **Simpler code** - Fewer NULL checks
✅ **Clear semantics** - PENDING means "not ready yet"
✅ **Better UX** - Clear error messages
✅ **No migration** - Works with existing schema
✅ **Predictable** - Database name known upfront

This is the recommended approach for production use.
