@echo off
echo ========================================
echo XAMPP Virtual Host Configuration
echo ========================================
echo.

echo Step 1: Backing up current httpd-vhosts.conf...
copy "C:\xampp\apache\conf\extra\httpd-vhosts.conf" "C:\xampp\apache\conf\extra\httpd-vhosts.conf.backup"

echo.
echo Step 2: Creating new httpd-vhosts.conf...
(
echo # Laravel Multi-Tenancy Configuration
echo ^<VirtualHost *:80^>
echo     ServerName localhost
echo     ServerAlias *.localhost
echo     DocumentRoot "C:/xampp/htdocs/erp/ERP/public"
echo.    
echo     ^<Directory "C:/xampp/htdocs/erp/ERP/public"^>
echo         Options Indexes FollowSymLinks MultiViews
echo         AllowOverride All
echo         Order allow,deny
echo         Allow from all
echo         Require all granted
echo     ^</Directory^>
echo.    
echo     ErrorLog "logs/laravel-error.log"
echo     CustomLog "logs/laravel-access.log" common
echo ^</VirtualHost^>
) > "C:\xampp\apache\conf\extra\httpd-vhosts.conf"

echo.
echo Step 3: Testing Apache configuration...
"C:\xampp\apache\bin\httpd.exe" -t

echo.
echo ========================================
echo Configuration complete!
echo ========================================
echo.
echo Next steps:
echo 1. Restart Apache in XAMPP Control Panel
echo 2. Clear your browser cache
echo 3. Visit: http://vishu.localhost/dashboard
echo.
echo If you see errors above, check the paths in httpd-vhosts.conf
echo.
pause
