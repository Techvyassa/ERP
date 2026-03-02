# Task 20: Artisan Commands for Administration

## Overview

Implemented comprehensive Artisan commands for administrative tasks in the Laravel Multi-Tenant ERP system. These commands provide CLI tools for managing tenants, subscriptions, and system maintenance.

## Implementation Summary

### 20.1 Tenant Management Commands ✓

Created four commands for tenant database management:

#### 1. `tenant:provision {org_slug}`
- **Purpose**: Manually provision a tenant database for an organization
- **Features**:
  - Validates organization exists and status
  - Checks if already provisioned with confirmation prompt
  - Executes full provisioning workflow via TenantProvisioningService
  - Displays detailed progress and completion steps
  - Handles errors gracefully with rollback support
- **Usage**: `php artisan tenant:provision acme-corp`

#### 2. `tenant:migrate {org_slug} [--fresh] [--seed]`
- **Purpose**: Run migrations on a specific tenant database
- **Features**:
  - Validates organization and tenant database existence
  - Switches to tenant database connection
  - Supports fresh migrations with confirmation prompt
  - Optional database seeding after migration
  - Displays migration output
- **Usage**: 
  - `php artisan tenant:migrate acme-corp`
  - `php artisan tenant:migrate acme-corp --fresh --seed`

#### 3. `tenant:migrate-all [--fresh]`
- **Purpose**: Run migrations on all active tenant databases
- **Features**:
  - Finds all active organizations with tenant databases
  - Batch processes migrations with progress tracking
  - Supports fresh migrations with confirmation
  - Provides detailed summary with success/failure counts
  - Lists failed tenants with error messages
- **Usage**: 
  - `php artisan tenant:migrate-all`
  - `php artisan tenant:migrate-all --fresh`

#### 4. `tenant:seed {org_slug} [--class=]`
- **Purpose**: Seed a specific tenant database
- **Features**:
  - Validates organization and tenant database
  - Switches to tenant database connection
  - Supports specific seeder class or default seeders
  - Runs DefaultRoleSeeder and DefaultRolePermissionSeeder by default
- **Usage**: 
  - `php artisan tenant:seed acme-corp`
  - `php artisan tenant:seed acme-corp --class=CustomSeeder`

### 20.2 Subscription Management Commands ✓

Created three commands for subscription lifecycle management:

#### 1. `subscription:check-trials`
- **Purpose**: Check and expire trial subscriptions that have reached their end date
- **Features**:
  - Finds all TRIAL subscriptions with expired trial_end_date
  - Updates subscription status to EXPIRED
  - Logs status changes via AuditLogger
  - Provides detailed summary of processed trials
  - Safe to run via cron jobs (idempotent)
- **Usage**: `php artisan subscription:check-trials`
- **Scheduling**: Recommended to run daily

#### 2. `subscription:process-renewals`
- **Purpose**: Process subscription renewals for subscriptions due for billing
- **Features**:
  - Finds all ACTIVE subscriptions with due next_billing_date
  - Processes renewal via SubscriptionManagementService
  - Handles payment success (extends period) and failure (sets PAST_DUE)
  - Displays renewal results with payment status
  - Provides comprehensive summary with failures
- **Usage**: `php artisan subscription:process-renewals`
- **Scheduling**: Recommended to run daily

#### 3. `subscription:enforce-grace`
- **Purpose**: Enforce grace period by suspending organizations with expired grace periods
- **Features**:
  - Finds all PAST_DUE subscriptions with expired grace_period_until
  - Updates subscription status to EXPIRED
  - Suspends organization (sets registration_status to SUSPENDED)
  - Logs all status changes
  - Provides summary of suspended organizations
- **Usage**: `php artisan subscription:enforce-grace`
- **Scheduling**: Recommended to run daily

### 20.3 Maintenance Commands ✓

Created three commands for system maintenance:

#### 1. `cache:clear-permissions [--user=]`
- **Purpose**: Clear RBAC permission cache for all users or a specific user
- **Features**:
  - Clears specific user's permission cache when --user provided
  - Clears all RBAC permission caches when no user specified
  - Supports cache tags (Redis) or pattern-based clearing
  - Confirmation prompt for clearing all caches
  - Logs cache clearing operations
