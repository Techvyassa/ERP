# Multi-Tenant Database Provisioning System

A robust, production-ready Laravel-based multi-tenant system with automatic database provisioning, subscription management, and comprehensive error handling.

---

## 🎯 Features

### Core Features
- ✅ **Automatic Tenant Provisioning** - Creates isolated databases for each organization
- ✅ **Idempotent Operations** - Safe to retry without side effects
- ✅ **Automatic Rollback** - Failed provisioning automatically cleans up
- ✅ **Rate Limiting** - Prevents abuse (5 registrations/hour per IP)
- ✅ **Queue-Based Processing** - Non-blocking async provisioning
- ✅ **Comprehensive Logging** - Full audit trail of all operations
- ✅ **Security Hardened** - SQL injection prevention, input validation
- ✅ **Connection Verification** - Ensures database accessibility
- ✅ **Configurable Timeouts** - Prevents hanging operations
- ✅ **Cleanup Tools** - Automated orphaned database cleanup

### Subscription Features
- Trial subscriptions (configurable duration)
- Multiple billing cycles (monthly, quarterly, annual)
- Grace period handling
- Plan upgrades/downgrades
- Module-based access control

### Security Features
- Input validation and sanitization
- SQL injection prevention
- Rate limiting
- Database isolation per tenant
- Configurable access controls

---

## 📁 Project Structure

```
├── app/
│   ├── Console/Commands/
│   │   ├── TenantProvision.php          # Manual provisioning
│   │   ├── CleanupOrphanedDatabases.php # Cleanup tool
│   │   └── ...
│   ├── Contracts/                        # Service interfaces
│   ├── Exceptions/                       # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── OrganizationController.php
│   │   └── Middleware/
│   │       └── ResolveTenant.php         # Tenant resolution
│   ├── Jobs/
│   │   └── ProvisionTenantJob.php        # Async provisioning
│   ├── Models/
│   │   ├── Control/                      # Control DB models
│   │   └── Tenant/                       # Tenant DB models
│   └── Services/
│       ├── TenantProvisioningServiceImpl.php
│       └── DatabaseConnectionRouterService.php
├── config/
│   ├── tenant.php                        # Tenant configuration
│   └── subscription.php                  # Subscription configuration
├── database/
│   └── migrations/
│       ├── control/                      # Control DB migrations
│       └── tenant/                       # Tenant DB migrations
└── docs/
    ├── SETUP_GUIDE.md                    # Setup instructions
    ├── QUICK_REFERENCE.md                # Command reference
    ├── TROUBLESHOOTING.md                # Common issues
    ├── TENANT_DB_CREATION_REVIEW.md      # Code review
    └── FIXES_APPLIED.md                  # Changelog
```

---

## 🚀 Quick Start

### 1. Install and Configure

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Update .env with your database credentials
```

### 2. Setup Database

```bash
# Create control database
mysql -u root -p
CREATE DATABASE ERP_saas_control;
exit

# Run migrations
php artisan migrate --database=control --path=database/migrations/control
```

### 3. Start Services

```bash
# Start application
php artisan serve

# Start queue worker (in separate terminal)
php artisan queue:work
```

### 4. Register Organization

```bash
curl -X POST http://localhost:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_name": "Acme Corp",
    "org_slug": "acme",
    "primary_email": "admin@acme.com",
    "country_code": "US"
  }'
```

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [SETUP_GUIDE.md](SETUP_GUIDE.md) | Complete setup instructions |
| [QUICK_REFERENCE.md](QUICK_REFERENCE.md) | Commands and API reference |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Common issues and solutions |
| [TENANT_DB_CREATION_REVIEW.md](TENANT_DB_CREATION_REVIEW.md) | Code review and analysis |
| [FIXES_APPLIED.md](FIXES_APPLIED.md) | Detailed changelog |

---

## 🔧 Configuration

### Environment Variables

```env
# Tenant Database
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=
TENANT_DB_GRANT_HOST=localhost

# Provisioning
TENANT_PROVISIONING_TIMEOUT=300
TENANT_PROVISIONING_MAX_ATTEMPTS=3

# Trial Subscription
SUBSCRIPTION_TRIAL_DAYS=14
SUBSCRIPTION_TRIAL_PLAN_CODE=TRIAL

# Cache (use 'database' if Redis not available)
CACHE_STORE=database

# Queue
QUEUE_CONNECTION=database
```

---

## 🎮 Commands

### Provisioning

```bash
# Manual provisioning
php artisan tenant:provision {org_slug}

# Migrate all tenants
php artisan tenant:migrate-all

# Seed tenant database
php artisan tenant:seed {org_slug}
```

### Maintenance

```bash
# Cleanup orphaned databases (dry run)
php artisan tenant:cleanup-orphaned --dry-run

# Cleanup databases older than 7 days
php artisan tenant:cleanup-orphaned --days=7

# Check trial expirations
php artisan subscription:check-trials

# Process renewals
php artisan subscription:process-renewals
```

### Queue Management

```bash
# Process one job
php artisan queue:work --once

# Keep processing
php artisan queue:work

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 🔌 API Endpoints

### Public Endpoints

```
POST   /api/v1/organizations/register    # Register new organization
POST   /api/v1/auth/login                # User login
POST   /api/v1/auth/refresh              # Refresh token
POST   /api/v1/webhooks/razorpay         # Razorpay webhook
POST   /api/v1/webhooks/stripe           # Stripe webhook
```

### Protected Endpoints (Require Authentication)

