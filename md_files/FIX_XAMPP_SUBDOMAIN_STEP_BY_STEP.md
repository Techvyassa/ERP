# Fix XAMPP Subdomain - Step by Step Guide

## Current Issue
You're seeing the XAMPP welcome page at `vishu.localhost/dashboard/` instead of your Laravel application.

## Solution: Configure Apache Virtual Host

### Step 1: Open httpd-vhosts.conf

1. Navigate to: `C:\xampp\apache\conf\extra\`
2. Open file: `httpd-vhosts.conf` with Notepad (as Administrator)
3. You'll see it's mostly commented out (lines starting with ##)

### Step 2: Replace Content

1. **Delete everything** in the file OR scroll to the bottom
2. **Add this configuration** at the end:

```apache
# Laravel Multi-Tenancy Configuration
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
    
    ErrorLog "logs/laravel-error.log"
    CustomLog "logs/laravel-access.log" common
</VirtualHost>
```

3. **Save the file** (Ctrl+S)

### Step 3: Enable Virtual Hosts in Apache

1. Navigate to: `C:\xampp\apache\conf\`
2. Open file: `httpd.conf` with Notepad (as Administrator)
3. Press Ctrl+F and search for: `httpd-vhosts.conf`
4. You'll find a line like: `#Include conf/extra/httpd-vhosts.conf`
5. **Remove the #** to make it: `Include conf/extra/httpd-vhosts.conf`
6. **Save the file** (Ctrl+S)

### Step 4: Verify Hosts File

1. Navigate to: `C:\Windows\System32\drivers\etc\`
2. Open file: `hosts` with Notepad (as Administrator)
3. Make sure you have these lines:

```
127.0.0.1 localhost
127.0.0.1 vishu.localhost
```

4. **Save the file** (Ctrl+S)

### Step 5: Restart Apache

1. Open **XAMPP Control Panel**
2. Click **Stop** next to Apache
3. Wait 2 seconds
4. Click **Start** next to Apache
5. Check for any errors in the XAMPP Control Panel

### Step 6: Clear Browser Cache

1. Press **Ctrl+Shift+Delete** in your browser
2. Select "Cached images and files"
3. Click "Clear data"
4. **Close and reopen your browser**

### Step 7: Test

Visit: `http://vishu.localhost/dashboard`

You should now see your Laravel tenant dashboard!

## Troubleshooting

### Issue 1: Apache won't start

**Check for syntax errors:**
1. Open Command Prompt as Administrator
2. Run: `C:\xampp\apache\bin\httpd.exe -t`
3. If you see errors, check your httpd-vhosts.conf for typos

**Common errors:**
- Missing closing `</VirtualHost>` tag
- Wrong path format (use forward slashes: `C:/xampp/...` not `C:\xampp\...`)
- Missing quotes around paths

### Issue 2: Still seeing XAMPP page

**Check DocumentRoot path:**
1. Open File Explorer
2. Navigate to: `C:\xampp\htdocs\erp\ERP\public`
3. Verify `index.php` exists in this folder
4. If the path is different, update it in httpd-vhosts.conf

**Check if virtual hosts are enabled:**
1. Open: `C:\xampp\apache\conf\httpd.conf`
2. Search for: `httpd-vhosts.conf`
3. Make sure the line is NOT commented (no # at the start)

### Issue 3: 403 Forbidden Error

**Fix permissions in httpd-vhosts.conf:**
```apache
<Directory "C:/xampp/htdocs/erp/ERP/public">
    Options Indexes FollowSymLinks MultiViews
    AllowOverride All
    Require all granted
</Directory>
```

### Issue 4: Changes not taking effect

1. **Restart Apache** (Stop and Start in XAMPP)
2. **Clear browser cache** (Ctrl+Shift+Delete)
3. **Try incognito mode** (Ctrl+Shift+N)
4. **Check you edited the correct file** (httpd-vhosts.conf in C:\xampp\apache\conf\extra\)

## Verification Steps

### 1. Check Apache Configuration
```bash
C:\xampp\apache\bin\httpd.exe -t
```
Should output: `Syntax OK`

### 2. Check Virtual Host is Active
```bash
C:\xampp\apache\bin\httpd.exe -S
```
Should show your VirtualHost configuration

### 3. Test Main Domain
Visit: `http://localhost/`
Should show your Laravel application (not XAMPP page)

### 4. Test Subdomain
Visit: `http://vishu.localhost/dashboard`
Should show your tenant dashboard

### 5. Test Path-Based URL
Visit: `http://localhost/org/vishu/dashboard`
Should also work

## Quick Reference

**Files to Edit:**
1. `C:\xampp\apache\conf\extra\httpd-vhosts.conf` - Add VirtualHost
2. `C:\xampp\apache\conf\httpd.conf` - Enable virtual hosts
3. `C:\Windows\System32\drivers\etc\hosts` - Add subdomain entries

**Commands:**
- Test Apache config: `C:\xampp\apache\bin\httpd.exe -t`
- View virtual hosts: `C:\xampp\apache\bin\httpd.exe -S`

**Restart Apache:**
- XAMPP Control Panel → Stop → Start

## Alternative: Use PHP Built-in Server (No XAMPP Config Needed)

If XAMPP configuration is too complex, use Laravel's built-in server:

1. Open Command Prompt
2. Navigate to your Laravel directory:
   ```bash
   cd C:\xampp\htdocs\erp\ERP
   ```
3. Start the server:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
4. Visit: `http://vishu.localhost:8000/dashboard`

Or use path-based URLs (works immediately):
```
http://localhost:8000/org/vishu/dashboard
```

## Need Help?

If you're still having issues:

1. Check Apache error log: `C:\xampp\apache\logs\error.log`
2. Check Laravel log: `C:\xampp\htdocs\erp\ERP\storage\logs\laravel.log`
3. Run diagnostic: `http://localhost/test-tenant`
4. Share the error messages for further assistance

## Success Checklist

- [ ] httpd-vhosts.conf configured with correct path
- [ ] httpd.conf includes httpd-vhosts.conf (no # at start)
- [ ] hosts file has subdomain entries
- [ ] Apache restarted successfully
- [ ] Browser cache cleared
- [ ] `http://localhost/` shows Laravel (not XAMPP)
- [ ] `http://vishu.localhost/dashboard` shows tenant dashboard