- **Usage**: 
  - `php artisan cache:clear-permissions --user=123`
  - `php artisan cache:clear-permissions`

#### 2. `logs:cleanup [--days=90]`
- **Purpose**: Remove old log files older than specified days
- **Features**:
  - Configurable retention period (default: 90 days)
  - Skips current log file (laravel.log)
  - Displays file details before deletion (modified date, size)
  - Calculates and reports space freed
  - Provides comprehensive summary
  - Logs cleanup operations
- **Usage**: 
  - `php artisan logs:cleanup`
  - `php artisan logs:cleanup --days=30`
- **Scheduling**: Recommended to run weekly or monthly

#### 3. `tokens:cleanup`
- **Purpose**: Remove expired refresh tokens from cache
- **Features**:
  - Finds all refresh token keys in Redis
  - Checks TTL for each token
  - Removes tokens with no expiry (orphaned)
  - Removes tokens expiring within 60 seconds
  - Handles Redis unavailability gracefully
  - Provides cleanup summary
- **Usage**: `php artisan tokens:cleanup`
- **Scheduling**: Recommended to run daily
- **Note**: Redis automatically removes expired keys, so this is supplementary

## Command Registration

All commands are automatically registered by Laravel's command discovery mechanism. They are located in:
- `app/Console/Commands/TenantProvision.php`
- `app/Console/Commands/TenantMigrate.php`
- `app/Console/Commands/TenantMigrateAll.php`
- `app/Console/Commands/TenantSeed.php`
- `app/Console/Commands/SubscriptionCheckTrials.php`
- `app/Console/Commands/SubscriptionProcessRenewals.php`
- `app/Console/Commands/SubscriptionEnforceGrace.php`
- `app/Console/Commands/CacheClearPermissions.php`
- `app/Console/Commands/LogsCleanup.php`
- `app/Console/Commands/TokensCleanup.php`

## Recommended Scheduling

Add to `app/Console/Kernel.php` or `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Daily subscription management
Schedule::command('subscription:check-trials')->daily();
Schedule::command('subscription:process-renewals')->daily();
Schedule::command('subscription:enforce-grace')->daily();

// Daily token cleanup
Schedule::command('tokens:cleanup')->daily();

// Weekly log cleanup
Schedule::command('logs:cleanup')->weekly();
```

## Error Handling

All commands implement:
- Comprehensive error handling with try-catch blocks
- Graceful failure with descriptive error messages
- Proper exit codes (SUCCESS = 0, FAILURE = 1)
- Detailed logging of operations and errors
- Transaction support where applicable
- Rollback mechanisms for critical operations

## Output Features

All commands provide:
- Clear progress indicators
- Detailed operation logs
- Success/failure status with checkmarks (✓/✗)
- Comprehensive summaries
- Color-coded output (info, warn, error)
- Confirmation prompts for destructive operations

## Testing

Commands can be tested using:
```bash
# Test help output
php artisan tenant:provision --help

# Test with dry-run or non-destructive operations
php artisan subscription:check-trials

# Test with specific tenant
php artisan tenant:migrate test-org
```

## Requirements Satisfied

- **Requirement 15.6**: Tenant provisioning and migration commands
- **Requirement 15.7**: Tenant migration commands for specific and all tenants
- **Requirement 15.8**: Tenant seeding commands
- **Requirement 6.3**: Trial subscription expiration checking
- **Requirement 6.6**: Subscription renewal processing
- **Requirement 6.10**: Grace period enforcement
- **Requirement 9.9**: Permission cache clearing

## Notes

1. All tenant commands properly switch database connections using DatabaseConnectionRouter
2. Subscription commands are safe to run via cron jobs (idempotent)
3. Maintenance commands include safety checks and confirmations
4. All commands log their operations for audit trail
5. Commands follow Laravel best practices and conventions
6. Exit codes are properly set for scripting and monitoring
7. Output is formatted for both human readability and log parsing

## Future Enhancements

Potential improvements for future iterations:
1. Add `--dry-run` option to preview changes without executing
2. Add `--force` option to skip confirmation prompts
3. Add progress bars for long-running operations
4. Add email notifications for critical operations
5. Add Slack/webhook notifications for failures
6. Add metrics collection for monitoring
7. Add parallel processing for tenant:migrate-all
8. Add backup creation before destructive operations
