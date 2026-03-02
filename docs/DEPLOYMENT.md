# Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying the Laravel Multi-Tenant ERP Foundation system to production environments.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Environment Setup](#environment-setup)
3. [Database Setup](#database-setup)
4. [Application Configuration](#application-configuration)
5. [Queue Worker Setup](#queue-worker-setup)
6. [Scheduled Jobs Configuration](#scheduled-jobs-configuration)
7. [Redis Setup](#redis-setup)
8. [Payment Gateway Configuration](#payment-gateway-configuration)
9. [Web Server Configuration](#web-server-configuration)
10. [SSL/TLS Configuration](#ssltls-configuration)
11. [Monitoring and Logging](#monitoring-and-logging)
12. [Backup Strategy](#backup-strategy)
13. [Deployment Checklist](#deployment-checklist)

---

## System Requirements

### Server Requirements

- **Operating System**: Ubuntu 20.04 LTS or higher (recommended)
- **PHP**: 8.1 or higher
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Cache**: Redis 6.0+
- **Process Manager**: Supervisor 4.0+

### PHP Extensions

Required PHP extensions:
```bash
php8.1-cli
php8.1-fpm
php8.1-mysql
php8.1-redis
php8.1-mbstring
php8.1-xml
php8.1-bcmath
php8.1-curl
php8.1-zip
php8.1-gd
php8.1-intl
```

### Hardware Requirements

**Minimum** (Development/Testing):
- CPU: 2 cores
- RAM: 4 GB
- Storage: 20 GB SSD

**Recommended** (Production):
- CPU: 4+ cores
- RAM: 8+ GB
- Storage: 100+ GB SSD
- Separate database server with 8+ GB RAM

---

## Environment Setup

### 1. Install System Dependencies

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and extensions
sudo apt install -y php8.1-cli php8.1-fpm php8.1-mysql php8.1-redis \
  php8.1-mbstring php8.1-xml php8.1-bcmath php8.1-curl php8.1-zip \
  php8.1-gd php8.1-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor
sudo apt install -y supervisor

# Install Redis
sudo apt install -y redis-server
# Install Nginx
sudo apt install -y nginx
```

### 2. Clone Repository

```bash
# Create application directory
sudo mkdir -p /var/www/erp-api
sudo chown -R $USER:$USER /var/www/erp-api

# Clone repository
cd /var/www/erp-api
git clone <repository-url> .

# Install dependencies
composer install --no-dev --optimize-autoloader
```

### 3. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/erp-api

# Set directory permissions
sudo find /var/www/erp-api -type d -exec chmod 755 {} \;
sudo find /var/www/erp-api -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/erp-api/storage
sudo chmod -R 775 /var/www/erp-api/bootstrap/cache
```

---

## Database Setup

### 1. Install MySQL

```bash
# Install MySQL 8.0
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation
```

### 2. Create Control Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE ERP_saas_control CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erp_control_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON ERP_saas_control.* TO 'erp_control_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configure MySQL for Multi-Tenant

Edit MySQL configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
# Connection settings
max_connections = 500
max_user_connections = 100

# Buffer pool settings (adjust based on RAM)
innodb_buffer_pool_size = 4G
innodb_log_file_size = 512M

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Performance
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Binary logging (for replication/backup)
log_bin = /var/log/mysql/mysql-bin.log
binlog_expire_logs_seconds = 604800
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### 4. Run Control Database Migrations

```bash
cd /var/www/erp-api

# Run migrations
php artisan migrate --database=control --path=database/migrations/control

# Seed subscription plans
php artisan db:seed --class=SubscriptionPlanSeeder
```

### 5. Tenant Database Configuration

Tenant databases are created automatically during provisioning. Ensure the database user has CREATE DATABASE privileges:

```sql
GRANT CREATE ON *.* TO 'erp_control_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Application Configuration

### 1. Environment Configuration

Create `.env` file from template:

```bash
cd /var/www/erp-api
cp .env.example .env
```

### 2. Configure Environment Variables

Edit `.env` file:

```env
# Application
APP_NAME="ERP API"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://api.your-domain.com

# Control Database
DB_CONNECTION=control
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ERP_saas_control
DB_USERNAME=erp_control_user
DB_PASSWORD=secure_password_here

# Tenant Database Configuration
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=erp_control_user
TENANT_DB_PASSWORD=secure_password_here

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# JWT Configuration
JWT_SECRET=GENERATE_WITH_php_artisan_jwt:secret
JWT_TTL=1440
JWT_REFRESH_TTL=43200

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateways
RAZORPAY_KEY_ID=your_razorpay_key
RAZORPAY_KEY_SECRET=your_razorpay_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret

STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DEPRECATIONS_CHANNEL=null
LOG_SLACK_WEBHOOK_URL=

# Timezone and Locale
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

### 3. Generate Application Key

```bash
php artisan key:generate
php artisan jwt:secret
```

### 4. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## Queue Worker Setup

### 1. Configure Supervisor

Create supervisor configuration file:

```bash
sudo nano /etc/supervisor/conf.d/erp-worker.conf
```

Add configuration:

```ini
[program:erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/erp-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/erp-api/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Start Queue Workers

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start erp-worker:*

# Check status
sudo supervisorctl status
```

### 3. Monitor Queue Workers

```bash
# View worker logs
tail -f /var/www/erp-api/storage/logs/worker.log

# Restart workers after deployment
sudo supervisorctl restart erp-worker:*
```

---

## Scheduled Jobs Configuration

### 1. Configure Cron

Add Laravel scheduler to crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:

```cron
* * * * * cd /var/www/erp-api && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Scheduled Jobs

The following jobs run automatically:

| Job | Schedule | Description |
|-----|----------|-------------|
| `subscription:check-trials` | Daily at 00:00 | Check and expire trial subscriptions |
| `subscription:process-renewals` | Daily at 01:00 | Process subscription renewals |
| `subscription:enforce-grace` | Daily at 02:00 | Enforce grace period for past due subscriptions |
| `tokens:cleanup` | Daily at 03:00 | Remove expired refresh tokens |
| `logs:cleanup` | Weekly (Sunday 04:00) | Remove logs older than 90 days |

### 3. Manual Job Execution

```bash
# Check trial expirations
php artisan subscription:check-trials

# Process renewals
php artisan subscription:process-renewals

# Enforce grace period
php artisan subscription:enforce-grace

# Cleanup tokens
php artisan tokens:cleanup

# Cleanup logs
php artisan logs:cleanup
```

---

## Redis Setup

### 1. Configure Redis

Edit Redis configuration (`/etc/redis/redis.conf`):

```conf
# Bind to localhost only (or specific IP)
bind 127.0.0.1

# Set password (recommended)
requirepass your_redis_password

# Memory management
maxmemory 2gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# AOF persistence
appendonly yes
appendfsync everysec
```

### 2. Restart Redis

```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 3. Test Redis Connection

```bash
redis-cli -a your_redis_password ping
# Should return: PONG
```

### 4. Update .env with Redis Password

```env
REDIS_PASSWORD=your_redis_password
```

---

## Payment Gateway Configuration

### 1. Razorpay Setup

1. Create account at https://razorpay.com
2. Generate API keys from Dashboard → Settings → API Keys
3. Configure webhook URL: `https://api.your-domain.com/api/v1/webhooks/razorpay`
4. Add webhook secret to `.env`

**Webhook Events to Enable**:
- `payment.authorized`
- `payment.captured`
- `payment.failed`
- `subscription.charged`

### 2. Stripe Setup

1. Create account at https://stripe.com
2. Generate API keys from Dashboard → Developers → API keys
3. Configure webhook endpoint: `https://api.your-domain.com/api/v1/webhooks/stripe`
4. Add webhook signing secret to `.env`

**Webhook Events to Enable**:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

### 3. Test Payment Integration

```bash
# Test Razorpay configuration
php artisan tinker
>>> app(\App\Contracts\PaymentGateway::class)->verifyConfiguration();

# Test webhook signature verification
curl -X POST https://api.your-domain.com/api/v1/webhooks/razorpay \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

---

## Web Server Configuration

### Nginx Configuration

Create Nginx site configuration:

```bash
sudo nano /etc/nginx/sites-available/erp-api
```

Add configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.your-domain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.your-domain.com;
    
    root /var/www/erp-api/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Logging
    access_log /var/log/nginx/erp-api-access.log;
    error_log /var/log/nginx/erp-api-error.log;
    
    # Client body size (for file uploads)
    client_max_body_size 20M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for long-running requests
        fastcgi_read_timeout 300;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/erp-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## SSL/TLS Configuration

### Using Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d api.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Certificate Auto-Renewal

Certbot automatically adds a cron job. Verify:

```bash
sudo systemctl status certbot.timer
```

---

## Monitoring and Logging

### 1. Application Logs

Logs are stored in `/var/www/erp-api/storage/logs/`

**Log Files**:
- `laravel.log` - Application logs
- `audit.log` - Audit trail logs
- `worker.log` - Queue worker logs

### 2. Log Rotation

Create logrotate configuration:

```bash
sudo nano /etc/logrotate.d/erp-api
```

Add configuration:

```
/var/www/erp-api/storage/logs/*.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        php /var/www/erp-api/artisan cache:clear > /dev/null 2>&1
    endscript
}
```

### 3. Monitoring Tools

**Recommended monitoring solutions**:
- **Application Performance**: New Relic, Datadog
- **Server Monitoring**: Prometheus + Grafana
- **Uptime Monitoring**: UptimeRobot, Pingdom
- **Error Tracking**: Sentry, Bugsnag

### 4. Health Check Endpoint

Monitor API health:

```bash
curl https://api.your-domain.com/api/v1/health
```

---

## Backup Strategy

### 1. Database Backup

Create backup script (`/usr/local/bin/backup-erp-databases.sh`):

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/erp"
DATE=$(date +%Y%m%d_%H%M%S)
MYSQL_USER="erp_control_user"
MYSQL_PASSWORD="secure_password_here"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup Control Database
mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD ERP_saas_control | gzip > $BACKUP_DIR/control_$DATE.sql.gz

# Backup all tenant databases
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "SHOW DATABASES LIKE 'erp_%'" | grep -v Database | while read db; do
    mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD $db | gzip > $BACKUP_DIR/${db}_$DATE.sql.gz
done

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/backup-erp-databases.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-erp-databases.sh >> /var/log/erp-backup.log 2>&1
```

### 2. Application Backup

```bash
# Backup application files
tar -czf /var/backups/erp/app_$(date +%Y%m%d).tar.gz \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  /var/www/erp-api
```

### 3. Offsite Backup

Use tools like `rsync` or cloud storage (AWS S3, Google Cloud Storage):

```bash
# Example: Sync to S3
aws s3 sync /var/backups/erp s3://your-backup-bucket/erp-backups/
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Server meets minimum requirements
- [ ] MySQL 8.0+ installed and configured
- [ ] Redis installed and configured
- [ ] PHP 8.1+ with required extensions installed
- [ ] Nginx/Apache configured
- [ ] SSL certificate obtained and configured
- [ ] Firewall configured (ports 80, 443 open)
- [ ] DNS records configured

### Application Setup

- [ ] Repository cloned
- [ ] Dependencies installed (`composer install --no-dev`)
- [ ] `.env` file configured with production values
- [ ] Application key generated (`php artisan key:generate`)
- [ ] JWT secret generated (`php artisan jwt:secret`)
- [ ] File permissions set correctly
- [ ] Control database created
- [ ] Control database migrations run
- [ ] Subscription plans seeded

### Services Configuration

- [ ] Queue workers configured in Supervisor
- [ ] Queue workers started and running
- [ ] Cron job configured for scheduler
- [ ] Redis connection tested
- [ ] Payment gateway webhooks configured
- [ ] Email service configured and tested

### Optimization

- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Autoloader optimized (`composer dump-autoload --optimize`)
- [ ] OPcache enabled in PHP

### Security

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords set
- [ ] Redis password configured
- [ ] JWT secrets are unique and secure
- [ ] Payment gateway secrets configured
- [ ] HTTPS enforced
- [ ] Security headers configured
- [ ] File upload limits set
- [ ] Rate limiting tested

### Monitoring

- [ ] Application logs configured
- [ ] Log rotation configured
- [ ] Health check endpoint tested
- [ ] Monitoring tools configured
- [ ] Error tracking configured
- [ ] Backup script configured and tested
- [ ] Backup restoration tested

### Testing

- [ ] Health check endpoint returns 200
- [ ] Organization registration works
- [ ] Tenant provisioning works
- [ ] Authentication flow works
- [ ] API endpoints respond correctly
- [ ] Payment webhooks process correctly
- [ ] Queue jobs process correctly
- [ ] Scheduled jobs run correctly

### Post-Deployment

- [ ] Monitor application logs for errors
- [ ] Monitor queue worker status
- [ ] Monitor database performance
- [ ] Monitor Redis memory usage
- [ ] Verify backup completion
- [ ] Test disaster recovery procedure
- [ ] Document any environment-specific configurations

---

## Troubleshooting

### Common Issues

**Queue workers not processing jobs**:
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart erp-worker:*

# Check worker logs
tail -f /var/www/erp-api/storage/logs/worker.log
```

**Database connection errors**:
```bash
# Test database connection
php artisan tinker
>>> DB::connection('control')->getPdo();

# Check MySQL status
sudo systemctl status mysql
```

**Redis connection errors**:
```bash
# Test Redis connection
redis-cli -a your_redis_password ping

# Check Redis status
sudo systemctl status redis-server
```

**Permission errors**:
```bash
# Reset permissions
sudo chown -R www-data:www-data /var/www/erp-api
sudo chmod -R 775 /var/www/erp-api/storage
sudo chmod -R 775 /var/www/erp-api/bootstrap/cache
```

---

## Support

For deployment support, contact: devops@your-domain.com
