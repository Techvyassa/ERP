# Testing Infrastructure Documentation

## Overview

This document describes the testing infrastructure for the Laravel Multi-Tenant ERP Foundation system. The testing setup includes PHPUnit, Pest PHP, test factories, helper traits, and utilities for comprehensive testing coverage.

## Testing Framework

### PHPUnit + Pest PHP

The project uses both PHPUnit and Pest PHP for testing:

- **PHPUnit**: Traditional class-based testing framework
- **Pest PHP**: Modern, expressive testing framework with a functional API

### Test Suites

Three test suites are configured:

1. **Unit Tests** (`tests/Unit/`): Test individual classes and methods in isolation
2. **Feature Tests** (`tests/Feature/`): Test complete features and API endpoints
3. **Property Tests** (`tests/Property/`): Property-based tests for universal correctness properties

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Property
```

### Run Specific Test File
```bash
php artisan test --filter=ExampleTest
```

### Run with Code Coverage
```bash
php artisan test --coverage
```

### Run with Coverage Report
```bash
php artisan test --coverage --min=80
```

## Test Database Configuration

### In-Memory SQLite

Tests use in-memory SQLite databases for speed and isolation:

- **Control Database**: `control_test` connection (`:memory:`)
- **Tenant Database**: `tenant_test` connection (`:memory:`)

### Configuration

Test database connections are automatically configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="control_test"/>
<env name="DB_CONTROL_CONNECTION" value="sqlite"/>
<env name="DB_CONTROL_DATABASE" value=":memory:"/>
<env name="DB_TENANT_CONNECTION" value="sqlite"/>
<env name="DB_TENANT_DATABASE" value=":memory:"/>
```

## Test Factories

Factories are available for all models to generate test data easily.

### Control Database Factories

Located in `database/factories/Control/`:

- **OrganizationFactory**: Create test organizations
- **SubscriptionPlanFactory**: Create subscription plans
- **OrgSubscriptionFactory**: Create subscriptions
- **PaymentRecordFactory**: Create payment records

### Tenant Database Factories

Located in `database/factories/Tenant/`:

- **DepartmentFactory**: Create departments
- **RoleFactory**: Create roles
- **RolePermissionFactory**: Create role permissions
- **UserFactory**: Create users

### Factory Usage Examples

```php
use App\Models\Control\Organization;
use App\Models\Tenant\User;

// Create a single organization
$org = Organization::factory()->create();

// Create an active organization
$org = Organization::factory()->active()->create();

// Create multiple organizations
$orgs = Organization::factory()->count(5)->create();

// Create with specific attributes
$org = Organization::factory()->create([
    'org_slug' => 'test-org',
    'max_users' => 50,
]);

// Create a user with admin role
$user = User::factory()->admin()->create();

// Create a subscription plan
$plan = SubscriptionPlan::factory()->professional()->create();
```

## Test Traits

### TenantTestTrait

Provides methods for setting up and managing tenant databases in tests.

**Key Methods:**

```php
// Set up a tenant database
$org = $this->setupTenantDatabase();

// Switch to tenant database
$this->switchToTenant($organization);

// Switch to control database
$this->switchToControl();

// Run tenant migrations
$this->runTenantMigrations();

// Seed tenant database
$this->seedTenantDatabase();

// Create a complete test tenant
$org = $this->createTestTenant();

// Assert tenant database has record
$this->assertTenantDatabaseHas('users', ['email' => 'test@example.com']);
```

### AuthenticationTestTrait

Provides methods for JWT token generation and authenticated API requests.

**Key Methods:**

```php
// Generate JWT token for a user
$token = $this->generateToken($user, $organization);

// Create authenticated user
$auth = $this->createAuthenticatedUser($organization);
// Returns: ['user' => User, 'token' => string]

// Create authenticated admin
$auth = $this->createAuthenticatedAdmin($organization);

// Act as authenticated user in API requests
$this->actingAsUser($user, $organization)
    ->get('/api/v1/users');

// Act as admin
$this->actingAsAdmin($organization)
    ->post('/api/v1/users', $data);

// Get auth headers
$headers = $this->getAuthHeaders($token, $orgSlug);

// Assert token is valid
$this->assertValidToken($token);
```

### SubscriptionTestTrait

Provides methods for creating and managing subscriptions in tests.

**Key Methods:**

```php
// Create trial subscription
$subscription = $this->createTrialSubscription($organization);

// Create active subscription
$subscription = $this->createActiveSubscription($organization);

// Create expired subscription
$subscription = $this->createExpiredSubscription($organization);

// Create past due subscription
$subscription = $this->createPastDueSubscription($organization);

// Create cancelled subscription
$subscription = $this->createCancelledSubscription($organization);

// Create complete subscription (subscription + active entry)
$result = $this->createCompleteSubscription($organization, 'ACTIVE');
// Returns: ['subscription' => OrgSubscription, 'active' => ActiveSubscription]

// Assert organization has active subscription
$this->assertHasActiveSubscription($organization);

// Assert subscription allows module
$this->assertSubscriptionAllowsModule($subscription, 'INVOICE');
```

## Test Helpers

The `TestHelpers` class provides utility methods for common testing scenarios.

**Key Methods:**

```php
use Tests\Helpers\TestHelpers;

// Clear Redis data
TestHelpers::clearRedis();

// Clear cache
TestHelpers::clearCache();

// Clear permission cache for user
TestHelpers::clearPermissionCache($userId);

// Manage rate limit counters
TestHelpers::setRateLimitCounter($orgId, 100);
$count = TestHelpers::getRateLimitCounter($orgId);
TestHelpers::clearRateLimitCounter($orgId);

// Generate unique identifiers
$slug = TestHelpers::generateUniqueOrgSlug();
$empCode = TestHelpers::generateUniqueEmployeeCode();
$payRef = TestHelpers::generateUniquePaymentReference();

// Assert JSON response structure
TestHelpers::assertJsonResponseStructure($response, true);
TestHelpers::assertErrorResponse($response, 'TENANT_NOT_FOUND');

// Get system constants
$modules = TestHelpers::getAllModuleCodes();
$statuses = TestHelpers::getAllSubscriptionStatuses();
```

