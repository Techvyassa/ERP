# Multi-Tenancy Setup Guide

## Overview
This system supports two types of tenant URL structures:
1. **Subdomain-based**: `company1.yoursite.com/dashboard`
2. **Path-based**: `yoursite.com/org/company1/dashboard`

Both can be enabled simultaneously, allowing organizations to choose their preferred access method.

## Architecture

### Components Created

1. **DetectTenantContext Middleware** (`app/Http/Middleware/DetectTenantContext.php`)
   - Detects organization from subdomain or URL path
   - Validates organization status
   - Sets tenant context for the request

2. **TenantUrlHelper** (`app/Helpers/TenantUrlHelper.php`)
   - Generates tenant-aware URLs
   - Supports both subdomain and path-based URLs
   - Provides utility methods for tenant context

3. **Tenant Routes** (`routes/tenant.php`)
   - Dedicated routes for tenant-specific pages
   - Automatically works with both URL structures

4. **Tenant Configuration** (`config/tenant.php`)
   - Centralized tenant settings
   - Mode selection (subdomain/path)
   - Reserved subdomains
   - Cache settings

5. **Helper Functions** (`app/Helpers/helpers.php`)
   - `tenantRoute()`: Generate tenant URLs
   - `currentTenantType()`: Get current access type
   - `currentOrgSlug()`: Get current organization
   - `isInTenantContext()`: Check if in tenant context

## Configuration

### 1. Environment Variables

Add to your `.env` file:

```env
# Main application domain (without protocol)
APP_DOMAIN=localhost

# URL protocol
APP_URL_PROTOCOL=http

# Tenant access mode: subdomain or path
TENANT_MODE=subdomain

# Allow both modes
TENANT_ALLOW_BOTH=true

# Auto-redirect settings
TENANT_SUBDOMAIN_AUTO_REDIRECT=false
TENANT_PATH_AUTO_REDIRECT=false

# Caching
TENANT_CACHE_ENABLED=true
TENANT_CACHE_TTL=3600
```

### 2. Local Development Setup

#### For Subdomain Testing on Localhost

Edit your hosts file:

**Windows**: `C:\Windows\System32\drivers\etc\hosts`
**Mac/Linux**: `/etc/hosts`

Add entries for each organization:
```
127.0.0.1 localhost
127.0.0.1 acme.localhost
127.0.0.1 techcorp.localhost
127.0.0.1 company1.localhost
```

#### For Path-Based Testing

No special configuration needed. Just use:
```
http://localhost/org/acme/dashboard
http://localhost/org/techcorp/dashboard
```

### 3. Production Setup

#### Subdomain Configuration

1. **DNS Wildcard Record**
   - Add a wildcard A record: `*.yoursite.com` → Your server IP
   - This allows any subdomain to resolve to your server

2. **Web Server Configuration**

