# Middleware Stack Usage Guide

This document explains how to register and use the middleware stack for the Laravel Multi-Tenant ERP system.

## Middleware Overview

The system implements 5 middleware components that process requests in the following order:

1. **ValidateJWT** - Authentication middleware
2. **ResolveTenant** - Tenant resolution middleware
3. **ValidateSubscription** - Subscription gate middleware
4. **CheckModulePermission** - RBAC middleware
5. **ThrottleRequests** - Rate limiting middleware

## Middleware Registration

Add the middleware to `bootstrap/app.php` or `app/Http/Kernel.php` (depending on Laravel version):

### Laravel 11+ (bootstrap/app.php)

```php
use App\Http\Middleware\ValidateJWT;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\ValidateSubscription;
use App\Http\Middleware\CheckModulePermission;
use App\Http\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'auth.jwt' => ValidateJWT::class,
            'tenant.resolve' => ResolveTenant::class,
            'subscription.validate' => ValidateSubscription::class,
            'permission.check' => CheckModulePermission::class,
            'rate.limit' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### Laravel 10 (app/Http/Kernel.php)

```php
protected $middlewareAliases = [
    'auth.jwt' => \App\Http\Middleware\ValidateJWT::class,
    'tenant.resolve' => \App\Http\Middleware\ResolveTenant::class,
    'subscription.validate' => \App\Http\Middleware\ValidateSubscription::class,
    'permission.check' => \App\Http\Middleware\CheckModulePermission::class,
    'rate.limit' => \App\Http\Middleware\ThrottleRequests::class,
];
```

## Route Usage Examples

### Basic Protected Route (View Access)

```php
Route::middleware([
    'auth.jwt',
    'tenant.resolve',
    'subscription.validate',
    'permission.check:view',
    'rate.limit'
])->group(function () {
    Route::get('/api/v1/materials', [MaterialController::class, 'index']);
});
```

### Create/Edit/Delete Routes

```php
// Create
Route::post('/api/v1/materials', [MaterialController::class, 'store'])
    ->middleware([
        'auth.jwt',
        'tenant.resolve',
        'subscription.validate',
        'permission.check:create',
        'rate.limit'
    ]);

// Edit
Route::put('/api/v1/materials/{id}', [MaterialController::class, 'update'])
    ->middleware([
        'auth.jwt',
        'tenant.resolve',
        'subscription.validate',
        'permission.check:edit',
        'rate.limit'
    ]);

// Delete
Route::delete('/api/v1/materials/{id}', [MaterialController::class, 'destroy'])
    ->middleware([
        'auth.jwt',
        'tenant.resolve',
        'subscription.validate',
        'permission.check:delete',
        'rate.limit'
    ]);

