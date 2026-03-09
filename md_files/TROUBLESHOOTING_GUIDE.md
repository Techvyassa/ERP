# Troubleshooting Guide: Profile Completion & Master Data

## Issue: Organization Data Not Showing

### Step 1: Check Browser Console

1. Open your browser's Developer Tools (F12)
2. Go to the Console tab
3. Look for any error messages
4. Check what the console logs show

### Step 2: Access Debug Page

Navigate to the debug page to see detailed information:

**Subdomain mode:**
```
https://yourorg.yourdomain.com/debug-profile
```

**Path-based mode:**
```
https://yourdomain.com/org/yourorg/debug-profile
```

This page will show:
- Organization data from server
- User data from localStorage
- Access token
- API test buttons

### Step 3: Test API Endpoints

On the debug page, click:
1. "Test Profile Completion API" button
2. "Test Master Data API" button

Check the responses for errors.

### Common Issues & Solutions

#### Issue 1: "Organization ID not found in request"

**Cause:** The middleware is not setting the org_id attribute.

**Solution:**
1. Check that `resolve.tenant` middleware is applied
2. Verify the middleware is setting `$request->attributes->set('org_id', $orgId)`
3. Check Laravel logs: `storage/logs/laravel.log`

**Fix:**
```php
// In app/Http/Middleware/ResolveTenant.php
$request->attributes->set('org_id', $organization->org_id);
```

#### Issue 2: "Tenant database not configured"

**Cause:** Organization doesn't have a tenant_db_name set.

**Solution:**
```sql
-- Check organization record
SELECT org_id, org_slug, tenant_db_name, registration_status 
FROM organizations 
WHERE org_slug = 'your-org-slug';

-- If tenant_db_name is NULL, update it
UPDATE organizations 
SET tenant_db_name = 'erp_your_org_slug'
WHERE org_slug = 'your-org-slug';
```

#### Issue 3: "401 Unauthorized"

**Cause:** JWT token is invalid or expired.

**Solution:**
1. Check if token exists in localStorage
2. Try logging out and logging back in
3. Check token expiration

**Debug:**
```javascript
// In browser console
console.log(localStorage.getItem('access_token'));
```

#### Issue 4: Master Data Shows 0 Records

**Cause:** Tables don't exist or are empty.

**Solution:**
1. Check if tenant database exists
2. Run tenant migrations
3. Seed initial data

**Commands:**
```bash
# Check databases
php artisan db:show

# Run tenant migrations
php artisan migrate --database=tenant --path=database/migrations/tenant

# Seed tenant data
php artisan db:seed --database=tenant
```

#### Issue 5: "Table not found" errors

**Cause:** Some master tables haven't been created yet.

**Solution:** This is expected. The system now only shows tables that exist. If you see this in logs, it's just informational.

### Step 4: Check Middleware Configuration

Verify the middleware is properly configured:

```php
// routes/api.php
Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])->group(function () {
    Route::prefix('profile-completion')->group(function () {
        Route::get('/status', [ProfileCompletionController::class, 'status']);
        // ...
    });
});
```

### Step 5: Check Database Connection

Test the database connection:

```bash
# Test control database
php artisan tinker
>>> DB::connection('control')->table('organizations')->count();

# Test tenant database
>>> DB::connection('tenant')->table('users')->count();
```

### Step 6: Check Laravel Logs

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for specific errors
grep "Profile completion" storage/logs/laravel.log
grep "Master data" storage/logs/laravel.log
```

### Step 7: Verify API Routes

```bash
# List all routes
php artisan route:list | grep profile-completion

# Should show:
# GET|HEAD  api/v1/profile-completion/status
# PUT       api/v1/profile-completion/organization
# GET|HEAD  api/v1/profile-completion/master-data-status
```

## Manual Testing with cURL

### Test Profile Completion Status

```bash
curl -X GET "http://yourdomain.com/api/v1/profile-completion/status" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test Master Data Status

```bash
curl -X GET "http://yourdomain.com/api/v1/profile-completion/master-data-status" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Update Organization Profile

```bash
curl -X PUT "http://yourdomain.com/api/v1/profile-completion/organization" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "org_name": "Test Company",
    "primary_phone": "+1234567890",
    "city": "New York"
  }'
```

## Database Verification

### Check Organization Data

```sql
-- Control database
USE erp_control;

SELECT 
    org_id,
    org_slug,
    org_name,
    tenant_db_name,
    registration_status,
    primary_email,
    primary_phone,
    city,
    state,
    country_code,
    timezone,
    currency_code,
    profile_completion_percentage
FROM organizations
WHERE org_slug = 'your-org-slug';
```

### Check Tenant Database

```sql
-- Tenant database
USE erp_your_org_slug;

-- Check tables exist
SHOW TABLES;

-- Check record counts
SELECT 
    (SELECT COUNT(*) FROM department_master) as departments,
    (SELECT COUNT(*) FROM role_master) as roles,
    (SELECT COUNT(*) FROM users) as users;
```

## Common Error Messages

### "Call to a member function on null"

**Cause:** Organization not found or middleware not setting data.

**Fix:** Check that organization exists and middleware is working.

### "SQLSTATE[42S02]: Base table or view not found"

**Cause:** Table doesn't exist in tenant database.

**Fix:** Run tenant migrations or the system will skip that table.

### "SQLSTATE[HY000] [1049] Unknown database"

**Cause:** Tenant database doesn't exist.

**Fix:** 
```sql
CREATE DATABASE erp_your_org_slug CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run migrations:
```bash
php artisan migrate --database=tenant --path=database/migrations/tenant
```

## Quick Fixes

### Reset Profile Completion

```sql
UPDATE organizations 
SET 
    profile_completion = NULL,
    profile_completion_percentage = 0,
    profile_completed_at = NULL
WHERE org_slug = 'your-org-slug';
```

### Force 100% Completion (for testing)

```sql
UPDATE organizations 
SET 
    profile_completion_percentage = 100,
    profile_completed_at = NOW()
WHERE org_slug = 'your-org-slug';
```

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

## Still Having Issues?

1. **Check the debug page** at `/debug-profile`
2. **Review Laravel logs** in `storage/logs/laravel.log`
3. **Check browser console** for JavaScript errors
4. **Test API directly** with cURL or Postman
5. **Verify database** connections and data
6. **Check middleware** is properly applied

## Getting Help

When reporting issues, include:
- Error messages from browser console
- Error messages from Laravel logs
- Output from debug page
- API response (from cURL test)
- Database query results
- Laravel version
- PHP version

## Debug Checklist

- [ ] Migration ran successfully
- [ ] Organization has tenant_db_name set
- [ ] Tenant database exists
- [ ] Tenant tables exist (at least users, roles, departments)
- [ ] JWT token exists in localStorage
- [ ] JWT token is valid (not expired)
- [ ] Middleware is applied to routes
- [ ] API routes are registered
- [ ] No errors in Laravel logs
- [ ] No errors in browser console
- [ ] Debug page shows organization data
- [ ] Debug page shows user data
- [ ] API test buttons return data