```
GET    /api/v1/subscriptions/current     # Current subscription
GET    /api/v1/subscriptions/plans       # Available plans
POST   /api/v1/subscriptions/upgrade     # Upgrade plan
POST   /api/v1/subscriptions/cancel      # Cancel subscription

GET    /api/v1/users                     # List users
POST   /api/v1/users                     # Create user
GET    /api/v1/users/{id}                # Get user
PUT    /api/v1/users/{id}                # Update user
DELETE /api/v1/users/{id}                # Delete user

GET    /api/v1/roles                     # List roles
POST   /api/v1/roles                     # Create role
GET    /api/v1/roles/{id}/permissions    # Get role permissions
PUT    /api/v1/roles/{id}/permissions    # Update permissions
```

---

## 🏗️ Architecture

### Database Structure

**Control Database (`ERP_saas_control`)**
- Stores organizations, subscriptions, payments
- Single database for all tenants' metadata

**Tenant Databases (`erp_{org_slug}`)**
- One database per organization
- Complete data isolation
- Independent schema per tenant

### Provisioning Flow

```
1. Organization Registration
   ↓
2. Queue Provisioning Job
   ↓
3. Create Database (erp_{slug})
   ↓
4. Grant Permissions
   ↓
5. Run Migrations
   ↓
6. Seed Default Data (roles, permissions)
   ↓
7. Create Admin User
   ↓
8. Create Trial Subscription
   ↓
9. Send Welcome Email
   ↓
10. Update Status to ACTIVE
```

### Request Flow

```
1. HTTP Request
   ↓
2. ResolveTenant Middleware
   ↓
3. Switch to Tenant Database
   ↓
4. Process Request
   ↓
5. Return Response
```

---

## 🔒 Security

### Implemented Security Measures

- ✅ SQL injection prevention (input validation)
- ✅ Rate limiting (5 requests/hour per IP)
- ✅ Database isolation per tenant
- ✅ Prepared statements for all queries
- ✅ Input sanitization and validation
- ✅ Secure password hashing (bcrypt)
- ✅ JWT token authentication
- ✅ RBAC (Role-Based Access Control)
- ✅ Audit logging for all operations

### Security Best Practices

1. Use dedicated database user (not root)
2. Set `TENANT_DB_GRANT_HOST=localhost` in production
3. Enable HTTPS in production
4. Keep dependencies updated
5. Regular security audits
6. Implement proper firewall rules
7. Regular database backups

---

## 🧪 Testing

### Manual Testing

```bash
# Test organization registration
curl -X POST http://localhost:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{"org_name":"Test","org_slug":"test","primary_email":"test@test.com","country_code":"US"}'

# Test idempotency (run twice)
php artisan tenant:provision test
php artisan tenant:provision test  # Should succeed

# Test rate limiting (run 6 times)
for i in {1..6}; do
  curl -X POST http://localhost:8000/api/v1/organizations/register -d {...}
done
```

### Automated Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter TenantProvisioningTest
```

---

## 📊 Monitoring

### Key Metrics to Monitor

- Provisioning success/failure rate
- Average provisioning time
- Queue job processing time
- Database connection pool usage
- API response times
- Error rates

### Log Locations

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue logs
tail -f storage/logs/queue.log

# Filter by provisioning
tail -f storage/logs/laravel.log | grep "provisioning"
```

---

## 🐛 Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Redis connection error | Use `CACHE_STORE=database` in `.env` |
| Queue not processing | Run `php artisan queue:work` |
| Database not found | Run `php artisan tenant:provision {slug}` |
| Permission denied | Grant database permissions manually |
| Rate limit exceeded | Wait 1 hour or clear cache |

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for detailed solutions.

---

## 🚀 Production Deployment

### Pre-Deployment Checklist

- [ ] Update `.env` for production
- [ ] Set `APP_DEBUG=false`
- [ ] Use Redis for cache and queue
- [ ] Setup queue workers (Supervisor/systemd)
- [ ] Setup scheduled tasks (cron)
- [ ] Configure backups
- [ ] Setup monitoring
- [ ] Enable HTTPS
- [ ] Configure firewall
- [ ] Test provisioning flow

### Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## 📈 Performance Tips

1. **Use Redis** for cache and queue (better than database)
2. **Add indexes** to frequently queried columns
3. **Enable query caching** in MySQL
4. **Use connection pooling** for database connections
5. **Optimize migrations** (avoid heavy operations)
6. **Monitor slow queries** and optimize them
7. **Use CDN** for static assets
8. **Enable OPcache** for PHP

---

## 🤝 Contributing

### Code Style

- Follow PSR-12 coding standards
- Use type hints for all parameters and return types
- Write comprehensive PHPDoc comments
- Keep methods focused and single-purpose

### Pull Request Process

1. Create feature branch
2. Write tests for new features
3. Update documentation
4. Submit pull request
5. Wait for code review

---

## 📝 License

[Your License Here]

---

## 👥 Support

- **Documentation**: See docs folder
- **Issues**: Create GitHub issue
- **Email**: support@example.com

---

## 🎉 Acknowledgments

Built with:
- Laravel 11.x
- MySQL 8.x
- Redis (optional)
- Predis for Redis client

---

## 📅 Changelog

See [FIXES_APPLIED.md](FIXES_APPLIED.md) for detailed changelog.

### Latest Updates

- ✅ Fixed race condition in organization registration
- ✅ Added idempotent provisioning operations
- ✅ Implemented automatic rollback on failure
- ✅ Added SQL injection prevention
- ✅ Implemented rate limiting
- ✅ Added cleanup command for orphaned databases
- ✅ Fixed Redis connection issue (fallback to database cache)

---

## 🔮 Roadmap

- [ ] Add database quotas per tenant
- [ ] Implement connection pooling
- [ ] Add provisioning progress tracking
- [ ] Create admin dashboard
- [ ] Add real-time monitoring
- [ ] Implement automated backups
- [ ] Add multi-region support
- [ ] Create API documentation (Swagger/OpenAPI)