// Approve
Route::post('/api/v1/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])
    ->middleware([
        'auth.jwt',
        'tenant.resolve',
        'subscription.validate',
        'permission.check:approve',
        'rate.limit'
    ]);
```

### Module-Specific Routes

```php
// Purchase Requisition Module
Route::prefix('api/v1/purchase-requisitions')
    ->middleware([
        'auth.jwt',
        'tenant.resolve',
        'subscription.validate',
        'rate.limit'
    ])
    ->group(function () {
        Route::get('/', [PRController::class, 'index'])
            ->middleware('permission.check:view')
            ->defaults('module_code', 'PR');
        
        Route::post('/', [PRController::class, 'store'])
            ->middleware('permission.check:create')
            ->defaults('module_code', 'PR');
    });
```

## Request Headers

### Required Headers

1. **Authorization**: Bearer token (JWT)
   ```
   Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
   ```

2. **X-Org-Slug**: Organization identifier
   ```
   X-Org-Slug: acme-corp
   ```

3. **X-Module-Code**: Module being accessed (optional, can be in route)
   ```
   X-Module-Code: PR
   ```

### Example Request

```bash
curl -X GET https://api.example.com/api/v1/materials \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "X-Org-Slug: acme-corp" \
  -H "X-Module-Code: INVENTORY"
```

## Middleware Flow

```
1. ValidateJWT
   ↓ Extracts: auth_user_id, auth_org_id
   
2. ResolveTenant
   ↓ Extracts: tenant_org_id, tenant_org_slug, tenant_db_name, tenant_organization
   
3. ValidateSubscription
   ↓ Extracts: active_subscription, subscription_status, modules_allowed
   
4. CheckModulePermission
   ↓ Extracts: user_permissions, module_permissions
   ↓ Switches to Tenant DB
   
5. ThrottleRequests
   ↓ Adds headers: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
   
6. Controller
```

## Error Responses

All middleware return consistent JSON error responses:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "details": {}
  },
  "message": "Human-readable error message",
  "request_id": "req_abc123",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Common Error Codes

| HTTP Status | Error Code | Middleware | Description |
|-------------|------------|------------|-------------|
| 401 | TOKEN_EXPIRED | ValidateJWT | JWT token has expired |
| 401 | TOKEN_INVALID | ValidateJWT | JWT signature is invalid |
| 401 | TOKEN_REQUIRED | ValidateJWT | No token provided |
| 400 | TENANT_CONTEXT_REQUIRED | ResolveTenant | Missing X-Org-Slug header |
| 404 | TENANT_NOT_FOUND | ResolveTenant | Organization not found |
| 403 | TENANT_SUSPENDED | ResolveTenant | Organization is suspended |
| 410 | TENANT_TERMINATED | ResolveTenant | Organization is terminated |
| 402 | SUBSCRIPTION_REQUIRED | ValidateSubscription | No active subscription |
| 402 | SUBSCRIPTION_EXPIRED | ValidateSubscription | Subscription has expired |
| 403 | MODULE_NOT_ALLOWED | ValidateSubscription | Module not in plan |
| 403 | PERMISSION_DENIED | CheckModulePermission | Insufficient permissions |
| 429 | RATE_LIMIT_EXCEEDED | ThrottleRequests | Too many requests |

## Caching

### Permission Cache
- **Key**: `rbac:user:{user_id}:permissions`
- **TTL**: 15 minutes (900 seconds)
- **Storage**: Redis

### Subscription Cache
- **Key**: `subscription:org:{org_id}`
- **TTL**: 5 minutes (300 seconds)
- **Storage**: Redis

### Rate Limit Counter
- **Key**: `rate_limit:org:{org_id}:{date}`
- **TTL**: Until midnight UTC
- **Storage**: Redis

## Cache Invalidation

### Invalidate User Permissions
```php
use Illuminate\Support\Facades\Cache;

// When role permissions are updated
Cache::forget("rbac:user:{$userId}:permissions");
```

### Invalidate Subscription Cache
```php
// When subscription is updated
Cache::forget("subscription:org:{$orgId}");
```

## Testing

### Unit Test Example

```php
use Tests\TestCase;
use App\Models\Control\Organization;
use App\Models\Control\ActiveSubscription;

class MiddlewareTest extends TestCase
{
    public function test_validates_jwt_token()
    {
        $response = $this->getJson('/api/v1/materials');
        
        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error' => ['code' => 'TOKEN_REQUIRED']
                 ]);
    }
    
    public function test_resolves_tenant()
    {
        $org = Organization::factory()->create([
            'org_slug' => 'test-org',
            'registration_status' => 'ACTIVE'
        ]);
        
        $token = $this->generateJWT($org->org_id);
        
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Org-Slug' => 'test-org'
        ])->getJson('/api/v1/materials');
        
        // Should pass tenant resolution
        $this->assertNotEquals(404, $response->status());
    }
}
```

## Notes

1. **Middleware Order**: The order is critical. Do not change the sequence.
2. **Database Switching**: CheckModulePermission switches to Tenant DB. Ensure DatabaseConnectionRouter is properly configured.
3. **Rate Limiting**: Excludes health check endpoints. Configure exclusions as needed.
4. **Grace Period**: PAST_DUE subscriptions allow read-only access during grace period.
5. **Module Codes**: Ensure module_code is provided either in route defaults or X-Module-Code header.
