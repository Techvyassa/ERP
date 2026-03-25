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


        // Department management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('departments')->group(function () {
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
            Route::get('/import/template', [App\Http\Controllers\UserController::class, 'downloadTemplate']);
            Route::post('/import', [App\Http\Controllers\UserController::class, 'importCSV']);
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

        // UOM Master management endpoints (with RBAC middleware for SETTINGS module)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('uoms')->group(function () {
            Route::get('/', [App\Http\Controllers\UOMController::class, 'index']);
            Route::get('/barcode', [App\Http\Controllers\UOMController::class, 'barcode']);
            Route::get('/{id}', [App\Http\Controllers\UOMController::class, 'show']);
            Route::post('/', [App\Http\Controllers\UOMController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\UOMController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\UOMController::class, 'destroy']);
        });

        // INVENTORY Management Endpoints
        Route::middleware(['check.module.permission:INVENTORY'])->group(function () {
            // Warehouse Master
            Route::prefix('warehouses')->group(function () {
                Route::get('/', [App\Http\Controllers\WarehouseController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\WarehouseController::class, 'barcode']);
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
                Route::put('/{id}', [App\Http\Controllers\MaterialController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\MaterialController::class, 'destroy']); // Deactivate
            });

            // Product Master
            Route::prefix('products')->group(function () {
                Route::get('/', [App\Http\Controllers\ProductController::class, 'index']);
                Route::get('/barcode', [App\Http\Controllers\ProductController::class, 'barcode']);
                Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show']);
                Route::post('/', [App\Http\Controllers\ProductController::class, 'store']);
                Route::put('/{id}', [App\Http\Controllers\ProductController::class, 'update']);
                Route::delete('/{id}', [App\Http\Controllers\ProductController::class, 'destroy']); // Deactivate
            });
        });

        // PROCUREMENT (Vendor & Procurement) Endpoints
        // Roles: PROC_EXE (create/edit vendors), PROC_MGR (approve vendors), ADMIN (all)
        Route::middleware(['check.module.permission:PO'])->group(function () {
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
        Route::middleware(['check.module.permission:PO'])->group(function () {
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
        Route::middleware(['check.module.permission:ASN'])->group(function () {
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
        Route::middleware(['check.module.permission:GATE_ENTRY'])->group(function () {
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
        Route::middleware(['check.module.permission:MR_GRN'])->group(function () {

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

        // QC Test Type Master (SETTINGS permission)
        Route::middleware(['check.module.permission:SETTINGS'])->prefix('qc-test-types')->group(function () {
            Route::get('/', [App\Http\Controllers\QCTestTypeController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'show']);
            Route::post('/', [App\Http\Controllers\QCTestTypeController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\QCTestTypeController::class, 'destroy']);
        });

        Route::middleware(['check.module.permission:SETTINGS'])->prefix('qc-parameters')->group(function () {
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
        Route::middleware(['check.module.permission:STOCK'])->group(function () {
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

        // BOM (Bill of Materials) Endpoints
        Route::middleware(['check.module.permission:BOM'])->group(function () {
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
