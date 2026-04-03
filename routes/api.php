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
        Route::post('/forgot-password', [App\Http\Controllers\PasswordResetController::class, 'forgotPassword']);
        Route::post('/reset-password', [App\Http\Controllers\PasswordResetController::class, 'resetPassword']);

        // Firebase authentication
        Route::post('/firebase-login', [App\Http\Controllers\FirebaseAuthController::class, 'firebaseLogin']);
    });

    // Authenticated user profile + permissions
    // Returns dept/role context and full permission map for dashboard routing
    Route::middleware(['validate.jwt', 'resolve.tenant'])
        ->get('/auth/me', [App\Http\Controllers\AuthController::class, 'me']);

    // Debug: Check current user permissions
    Route::middleware(['validate.jwt', 'resolve.tenant'])->get('/debug/my-permissions', function (Request $request) {
        $userId = $request->input('auth_user_id');
        $tenantDb = $request->input('tenant_db_name');

        // Switch to tenant DB
        config(['database.connections.tenant.database' => $tenantDb]);

        $user = \App\Models\Tenant\User::with('role')->find($userId);
        $permissions = \App\Models\Tenant\RolePermission::where('role_id', $user->role_id)->get();

        // Clear cache for this user
        \Illuminate\Support\Facades\Cache::forget("rbac:user:{$userId}:permissions");

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'user_email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $user->role->role_name ?? 'N/A',
                'role_code' => $user->role->role_code ?? 'N/A',
                'permissions_count' => $permissions->count(),
                'permissions' => $permissions->map(fn($p) => [
                    'module' => $p->module_code,
                    'view' => $p->can_view,
                    'create' => $p->can_create,
                    'edit' => $p->can_edit,
                    'approve' => $p->can_approve,
                    'delete' => $p->can_delete,
                ]),
                'cache_cleared' => true,
            ],
            'message' => 'User permissions retrieved and cache cleared',
        ]);
    });

    // Organization registration and utilities (public)
    Route::prefix('organizations')->group(function () {
        Route::post('/register', [App\Http\Controllers\OrganizationController::class, 'register']);
        Route::get('/check-slug/{slug}', [App\Http\Controllers\OrganizationController::class, 'checkSlug']);
        Route::post('/suggest-slug', [App\Http\Controllers\OrganizationController::class, 'suggestSlug']);
        Route::get('/slugs', [App\Http\Controllers\OrganizationController::class, 'slugs']);
    });

    // Rate limit status endpoint (excluded from rate limiting and subscription validation)
    Route::get('/rate-limit/status', [App\Http\Controllers\RateLimitController::class, 'status']);

    // Subscription plans (public)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [App\Http\Controllers\SubscriptionPlanController::class, 'index']);
        Route::get('/{planCode}', [App\Http\Controllers\SubscriptionPlanController::class, 'show']);
    });

    // Protected routes (require authentication, tenant resolution, subscription validation, and RBAC)
    Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])->group(function () {

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

        Route::middleware(['check.module.permission:ADMIN'])->prefix('dashboard')->group(function () {
            Route::get('/master-stats', [App\Http\Controllers\MasterDashboardController::class, 'index']);
        });


        // Department management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('departments')->group(function () {
            Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index']);
            Route::get('/import/template', [App\Http\Controllers\DepartmentController::class, 'downloadTemplate']);
            Route::post('/import', [App\Http\Controllers\DepartmentController::class, 'importCSV']);
            Route::get('/{id}', [App\Http\Controllers\DepartmentController::class, 'show']);
            Route::post('/', [App\Http\Controllers\DepartmentController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\DepartmentController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\DepartmentController::class, 'deactivate']);
            // Returns only valid roles for this department (used by admin user-creation UI)
            Route::get('/{id}/roles', [App\Http\Controllers\DepartmentController::class, 'roles']);
        });


        // Role management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('roles')->group(function () {
            Route::get('/', [App\Http\Controllers\RoleController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\RoleController::class, 'show']);
            Route::post('/', [App\Http\Controllers\RoleController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\RoleController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\RoleController::class, 'destroy']);

            // Role permissions endpoints
            Route::get('/{id}/permissions', [App\Http\Controllers\RolePermissionController::class, 'show']);
            Route::put('/{id}/permissions', [App\Http\Controllers\RolePermissionController::class, 'update']);
        });


        // User management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'index']);
            Route::get('/import/template', [App\Http\Controllers\UserController::class, 'downloadTemplate']);
            Route::post('/import', [App\Http\Controllers\UserController::class, 'importCSV']);
            Route::get('/{id}', [App\Http\Controllers\UserController::class, 'show']);
            Route::post('/', [App\Http\Controllers\UserController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\UserController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\UserController::class, 'deactivate']);
        });


        // HSN Code management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('hsn-codes')->group(function () {
            Route::get('/', [App\Http\Controllers\HSNCodeController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\HSNCodeController::class, 'show']);
            Route::post('/', [App\Http\Controllers\HSNCodeController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\HSNCodeController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\HSNCodeController::class, 'destroy']);
        });

        // GST Tax management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('gst-taxes')->group(function () {
            Route::get('/', [App\Http\Controllers\GSTTaxController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\GSTTaxController::class, 'show']);
            Route::post('/', [App\Http\Controllers\GSTTaxController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\GSTTaxController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\GSTTaxController::class, 'destroy']);
        });

        // Currency management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('currencies')->group(function () {
            Route::get('/', [App\Http\Controllers\CurrencyController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\CurrencyController::class, 'show']);
            Route::post('/', [App\Http\Controllers\CurrencyController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\CurrencyController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\CurrencyController::class, 'destroy']);
        });

        // UOM Master management endpoints (with RBAC middleware for ADMINISTRATION module)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('uoms')->group(function () {
            Route::get('/', [App\Http\Controllers\UOMController::class, 'index']);
            Route::get('/barcode', [App\Http\Controllers\UOMController::class, 'barcode']);
            Route::get('/{id}', [App\Http\Controllers\UOMController::class, 'show']);
            Route::post('/', [App\Http\Controllers\UOMController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\UOMController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\UOMController::class, 'destroy']);
        });

        // INVENTORY Management Endpoints (Master Data)
        Route::middleware(['check.module.permission:ADMIN'])->group(function () {
            // Warehouse Master
            Route::prefix('warehouses')->group(function () {
                Route::get('/', [App\Http\Controllers\WarehouseController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\WarehouseController::class, 'barcode']);
                Route::get('/all-stock', [App\Http\Controllers\WarehouseController::class, 'allWarehouseStock']);
                Route::get('/{id}', [App\Http\Controllers\WarehouseController::class, 'show']);
                Route::post('/', [App\Http\Controllers\WarehouseController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\WarehouseController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\WarehouseController::class, 'destroy']);
            });

            // Bin Locations
            Route::prefix('bin-locations')->group(function () {
                Route::get('/', [App\Http\Controllers\BinLocationController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\BinLocationController::class, 'barcode']);
                Route::get('/{id}', [App\Http\Controllers\BinLocationController::class, 'show']);
                Route::post('/', [App\Http\Controllers\BinLocationController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\BinLocationController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\BinLocationController::class, 'destroy']);
            });

            // Material Master
            Route::prefix('materials')->group(function () {
                Route::get('/', [App\Http\Controllers\MaterialController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\MaterialController::class, 'barcode']);
                Route::get('/search/barcode', [App\Http\Controllers\MaterialController::class, 'searchByBarcode']);
                Route::get('/search', [App\Http\Controllers\MaterialController::class, 'search']);
                Route::get('/{id}', [App\Http\Controllers\MaterialController::class, 'show']);
                Route::post('/', [App\Http\Controllers\MaterialController::class, 'store']);
                Route::post('/bulk', [App\Http\Controllers\MaterialController::class, 'bulkStore']);
                Route::put('/{id}', [App\Http\Controllers\MaterialController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\MaterialController::class, 'destroy']); // Deactivate
            });

            // Product Master
            Route::prefix('products')->group(function () {
                Route::get('/', [App\Http\Controllers\ProductController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\ProductController::class, 'barcode']);
                Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show']);
                Route::post('/', [App\Http\Controllers\ProductController::class, 'store']);
                Route::post('/bulk', [App\Http\Controllers\ProductController::class, 'bulkStore']);
                Route::put('/{id}', [App\Http\Controllers\ProductController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\ProductController::class, 'destroy']); // Deactivate
            });
        });

        // PROCUREMENT (Vendor Master Data) Endpoints
        // Roles: ADMIN (all)
        Route::middleware(['check.module.permission:ADMIN'])->group(function () {
            // Vendor Master
            Route::prefix('vendors')->group(function () {
                Route::get('/', [App\Http\Controllers\VendorController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\VendorController::class, 'show']);
                Route::post('/', [App\Http\Controllers\VendorController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\VendorController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\VendorController::class, 'destroy']); // Blacklist vendor
            });

            // Vendor Contacts
            Route::prefix('vendor-contacts')->group(function () {
                Route::get('/', [App\Http\Controllers\VendorContactController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\VendorContactController::class, 'show']);
                Route::post('/', [App\Http\Controllers\VendorContactController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\VendorContactController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\VendorContactController::class, 'destroy']);
            });

            // Approved Vendor List (AVL) — Vendor Material Map
            Route::prefix('vendor-material-map')->group(function () {
                Route::get('/', [App\Http\Controllers\VendorMaterialMapController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\VendorMaterialMapController::class, 'show']);
                Route::post('/', [App\Http\Controllers\VendorMaterialMapController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\VendorMaterialMapController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\VendorMaterialMapController::class, 'destroy']);
            });
        });

        // PO Management Endpoints
        // Roles: PROC_EXE (create/edit), PROC_MGR (approve), ADMIN (all)
        // Status Flow: DRAFT → PENDING_APPROVAL → APPROVED → OPEN → PARTIAL → CLOSED/CANCELLED
        Route::middleware(['check.module.permission:STORE'])->group(function () {
            // Purchase Requisition Endpoints
            Route::prefix('purchase-requisitions')->group(function () {
                // Master data lookups for PR form
                Route::get('/master/materials',  [App\Http\Controllers\PurchaseRequisitionController::class, 'getMaterials']);
                Route::get('/master/uoms',        [App\Http\Controllers\PurchaseRequisitionController::class, 'getUoms']);
                Route::get('/master/warehouses',  [App\Http\Controllers\PurchaseRequisitionController::class, 'getWarehouses']);
                Route::get('/master/users',       [App\Http\Controllers\PurchaseRequisitionController::class, 'getUsers']);
                // CRUD
                Route::get('/',      [App\Http\Controllers\PurchaseRequisitionController::class, 'index']);
                Route::post('/',     [App\Http\Controllers\PurchaseRequisitionController::class, 'store']);
                Route::get('/{id}',  [App\Http\Controllers\PurchaseRequisitionController::class, 'show']);
                Route::put('/{id}',  [App\Http\Controllers\PurchaseRequisitionController::class, 'update']);
                // Status transitions
                Route::patch('/{id}/submit',  [App\Http\Controllers\PurchaseRequisitionController::class, 'submit']);
                Route::patch('/{id}/approve', [App\Http\Controllers\PurchaseRequisitionController::class, 'approve']);
                Route::patch('/{id}/reject',  [App\Http\Controllers\PurchaseRequisitionController::class, 'reject']);
            });
        });

        Route::middleware(['check.module.permission:STORE'])->group(function () {
            Route::prefix('purchase-orders')->group(function () {
                Route::get('/', [App\Http\Controllers\PurchaseOrderController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\PurchaseOrderController::class, 'show']);
                Route::post('/', [App\Http\Controllers\PurchaseOrderController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\PurchaseOrderController::class, 'update']);

                // Status Transitions
                Route::patch('/{id}/submit', [App\Http\Controllers\PurchaseOrderController::class, 'submit']); // DRAFT → PENDING_APPROVAL
                Route::patch('/{id}/approve', [App\Http\Controllers\PurchaseOrderController::class, 'approve']); // PENDING_APPROVAL → APPROVED
                Route::patch('/{id}/reject', [App\Http\Controllers\PurchaseOrderController::class, 'reject']); // PENDING_APPROVAL → DRAFT
                Route::patch('/{id}/release', [App\Http\Controllers\PurchaseOrderController::class, 'release']); // APPROVED → OPEN
                Route::patch('/{id}/close', [App\Http\Controllers\PurchaseOrderController::class, 'close']); // OPEN/PARTIAL → CLOSED
                Route::patch('/{id}/cancel', [App\Http\Controllers\PurchaseOrderController::class, 'cancel']); // Any → CANCELLED
                Route::post('/{id}/send-to-vendor', [App\Http\Controllers\PurchaseOrderController::class, 'sendToVendor']); // Send PO email to vendor
            });
        });

        // ------currently not using (START) -------------------------
        // ASN (Advance Shipping Notice) Endpoints 
        // Roles: PROC_EXE/PROC_MGR (create/edit), STOREKEEPER/STORE_MGR (view/receive), ADMIN (all)
        // Status Flow: DRAFT → SENT → IN_TRANSIT → ARRIVED → RECEIVED/CANCELLED
        Route::middleware(['check.module.permission:STORE'])->group(function () {
            Route::prefix('asn')->group(function () {
                // Lookup endpoints (before resource routes to avoid conflicts)
                Route::get('/arriving-today', [App\Http\Controllers\ASNController::class, 'arrivingToday']);
                Route::get('/overdue', [App\Http\Controllers\ASNController::class, 'overdue']);
                Route::get('/by-po/{poId}', [App\Http\Controllers\ASNController::class, 'getByPO']);
                Route::get('/by-vendor/{vendorId}', [App\Http\Controllers\ASNController::class, 'getByVendor']);
                Route::post('/upload-csv', [App\Http\Controllers\ASNController::class, 'uploadCSV']);

                // Resource routes
                Route::get('/', [App\Http\Controllers\ASNController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\ASNController::class, 'show']);
                Route::post('/', [App\Http\Controllers\ASNController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\ASNController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\ASNController::class, 'destroy']);

                // Status transitions
                Route::patch('/{id}/send', [App\Http\Controllers\ASNController::class, 'send']); // DRAFT → SENT
                Route::patch('/{id}/in-transit', [App\Http\Controllers\ASNController::class, 'markInTransit']); // SENT → IN_TRANSIT
                Route::patch('/{id}/arrived', [App\Http\Controllers\ASNController::class, 'markArrived']); // IN_TRANSIT → ARRIVED
            });
        });

        // ------currently not using (END) -------------------------

        // Gate Entry Management Endpoints
        // Roles: Security Department (create), ADMIN (all)
        // Status Flow: PENDING → COMPLETED (after GRN auto-created)
        Route::middleware(['check.module.permission:SECURITY'])->group(function () {
            Route::prefix('gate-entries')->group(function () {
                // Lookup endpoints
                Route::get('/by-vendor/{vendorId}', [App\Http\Controllers\GateEntryController::class, 'byVendor']);
                Route::get('/by-po/{poId}', [App\Http\Controllers\GateEntryController::class, 'byPO']);

                // Resource routes
                Route::get('/', [App\Http\Controllers\GateEntryController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\GateEntryController::class, 'show']);
                Route::post('/', [App\Http\Controllers\GateEntryController::class, 'store']);
            });
        });

        // Material Receipt (MR) & GRN Endpoints
        // Roles: STOREKEEPER (create/edit), STORE_MGR (approve), ADMIN (all)
        Route::middleware(['check.module.permission:STORE'])->group(function () {

            // ------Material Receipts: kept for backward compat (legacy flow)------
            Route::prefix('material-receipts')->group(function () {
                Route::get('/by-ge/{geId}', [App\Http\Controllers\MaterialReceiptController::class, 'byGateEntry']);
                Route::get('/by-po/{poId}', [App\Http\Controllers\MaterialReceiptController::class, 'byPO']);
                Route::get('/pending-grn', [App\Http\Controllers\MaterialReceiptController::class, 'pendingGRN']);
                Route::get('/', [App\Http\Controllers\MaterialReceiptController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\MaterialReceiptController::class, 'show']);
            });

            // GRN (Goods Receipt Note) Endpoints
            // Status Flow: PROVISIONAL → QC_PENDING → ACCEPTED/REJECTED/PARTIALLY_ACCEPTED
            Route::prefix('grn')->group(function () {
                // Lookup endpoints
                Route::get('/by-ge/{geId}', [App\Http\Controllers\GRNController::class, 'byGateEntry']);
                Route::get('/by-po/{poId}', [App\Http\Controllers\GRNController::class, 'byPO']);
                Route::get('/by-vendor/{vendorId}', [App\Http\Controllers\GRNController::class, 'byVendor']);
                Route::get('/provisional', [App\Http\Controllers\GRNController::class, 'provisional']);
                Route::get('/qc-pending', [App\Http\Controllers\GRNController::class, 'qcPending']);

                // Resource routes
                Route::get('/', [App\Http\Controllers\GRNController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\GRNController::class, 'show']);
                Route::post('/', [App\Http\Controllers\GRNController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\GRNController::class, 'update']);

                // Status transitions
                Route::patch('/{id}/approve', [App\Http\Controllers\GRNController::class, 'approve']); // PROVISIONAL → QC_PENDING
                Route::patch('/{id}/cancel', [App\Http\Controllers\GRNController::class, 'cancel']); // Any → CANCELLED
                Route::patch('/{id}/post-qc', [App\Http\Controllers\GRNController::class, 'postQCUpdate']); // Post-QC edit by Store/QC
            });
        });

        // QC Test Type Master (ADMINISTRATION permission)
        Route::middleware(['check.module.permission:ADMIN'])->prefix('qc-test-types')->group(function () {
            Route::get('/', [App\Http\Controllers\QCTestTypeController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'show']);
            Route::post('/', [App\Http\Controllers\QCTestTypeController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'destroy']);
        });

        Route::middleware(['check.module.permission:ADMIN'])->prefix('qc-parameters')->group(function () {
            Route::get('/', [App\Http\Controllers\QCParameterController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\QCParameterController::class, 'show']);
            Route::post('/', [App\Http\Controllers\QCParameterController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\QCParameterController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\QCParameterController::class, 'destroy']);
        });

        // Quality Control (QC) Endpoints
        // Roles: QC_TECH (record tests), QC_MGR (make decisions), ADMIN (all)
        // Status Flow: PENDING → IN_PROGRESS → COMPLETED → DECISION_MADE
        Route::middleware(['check.module.permission:QC'])->group(function () {
            Route::prefix('qc')->group(function () {
                // Lookup endpoints
                Route::get('/pending', [App\Http\Controllers\QCController::class, 'pending']);
                Route::get('/in-progress', [App\Http\Controllers\QCController::class, 'inProgress']);
                Route::get('/completed', [App\Http\Controllers\QCController::class, 'completed']);
                Route::get('/by-grn/{grnId}', [App\Http\Controllers\QCController::class, 'byGRN']);
                Route::get('/by-production-order/{productionOrderId}', [App\Http\Controllers\QCController::class, 'byProductionOrder']);
                Route::get('/parameters/{materialId}', [App\Http\Controllers\QCController::class, 'getParameters']);

                // Resource routes
                Route::get('/', [App\Http\Controllers\QCController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\QCController::class, 'show']);
                Route::post('/', [App\Http\Controllers\QCController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\QCController::class, 'update']);

                // Status transitions
                Route::patch('/{id}/start', [App\Http\Controllers\QCController::class, 'startInspection']); // PENDING → IN_PROGRESS
                Route::patch('/{id}/complete', [App\Http\Controllers\QCController::class, 'completeInspection']); // IN_PROGRESS → COMPLETED

                // Test results
                Route::post('/{lotId}/test-results', [App\Http\Controllers\QCController::class, 'recordTestResult']);

                // Usage decision
                Route::post('/{id}/decision', [App\Http\Controllers\QCController::class, 'makeDecision']);
            });
        });

        // Putaway & Store Posting Endpoints
        // Roles: STOREKEEPER (create/execute), STORE_MGR (approve), ADMIN (all)
        // Status Flow: PENDING → IN_PROGRESS → COMPLETED
        Route::middleware(['check.module.permission:STORE'])->group(function () {
            Route::prefix('putaway')->group(function () {
                // Lookup endpoints
                Route::get('/pending', [App\Http\Controllers\PutawayController::class, 'pending']);
                Route::get('/in-progress', [App\Http\Controllers\PutawayController::class, 'inProgress']);
                Route::get('/completed', [App\Http\Controllers\PutawayController::class, 'completed']);

                // Resource routes
                Route::get('/', [App\Http\Controllers\PutawayController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\PutawayController::class, 'show']);
                Route::post('/', [App\Http\Controllers\PutawayController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\PutawayController::class, 'update']);

                // Status transitions
                Route::patch('/{id}/start', [App\Http\Controllers\PutawayController::class, 'start']); // PENDING → IN_PROGRESS
                Route::patch('/{id}/complete', [App\Http\Controllers\PutawayController::class, 'complete']); // IN_PROGRESS → COMPLETED
                Route::patch('/{id}/cancel', [App\Http\Controllers\PutawayController::class, 'cancel']); // Any → CANCELLED
            });

            // Alias for putaway-tasks
            Route::prefix('putaway-tasks')->group(function () {
                Route::get('/', [App\Http\Controllers\PutawayController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\PutawayController::class, 'show']);
                Route::post('/', [App\Http\Controllers\PutawayController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\PutawayController::class, 'update']);
                Route::patch('/{id}/start', [App\Http\Controllers\PutawayController::class, 'start']);
                Route::patch('/{id}/complete', [App\Http\Controllers\PutawayController::class, 'complete']);
                Route::patch('/{id}/cancel', [App\Http\Controllers\PutawayController::class, 'cancel']);
                Route::post('/{id}/scan-bin', [App\Http\Controllers\PutawayController::class, 'scanBin']);
            });

            // ── Stock Query API ─────────────────────────────────────────────────
            // Read-only endpoints. All mutations go via domain controllers above.
            // Buckets: QC_HOLD | PUTAWAY_PENDING | AVAILABLE | RESERVED | BLOCKED | CONSUMED | SHIPPED
            Route::prefix('stock')->group(function () {
                // ATP check: net available qty = on_hand - reserved (AVAILABLE bucket only)
                Route::get('/available/{materialId}', [App\Http\Controllers\StockController::class, 'available']);

                // Full snapshot: all buckets + by_bin breakdown for a material
                Route::get('/snapshot/{materialId}', [App\Http\Controllers\StockController::class, 'snapshot']);

                // Audit trail: every inventory_transaction for a material (most recent first)
                Route::get('/history/{materialId}', [App\Http\Controllers\StockController::class, 'history']);

                // Warehouse dashboard: all materials with stock in a warehouse, grouped by bucket
                Route::get('/warehouse/{warehouseId}', [App\Http\Controllers\StockController::class, 'warehouseSummary']);

                // Bucket drill-down: all balance rows for a material in one bucket
                Route::get('/bucket/{materialId}/{bucket}', [App\Http\Controllers\StockController::class, 'byBucket']);
            });
        });

        // BOM (Bill of Materials) Endpoints (Master Data)
        Route::middleware(['check.module.permission:ADMIN'])->group(function () {
            // BOM Header
            Route::prefix('bom-headers')->group(function () {
                Route::get('/', [App\Http\Controllers\BOMHeaderController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\BOMHeaderController::class, 'show']);
                Route::post('/', [App\Http\Controllers\BOMHeaderController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\BOMHeaderController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\BOMHeaderController::class, 'destroy']);
            });

            // Allow BOM users to access Materials and UOMs for BOM creation
            Route::prefix('materials')->group(function () {
                Route::get('/', [App\Http\Controllers\MaterialController::class, 'index']);
            });

            // BOM Details
            Route::prefix('bom-details')->group(function () {
                Route::get('/', [App\Http\Controllers\BOMDetailController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\BOMDetailController::class, 'show']);
                Route::post('/', [App\Http\Controllers\BOMDetailController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\BOMDetailController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\BOMDetailController::class, 'destroy']);
            });
        });

        // ── PRODUCTION Module ─────────────────────────────────────────────
        Route::middleware(['check.module.permission:PRODUCTION'])->group(function () {
            // Production Orders & Material Issue Requests
            Route::prefix('production-orders')->group(function () {
                Route::get('/', [App\Http\Controllers\ProductionOrderController::class, 'index']);
                Route::post('/', [App\Http\Controllers\ProductionOrderController::class, 'store']);
                Route::get('/{id}', [App\Http\Controllers\ProductionOrderController::class, 'show']);
                Route::post('/{id}/start', [App\Http\Controllers\ProductionOrderController::class, 'start']);
                Route::post('/{id}/confirm-fg', [App\Http\Controllers\ProductionOrderController::class, 'confirmFG']);
                Route::get('/{id}/fg-sessions', [App\Http\Controllers\ProductionOrderController::class, 'fgSessions']);
                Route::get('/{id}/variance', [App\Http\Controllers\ProductionOrderController::class, 'variance']);
            });

            Route::prefix('material-issue-requests')->group(function () {
                Route::get('/', [App\Http\Controllers\MaterialIssueRequestController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\MaterialIssueRequestController::class, 'show']);
                Route::post('/{id}/approve', [App\Http\Controllers\MaterialIssueRequestController::class, 'approve']);
                Route::post('/{id}/reject', [App\Http\Controllers\MaterialIssueRequestController::class, 'reject']);
                Route::post('/{id}/lines/{lineId}/scan', [App\Http\Controllers\MaterialIssueRequestController::class, 'scan']);
            });

            Route::prefix('packing-orders')->group(function () {
                Route::get('/', [App\Http\Controllers\PackingOrderController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\PackingOrderController::class, 'show']);
                Route::post('/', [App\Http\Controllers\PackingOrderController::class, 'store']);
                Route::post('/{id}/cartons', [App\Http\Controllers\PackingOrderController::class, 'createCarton']);
                Route::post('/{id}/cartons/{cartonId}/scan', [App\Http\Controllers\PackingOrderController::class, 'scanIntoCarton']);
                Route::post('/{id}/cartons/{cartonId}/seal', [App\Http\Controllers\PackingOrderController::class, 'sealCarton']);
                Route::post('/{id}/complete', [App\Http\Controllers\PackingOrderController::class, 'complete']);
            });
        });


        // ── Lookup routes for Sales Order creation (no module-permission gate) ──
        Route::get('/lookup/customers', function (\Illuminate\Http\Request $request) {
            // Switch to tenant DB (normally done by CheckModulePermission, must do manually here)
            $tenantDb = $request->input('tenant_db_name');
            if ($tenantDb) {
                config(['database.connections.tenant.database' => $tenantDb]);
                \DB::purge('tenant');
                \DB::reconnect('tenant');
            }

            $customers = \App\Models\Tenant\Customer::where('is_active', true)
                ->when($request->filled('search'), fn($q) => $q->where('customer_name', 'like', '%'.$request->search.'%'))
                ->orderBy('customer_name')
                ->get(['id', 'customer_name', 'customer_code', 'phone', 'email'])
                ->map(fn($c) => ['id' => 'c_'.$c->id, 'label' => $c->customer_name, 'sub' => $c->customer_code, 'source' => 'customer', 'raw_id' => $c->id]);

            $users = \App\Models\Tenant\User::where('is_active', true)
                ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                    $q2->where('first_name', 'like', '%'.$request->search.'%')
                       ->orWhere('last_name', 'like', '%'.$request->search.'%')
                       ->orWhere('email', 'like', '%'.$request->search.'%')
                ))
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email', 'employee_code'])
                ->map(fn($u) => ['id' => 'u_'.$u->id, 'label' => trim($u->first_name.' '.$u->last_name), 'sub' => $u->email, 'source' => 'user', 'raw_id' => $u->id]);

            $merged = $customers->concat($users)->sortBy('label')->values();
            return response()->json(['success' => true, 'data' => $merged]);
        });

        Route::post('/lookup/customers', function (\Illuminate\Http\Request $request) {
            $tenantDb = $request->input('tenant_db_name');
            if ($tenantDb) {
                config(['database.connections.tenant.database' => $tenantDb]);
                \DB::purge('tenant');
                \DB::reconnect('tenant');
            }
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'customer_name' => 'required|string|max:200',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            $customer = \App\Models\Tenant\Customer::create([
                'customer_name' => $request->customer_name,
                'customer_code' => \App\Models\Tenant\Customer::generateCode(),
                'created_by'    => $request->input('auth_user_id'),
            ]);
            return response()->json(['success' => true, 'data' => $customer], 201);
        });

        Route::get('/lookup/products', function (\Illuminate\Http\Request $request) {
            $tenantDb = $request->input('tenant_db_name');
            if ($tenantDb) {
                config(['database.connections.tenant.database' => $tenantDb]);
                \DB::purge('tenant');
                \DB::reconnect('tenant');
            }
            $products = \App\Models\Tenant\Product::where('is_active', true)
                ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                    $q2->where('product_name', 'like', '%'.$request->search.'%')
                       ->orWhere('product_code', 'like', '%'.$request->search.'%')
                ))
                ->orderBy('product_name')
                ->get(['id', 'product_code', 'product_name', 'pack_size', 'pack_uom_id', 'standard_cost', 'mrp']);
            return response()->json(['success' => true, 'data' => $products]);
        });

        Route::get('/lookup/uoms', function (\Illuminate\Http\Request $request) {
            $tenantDb = $request->input('tenant_db_name');
            if ($tenantDb) {
                config(['database.connections.tenant.database' => $tenantDb]);
                \DB::purge('tenant');
                \DB::reconnect('tenant');
            }
            return response()->json(['success' => true, 'data' => \App\Models\Tenant\UOM::where('is_active', true)->orderBy('uom_name')->get(['id', 'uom_code', 'uom_name'])]);
        });

        Route::get('/lookup/stock-bins', function (\Illuminate\Http\Request $request) {
            $tenantDb = $request->input('tenant_db_name');
            if ($tenantDb) {
                config(['database.connections.tenant.database' => $tenantDb]);
                \DB::purge('tenant');
                \DB::reconnect('tenant');
            }
            $productId = $request->input('product_id');
            if (!$productId) {
                return response()->json(['success' => false, 'message' => 'product_id required'], 422);
            }
            $bins = \DB::connection('tenant')
                ->table('stock_balances as sb')
                ->join('bin_locations as bl', 'sb.bin_id', '=', 'bl.id')
                ->join('warehouse_master as wm', 'sb.warehouse_id', '=', 'wm.id')
                ->where('sb.product_id', $productId)
                ->where('sb.bucket', 'AVAILABLE')
                ->whereRaw('(sb.qty_on_hand - sb.qty_reserved) > 0')
                ->select(
                    'bl.bin_code',
                    'wm.warehouse_name',
                    \DB::raw('(sb.qty_on_hand - sb.qty_reserved) as qty_available')
                )
                ->orderByDesc('qty_available')
                ->get();
            return response()->json(['success' => true, 'data' => $bins]);
        });

        // Sales Order Endpoints (Outward Flow)
        // Status Flow: DRAFT → CONFIRMED → STOCK_CHECKED → PICKING → PACKED → DISPATCHED → DELIVERED
        Route::middleware(['check.module.permission:SALES'])->group(function () {
            // Customer Master (accessible to sales users)
            Route::prefix('customers')->group(function () {
                Route::get('/', [App\Http\Controllers\CustomerController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\CustomerController::class, 'show']);
                Route::post('/', [App\Http\Controllers\CustomerController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\CustomerController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\CustomerController::class, 'destroy']);
            });

            // Sales Orders
            Route::prefix('sales-orders')->group(function () {
                Route::get('/dashboard-stats', [App\Http\Controllers\SalesOrderController::class, 'dashboardStats']);
                Route::get('/', [App\Http\Controllers\SalesOrderController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\SalesOrderController::class, 'show']);
                Route::post('/', [App\Http\Controllers\SalesOrderController::class, 'store']);
                Route::patch('/{id}/confirm', [App\Http\Controllers\SalesOrderController::class, 'confirm']);
                Route::patch('/{id}/check-stock', [App\Http\Controllers\SalesOrderController::class, 'checkStock']);
                Route::patch('/{id}/cancel', [App\Http\Controllers\SalesOrderController::class, 'cancel']);
                Route::post('/{id}/generate-picklist', [App\Http\Controllers\SalesOrderController::class, 'generatePicklist']);
                Route::patch('/{id}/dispatch', [App\Http\Controllers\SalesOrderController::class, 'dispatch']);
            });
        });

        // ── SALES Module ─────────────────────────────────────────────────
        Route::middleware(['check.module.permission:SALES'])->group(function () {
            Route::prefix('sales')->group(function () {
                Route::get('/dashboard-stats', [App\Http\Controllers\SalesOrderController::class, 'dashboardStats']);
                Route::get('/orders', [App\Http\Controllers\SalesOrderController::class, 'index']);
                Route::get('/orders/{id}', [App\Http\Controllers\SalesOrderController::class, 'show']);
                Route::post('/orders', [App\Http\Controllers\SalesOrderController::class, 'store']);
                Route::patch('/orders/{id}/confirm', [App\Http\Controllers\SalesOrderController::class, 'confirm']);
                Route::patch('/orders/{id}/check-stock', [App\Http\Controllers\SalesOrderController::class, 'checkStock']);
                Route::patch('/orders/{id}/cancel', [App\Http\Controllers\SalesOrderController::class, 'cancel']);
                Route::post('/orders/{id}/generate-picklist', [App\Http\Controllers\SalesOrderController::class, 'generatePicklist']);
                Route::patch('/orders/{id}/dispatch', [App\Http\Controllers\SalesOrderController::class, 'dispatch']);
            });
        });

        // ── CUSTOMER Module ───────────────────────────────────────────────
        Route::middleware(['check.module.permission:CUSTOMER'])->group(function () {
            Route::prefix('customer-mgmt')->group(function () {
                Route::get('/', [App\Http\Controllers\CustomerController::class, 'index']);
                Route::get('/{id}', [App\Http\Controllers\CustomerController::class, 'show']);
                Route::post('/', [App\Http\Controllers\CustomerController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\CustomerController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\CustomerController::class, 'destroy']);
            });
        });

        // ── MAINTENANCE Module ────────────────────────────────────────────
        Route::middleware(['check.module.permission:MAINTENANCE'])->group(function () {
            Route::prefix('maintenance')->group(function () {
                // Work Orders
                Route::get('/work-orders', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'Work orders endpoint — coming soon']));
                Route::post('/work-orders', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'Work order created — coming soon'], 201));
                Route::patch('/work-orders/{id}/close', fn() => response()->json(['success' => true, 'message' => 'Work order closed — coming soon']));
                // Assets
                Route::get('/assets', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'Assets endpoint — coming soon']));
                Route::post('/assets', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'Asset created — coming soon'], 201));
                // PM Schedule
                Route::get('/schedule', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'PM schedule endpoint — coming soon']));
                // Spare Parts
                Route::get('/spare-parts', fn() => response()->json(['success' => true, 'data' => [], 'message' => 'Spare parts endpoint — coming soon']));
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
