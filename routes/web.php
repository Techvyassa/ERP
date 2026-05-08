<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceDashboardController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceApprovalController;
use App\Http\Controllers\MaintenanceAssignmentController;
use App\Http\Controllers\MaintenanceWorkOrderController;
use App\Http\Controllers\MaintenanceMaterialRequestController;
use App\Http\Controllers\MaintenanceClosureController;
use App\Http\Controllers\MaintenanceAssetController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MaintenanceSparePartController;
use App\Http\Controllers\MaintenanceProcurementController;
use App\Http\Controllers\MaintenanceStockController;
use App\Models\Control\Organization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Public Routes: Landing, Pricing, Auth
| Protected Routes: Dashboard, Control Panel, Tenant Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// VENDOR PORTAL (Public, token-based)
// ============================================================================
Route::get('/vendor/po/{token}', [App\Http\Controllers\VendorPortalController::class, 'viewPO'])->name('vendor.po.view');
Route::get('/vendor/pr/{token}', [App\Http\Controllers\VendorPortalController::class, 'viewPR'])->name('vendor.pr.view');
Route::post('/vendor/po/{token}/acknowledge', [App\Http\Controllers\VendorPortalController::class, 'acknowledge'])->name('vendor.po.acknowledge');
Route::post('/vendor/po/{token}/vendor-approve', [App\Http\Controllers\VendorPortalController::class, 'vendorApprove'])->name('vendor.po.vendor-approve');
Route::post('/vendor/po/{token}/vendor-reject', [App\Http\Controllers\VendorPortalController::class, 'vendorReject'])->name('vendor.po.vendor-reject');
Route::post('/vendor/po/{token}/upload-asn', [App\Http\Controllers\VendorPortalController::class, 'uploadASN'])->name('vendor.po.upload-asn');

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

// Landing & Marketing Pages
Route::get('/', [PublicController::class, 'landing'])->name('home');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

// Authentication Pages
Route::get('/register', [PublicController::class, 'register'])->name('register');
Route::get('/login', [PublicController::class, 'login'])->name('login');
Route::get('/forgot-password', fn() => view('auth.forgot-password'))->name('password.request');
Route::get('/reset-password', fn() => view('auth.reset-password'))->name('password.reset');

// Google OAuth
Route::get('/auth/google', function () {
    return redirect()->away('https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'redirect_uri' => url('/auth/google/callback'),
        'response_type' => 'code',
        'scope' => 'email profile',
    ]));
})->name('auth.google');

Route::get('/auth/google/callback', function () {
    return redirect('/'); // Redirect back to home or a logical entry point after OAuth
})->name('auth.google.callback');

// ============================================================================
// PROTECTED ROUTES (Require Authentication)
// ============================================================================

Route::middleware(['web.jwt'])->group(function () {

    // ------------------------------------------------------------------------
    // MAIN DASHBOARD (Entry Point)
    // ------------------------------------------------------------------------


    // ------------------------------------------------------------------------
    // SUPER ADMIN / CONTROL PANEL ROUTES
    // ------------------------------------------------------------------------
    Route::prefix('control')->name('control.')->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return view('control.dashboard');
        })->name('dashboard');

        // Organizations Management
        Route::get('/organizations', function () {
            return view('control.organizations.index');
        })->name('organizations.index');

        // Subscriptions Management
        Route::get('/subscriptions', function () {
            return view('control.subscriptions.index');
        })->name('subscriptions.index');

        // Subscription Plans Management
        Route::get('/plans', function () {
            return view('control.plans.index');
        })->name('plans.index');

        // Payments Management
        Route::get('/payments', function () {
            return view('control.payments.index');
        })->name('payments.index');

        // Feature Control
        Route::get('/features', function () {
            return view('control.features.index');
        })->name('features.index');

        // System Settings
        Route::get('/settings', function () {
            return view('control.settings');
        })->name('settings');

        // Admin Profile
        Route::get('/profile', function () {
            return view('control.profile');
        })->name('profile');
    });
});

