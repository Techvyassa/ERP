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

        // Firebase authentication
        Route::post('/firebase-login', [App\Http\Controllers\FirebaseAuthController::class, 'firebaseLogin']);
    });

    // Organization registration and utilities (public)
    Route::prefix('organizations')->group(function () {
        Route::post('/register', [App\Http\Controllers\OrganizationController::class, 'register']);
        Route::get('/check-slug/{slug}', [App\Http\Controllers\OrganizationController::class, 'checkSlug']);
        Route::post('/suggest-slug', [App\Http\Controllers\OrganizationController::class, 'suggestSlug']);
    });

    // Subscription plans (public)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [App\Http\Controllers\SubscriptionPlanController::class, 'index']);
        Route::get('/{planCode}', [App\Http\Controllers\SubscriptionPlanController::class, 'show']);
    });

    // Protected routes (require authentication, tenant resolution, subscription validation, and RBAC)
    Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])->group(function () {

        // Rate limit status endpoint (excluded from rate limiting)
        Route::get('/rate-limit/status', [App\Http\Controllers\RateLimitController::class, 'status']);

        // Profile completion endpoints
        Route::prefix('profile-completion')->group(function () {
            Route::get('/status', [App\Http\Controllers\ProfileCompletionController::class, 'status']);
            Route::put('/organization', [App\Http\Controllers\ProfileCompletionController::class, 'updateOrganization']);
            Route::get('/master-data-status', [App\Http\Controllers\ProfileCompletionController::class, 'masterDataStatus']);
        });

        // Subscription management endpoints
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [App\Http\Controllers\SubscriptionController::class, 'current']);
            Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans']);
            Route::post('/upgrade', [App\Http\Controllers\SubscriptionController::class, 'upgrade']);
            Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel']);
        });


        // Department management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('departments')->group(function () {
            Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\DepartmentController::class, 'show']);
            Route::post('/', [App\Http\Controllers\DepartmentController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\DepartmentController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\DepartmentController::class, 'deactivate']);
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


        // User management endpoints (with RBAC middleware for USERS module)
        Route::middleware(['check.module.permission:USERS'])->prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\UserController::class, 'show']);
            Route::post('/', [App\Http\Controllers\UserController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\UserController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\UserController::class, 'deactivate']);
        });


        // HSN Code management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('hsn-codes')->group(function () {
            Route::get('/', [App\Http\Controllers\HSNCodeController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\HSNCodeController::class, 'show']);
            Route::post('/', [App\Http\Controllers\HSNCodeController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\HSNCodeController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\HSNCodeController::class, 'destroy']);
        });

        // GST Tax management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('gst-taxes')->group(function () {
            Route::get('/', [App\Http\Controllers\GSTTaxController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\GSTTaxController::class, 'show']);
            Route::post('/', [App\Http\Controllers\GSTTaxController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\GSTTaxController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\GSTTaxController::class, 'destroy']);
        });

        // Currency management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('currencies')->group(function () {
            Route::get('/', [App\Http\Controllers\CurrencyController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\CurrencyController::class, 'show']);
            Route::post('/', [App\Http\Controllers\CurrencyController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\CurrencyController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\CurrencyController::class, 'destroy']);
        });

        // INVENTORY Management Endpoints
        Route::middleware(['check.module.permission:INVENTORY'])->group(function () {
            // UOM Master
            Route::prefix('uoms')->group(function () {
                Route::get('/', [App\Http\Controllers\UOMController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\UOMController::class, 'show']);
                Route::post('/', [App\Http\Controllers\UOMController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\UOMController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\UOMController::class, 'destroy']);
            });

            // Warehouse Master
            Route::prefix('warehouses')->group(function () {
                Route::get('/', [App\Http\Controllers\WarehouseController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\WarehouseController::class, 'show']);
                Route::post('/', [App\Http\Controllers\WarehouseController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\WarehouseController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\WarehouseController::class, 'destroy']);
            });

            // Bin Locations
            Route::prefix('bin-locations')->group(function () {
                Route::get('/', [App\Http\Controllers\BinLocationController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\BinLocationController::class, 'show']);
                Route::post('/', [App\Http\Controllers\BinLocationController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\BinLocationController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\BinLocationController::class, 'destroy']);
            });

            // Material Master
            Route::prefix('materials')->group(function () {
                Route::get('/', [App\Http\Controllers\MaterialController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\MaterialController::class, 'show']);
                Route::post('/', [App\Http\Controllers\MaterialController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\MaterialController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\MaterialController::class, 'destroy']); // Deactivate
            });

            // Product Master
            Route::prefix('products')->group(function () {
                Route::get('/', [App\Http\Controllers\ProductController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show']);
                Route::post('/', [App\Http\Controllers\ProductController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\ProductController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\ProductController::class, 'destroy']); // Deactivate
            });
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
