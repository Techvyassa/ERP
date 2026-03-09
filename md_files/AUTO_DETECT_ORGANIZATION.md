# Auto-Detect Organization Feature

## Overview
The authentication system now automatically detects which organization a user belongs to based on their email address. Users no longer need to manually enter their organization slug during login.

## Changes Made

### 1. AuthenticationServiceImpl
**File:** `app/Services/AuthenticationServiceImpl.php`

**Changes:**
- Made `org_slug` parameter optional in `login()` method
- Added `findOrganizationByUserEmail()` method that searches across all tenant databases
- If `org_slug` is not provided, the system automatically finds the organization
- Returns organization data in `AuthResult`

**How it works:**
```php
// If org_slug not provided, search all organizations
if (!$orgSlug) {
    $organization = $this->findOrganizationByUserEmail($email);
}

// Search across all active/pending organizations
private function findOrganizationByUserEmail(string $email): ?Organization
{
    $organizations = Organization::whereIn('registration_status', ['ACTIVE', 'PENDING'])->get();
    
    foreach ($organizations as $organization) {
        // Switch to tenant database and check if user exists
        $this->connectionRouter->switchToTenant($organization->tenant_db_name);
        if (User::where('email', $email)->exists()) {
            return $organization;
        }
    }
    return null;
}
```

### 2. AuthResult Contract
**File:** `app/Contracts/AuthResult.php`

**Changes:**
- Added `organization` property to include organization data in authentication result
- Organization data is now returned to the frontend

### 3. AuthController
**File:** `app/Http/Controllers/AuthController.php`

**Changes:**
- Made `org_slug` validation rule `nullable` instead of `required`
- Added organization data to API response:
  ```json
  {
    "organization": {
      "org_id": 1,
      "org_slug": "acme-corp",
      "org_name": "Acme Corporation"
    }
  }
  ```

### 4. Login Form
**File:** `resources/views/auth/login.blade.php`

**Changes:**
- Removed the "Organization" input field
- Updated JavaScript to not send `org_slug` in login request
- Updated to extract `org_slug` from API response and store in localStorage

**Before:**
```html
<input id="org_slug" name="org_slug" required placeholder="your-company">
```

**After:**
```html
<!-- Field removed - auto-detected by backend -->
```

## API Changes

### Login Endpoint
**Endpoint:** `POST /api/v1/auth/login`

**Request (Before):**
```json
{
  "org_slug": "acme-corp",
  "email": "user@example.com",
  "password": "password123"
}
```

**Request (After):**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "a1b2c3d4e5f6...",
    "expires_in": 86400,
    "token_type": "Bearer",
    "user": {
      "user_id": 123,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      ...
    },
    "organization": {
      "org_id": 1,
      "org_slug": "acme-corp",
      "org_name": "Acme Corporation"
    }
  }
}
```

## User Experience

### Before
1. User opens login page
2. User enters organization slug (e.g., "acme-corp")
3. User enters email and password
4. User clicks "Sign in"

### After
1. User opens login page
2. User enters email and password
3. User clicks "Sign in"
4. System automatically detects organization from email

## Performance Considerations

### Database Queries
The auto-detection feature queries multiple tenant databases to find the user:
- Queries control database once to get list of organizations
- Queries each tenant database until user is found
- Average case: 1-2 tenant database queries
- Worst case: N tenant database queries (where N = number of organizations)

### Optimization Strategies

1. **Caching:** Cache email-to-organization mappings
   ```php
   $orgSlug = Cache::remember("user_org:{$email}", 3600, function() use ($email) {
       return $this->findOrganizationByUserEmail($email)?->org_slug;
   });
   ```

2. **Email Domain Mapping:** If organizations use custom email domains
   ```php
   // Extract domain from email
   $domain = substr(strrchr($email, "@"), 1);
   // Look up organization by domain
   $org = Organization::where('email_domain', $domain)->first();
   ```

3. **User-Organization Table:** Create a mapping table in control database
   ```sql
   CREATE TABLE user_organization_map (
       email VARCHAR(255) PRIMARY KEY,
       org_id BIGINT,
       last_login TIMESTAMP
   );
   ```

## Backward Compatibility

The system still supports providing `org_slug` explicitly:
```json
{
  "org_slug": "acme-corp",
  "email": "user@example.com",
  "password": "password123"
}
```

This is useful for:
- Users with accounts in multiple organizations
- API clients that already know the organization
- Faster authentication (skips auto-detection)

## Error Messages

### User Not Found
```json
{
  "success": false,
  "error": {
    "code": "AUTHENTICATION_FAILED"
  },
  "message": "No account found with this email address"
}
```

### Multiple Organizations (Future Enhancement)
If a user exists in multiple organizations, the system could:
1. Return a list of organizations for the user to choose from
2. Use the most recently accessed organization
3. Prompt user to specify organization

## Security Considerations

1. **Information Disclosure:** Error messages don't reveal whether an email exists
2. **Timing Attacks:** Consider constant-time comparison for email lookups
3. **Rate Limiting:** Apply rate limiting to prevent organization enumeration
4. **Audit Logging:** All login attempts are logged with organization context

## Testing

### Test Cases

1. **Valid Login - Auto-Detect:**
   - Input: email + password (no org_slug)
   - Expected: Successful login with organization data

2. **Valid Login - Explicit Org:**
   - Input: email + password + org_slug
   - Expected: Successful login (faster, no auto-detection)

3. **Invalid Email:**
   - Input: non-existent email
   - Expected: "No account found" error

4. **User in Suspended Organization:**
   - Input: valid email from suspended org
   - Expected: "Organization is suspended" error

5. **Database Connection Error:**
   - Scenario: One tenant database is unreachable
   - Expected: Skip that org and continue searching

## Future Enhancements

1. **Email-to-Org Cache:** Implement Redis caching for faster lookups
2. **Domain-Based Detection:** Use email domain for instant organization lookup
3. **Multi-Org Support:** Handle users with accounts in multiple organizations
4. **SSO Integration:** Support single sign-on across organizations
5. **Organization Hints:** Show organization logo/name after email entry

## Migration Guide

### For Existing Users
No action required. The system automatically detects their organization.

### For Developers
1. Update any API clients to remove `org_slug` from login requests
2. Update tests to expect organization data in login response
3. Consider implementing caching for production environments

### For Administrators
1. Monitor login performance with auto-detection
2. Consider implementing email domain mapping for large deployments
3. Review audit logs for any failed auto-detection attempts