## Writing Tests

### PHPUnit Style Test

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Control\Organization;

class OrganizationTest extends TestCase
{
    public function test_organization_can_be_created(): void
    {
        $org = Organization::factory()->create();
        
        $this->assertDatabaseHas('organizations', [
            'org_id' => $org->org_id,
        ]);
    }
    
    public function test_organization_has_active_scope(): void
    {
        Organization::factory()->active()->create();
        Organization::factory()->suspended()->create();
        
        $activeOrgs = Organization::active()->get();
        
        $this->assertCount(1, $activeOrgs);
    }
}
```

### Pest Style Test

```php
<?php

use App\Models\Control\Organization;

test('organization can be created', function () {
    $org = Organization::factory()->create();
    
    expect($org)->toBeInstanceOf(Organization::class)
        ->and($org->org_id)->toBeInt()
        ->and($org->org_slug)->toBeString();
});

test('organization has active scope', function () {
    Organization::factory()->active()->create();
    Organization::factory()->suspended()->create();
    
    $activeOrgs = Organization::active()->get();
    
    expect($activeOrgs)->toHaveCount(1);
});
```

### Feature Test with Authentication

```php
<?php

use App\Models\Control\Organization;

test('authenticated user can access users endpoint', function () {
    $org = $this->createTestTenant();
    
    $response = $this->actingAsAdmin($org)
        ->get('/api/v1/users');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data',
            'message',
        ]);
});
```

### Property-Based Test

```php
<?php

use App\Models\Control\Organization;

test('Property 1: Tenant Database Naming Convention', function () {
    // Generate random organizations
    $orgs = Organization::factory()->count(10)->make();
    
    foreach ($orgs as $org) {
        // Verify tenant_db_name follows pattern
        expect($org->tenant_db_name)
            ->toBe("erp_{$org->org_slug}");
    }
})->repeat(100);
```

## Test Organization

```
tests/
├── Feature/              # Feature tests (API endpoints, workflows)
│   ├── Auth/
│   ├── UserManagement/
│   ├── Subscription/
│   └── ...
├── Unit/                 # Unit tests (models, services, helpers)
│   ├── Models/
│   │   ├── Control/
│   │   └── Tenant/
│   ├── Services/
│   └── Middleware/
├── Property/             # Property-based tests
│   ├── TenantIsolationTest.php
│   ├── SubscriptionAccessTest.php
│   └── ...
├── Traits/               # Test traits
│   ├── TenantTestTrait.php
│   ├── AuthenticationTestTrait.php
│   └── SubscriptionTestTrait.php
├── Helpers/              # Test helper classes
│   └── TestHelpers.php
├── Pest.php              # Pest configuration
├── TestCase.php          # Base test case
└── README.md             # This file
```

## Best Practices

### 1. Use Factories

Always use factories instead of manually creating models:

```php
// Good
$org = Organization::factory()->create();

// Bad
$org = Organization::create([
    'org_slug' => 'test',
    'org_name' => 'Test Org',
    // ... many more fields
]);
```

### 2. Use Traits

Leverage test traits for common operations:

```php
// Good
$org = $this->createTestTenant();
$this->actingAsAdmin($org)->get('/api/v1/users');

// Bad
$org = Organization::factory()->create();
$this->setupTenantDatabase($org);
$this->seedTenantDatabase();
$user = User::factory()->admin()->create();
$token = $this->generateToken($user, $org);
$this->withHeaders(['Authorization' => "Bearer {$token}"])->get('/api/v1/users');
```

### 3. Clean Up

The base TestCase handles cleanup automatically, but for custom resources:

```php
protected function tearDown(): void
{
    // Clean up custom resources
    
    parent::tearDown();
}
```

### 4. Isolate Tests

Each test should be independent and not rely on other tests:

```php
// Good
test('user can be created', function () {
    $org = $this->createTestTenant();
    $user = User::factory()->create();
    // ...
});

// Bad - relies on previous test
test('user can be updated', function () {
    // Assumes user from previous test exists
    $user = User::first();
    // ...
});
```

### 5. Use Descriptive Names

Test names should clearly describe what is being tested:

```php
// Good
test('expired subscription blocks API access')
test('user without can_view permission is denied access')

// Bad
test('subscription test')
test('permission check')
```

## Continuous Integration

Tests are designed to run in CI environments. Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_sqlite
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: php artisan test --coverage --min=80
```

## Coverage Goals

- **Unit Tests**: 80%+ code coverage
- **Property Tests**: All 31 correctness properties implemented
- **Integration Tests**: All critical workflows covered
- **API Tests**: All endpoints tested with success and error cases

## Troubleshooting

### Tests Fail with Database Connection Error

Ensure test database connections are configured in `config/database.php`:

```php
'control_test' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
],
```

### Redis Connection Errors

Redis is optional in tests. The TestHelpers class handles Redis errors gracefully.

### Factory Relationship Errors

When using factories with relationships, ensure related models are created first:

```php
// Good
$role = Role::factory()->create();
$user = User::factory()->create(['role_id' => $role->role_id]);

// Or let factory handle it
$user = User::factory()->create(); // Factory creates role automatically
```

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Laravel Database Testing](https://laravel.com/docs/database-testing)
