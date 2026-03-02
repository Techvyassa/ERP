<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 routes
Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'ERP API is running',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Webhook routes (public, no authentication required)
    Route::prefix('webhooks')->group(function () {
        Route::post('/razorpay', [App\Http\Controllers\RazorpayWebhookController::class, 'handleWebhook']);
        Route::post('/stripe', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);
    });

    // Authentication routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
        Route::post('/refresh', [App\Http\Controllers\AuthController::class, 'refresh']);
        Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);
    });

    // Organization registration (public)
    Route::post('/organizations/register', [App\Http\Controllers\OrganizationController::class, 'register']);

    // Protected routes (require authentication, tenant resolution, subscription validation, and RBAC)
    Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])->group(function () {
        
        // Rate limit status endpoint (excluded from rate limiting)
        Route::get('/rate-limit/status', [App\Http\Controllers\RateLimitController::class, 'status']);

        // Subscription management endpoints
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [App\Http\Controllers\SubscriptionController::class, 'current']);
            Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans']);
            Route::post('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'upgrade']);
            Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel']);
        });

        // User management endpoints (with RBAC middleware for USERS module)
        Route::middleware(['check.module.permission:USERS'])->prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\UserController::class, 'show']);
            Route::post('/', [App\Http\Controllers\UserController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\UserController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\UserController::class, 'destroy']);
        });

        // Department management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('departments')->group(function () {
            Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\DepartmentController::class, 'show']);
            Route::post('/', [App\Http\Controllers\DepartmentController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\DepartmentController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\DepartmentController::class, 'destroy']);
        });

        // Role management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('roles')->group(function () {
            Route::get('/', [App\Http\Controllers\RoleController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\RoleController::class, 'show']);
            Route::post('/', [App\Http\Controllers\RoleController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\RoleController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\RoleController::class, 'destroy']);
            
            // Role permissions endpoints
            Route::get('/{id}/permissions', [App\Http\Controllers\RolePermissionController::class, 'show']);
            Route::put('/{id}/permissions', [App\Http\Controllers\RolePermissionController::class, 'update']);
        });

        // Admin-only feature control endpoints (require admin authentication)
        Route::prefix('admin')->group(function () {
            Route::prefix('feature-controls')->group(function () {
                Route::get('/', [App\Http\Controllers\FeatureControlController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\FeatureControlController::class, 'show']);
                Route::post('/', [App\Http\Controllers\FeatureControlController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\FeatureControlController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\FeatureControlController::class, 'destroy']);
            });
        });
    });
});
