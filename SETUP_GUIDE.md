# Setup Guide - Tenant Provisioning System

## Prerequisites

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- (Optional) Redis for caching

---

## Initial Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:generate
```

### 3. Configure Database

Edit `.env`:

```env
# Control Database (main database)
CONTROL_DB_HOST=127.0.0.1
CONTROL_DB_PORT=3306
CONTROL_DB_DATABASE=ERP_saas_control
CONTROL_DB_USERNAME=root
CONTROL_DB_PASSWORD=

# Tenant Database (dynamic databases)
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=
TENANT_DB_GRANT_HOST=localhost  # or % for remote access

# Cache Configuration
CACHE_STORE=database  # Use 'redis' if Redis is available

# Queue Configuration
QUEUE_CONNECTION=database  # Use 'redis' for better performance
```

### 4. Create Control Database

```bash
# Using MySQL CLI
mysql -u root -p
CREATE DATABASE ERP_saas_control CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

Or using PHP:

```bash
php artisan tinker
>>> DB::statement("CREATE DATABASE ERP_saas_control CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
>>> exit
```

### 5. Run Migrations

```bash
# Run control database migrations
php artisan migrate --database=control --path=database/migrations/control

# Verify migrations
php artisan migrate:status --database=control
```

### 6. Seed Initial Data (Optional)

```bash
# Seed subscription plans and other initial data
php artisan db:seed --database=control
```

---

## Configuration

### Cache Setup

**Option 1: Database Cache (Recommended for Development)**

```env
CACHE_STORE=database
```

Ensure cache table exists:
```bash
php artisan migrate
```

**Option 2: Redis Cache (Recommended for Production)**

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Start Redis:
```bash
# Linux/Mac
redis-server

# Docker
docker run -d -p 6379:6379 redis:alpine

# Verify
redis-cli ping  # Should return PONG
```

### Queue Setup

**Option 1: Database Queue (Simple)**

```env
QUEUE_CONNECTION=database
```

Run worker:
```bash
php artisan queue:work
```

**Option 2: Redis Queue (Better Performance)**

```env
QUEUE_CONNECTION=redis
```

Run worker:
```bash
php artisan queue:work redis
```

### Tenant Configuration

Edit `config/tenant.php` or set in `.env`:

```env
# Database prefix for tenant databases
TENANT_DB_PREFIX=erp_

# Provisioning settings
TENANT_PROVISIONING_TIMEOUT=300
TENANT_PROVISIONING_MAX_ATTEMPTS=3

# Trial settings
SUBSCRIPTION_TRIAL_DAYS=14
SUBSCRIPTION_TRIAL_PLAN_CODE=TRIAL
```

---

## Testing the Setup

### 1. Start the Application

```bash
# Development server
php artisan serve

# Or with specific host/port
php artisan serve --host=127.0.0.1 --port=8000
```

### 2. Test Health Endpoint

```bash
curl http://localhost:8000/api/v1/health
```

Expected response:
```json
{
  "success": true,
  "message": "ERP API is running",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### 3. Register Test Organization

```bash
curl -X POST http://localhost:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_name": "Test Corporation",
    "org_slug": "test-corp",
    "primary_email": "admin@test.com",
    "country_code": "US",
    "timezone": "UTC",
    "currency_code": "USD"
  }'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "org_id": 1,
    "org_slug": "test-corp",
    "org_name": "Test Corporation",
    "registration_status": "PENDING",
    "tenant_db_name": null
  },
  "message": "Organization registered successfully. Provisioning in progress."
}
```

### 4. Process Provisioning Job

```bash
# In a separate terminal, run queue worker
php artisan queue:work --once

# Or manually provision
php artisan tenant:provision test-corp
```

### 5. Verify Tenant Database

```bash
# Check if database was created
mysql -u root -p
SHOW DATABASES LIKE 'erp_%';

# Check organization status
php artisan tinker
>>> Organization::where('org_slug', 'test-corp')->first();
>>> # Should show registration_status = 'ACTIVE'
```

---

## Production Setup

### 1. Environment Configuration

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# Use Redis for better performance
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Secure database access
TENANT_DB_GRANT_HOST=localhost  # Don't use % in production
```

### 2. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 3. Setup Queue Workers

**Using Supervisor (Linux):**

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

**Using systemd (Linux):**

Create `/etc/systemd/system/laravel-worker.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/application/artisan queue:work redis --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
```

### 4. Setup Scheduled Tasks

Add to crontab:

```bash
crontab -e
```

Add this line:
```
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Setup Cleanup Job (Optional)

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup orphaned databases weekly
    $schedule->command('tenant:cleanup-orphaned --days=7')
             ->weekly()
             ->sundays()
             ->at('02:00');
    
    // Check trial expirations daily
    $schedule->command('subscription:check-trials')
             ->daily()
             ->at('00:00');
}
```

### 6. Setup Monitoring

**Log Monitoring:**
```bash
# Install log monitoring tool
sudo apt-get install logwatch

# Or use Laravel Telescope for development
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Application Monitoring:**
- Setup error tracking (Sentry, Bugsnag, etc.)
- Setup uptime monitoring (Pingdom, UptimeRobot, etc.)
- Setup performance monitoring (New Relic, DataDog, etc.)

---

## Security Checklist

- [ ] Change default database passwords
- [ ] Use environment-specific `.env` files
- [ ] Enable HTTPS in production
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use `TENANT_DB_GRANT_HOST=localhost` in production
- [ ] Implement proper firewall rules
- [ ] Regular database backups
- [ ] Keep dependencies updated
- [ ] Enable rate limiting
- [ ] Implement proper authentication
- [ ] Use secure session configuration
- [ ] Enable CSRF protection
- [ ] Sanitize all user inputs
- [ ] Use prepared statements (already done)

---

## Backup Strategy

### Database Backup

```bash
# Backup control database
mysqldump -u root -p ERP_saas_control > backup_control_$(date +%Y%m%d).sql

# Backup all tenant databases
for db in $(mysql -u root -p -e "SHOW DATABASES LIKE 'erp_%'" -s --skip-column-names); do
    mysqldump -u root -p $db > backup_${db}_$(date +%Y%m%d).sql
done
```

### Automated Backup Script

Create `backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup control database
mysqldump -u root -p ERP_saas_control | gzip > $BACKUP_DIR/control_$DATE.sql.gz

# Backup tenant databases
for db in $(mysql -u root -p -e "SHOW DATABASES LIKE 'erp_%'" -s --skip-column-names); do
    mysqldump -u root -p $db | gzip > $BACKUP_DIR/${db}_$DATE.sql.gz
done

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

Schedule in crontab:
```
0 2 * * * /path/to/backup.sh
```

---

## Troubleshooting

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues and solutions.

---

## Next Steps

1. ✅ Complete initial setup
2. ✅ Test organization registration
3. ✅ Verify tenant provisioning
4. Configure payment gateways (Stripe/Razorpay)
5. Setup email notifications
6. Implement frontend application
7. Setup monitoring and alerts
8. Configure backups
9. Deploy to production
10. Setup CI/CD pipeline

---

## Additional Resources

- [Quick Reference Guide](QUICK_REFERENCE.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Code Review](TENANT_DB_CREATION_REVIEW.md)
- [Fixes Applied](FIXES_APPLIED.md)
- [Laravel Documentation](https://laravel.com/docs)