**Apache** (`.htaccess` or VirtualHost):
```apache
<VirtualHost *:80>
    ServerName yoursite.com
    ServerAlias *.yoursite.com
    DocumentRoot /path/to/public
    
    <Directory /path/to/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx**:
```nginx
server {
    listen 80;
    server_name yoursite.com *.yoursite.com;
    root /path/to/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

3. **SSL Certificate**
   - Use Let's Encrypt with wildcard certificate:
   ```bash
   certbot certonly --manual --preferred-challenges=dns \
     -d yoursite.com -d *.yoursite.com
   ```

4. **Update .env**:
```env
APP_DOMAIN=yoursite.com
APP_URL_PROTOCOL=https
TENANT_MODE=subdomain
```

## Usage

### Creating Tenant URLs in Code

```php
// In controllers or views
$dashboardUrl = tenantRoute('dashboard', 'acme');
// Returns: https://acme.yoursite.com/dashboard (subdomain mode)
// Or: https://yoursite.com/org/acme/dashboard (path mode)

$usersUrl = tenantRoute('users', 'acme');
// Returns: https://acme.yoursite.com/users

// With parameters
$userUrl = tenantRoute('users/123', 'acme', ['tab' => 'profile']);
// Returns: https://acme.yoursite.com/users/123?tab=profile
```

### In Blade Templates

```blade
<a href="{{ tenantRoute('dashboard', $organization->org_slug) }}">
    Dashboard
</a>

<a href="{{ tenantRoute('users', currentOrgSlug()) }}">
    Users
</a>

@if(currentTenantType() === 'subdomain')
    <p>You're using subdomain access</p>
@else
    <p>You're using path-based access</p>
@endif
```

### Checking Tenant Context

```php
if (isInTenantContext()) {
    $orgSlug = currentOrgSlug();
    $tenantType = currentTenantType();
    // Do tenant-specific logic
}
```

## URL Structure Examples

### Subdomain Mode

```
Login:          https://yoursite.com/login
Dashboard:      https://acme.yoursite.com/dashboard
Users:          https://acme.yoursite.com/users
Settings:       https://acme.yoursite.com/settings
User Profile:   https://acme.yoursite.com/users/123
```

### Path-Based Mode

```
Login:          https://yoursite.com/login
Dashboard:      https://yoursite.com/org/acme/dashboard
Users:          https://yoursite.com/org/acme/users
Settings:       https://yoursite.com/org/acme/settings
User Profile:   https://yoursite.com/org/acme/users/123
```

## Route Registration

Routes are automatically registered for both modes in `bootstrap/app.php`:

```php
// Subdomain routes
Route::domain('{tenant}.' . config('app.domain'))
    ->middleware('web')
    ->group(base_path('routes/tenant.php'));

// Path-based routes
Route::prefix('org/{tenant}')
    ->middleware('web')
    ->group(base_path('routes/tenant.php'));
```

## Middleware Flow

1. **DetectTenantContext** - Detects organization from URL
2. **WebJWTAuth** - Validates authentication
3. **ResolveTenant** (API only) - Switches database connection
4. **ValidateSubscription** (API only) - Checks subscription status

## Reserved Subdomains

The following subdomains are reserved and cannot be used as organization slugs:
- www
- api
- admin
- app
- mail
- ftp
- localhost
- staging
- dev
- test

Configure additional reserved subdomains in `config/tenant.php`.

## Switching Between Modes

Users can switch between subdomain and path-based URLs. The dashboard includes a button to switch:

```blade
@if($tenantType === 'subdomain')
    <a href="{{ url('/org/' . $organization->org_slug . '/dashboard') }}">
        Switch to Path-based URL
    </a>
@else
    <a href="{{ 'https://' . $organization->org_slug . '.' . config('app.domain') . '/dashboard' }}">
        Switch to Subdomain
    </a>
@endif
```

## Auto-Redirect (Optional)

Enable auto-redirect to enforce a single mode:

```env
# Redirect path-based to subdomain
TENANT_SUBDOMAIN_AUTO_REDIRECT=true

# Or redirect subdomain to path-based
TENANT_PATH_AUTO_REDIRECT=true
```

## Caching

Tenant data is cached for performance:

```php
// Cache is automatically managed by DetectTenantContext
// Manual cache operations:
Cache::remember('tenant:acme', 3600, function() {
    return Organization::where('org_slug', 'acme')->first();
});
```

## Testing

### Test Subdomain Access

```bash
# Add to hosts file
127.0.0.1 testorg.localhost

# Visit in browser
http://testorg.localhost:8000/dashboard
```

### Test Path-Based Access

```bash
# Visit in browser
http://localhost:8000/org/testorg/dashboard
```

### Test Both Modes

```php
// In tests
$this->get('http://acme.localhost/dashboard')
    ->assertStatus(200);

$this->get('/org/acme/dashboard')
    ->assertStatus(200);
```

## Troubleshooting

### Subdomain Not Working

1. Check DNS/hosts file configuration
2. Verify `APP_DOMAIN` in `.env`
3. Check web server configuration
4. Ensure wildcard SSL certificate is installed

### Path-Based Not Working

1. Check route registration in `bootstrap/app.php`
2. Verify middleware is applied
3. Check for route conflicts

### Organization Not Found

1. Verify organization exists in database
2. Check `org_slug` matches URL
3. Review `DetectTenantContext` logs
4. Clear cache: `php artisan cache:clear`

### Cookie Issues Across Subdomains

Set cookie domain to allow sharing:

```php
->cookie(
    'auth_token',
    $token,
    60 * 24,
    '/',
    '.yoursite.com', // Note the leading dot
    true,
    true,
    false,
    'lax'
);
```

## Security Considerations

1. **Subdomain Validation**: Always validate organization exists and is active
2. **Reserved Subdomains**: Prevent use of system subdomains
3. **SSL/TLS**: Use HTTPS in production with wildcard certificates
4. **Cookie Security**: Set appropriate cookie domain and flags
5. **CORS**: Configure CORS if API is on different subdomain
6. **Rate Limiting**: Apply per-tenant rate limiting
7. **Database Isolation**: Ensure proper tenant database switching

## Performance Optimization

1. **Cache tenant lookups**: Enabled by default
2. **DNS caching**: Configure appropriate TTL
3. **CDN**: Use CDN with wildcard subdomain support
4. **Database connection pooling**: Reuse tenant connections
5. **Lazy loading**: Load tenant data only when needed

## Migration from Single-Tenant

1. Update all hardcoded URLs to use `tenantRoute()`
2. Add tenant context to all routes
3. Update navigation menus
4. Test both URL modes
5. Update external integrations
6. Update email templates with tenant URLs

## Next Steps

1. Run `composer dump-autoload` to load helper functions
2. Configure `.env` with your domain
3. Set up DNS/hosts file for testing
4. Test login and redirect to tenant dashboard
5. Customize tenant dashboard view
6. Add more tenant-specific routes as needed
