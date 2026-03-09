# Subdomain Troubleshooting Guide

## Issue: `https://vishu.localhost/dashboard/` not working

### Step 1: Check Configuration

Visit: `http://localhost/test-tenant`

This diagnostic page will show you:
- Current host and subdomain detection
- All organizations in your database
- Configuration settings
- Test links for each organization

### Step 2: Verify .env Configuration

Make sure your `.env` file has:

```env
APP_DOMAIN=localhost
APP_URL_PROTOCOL=http
TENANT_MODE=subdomain
TENANT_ALLOW_BOTH=true
```

### Step 3: Add to Hosts File

**Windows**: `C:\Windows\System32\drivers\etc\hosts`

Add this line:
```
127.0.0.1 vishu.localhost
```

**Important**: 
- Open Notepad as Administrator
- Edit the hosts file
- Save and close
- Restart your browser (completely close and reopen)

### Step 4: Clear Laravel Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 5: Check Organization Exists

Run this command to check if "vishu" organization exists:

```bash
php artisan tinker
```

Then in tinker:
```php
\App\Models\Control\Organization::where('org_slug', 'vishu')->first();
```

If it returns `null`, the organization doesn't exist. Create it or use an existing org_slug.

### Step 6: Check Laravel Logs

Check `storage/logs/laravel.log` for errors. Look for:
- "DetectTenantContext Debug" entries
- "Organization not found" errors
- Any other errors

### Step 7: Test Path-Based URL First

Try the path-based URL first to verify the organization exists:

```
http://localhost/org/vishu/dashboard
```

If this works but subdomain doesn't, it's a hosts file or DNS issue.

### Step 8: Verify Web Server

Make sure you're using PHP's built-in server or your web server is configured correctly:

```bash
php artisan serve
```

Then visit: `http://vishu.localhost:8000/dashboard`

### Common Issues

#### Issue 1: "Organization not found"
**Solution**: The org_slug "vishu" doesn't exist in your database. Check available organizations at `/test-tenant`

#### Issue 2: Subdomain not detected
**Solution**: 
- Verify hosts file entry
- Restart browser
- Check APP_DOMAIN in .env matches "localhost"

#### Issue 3: 404 Not Found
**Solution**:
- Run `php artisan route:clear`
- Check that tenant routes are registered in `bootstrap/app.php`

#### Issue 4: Cookie/Auth issues
**Solution**:
- Cookies may not work across subdomains on localhost
- Try using path-based URLs for local development
- Or set cookie domain to `.localhost` in controllers

### Quick Fix: Use Path-Based URLs for Local Development

If subdomains continue to cause issues on localhost, use path-based URLs:

1. Update `.env`:
```env
TENANT_MODE=path
```

2. Visit:
```
http://localhost/org/vishu/dashboard
```

### Debugging Commands

```bash
# Check configuration
php artisan config:show app.domain
php artisan config:show tenant.default_mode

# List all organizations
php artisan tinker
>>> \App\Models\Control\Organization::all(['org_id', 'org_slug', 'org_name', 'registration_status']);

# Test subdomain extraction
php artisan tinker
>>> $host = 'vishu.localhost';
>>> $mainDomain = 'localhost';
>>> $pattern = '/^(.+)\.' . preg_quote($mainDomain, '/') . '$/';
>>> preg_match($pattern, $host, $matches);
>>> $matches[1]; // Should return 'vishu'
```

### Production Setup

For production with real domain (e.g., yoursite.com):

1. **DNS**: Add wildcard A record `*.yoursite.com` pointing to your server
2. **SSL**: Get wildcard SSL certificate
3. **Web Server**: Configure to accept all subdomains
4. **Update .env**:
```env
APP_DOMAIN=yoursite.com
APP_URL_PROTOCOL=https
```

### Alternative: Use IP Address

If localhost subdomains don't work, try using IP:

1. Hosts file:
```
127.0.0.1 vishu.127.0.0.1.nip.io
```

2. Visit:
```
http://vishu.127.0.0.1.nip.io:8000/dashboard
```

nip.io is a service that provides wildcard DNS for any IP address.
