# Developer Onboarding Guide

## Welcome

Welcome to the Laravel Multi-Tenant ERP Foundation project! This guide will help you understand the architecture, set up your development environment, and start contributing effectively.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture Overview](#architecture-overview)
3. [Project Structure](#project-structure)
4. [Development Environment Setup](#development-environment-setup)
5. [Multi-Tenant Architecture](#multi-tenant-architecture)
6. [Middleware Stack Flow](#middleware-stack-flow)
7. [Database Architecture](#database-architecture)
8. [Service Layer](#service-layer)
9. [Testing Strategy](#testing-strategy)
10. [Common Development Tasks](#common-development-tasks)
11. [Coding Standards](#coding-standards)
12. [Git Workflow](#git-workflow)
13. [Troubleshooting](#troubleshooting)

---

## Project Overview

### What is this project?

The Laravel Multi-Tenant ERP Foundation is a SaaS platform that provides a foundation for building multi-tenant ERP systems. It implements:

- **Complete tenant isolation** using database-per-tenant architecture
- **Subscription-based access control** with module-level entitlements
- **Role-based access control (RBAC)** for fine-grained permissions
- **RESTful APIs** designed for mobile app consumption
- **Automated tenant provisioning** with zero-touch setup
- **Payment processing** integration with Razorpay and Stripe

### Key Features

- Two-database architecture (Control DB + Tenant DBs)
- JWT-based authentication
- Dynamic database connection routing
- Subscription lifecycle management
- Payment processing and billing
- Feature flags and overrides
- Comprehensive audit logging
- API rate limiting

---

## Architecture Overview

### High-Level Architecture

```
Mobile App
    ↓
API Gateway (Laravel)
    ↓
Middleware Stack (Auth → Tenant → Subscription → RBAC → Rate Limit)
    ↓
Controllers
    ↓
Service Layer
    ↓
Database Router
    ↓
Control DB ← → Tenant DBs (erp_*)
```

### Core Concepts

**Control Database**: Centralized database managing all tenants, subscriptions, billing, and feature controls.

**Tenant Database**: Isolated database per organization containing ERP operational data.

**Tenant Context**: Runtime identification of which organization is making a request (via `org_slug`).

**Subscription Gate**: Middleware that validates active subscription and module access before allowing API requests.

**RBAC System**: Role-based permissions at module level (can_view, can_create, can_edit, can_approve, can_delete).

---

## Project Structure

```
material-management/
├── app/
│   ├── Console/
│   │   └── Commands/          # Artisan commands
│   ├── Contracts/             # Service interfaces
│   ├── Exceptions/            # Custom exceptions
│   ├── Helpers/               # Helper classes
│   ├── Http/
│   │   ├── Controllers/       # API controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Jobs/                  # Queue jobs
│   ├── Models/
│   │   ├── Control/           # Control DB models
│   │   └── Tenant/            # Tenant DB models
│   └── Services/              # Business logic services
├── config/                    # Configuration files
│   ├── database.php           # Database connections
│   ├── tenant.php             # Tenant configuration
│   ├── subscription.php       # Subscription settings
│   └── payment.php            # Payment gateway config
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/
│   │   ├── control/           # Control DB migrations
│   │   └── tenant/            # Tenant DB migrations
│   └── seeders/               # Database seeders
├── docs/                      # Documentation
├── routes/
│   ├── api.php                # API routes
│   └── console.php            # Console routes
├── storage/
│   └── logs/                  # Application logs
└── tests/                     # Test suites
    ├── Feature/               # Feature tests
    ├── Unit/                  # Unit tests
    ├── Property/              # Property-based tests
    ├── Traits/                # Test traits
    └── Helpers/               # Test helpers
```

### Key Directories

**app/Contracts/**: Interface definitions for services (dependency injection).

**app/Services/**: Implementation of business logic (TenantProvisioning, SubscriptionManagement, etc.).

**app/Models/Control/**: Eloquent models for Control Database tables.

**app/Models/Tenant/**: Eloquent models for Tenant Database tables.

**app/Http/Middleware/**: Custom middleware for authentication, tenant resolution, subscription validation, RBAC.

**database/migrations/control/**: Schema migrations for Control Database.

**database/migrations/tenant/**: Schema migrations for Tenant Databases.

---

## Development Environment Setup

### Prerequisites

- PHP 8.1+
- Composer
- MySQL 8.0+
- Redis
- Git

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd material-management
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Environment Configuration

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_NAME="ERP API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Control Database
DB_CONNECTION=control
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ERP_saas_control
DB_USERNAME=root
DB_PASSWORD=

# Tenant Database
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# JWT
JWT_TTL=1440
JWT_REFRESH_TTL=43200
```

### Step 4: Generate Keys

```bash
php artisan key:generate
php artisan jwt:secret
```

### Step 5: Create Databases

```bash
# Create Control Database
mysql -u root -p -e "CREATE DATABASE ERP_saas_control CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 6: Run Migrations

```bash
# Control Database migrations
php artisan migrate --database=control --path=database/migrations/control

# Seed subscription plans
php artisan db:seed --class=SubscriptionPlanSeeder
```

### Step 7: Start Development Server

```bash
php artisan serve
```

### Step 8: Start Queue Worker

```bash
php artisan queue:work
```

### Step 9: Test Setup

```bash
curl http://localhost:8000/api/v1/health
```

---

## Multi-Tenant Architecture

### Database-Per-Tenant Pattern

Each organization gets its own isolated MySQL database:

- **Control DB**: `ERP_saas_control` (one database for all tenants)
- **Tenant DBs**: `erp_{org_slug}` (one database per tenant)

### Tenant Lifecycle

1. **Registration**: Organization registers via `/api/v1/organizations/register`
2. **Provisioning**: System creates tenant database, runs migrations, seeds data
3. **Activation**: Organization status changes to ACTIVE, trial subscription created
4. **Operation**: Tenant uses system with subscription-based access
5. **Suspension**: If payment fails, tenant is suspended after grace period
6. **Termination**: Tenant account permanently closed

### Database Connection Routing

The `DatabaseConnectionRouter` service dynamically switches connections:

```php
// Switch to tenant database
$router->switchToTenant('erp_acme');

// Query tenant data
$users = User::all();

// Switch back to control database
$router->switchToControl();

// Query control data
$org = Organization::find($orgId);
```

### Tenant Context Resolution

Every authenticated request includes `X-Tenant-Slug` header:

```
GET /api/v1/users
Authorization: Bearer {token}
X-Tenant-Slug: acme
```

The `ResolveTenant` middleware:
1. Extracts `org_slug` from header
2. Queries Control DB for organization
3. Validates organization status
4. Resolves `tenant_db_name`
5. Stores in request context

---

## Middleware Stack Flow

Every protected API request flows through this middleware stack:

### 1. ValidateJWT

**Purpose**: Authenticate user via JWT token

**Actions**:
- Validates JWT signature and expiration
- Extracts `user_id` and `org_id` from token claims
- Returns 401 if invalid

**Code Location**: `app/Http/Middleware/ValidateJWT.php`

### 2. ResolveTenant

**Purpose**: Resolve tenant context and validate organization status

**Actions**:
- Extracts `org_slug` from `X-Tenant-Slug` header
- Queries Control DB `organizations` table
- Validates `registration_status` (must be ACTIVE)
- Resolves `tenant_db_name`
- Returns 400/403/404/410 based on validation

**Code Location**: `app/Http/Middleware/ResolveTenant.php`

### 3. ValidateSubscription

**Purpose**: Validate active subscription and module access

**Actions**:
- Queries Control DB `active_subscriptions` table
- Validates `subscription_status`
- Checks module access in `modules_allowed`
- Enforces grace period for PAST_DUE status
- Caches subscription data for 5 minutes
- Returns 402/403 if invalid

**Code Location**: `app/Http/Middleware/ValidateSubscription.php`

### 4. CheckModulePermission

**Purpose**: Enforce RBAC permissions

**Actions**:
- Switches to Tenant DB connection
- Loads user's `role_permissions` by `role_id`
- Validates permission flags (can_view, can_create, etc.)
- Caches permissions for 15 minutes in Redis
- Logs permission denials
- Returns 403 if denied

**Code Location**: `app/Http/Middleware/CheckModulePermission.php`

### 5. ThrottleRequests

**Purpose**: Enforce API rate limiting

**Actions**:
- Tracks request count per `org_id` per day in Redis
- Compares against `api_rate_limit_day` from subscription
- Checks for feature control override
- Returns 429 with `Retry-After` header when exceeded
- Resets counters at midnight UTC

**Code Location**: `app/Http/Middleware/ThrottleRequests.php`

---

## Database Architecture

### Control Database Schema

**organizations**: Tenant organizations
- Primary key: `org_id`
- Unique: `org_slug`, `primary_email`, `tenant_db_name`
- Status: `registration_status` (PENDING, ACTIVE, SUSPENDED, TERMINATED)

**subscription_plans**: SaaS pricing tiers
- Primary key: `plan_id`
- Unique: `plan_code`
- JSON field: `modules_included` (array of module codes)

**org_subscriptions**: Subscription lifecycle per organization
- Primary key: `subscription_id`
- Foreign keys: `org_id`, `plan_id`
- Status: `subscription_status` (TRIAL, ACTIVE, PAST_DUE, CANCELLED, EXPIRED)

**active_subscriptions**: Fast-lookup denormalized table
- Primary key: `org_id`
- Synced via database triggers
- Contains current subscription data

**payment_records**: Immutable transaction ledger
- Primary key: `payment_id`
- No `updated_at` timestamp (append-only)
- Tax breakdown fields (CGST, SGST, IGST)

**feature_controls**: Per-tenant feature overrides
- Primary key: `feature_control_id`
- Unique: `(org_id, feature_key)`
- Types: BOOLEAN, NUMERIC, TEXT, JSON

### Tenant Database Schema

**department_master**: Hierarchical department structure
- Primary key: `dept_id`
- Self-referencing FK: `parent_dept_id`
- Cycle detection enforced

**role_master**: System roles
- Primary key: `role_id`
- Unique: `role_code`
- Flag: `is_system_role` (prevents deletion)

**role_permissions**: Module-level permissions
- Primary key: `permission_id`
- Unique: `(role_id, module_code)`
- Flags: `can_view`, `can_create`, `can_edit`, `can_approve`, `can_delete`

**users**: Tenant users
- Primary key: `user_id`
- Unique: `email`, `employee_code`
- Foreign keys: `dept_id`, `role_id`
- Password hashed with bcrypt cost 12

---

## Service Layer

### Service Interfaces (Contracts)

All services implement interfaces for dependency injection:

```php
// app/Contracts/TenantProvisioningService.php
interface TenantProvisioningService
{
    public function provisionTenant(int $orgId): ProvisioningResult;
    public function rollbackProvisioning(int $orgId): void;
}

// Usage in controller
public function __construct(
    private TenantProvisioningService $provisioningService
) {}
```

### Key Services

**TenantProvisioningService** (`app/Services/TenantProvisioningServiceImpl.php`):
- Creates tenant database
- Runs migrations
- Seeds master data
- Creates initial admin user

**SubscriptionManagementService** (`app/Services/SubscriptionManagementServiceImpl.php`):
- Manages subscription lifecycle
- Processes renewals
- Handles cancellations

**PaymentProcessingService** (`app/Services/PaymentProcessingServiceImpl.php`):
- Integrates with payment gateways
- Records transactions
- Calculates taxes

**AuthenticationService** (`app/Services/AuthenticationServiceImpl.php`):
- Handles login/logout
- Issues JWT tokens
- Manages refresh tokens

**RBACPermissionService** (`app/Services/RBACPermissionServiceImpl.php`):
- Checks user permissions
- Caches permissions
- Invalidates cache on updates

**FeatureControlService** (`app/Services/FeatureControlServiceImpl.php`):
- Retrieves feature overrides
- Checks effective periods
- Falls back to plan defaults

---

## Testing Strategy

### Test Structure

```
tests/
├── Unit/              # Unit tests for individual classes
├── Feature/           # Feature tests for API endpoints
├── Property/          # Property-based tests for invariants
├── Traits/            # Reusable test traits
└── Helpers/           # Test helper functions
```

### Test Traits

**TenantTestTrait**: Sets up tenant database for testing
```php
use Tests\Traits\TenantTestTrait;

class UserTest extends TestCase
{
    use TenantTestTrait;
    
    public function test_create_user()
    {
        $this->setupTenantDatabase('test_tenant');
        // Test code
    }
}
```

**AuthenticationTestTrait**: Generates JWT tokens for testing
```php
use Tests\Traits\AuthenticationTestTrait;

class ApiTest extends TestCase
{
    use AuthenticationTestTrait;
    
    public function test_protected_endpoint()
    {
        $token = $this->generateTestToken($userId, $orgId);
        $response = $this->withToken($token)->get('/api/v1/users');
    }
}
```

**SubscriptionTestTrait**: Sets up subscription data
```php
use Tests\Traits\SubscriptionTestTrait;

class SubscriptionTest extends TestCase
{
    use SubscriptionTestTrait;
    
    public function test_subscription_gate()
    {
        $this->createActiveSubscription($orgId, $planId);
        // Test code
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/Services/AuthenticationServiceTest.php

# Run with coverage
php artisan test --coverage

# Run property-based tests
php artisan test tests/Property/
```

### Writing Tests

**Unit Test Example**:
```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthenticationServiceImpl;

class AuthenticationServiceTest extends TestCase
{
    public function test_login_with_valid_credentials()
    {
        // Arrange
        $service = app(AuthenticationServiceImpl::class);
        
        // Act
        $result = $service->login('user@example.com', 'password', 'acme');
        
        // Assert
        $this->assertNotNull($result->accessToken);
        $this->assertNotNull($result->refreshToken);
    }
}
```

**Feature Test Example**:
```php
namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\AuthenticationTestTrait;

class UserApiTest extends TestCase
{
    use AuthenticationTestTrait;
    
    public function test_list_users()
    {
        $token = $this->generateTestToken(1, 1);
        
        $response = $this->withToken($token)
            ->withHeader('X-Tenant-Slug', 'test')
            ->get('/api/v1/users');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['users', 'pagination']
            ]);
    }
}
```

---

## Common Development Tasks

### Creating a New Tenant

```bash
# Via Artisan command
php artisan tenant:provision --org-id=1

# Via API
curl -X POST http://localhost:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_slug": "testorg",
    "org_name": "Test Organization",
    "primary_email": "admin@testorg.com",
    "country_code": "US"
  }'
```

### Running Migrations on Tenant

```bash
# Specific tenant
php artisan tenant:migrate --org-slug=acme

# All tenants
php artisan tenant:migrate-all
```

### Seeding Tenant Data

```bash
php artisan tenant:seed --org-slug=acme
```

### Clearing Permission Cache

```bash
php artisan cache:clear-permissions
```

### Testing Authentication Flow

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@acme.com",
    "password": "password",
    "org_slug": "acme"
  }'

# Use returned access_token for subsequent requests
curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer {access_token}" \
  -H "X-Tenant-Slug: acme"
```

### Debugging Database Connections

```php
// In tinker or controller
php artisan tinker

// Check current connection
>>> DB::connection()->getDatabaseName();

// Test Control DB connection
>>> DB::connection('control')->table('organizations')->count();

// Test Tenant DB connection
>>> config(['database.connections.tenant.database' => 'erp_acme']);
>>> DB::purge('tenant');
>>> DB::connection('tenant')->table('users')->count();
```

### Adding a New API Endpoint

1. **Create Controller Method**:
```php
// app/Http/Controllers/ExampleController.php
public function index(Request $request)
{
    // Business logic
    return ResponseFormatter::success($data, 'Success message');
}
```

2. **Add Route**:
```php
// routes/api.php
Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])
    ->prefix('examples')
    ->group(function () {
        Route::get('/', [ExampleController::class, 'index']);
    });
```

3. **Add RBAC Middleware** (if needed):
```php
Route::middleware(['check.module.permission:EXAMPLE_MODULE'])
    ->get('/', [ExampleController::class, 'index']);
```

### Adding a New Service

1. **Create Interface**:
```php
// app/Contracts/ExampleService.php
namespace App\Contracts;

interface ExampleService
{
    public function doSomething(): Result;
}
```

2. **Create Implementation**:
```php
// app/Services/ExampleServiceImpl.php
namespace App\Services;

use App\Contracts\ExampleService;

class ExampleServiceImpl implements ExampleService
{
    public function doSomething(): Result
    {
        // Implementation
    }
}
```

3. **Register in Service Provider**:
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->bind(
        \App\Contracts\ExampleService::class,
        \App\Services\ExampleServiceImpl::class
    );
}
```

---

## Coding Standards

### PSR Standards

Follow PSR-12 coding style standard:
- Use 4 spaces for indentation
- Opening braces on same line for methods
- One blank line after namespace declaration

### Laravel Conventions

- Use Eloquent ORM for database queries
- Use dependency injection for services
- Use form requests for validation
- Use resource classes for API responses
- Use jobs for async processing

### Naming Conventions

**Classes**: PascalCase
```php
class UserController
class TenantProvisioningService
```

**Methods**: camelCase
```php
public function createUser()
public function processPayment()
```

**Variables**: camelCase
```php
$userId = 1;
$orgSlug = 'acme';
```

**Database Tables**: snake_case, plural
```php
organizations
subscription_plans
role_permissions
```

**Database Columns**: snake_case
```php
org_id
created_at
subscription_status
```

### Code Documentation

Use PHPDoc blocks for classes and methods:

```php
/**
 * Provision a new tenant database
 *
 * @param int $orgId Organization ID
 * @return ProvisioningResult
 * @throws ProvisioningException
 */
public function provisionTenant(int $orgId): ProvisioningResult
{
    // Implementation
}
```

---

## Git Workflow

### Branch Strategy

- `main`: Production-ready code
- `develop`: Integration branch for features
- `feature/*`: Feature branches
- `bugfix/*`: Bug fix branches
- `hotfix/*`: Production hotfixes

### Commit Messages

Follow conventional commits format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

**Examples**:
```
feat(auth): add JWT token refresh endpoint

Implement token refresh functionality to allow users to obtain
new access tokens without re-authenticating.

Closes #123
```

```
fix(subscription): correct grace period calculation

Grace period was not accounting for timezone differences.
Now uses UTC consistently.

Fixes #456
```

### Pull Request Process

1. Create feature branch from `develop`
2. Implement changes with tests
3. Run tests locally (`php artisan test`)
4. Push branch and create PR
5. Request code review
6. Address review comments
7. Merge after approval

---

## Troubleshooting

### Common Issues

**Issue**: "Tenant database not found"
```bash
# Solution: Create tenant database
php artisan tenant:provision --org-id=1
```

**Issue**: "Permission denied" errors
```bash
# Solution: Reset permissions
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Issue**: "Class not found" errors
```bash
# Solution: Regenerate autoload files
composer dump-autoload
```

**Issue**: Queue jobs not processing
```bash
# Solution: Restart queue worker
php artisan queue:restart
php artisan queue:work
```

**Issue**: Redis connection refused
```bash
# Solution: Start Redis server
redis-server
# Or on Linux
sudo systemctl start redis-server
```

### Debug Mode

Enable debug mode in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

### Database Queries

Log all database queries:
```php
// In AppServiceProvider boot method
DB::listen(function ($query) {
    Log::info($query->sql, $query->bindings);
});
```

---

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **API Documentation**: `docs/API_DOCUMENTATION.md`
- **Deployment Guide**: `docs/DEPLOYMENT.md`
- **Architecture Diagrams**: `docs/architecture/`

---

## Getting Help

- **Slack Channel**: #erp-dev
- **Email**: dev-team@your-domain.com
- **Issue Tracker**: GitHub Issues

---

## Contributing

We welcome contributions! Please:

1. Read this guide thoroughly
2. Follow coding standards
3. Write tests for new features
4. Update documentation
5. Submit pull requests for review

Thank you for contributing to the Laravel Multi-Tenant ERP Foundation!
