# XAMPP Subdomain Configuration Guide

## Issue
When accessing `vishu.localhost/dashboard/`, you see the XAMPP welcome page instead of your Laravel application.

## Root Cause
Apache is not configured to route subdomain requests to your Laravel application's public directory.

## Solution

### Step 1: Edit Apache Virtual Hosts Configuration

**File Location**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

Add the following configuration:

```apache
# Main domain - Laravel application
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/erp/ERP/public"
    
    <Directory "C:/xampp/htdocs/erp/ERP/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/localhost-error.log"
    CustomLog "logs/localhost-access.log" common
</VirtualHost>

# Wildcard subdomain - All tenant subdomains
<VirtualHost *:80>
    ServerName localhost
    ServerAlias *.localhost
    DocumentRoot "C:/xampp/htdocs/erp/ERP/public"
    
    <Directory "C:/xampp/htdocs/erp/ERP/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/tenant-error.log"
    CustomLog "logs/tenant-access.log" common
</VirtualHost>
```

**Important**: Replace `C:/xampp/htdocs/erp/ERP/public` with your actual Laravel public directory path.

### Step 2: Enable Virtual Hosts in Apache

**File Location**: `C:\xampp\apache\conf\httpd.conf`

Find this line (around line 477):
```apache
#Include conf/extra/httpd-vhosts.conf
```

Remove the `#` to uncomment it:
```apache
Include conf/extra/httpd-vhosts.conf
```

### Step 3: Verify Hosts File

**File Location**: `C:\Windows\System32\drivers\etc\hosts`

Make sure you have these entries:
```
127.0.0.1 localhost
127.0.0.1 vishu.localhost
127.0.0.1 acme.localhost
127.0.0.1 techcorp.localhost
```

Add entries for each organization you want to test.

### Step 4: Restart Apache

1. Open XAMPP Control Panel
2. Stop Apache
3. Start Apache
4. Check for any errors in the XAMPP Control Panel

### Step 5: Test

Visit: `http://vishu.localhost/dashboard`

You should now see your Laravel application instead of the XAMPP welcome page.

## Alternative: Use Port-Based Configuration

If the above doesn't work, try this simpler approach:

### Edit httpd-vhosts.conf:

```apache
<VirtualHost *:80>
    ServerName localhost
    ServerAlias *.localhost
    DocumentRoot "C:/xampp/htdocs/erp/ERP/public"
    
    <Directory "C:/xampp/htdocs/erp/ERP/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
        Require all granted
    </Directory>
</VirtualHost>
```

## Troubleshooting

### Issue 1: Still seeing XAMPP page

**Check Apache error logs**:
- `C:\xampp\apache\logs\error.log`
- Look for configuration errors

**Verify DocumentRoot path**:
```bash
# In command prompt, navigate to your Laravel directory
cd C:\xampp\htdocs\erp\ERP\public
dir
# You should see index.php
```

### Issue 2: 403 Forbidden Error

**Solution**: Check directory permissions in httpd-vhosts.conf:
```apache
<Directory "C:/xampp/htdocs/erp/ERP/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Issue 3: Apache won't start

**Check for syntax errors**:
```bash
# In XAMPP directory
apache\bin\httpd.exe -t
```

This will show any configuration errors.

### Issue 4: Changes not taking effect

1. Clear browser cache (Ctrl+Shift+Delete)
2. Try incognito/private mode
3. Restart Apache completely
4. Check if you're editing the correct httpd-vhosts.conf file

## Verify Configuration

### Check Apache is using correct DocumentRoot:

1. Visit: `http://localhost/`
2. You should see your Laravel application (not XAMPP page)

### Check subdomain routing:

1. Visit: `http://vishu.localhost/test-tenant`
2. Should show the tenant diagnostic page

### Check Laravel routes:

1. Run: `php artisan route:list`
2. Look for tenant routes

## Complete XAMPP Configuration Example

Here's a complete working configuration:

**httpd-vhosts.conf**:
```apache
# Disable default XAMPP virtual hosts
#<VirtualHost *:80>
#    DocumentRoot "C:/xampp/htdocs/"
#    ServerName localhost
#</VirtualHost>

# Laravel Application - Main domain and all subdomains
<VirtualHost *:80>
    ServerName localhost
    ServerAlias *.localhost
    DocumentRoot "C:/xampp/htdocs/erp/ERP/public"
    
    <Directory "C:/xampp/htdocs/erp/ERP/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
        Require all granted
    </Directory>
    
    # Enable mod_rewrite for Laravel
    <IfModule mod_rewrite.c>
        RewriteEngine On
    </IfModule>
    
    ErrorLog "logs/laravel-error.log"
    CustomLog "logs/laravel-access.log" common
</VirtualHost>
```

## Testing Checklist

- [ ] httpd-vhosts.conf configured
- [ ] httpd.conf includes httpd-vhosts.conf
- [ ] hosts file has subdomain entries
- [ ] Apache restarted
- [ ] Browser cache cleared
- [ ] `http://localhost/` shows Laravel app
- [ ] `http://vishu.localhost/` shows Laravel app
- [ ] `http://vishu.localhost/dashboard` shows tenant dashboard

## Quick Test Commands

```bash
# Test Apache configuration
C:\xampp\apache\bin\httpd.exe -t

# Check if Apache is listening on port 80
netstat -ano | findstr :80

# View Apache error log
type C:\xampp\apache\logs\error.log

# View Laravel log
type C:\xampp\htdocs\erp\ERP\storage\logs\laravel.log
```

## If All Else Fails: Use PHP Built-in Server

As a temporary solution, use PHP's built-in server which handles subdomains differently:

```bash
cd C:\xampp\htdocs\erp\ERP
php artisan serve --host=0.0.0.0 --port=8000
```

Then visit:
- `http://localhost:8000/org/vishu/dashboard` (path-based, works immediately)
- `http://vishu.localhost:8000/dashboard` (subdomain, requires hosts file)

## Production Note

This configuration is for local development only. For production:
- Use proper web server configuration (Apache/Nginx)
- Set up SSL certificates
- Configure proper DNS records
- Use environment-specific settings
