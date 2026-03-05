# User Roles and Login Flow

## User Types

### 1. Super Admin (ERP Owner)
- **Database:** Control database
- **Access:** Full system access, manages all organizations
- **Identification:** 
  - Email: `admin@zaperp.com`
  - OR org_slug: `super-admin`
  - OR user field: `is_super_admin: true`
- **Login Redirect:** `/control/dashboard` (Control Panel)

### 2. Tenant Users (Organization Users)
- **Database:** Tenant database (organization-specific)
- **Access:** Organization-specific access based on roles
- **Identification:** Any user not matching super admin criteria
- **Login Redirect:** `/org/{org_slug}/dashboard` (Organization Dashboard)

---

## Login Flow

### Email/Password Login
```
1. User enters email & password
2. POST /api/v1/auth/login
3. Backend validates credentials
4. Backend returns user data + organization data
5. Frontend stores in localStorage:
   - user
   - access_token
   - refresh_token
   - org_slug
   - org_data
6. Frontend checks user type:
   ├─ Super Admin → Redirect to /control/dashboard
   └─ Tenant User → Redirect to /org/{org_slug}/dashboard
```

### Google Firebase Login
```
1. User clicks "Continue with Google"
2. Firebase authentication
3. POST /api/v1/auth/firebase-login
4. Backend validates Firebase token
5. Backend returns user data + organization data
6. Frontend stores in localStorage (same as email login)
7. Frontend checks user type:
   ├─ Super Admin → Redirect to /control/dashboard
   └─ Tenant User → Redirect to /org/{org_slug}/dashboard
```

---

## Super Admin Identification Logic

```javascript
const isSuperAdmin = user.email === 'admin@zaperp.com' || 
                   orgSlug === 'super-admin' ||
                   user.is_super_admin === true;
```

### Priority Order:
1. Check email against `admin@zaperp.com`
2. Check org_slug against `super-admin`
3. Check user object for `is_super_admin` flag

---

## Dashboard Access

### Main Dashboard (`/dashboard`)
- **Purpose:** Navigation hub (optional intermediate page)
- **Auto-redirect:** 
  - Super Admin → `/control/dashboard`
  - Tenant User → Can navigate to any module
- **Note:** Currently configured to auto-redirect super admin

### Control Panel (`/control/dashboard`)
- **Access:** Super Admin only
- **Features:**
  - Manage all organizations
  - View all subscriptions
  - Manage subscription plans
  - View all payments
  - Feature control management
  - System settings

### Tenant Dashboard (`/org/{org_slug}/dashboard`)
- **Access:** Tenant users of that organization
- **Features:**
  - Organization-specific data
  - Master data management
  - Production, inventory, vendors
  - User and role management
  - Reports and analytics

---

## Database Structure

### Control Database
```
Tables:
- organizations
- org_subscriptions
- active_subscriptions
- subscription_plans
- payment_records
- feature_controls
- refresh_tokens
```

### Tenant Database (per organization)
```
Tables:
- users
- departments
- roles
- role_permissions
- materials
- products
- vendors
- warehouses
- bom_header
- bom_detail
- hsn_codes
- gst_taxes
- currency
```

---

## Authentication Middleware

### `web.jwt` Middleware
- Validates JWT token from cookie
- Checks token expiration
- Verifies user exists
- Sets user context for request

### Route Protection
```php
Route::middleware(['web.jwt'])->group(function () {
    // Main dashboard
    Route::get('/dashboard', ...);
    
    // Control panel (super admin)
    Route::prefix('control')->group(...);
    
    // Tenant routes (organization users)
    Route::prefix('org/{org_slug}')->group(...);
});
```

---

## Creating Super Admin

### Option 1: Special Organization
Create an organization with `org_slug = 'super-admin'`:
```sql
INSERT INTO organizations (org_slug, org_name, registration_status)
VALUES ('super-admin', 'System Administration', 'ACTIVE');
```

### Option 2: Special Email
Create a user with email `admin@zaperp.com` in any organization.

### Option 3: Add is_super_admin Field
Add a field to the user table:
```sql
ALTER TABLE users ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE;
UPDATE users SET is_super_admin = TRUE WHERE email = 'admin@zaperp.com';
```

---

## Security Considerations

### 1. Super Admin Protection
- Super admin credentials should be highly secure
- Consider 2FA for super admin accounts
- Limit super admin access to specific IPs (optional)

### 2. Tenant Isolation
- Each tenant has separate database
- Users cannot access other tenants' data
- org_slug validation on every request

### 3. Token Security
- JWT tokens stored in httpOnly cookies
- Refresh tokens for long-term sessions
- Token expiration and rotation

---

## Testing Scenarios

### Test 1: Super Admin Login
```
Email: admin@zaperp.com
Password: [super_admin_password]
Expected: Redirect to /control/dashboard
```

### Test 2: Tenant User Login
```
Email: user@acme.com
Password: [user_password]
Expected: Redirect to /org/acme-corp/dashboard
```

### Test 3: Invalid Credentials
```
Email: invalid@example.com
Password: wrong_password
Expected: Error message, stay on login page
```

### Test 4: Expired Token
```
Access with expired token
Expected: Redirect to /login
```

---

## Troubleshooting

### Issue: User redirected to wrong dashboard
**Solution:** Check user email, org_slug, and is_super_admin flag

### Issue: Cannot access control panel
**Solution:** Verify user is identified as super admin

### Issue: Cannot access tenant dashboard
**Solution:** Verify org_slug is correct and user belongs to that organization

### Issue: Redirect loop
**Solution:** Check localStorage data, clear and re-login

---

## Future Enhancements

### 1. Role-Based Access Control (RBAC)
- Define granular permissions
- Assign permissions to roles
- Check permissions on each action

### 2. Multi-Organization Access
- Allow users to belong to multiple organizations
- Organization switcher in UI
- Separate sessions per organization

### 3. Audit Logging
- Log all super admin actions
- Track user access patterns
- Security monitoring

### 4. Advanced Authentication
- 2FA/MFA support
- SSO integration
- Biometric authentication

---

**Last Updated:** March 5, 2026  
**Version:** 1.0  
**Status:** Implemented ✅
