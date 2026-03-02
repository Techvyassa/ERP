# Tenant Database Creation - Quick Reference

## 🚀 Commands

### Provision a Tenant
```bash
# Manual provisioning
php artisan tenant:provision {org_slug}

# Example
php artisan tenant:provision acme-corp
```

### Cleanup Orphaned Databases
```bash
# Dry run (preview only)
php artisan tenant:cleanup-orphaned --dry-run

# Delete databases older than 7 days
php artisan tenant:cleanup-orphaned --days=7

# Delete databases older than 30 days
php artisan tenant:cleanup-orphaned --days=30
```

### Migrate All Tenants
```bash
php artisan tenant:migrate-all
```

---

## 📋 API Endpoints

### Register Organization
```bash
POST /api/v1/organizations/register
Content-Type: application/json

{
  "org_name": "Acme Corporation",
  "org_slug": "acme-corp",
  "primary_email": "admin@acme.com",
  "primary_phone": "+1234567890",
  "country_code": "US",
  "timezone": "America/New_York",
  "currency_code": "USD",
  "max_users": 50
}
```

**Rate Limit:** 5 requests per hour per IP

**Response:**
```json
{
  "success": true,
  "data": {
    "org_id": 1,
    "org_slug": "acme-corp",
    "org_name": "Acme Corporation",
    "registration_status": "PENDING",
    "tenant_db_name": null
  },
  "message": "Organization registered successfully. Provisioning in progress."
}
```

---

## ⚙️ Configuration

### Environment Variables

```env
# Tenant Database
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=
TENANT_DB_GRANT_HOST=%  # % = all hosts, localhost = local only

# Provisioning
TENANT_PROVISIONING_TIMEOUT=300
TENANT_PROVISIONING_MAX_ATTEMPTS=3

# Trial Subscription
SUBSCRIPTION_TRIAL_DAYS=14
SUBSCRIPTION_TRIAL_PLAN_CODE=TRIAL
```

### Config Files

**config/tenant.php**
- Database prefix: `erp_`
- Provisioning settings
- Isolation settings
- Validation rules

**config/subscription.php**
- Trial duration
- Billing cycles
- Grace period
- Module access

---

## 🔍 Monitoring

### Check Provisioning Status
```php
use App\Contracts\TenantProvisioningService;

$service = app(TenantProvisioningService::class);
$status = $service->getProvisioningStatus($orgId);

echo $status->status; // PENDING, ACTIVE, FAILED
echo $status->tenantDbName;
```

### View Logs
```bash
# All provisioning logs
tail -f storage/logs/laravel.log | grep "provisioning"

# Specific organization
tail -f storage/logs/laravel.log | grep "org_id: 123"

# Errors only
tail -f storage/logs/laravel.log | grep "ERROR"
```

---

## 🛠️ Troubleshooting

### Issue: Organization stuck in PENDING
```bash
# Check job queue
php artisan queue:work --once

# Manually provision
php artisan tenant:provision {org_slug}

# Check logs
tail -f storage/logs/laravel.log | grep "org_slug"
```

### Issue: Database exists but provisioning failed
```bash
# Rollback and retry
php artisan tinker
>>> $service = app(\App\Contracts\TenantProvisioningService::class);
>>> $service->rollbackProvisioning($orgId);
>>> exit

# Then retry
php artisan tenant:provision {org_slug}
```

### Issue: Permission denied on database
```bash
# Check MySQL user permissions
mysql -u root -p
SHOW GRANTS FOR 'tenant_user'@'%';

# Grant permissions manually
GRANT ALL PRIVILEGES ON `erp_*`.* TO 'tenant_user'@'%';
FLUSH PRIVILEGES;
```

### Issue: Rate limit exceeded
```bash
# Clear rate limit cache
php artisan cache:clear

# Or wait 1 hour for automatic reset
```

---

## 📊 Database Structure

