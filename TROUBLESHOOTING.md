# Troubleshooting Guide

## Common Issues and Solutions

---

## ❌ Redis Connection Error

### Error Message
```
No connection could be made because the target machine actively refused it [tcp://127.0.0.1:6379]
```

### Cause
The application is configured to use Redis for caching/rate limiting, but Redis server is not running.

### Solution 1: Use Database Cache (Recommended for Development)

1. Update `.env`:
```env
CACHE_STORE=database
```

2. Ensure cache table exists:
```bash
php artisan migrate
```

3. Clear config cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### Solution 2: Install and Start Redis

**Windows (using Memurai - Redis alternative):**
```bash
# Download from https://www.memurai.com/
# Or use WSL with Redis
```

**Linux/Mac:**
```bash
# Install Redis
sudo apt-get install redis-server  # Ubuntu/Debian
brew install redis                  # macOS

# Start Redis
redis-server

# Verify Redis is running
redis-cli ping
# Should return: PONG
```

**Docker:**
```bash
docker run -d -p 6379:6379 redis:alpine
```

---

## ❌ Database Connection Error

### Error Message
```
SQLSTATE[HY000] [2002] No connection could be made
```

### Solution

1. Check MySQL is running:
```bash
# Windows (XAMPP)
# Start MySQL from XAMPP Control Panel

# Linux
sudo service mysql start

# Mac
brew services start mysql
```

2. Verify database credentials in `.env`:
```env
CONTROL_DB_HOST=127.0.0.1
CONTROL_DB_PORT=3306  # or 3307 for XAMPP
CONTROL_DB_DATABASE=ERP_saas_control
CONTROL_DB_USERNAME=root
CONTROL_DB_PASSWORD=
```

3. Test connection:
```bash
mysql -h 127.0.0.1 -P 3306 -u root -p
```

---

## ❌ Queue Job Not Processing

### Symptoms
- Organization stuck in PENDING status
- Provisioning not completing

### Solution

1. Check queue configuration in `.env`:
```env
QUEUE_CONNECTION=database
```

2. Run queue worker:
```bash
# Process one job
php artisan queue:work --once

# Keep processing jobs
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=tenant-provisioning
```

3. Check failed jobs:
```bash
php artisan queue:failed
```

4. Retry failed jobs:
```bash
php artisan queue:retry all
```

---

## ❌ Rate Limit Error

### Error Message
```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED"
  },
  "message": "Too many organization registration attempts"
}
```

### Solution

1. Wait 1 hour for automatic reset

2. Or clear rate limit cache:
```bash
php artisan cache:clear
```

3. Or adjust rate limit in `AppServiceProvider.php`:
```php
return Limit::perHour(10)->by($request->ip()); // Increase from 5 to 10
```

---

## ❌ Tenant Database Not Found

### Error Message
```
Tenant database not found: erp_acme
```

### Solution

1. Check organization status:
```bash
php artisan tinker
>>> Organization::where('org_slug', 'acme')->first();
```

2. If status is PENDING, run provisioning:
```bash
php artisan tenant:provision acme
```

3. If provisioning failed, check logs:
```bash
tail -f storage/logs/laravel.log | grep "provisioning"
```

4. Rollback and retry:
```bash
php artisan tinker
>>> app(\App\Contracts\TenantProvisioningService::class)->rollbackProvisioning(1);
>>> exit
php artisan tenant:provision acme
```

---

## ❌ Permission Denied on Database

### Error Message
```
Access denied for user 'tenant_user'@'localhost'
```

### Solution

1. Grant permissions manually:
```sql
mysql -u root -p

-- For all tenant databases
GRANT ALL PRIVILEGES ON `erp_%`.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON `erp_%`.* TO 'root'@'%';
FLUSH PRIVILEGES;

-- For specific database
GRANT ALL PRIVILEGES ON `erp_acme`.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

2. Update `.env` if using different user:
```env
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=
TENANT_DB_GRANT_HOST=localhost
```

---

## ❌ Migration Failed

### Error Message
```
Migration table not found
```

### Solution

1. Run migrations on control database:
```bash
php artisan migrate --database=control --path=database/migrations/control
```

2. For tenant database:
```bash
php artisan tenant:migrate {org_slug}
```

---

## ❌ Config Cache Issues

### Symptoms
- Changes to `.env` not taking effect
- Old configuration values being used

### Solution

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache (production only)
php artisan config:cache
php artisan route:cache
```

---

## ❌ Validation Errors

### Error Message
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "org_slug": ["The org slug has already been taken."]
    }
  }
}
```

### Solution

1. Check if organization already exists:
```bash
php artisan tinker
>>> Organization::where('org_slug', 'acme')->first();
```

2. Use different org_slug or delete existing:
```bash
>>> Organization::where('org_slug', 'acme')->delete();
```

---

## 🔍 Debugging Tips

### Enable Debug Mode
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Logs
```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Filter by error level
grep "ERROR" storage/logs/laravel.log

# Filter by organization
grep "org_id: 123" storage/logs/laravel.log
```

### Test Database Connection
```bash
php artisan tinker
>>> DB::connection('control')->select('SELECT 1');
>>> DB::connection('tenant')->select('SELECT 1');
```

### Check Queue Status
```bash
# List all jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Monitor queue in real-time
php artisan queue:listen
```

### Verify Configuration
```bash
php artisan config:show database
php artisan config:show cache
php artisan config:show queue
```

---

## 🚨 Emergency Recovery

### Reset Everything
```bash
# 1. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 2. Drop and recreate control database
mysql -u root -p
DROP DATABASE IF EXISTS ERP_saas_control;
CREATE DATABASE ERP_saas_control;
exit

# 3. Run migrations
php artisan migrate --database=control --path=database/migrations/control

# 4. Seed data if needed
php artisan db:seed

# 5. Restart queue workers
php artisan queue:restart
```

### Clean Up Orphaned Databases
```bash
# Preview what will be deleted
php artisan tenant:cleanup-orphaned --dry-run

# Delete databases older than 7 days
php artisan tenant:cleanup-orphaned --days=7
```

---

## 📞 Getting Help

### Information to Provide

1. **Error message** (full stack trace)
2. **Laravel version**: `php artisan --version`
3. **PHP version**: `php -v`
4. **Database version**: `mysql --version`
5. **Environment**: Development/Production
6. **Recent changes**: What did you change before the error?
7. **Logs**: Last 50 lines from `storage/logs/laravel.log`

### Useful Commands

```bash
# System info
php artisan about

# Check environment
php artisan env

# List all routes
php artisan route:list

# List all commands
php artisan list

# Check database connection
php artisan db:show

# Check migrations status
php artisan migrate:status
```

---

## 🔧 Performance Issues

### Slow Provisioning

1. Check database performance:
```sql
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads%';
```

2. Optimize tables:
```sql
OPTIMIZE TABLE organizations;
OPTIMIZE TABLE org_subscriptions;
```

3. Add indexes (if not already present):
```sql
CREATE INDEX idx_org_slug ON organizations(org_slug);
CREATE INDEX idx_registration_status ON organizations(registration_status);
```

### High Memory Usage

1. Increase PHP memory limit:
```ini
; php.ini
memory_limit = 512M
```

2. Or in `.env`:
```env
PHP_MEMORY_LIMIT=512M
```

### Queue Timeout

1. Increase timeout in `.env`:
```env
TENANT_PROVISIONING_TIMEOUT=600
```

2. Or in job class:
```php
public $timeout = 600;
```

---

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Redis Documentation](https://redis.io/documentation)