// ------------------------------------------------------------------------
// TENANT ROUTES (Organization-specific)
// Pattern: /org/{org_slug}/...
// ------------------------------------------------------------------------
Route::prefix('org/{org_slug}')->middleware(['resolve.tenant', 'switch.tenant.db'])->name('tenant.')->group(function () {
    // Helper function to get organization and tenant type
    $getOrg = function ($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        $tenantType = 'path';
        return compact('org', 'tenantType');
    };

    // ====================================================================
    // PUBLIC TENANT ROUTES (Logins)
    // ====================================================================

    // Specialized Department Logins
    Route::get('/procurement/login', [PublicController::class, 'loginProcurement'])->name('procurement.login');
    Route::get('/warehouse/login', [PublicController::class, 'loginWarehouse'])->name('warehouse.login');
    Route::get('/quality/login', [PublicController::class, 'loginQuality'])->name('quality.login');
    Route::get('/security/login', [PublicController::class, 'loginSecurity'])->name('security.login');
    Route::get('/admin/login', [PublicController::class, 'loginAdmin'])->name('admin.login');
    Route::get('/production/login', [PublicController::class, 'loginProduction'])->name('production.login');
    Route::get('/sales/login', [PublicController::class, 'loginSales'])->name('sales.login');
    Route::get('/customer/login', [PublicController::class, 'loginCustomer'])->name('customer.login');
    Route::get('/maintenance/login', [PublicController::class, 'loginMaintenance'])->name('maintenance.login');

    // ====================================================================
    // PROTECTED TENANT ROUTES (Require Login)
    // ====================================================================
    Route::middleware(['web.jwt'])->group(function () use ($getOrg) {

        // ====================================================================
        // DASHBOARD & SETUP
        // ====================================================================

        // Main Tenant Dashboard
        Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('dashboard');

        // Profile Completion
        Route::get('/profile-completion', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.profile-completion', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('profile-completion');

        // Master Data Setup Dashboard
        Route::get('/master-setup', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('master-setup');

        // ====================================================================
        // MASTERS & ORGANIZATION MANAGEMENT (Admin Only)
        // ====================================================================
        Route::middleware(['check.module.permission:ADMIN'])->group(function () use ($getOrg) {

            // --- Category Dashboards ---
            Route::get('/organization-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('organization-dashboard');

            Route::get('/inventory-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('inventory-dashboard');

            Route::get('/vendor-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('vendor-dashboard');

            Route::get('/tax-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.tax.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('tax-dashboard');

            Route::get('/production-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('production-dashboard');

            Route::get('/quality-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.qc.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('quality-dashboard');

            Route::get('/customer-dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.customer.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('customer-dashboard');

            // --- Organization Masters ---
            Route::get('/departments', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.departments.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('departments.index');

            Route::get('/departments/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.departments.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'departmentId' => $id
                ]);
            })->name('departments.edit');

            Route::get('/roles', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.roles.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('roles.index');

            Route::get('/roles/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.roles.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('roles.create');

            Route::get('/users', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.users.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('users.index');

            Route::get('/users/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.users.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('users.create');

            Route::get('/users/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.users.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'userId' => $id
                ]);
            })->name('users.edit');

            Route::get('/approval-matrix', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.organization.approval-matrix.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('approval-matrix.index');

            // ====================================================================
            // INVENTORY MASTERS
            // ====================================================================

            Route::get('/materials', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.materials.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('materials.index');

            Route::get('/products', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.products.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('products.index');

            Route::get('/warehouses', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.warehouses.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('warehouses.index');

            Route::get('/bin-locations', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.bin-locations.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('bin-locations.index');

            Route::get('/uom', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.inventory.uom.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('uom.index');

            // ====================================================================
            // VENDOR MASTERS
            // ====================================================================

            Route::get('/vendors', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendors.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendors.index');

            Route::get('/vendors/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendors.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendors.create');

            Route::get('/vendors/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendors.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'vendorId' => $id
                ]);
            })->name('vendors.edit');

            Route::get('/vendor-contacts', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendor-contacts.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendor-contacts.index');

            Route::get('/vendor-contacts/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendor-contacts.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'contactId' => $id
                ]);
            })->name('vendor-contacts.edit');

            Route::get('/vendor-material-map', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendor-material-map.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendor-material-map.index');

            Route::get('/vendor-material-map/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendor-material-map.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendor-material-map.create');

            Route::get('/vendor-material-map/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.vendor.vendor-material-map.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'id' => $id
                ]);
            })->name('vendor-material-map.edit');

            // ====================================================================
            // TAX MASTERS
            // ====================================================================

            Route::get('/hsn-codes', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.tax.hsn-codes.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('hsn-codes.index');

            Route::get('/gst-taxes', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.tax.gst-taxes.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('gst-taxes.index');

            Route::get('/currency', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.tax.currency.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('currency.index');

            // ====================================================================
            // BOM MASTERS
            // ====================================================================

            Route::get('/bom-header', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('bom-header.index');

            Route::get('/bom-header/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('bom-header.create');

            Route::get('/bom-header/multiple-create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.multiple-create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('bom-header.multiple-create');

            Route::get('/bom-header/bulk-upload', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.bulk-upload', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('bom-header.bulk-upload');

            Route::get('/bom-header/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'bomId' => $id
                ]);
            })->name('bom-header.edit');

            Route::get('/bom-header/{id}/view', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.bom.bom-header.view', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'bomId' => $id
                ]);
            })->name('bom-header.view');
        }); // End of ADMIN Masters Group



        // ====================================================================
        // DEPARTMENTAL PORTALS (Organization-specific)
        // ====================================================================

        // Procurement Portal
        Route::prefix('procurement')->middleware(['check.module.permission:PROCUREMENT'])->name('procurement.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('dashboard.procurement', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            Route::get('/purchase-requisition', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.purchase-requisition.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('purchase-requisition');

            Route::get('/purchase-requisition/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.purchase-requisition.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('purchase-requisition.create');

            Route::get('/purchase-requisition/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.purchase-requisition.edit', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'prId' => $id,
                ]);
            })->name('purchase-requisition.edit');

            Route::get('/purchase-orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.purchase-orders.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('purchase-orders');

            Route::get('/purchase-orders/create', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.purchase-orders.create', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('purchase-orders.create');

            Route::get('/vendors', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.vendors.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('vendors');

            Route::get('/asn', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.asn.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('asn');

            Route::get('/po-approval', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.po-approval.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('po-approval');

            Route::get('/pr-approval', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.pr-approval.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('pr-approval');

            Route::get('/quotation-comparison', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.quotation-comparison.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('quotation-comparison');

            Route::get('/quotation-comparison/{prNumber}', function ($orgSlug, $prNumber) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.quotation-comparison.compare', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'prNumber' => $prNumber
                ]);
            })->name('quotation-comparison.compare');
        });

        // Security Department Portal
        Route::prefix('security')->middleware(['check.module.permission:SECURITY'])->name('security.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.security.dashboard', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            Route::get('/gate-entry', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.security.gate-entry.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('gate-entry');

            Route::get('/dispatch', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.security.dispatch', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dispatch');
        });

        // Warehouse/store Department Portal
        Route::prefix('warehouse')->middleware(['check.module.permission:STORE'])->name('warehouse.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.dashboard', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            // Material Receipts route removed — new flow: Gate Entry → GRN auto-created
            // Route::get('/receipts', function ($orgSlug) use ($getOrg) {
            //     extract($getOrg($orgSlug));
            //     return view('tenant.warehouse.material-receipts.index', [
            //         'organization' => $org,
            //         'tenantType' => $tenantType
            //     ]);
            // })->name('receipts');

            Route::get('/grn', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.grn.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('grn');

            Route::get('/putaway', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.putaway.index', data: [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('putaway');

            Route::get('/putaway/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.putaway.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'taskId' => $id
                ]);
            })->name('putaway.show');

            // Stock Management Dashboard
            Route::get('/stock-management', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.stock-management', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('stock-management');

            Route::get('/mir', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.mir.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('mir.index');

            Route::get('/mir/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.mir.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'mirId' => $id
                ]);
            })->name('mir.show');

            // Route::get('/sales-orders', function ($orgSlug) use ($getOrg) {
            //     extract($getOrg($orgSlug));
            //     return view('tenant.warehouse.sales-orders.index', [
            //         'organization' => $org,
            //         'tenantType' => $tenantType
            //     ]);
            // })->name('sales-orders');

            Route::get('/outward', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.warehouse.outward', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('outward');
        });

        // Quality Portal
        Route::prefix('quality')->middleware(['check.module.permission:QC'])->name('quality.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.quality.dashboard', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            Route::get('/inspections', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.quality.inspections.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('inspections');

            Route::get('/inspections/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.quality.inspections.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'lotId' => $id
                ]);
            })->name('inspections.show');

            Route::get('/decisions', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.quality.decisions.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('decisions');

            Route::get('/reports', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.quality.reports.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('reports');

            // QC Masters: Test Types
            Route::get('/qc-test-types', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.qc.qc-test-types.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('qc-test-types');

            Route::get('/qc-parameters', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.masters.qc.qc-parameters.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('qc-parameters');
        });

        // ====================================================================
        // Production Portal
        // ====================================================================
        Route::prefix('production')->middleware(['check.module.permission:PRODUCTION'])->name('production.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.dashboard', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            // Production Requests - Material list before production order
            Route::get('/requests', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.requests.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('requests.index');

            Route::get('/requests/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.requests.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'requestId' => $id
                ]);
            })->name('requests.show');

            // Production Orders
            Route::get('/orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.orders.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('orders');

            Route::get('/orders/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.orders.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'orderId' => $id
                ]);
            })->name('orders.show');

            // Production Floor Receiving list — orders pending floor receipt confirmation
            // Production Floor Receiving list — orders pending floor receipt confirmation
            Route::get('/receiving', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.receiving.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('receiving.index');

            // Production Floor Receiving — confirm materials arrived at workstation
            Route::get('/orders/{id}/receiving', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.orders.receiving', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'orderId' => $id
                ]);
            })->name('orders.receiving');

            // Batch Runs — Independent execution units
            Route::get('/batch-runs', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.batch-runs.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('batch-runs.index');

            Route::get('/batch-runs/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.batch-runs.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'batchRunId' => $id
                ]);
            })->name('batch-runs.show');

            // Material Issue Requests (MIR) — Per batch run
            Route::get('/material-issue-requests', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.mir.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('mir.index');

            Route::get('/material-issue-requests/{id}', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.mir.show', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'mirId' => $id
                ]);
            })->name('mir.show');

            // Production Floor Receiving — Confirm materials at workstation
            Route::get('/batch-runs/{id}/receiving', function ($orgSlug, $id) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.batch-runs.receiving', [
                    'organization' => $org,
                    'tenantType' => $tenantType,
                    'batchRunId' => $id
                ]);
            })->name('batch-runs.receiving');

            Route::get('/packing', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.packing.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('packing');

            Route::get('/fg-confirmation', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.fg-confirmation.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('fg-confirmation');

            Route::get('/mir', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.mir.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('mir');

            // Production Planning
            Route::prefix('planning')->name('planning.')->group(function () use ($getOrg) {
                Route::get('/forecast', function ($orgSlug) use ($getOrg) {
                    extract($getOrg($orgSlug));
                    return view('tenant.production.planning.forecast', [
                        'organization' => $org,
                        'tenantType' => $tenantType
                    ]);
                })->name('forecast');

                Route::get('/gap-analysis', function ($orgSlug) use ($getOrg) {
                    extract($getOrg($orgSlug));
                    return view('tenant.production.planning.gap-analysis', [
                        'organization' => $org,
                        'tenantType' => $tenantType
                    ]);
                })->name('gap-analysis');

                Route::get('/capacity', function ($orgSlug) use ($getOrg) {
                    extract($getOrg($orgSlug));
                    return view('tenant.production.planning.capacity', [
                        'organization' => $org,
                        'tenantType' => $tenantType
                    ]);
                })->name('capacity');
            });
        });

        // ====================================================================
        // SALES PORTAL
        // ====================================================================
        Route::prefix('sales')->middleware(['check.module.permission:SALES'])->name('sales.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('dashboard');

            Route::get('/approval', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.approval.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('approval');

            Route::get('/picklist', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.picklist.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('picklist');

            Route::get('/orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.orders.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('orders');

            Route::get('/customers', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.customers.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('customers');

            Route::get('/invoices', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.invoices.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('invoices');

            Route::get('/dispatch', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.dispatch.index', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('dispatch');
        });

        // ====================================================================
        // CUSTOMER PORTAL
        // ====================================================================
        Route::prefix('customer')->middleware(['check.module.permission:CUSTOMER'])->name('customer.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.customer.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('dashboard');

            Route::get('/list', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.customer.list', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('list');

            Route::get('/orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.customer.orders', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('orders');

            Route::get('/complaints', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.customer.complaints', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('complaints');

            Route::get('/ledger', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.customer.ledger', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('ledger');
        });

        // ====================================================================
        // MAINTENANCE PORTAL
        // ====================================================================
        Route::prefix('maintenance')->middleware(['check.module.permission:MAINTENANCE'])->name('maintenance.')->group(function () {

            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Maintenance\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/work-orders-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'workOrdersJson'])->name('dashboard.work-orders-json');
            Route::get('/dashboard/assets-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'assetsJson'])->name('dashboard.assets-json');
            Route::get('/dashboard/pm-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'pmJson'])->name('dashboard.pm-json');
            Route::get('/dashboard/low-stock-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'lowStockJson'])->name('dashboard.low-stock-json');
            Route::get('/dashboard/material-requests-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'materialRequestsJson'])->name('dashboard.material-requests-json');
            Route::get('/dashboard/requests-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'requestsJson'])->name('dashboard.requests-json');

            // Requests
            Route::get('/requests', [\App\Http\Controllers\Maintenance\RequestController::class, 'index'])->name('requests');
            Route::post('/requests', [\App\Http\Controllers\Maintenance\RequestController::class, 'store'])->name('requests.store');

            // Approvals
            Route::get('/approvals', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'index'])->name('approvals');
            Route::post('/approvals/{id}/approve', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{id}/reject', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'reject'])->name('approvals.reject');

            // Assignments
            Route::get('/assignments', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'index'])->name('assignments');
            Route::post('/assignments', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'store'])->name('assignments.store');
            Route::post('/assignments/{wo}/update-status', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'updateStatus'])->name('assignments.update-status');

            // Work Orders
            Route::get('/work-orders', [\App\Http\Controllers\Maintenance\WorkOrderController::class, 'index'])->name('work-orders');

            // Material Requests
            Route::get('/material-requests', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'index'])->name('material-requests');
            Route::post('/material-requests', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'store'])->name('material-requests.store');
            Route::post('/material-requests/{id}/issue', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'issue'])->name('material-requests.issue');
            Route::post('/material-requests/{id}/raise-po', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'raisePo'])->name('material-requests.raise-po');
            Route::post('/material-requests/raise-po-direct', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'raisePoDirect'])->name('material-requests.raise-po-direct');

            // Closure
            Route::get('/closure', [\App\Http\Controllers\Maintenance\ClosureController::class, 'index'])->name('closure');
            Route::post('/closure/{wo}/close', [\App\Http\Controllers\Maintenance\ClosureController::class, 'close'])->name('closure.close');

            // Assets
            Route::get('/assets', [\App\Http\Controllers\Maintenance\AssetController::class, 'index'])->name('assets');
            Route::post('/assets', [\App\Http\Controllers\Maintenance\AssetController::class, 'store'])->name('assets.store');

            // Schedule
            Route::get('/schedule', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'index'])->name('schedule');
            Route::post('/schedule', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'store'])->name('schedule.store');
            Route::post('/schedule/{id}/done', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'done'])->name('schedule.done');

            // Spare Parts
            Route::get('/spare-parts', [\App\Http\Controllers\Maintenance\SparePartController::class, 'index'])->name('spare-parts');
            Route::post('/spare-parts', [\App\Http\Controllers\Maintenance\SparePartController::class, 'store'])->name('spare-parts.store');
            Route::post('/spare-parts/{code}/issue', [\App\Http\Controllers\Maintenance\SparePartController::class, 'issue'])->name('spare-parts.issue');
            Route::post('/spare-parts/{code}/receive', [\App\Http\Controllers\Maintenance\SparePartController::class, 'receive'])->name('spare-parts.receive');

            // Procurement
            Route::get('/procurement', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'index'])->name('procurement');
            Route::get('/procurement/orders-json', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'ordersJson'])->name('procurement.orders-json');
            Route::post('/procurement', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'store'])->name('procurement.store');
            Route::post('/procurement/{id}/mark-ordered', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'markOrdered'])->name('procurement.mark-ordered');
            Route::post('/procurement/{id}/receive', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'receive'])->name('procurement.receive');

            // Stock Management
            Route::get('/stock-management', [\App\Http\Controllers\Maintenance\StockManagementController::class, 'index'])->name('stock-management');
            Route::post('/stock-management/{id}/adjust', [\App\Http\Controllers\Maintenance\StockManagementController::class, 'adjust'])->name('stock-management.adjust');
        });

        // ====================================================================        // OTHER PAGES
        // ====================================================================

        Route::get('/reports', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.reports.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('reports.index');

        Route::get('/settings', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.settings', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('settings');

        Route::get('/profile', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.profile', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('profile');
    });
});