### Control Database: `ERP_saas_control`
- `organizations` - Organization records
- `subscription_plans` - Available plans
- `org_subscriptions` - Subscription records
- `payment_records` - Payment history
- `feature_controls` - Feature flags

### Tenant Database: `erp_{org_slug}`
- `departments` - Department hierarchy
- `roles` - User roles
- `role_permissions` - Role-module permissions
- `users` - Tenant users

---

## 🔐 Security Best Practices

### 1. Database User Permissions
```sql
-- Create dedicated tenant user (recommended)
CREATE USER 'tenant_user'@'%' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON `erp_%`.* TO 'tenant_user'@'%';
FLUSH PRIVILEGES;
```

### 2. Environment Variables
```env
# Production: Use localhost for security
TENANT_DB_GRANT_HOST=localhost

# Development: Allow remote access
TENANT_DB_GRANT_HOST=%
```

### 3. Rate Limiting
- Default: 5 registrations per hour per IP
- Adjust in `AppServiceProvider::configureRateLimiters()`

---

## 🧪 Testing

### Test Organization Registration
```bash
# Using curl
curl -X POST http://localhost/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_name": "Test Corp",
    "org_slug": "test-corp",
    "primary_email": "test@example.com",
    "country_code": "US"
  }'
```

### Test Idempotency
```bash
# Provision twice - should succeed both times
php artisan tenant:provision test-corp
php artisan tenant:provision test-corp
```

### Test Rollback
```bash
# Provision, then rollback
php artisan tenant:provision test-corp
php artisan tinker
>>> app(\App\Contracts\TenantProvisioningService::class)->rollbackProvisioning(1);
```

---

## 📈 Performance Tips

### 1. Queue Configuration
```env
# Use Redis for better performance
QUEUE_CONNECTION=redis

# Or database for simplicity
QUEUE_CONNECTION=database
```

### 2. Cache Configuration
```env
# Use Redis for caching
CACHE_STORE=redis
SUBSCRIPTION_CACHE_ENABLED=true
SUBSCRIPTION_CACHE_TTL=300
```

### 3. Database Optimization
```sql
-- Add indexes for faster lookups
CREATE INDEX idx_org_slug ON organizations(org_slug);
CREATE INDEX idx_tenant_db_name ON organizations(tenant_db_name);
CREATE INDEX idx_registration_status ON organizations(registration_status);
```

---

## 🔄 Maintenance

### Weekly Tasks
```bash
# Cleanup orphaned databases
php artisan tenant:cleanup-orphaned --days=7

# Check for failed provisioning
php artisan tinker
>>> Organization::where('registration_status', 'PENDING')
    ->where('created_at', '<', now()->subDays(1))
    ->get();
```

### Monthly Tasks
```bash
# Review logs for patterns
grep "provisioning failed" storage/logs/*.log | wc -l

# Check database sizes
mysql -u root -p -e "
  SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
  FROM information_schema.tables
  WHERE table_schema LIKE 'erp_%'
  GROUP BY table_schema
  ORDER BY SUM(data_length + index_length) DESC;
"
```

---

## 📞 Support

### Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| `VALIDATION_ERROR` | Invalid input | Check request payload |
| `RATE_LIMIT_EXCEEDED` | Too many requests | Wait 1 hour or contact admin |
| `TENANT_DB_NOT_CONFIGURED` | Database not provisioned | Wait for provisioning or retry |
| `TENANT_NOT_FOUND` | Invalid org_slug | Check organization exists |
| `TENANT_SUSPENDED` | Account suspended | Contact billing |

### Log Locations
- Application logs: `storage/logs/laravel.log`
- Queue logs: `storage/logs/queue.log`
- Audit logs: Database `audit_logs` table

### Getting Help
1. Check logs: `tail -f storage/logs/laravel.log`
2. Check queue: `php artisan queue:work --once`
3. Check database: Verify organization and database exist
4. Review documentation: `TENANT_DB_CREATION_REVIEW.md`