// Logout — outside auth middleware so it always works regardless of token state
Route::post('/logout', [\App\Http\Controllers\LogoutController::class, 'logout'])->name('logout');

// ============================================================================
// TEST & DEBUG ROUTES (Remove in production)
// ============================================================================

Route::get('/test-firebase', function () {
    return view('test-firebase');
})->name('test.firebase');

Route::get('/test-cookie', function () {
    return view('test-cookie');
})->name('test.cookie');

Route::get('/test-email-template', function () {
    return view('emails.welcome', [
        'firstName' => 'John',
        'organizationName' => 'Acme Manufacturing Ltd.',
        'email' => 'john@acme.com',
        'tempPassword' => 'TempPass123!',
        'loginUrl' => url('/login'),
    ]);
})->name('emails.template');

Route::get('/test-tenant', function () {
    $host = request()->getHost();
    $mainDomain = config('app.domain', 'localhost');

    $hostParts = explode(':', $host)[0];
    $pattern = '/^(.+)\.' . preg_quote($mainDomain, '/') . '$/';
    $subdomain = null;
    if (preg_match($pattern, $hostParts, $matches)) {
        $subdomain = $matches[1];
    }

    $organizations = Organization::all();

    return view('test-tenant', [
        'host' => $host,
        'mainDomain' => $mainDomain,
        'subdomain' => $subdomain,
        'organizations' => $organizations,
        'config' => [
            'app_domain' => config('app.domain'),
            'tenant_mode' => config('tenant.default_mode'),
            'allow_both' => config('tenant.allow_both_modes'),
        ]
    ]);
})->name('test.tenant');
