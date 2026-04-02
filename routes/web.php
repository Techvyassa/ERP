<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
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
    return redirect()->route('dashboard');
})->name('auth.google.callback');

// ============================================================================
// PROTECTED ROUTES (Require Authentication)
// ============================================================================

Route::middleware(['web.jwt'])->group(function () {

    // ------------------------------------------------------------------------
    // MAIN DASHBOARD (Entry Point)
    // ------------------------------------------------------------------------
    Route::get('/dashboard', function () {
        return view('dashboard.main');
    })->name('dashboard');

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

    // ------------------------------------------------------------------------
    // TENANT ROUTES (Organization-specific)
    // Pattern: /org/{org_slug}/...
    // ------------------------------------------------------------------------
    Route::prefix('org/{org_slug}')->name('tenant.')->group(function () {
        // Helper function to get organization and tenant type
        $getOrg = function ($orgSlug) {
            $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
            $tenantType = 'path';
            return compact('org', 'tenantType');
        };

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
        // CATEGORY DASHBOARDS
        // ====================================================================

        // Organization & Access Control Dashboard
        Route::get('/organization-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.organization.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('organization-dashboard');

        // Inventory & Material Management Dashboard
        Route::get('/inventory-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.inventory.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('inventory-dashboard');

        // Vendor & Procurement Dashboard
        Route::get('/vendor-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.vendor.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('vendor-dashboard');

        // Tax & Financial Dashboard
        Route::get('/tax-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.tax.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tax-dashboard');

        // Production & BOM Dashboard
        Route::get('/production-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('production-dashboard');

        // Quality Dashboard
        Route::get('/quality-dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.qc.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('quality-dashboard');

        // ====================================================================
        // ORGANIZATION MASTERS
        // ====================================================================

        Route::get('/departments', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.organization.departments.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
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

        Route::get('/bom-detail', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.bom-detail.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('bom-detail.index');

        Route::get('/bom-detail/create', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.bom-detail.create', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('bom-detail.create');

        Route::get('/bom-detail/{id}/edit', function ($orgSlug, $id) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.bom-detail.edit', [
                'organization' => $org,
                'tenantType' => $tenantType,
                'id' => $id
            ]);
        })->name('bom-detail.edit');

        Route::get('/bom-detail/{id}/view', function ($orgSlug, $id) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.bom-detail.view', [
                'organization' => $org,
                'tenantType' => $tenantType,
                'id' => $id
            ]);
        })->name('bom-detail.view');

        // ====================================================================
        // DEPARTMENTAL PORTALS (Organization-specific)
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

        // Procurement Portal
        Route::prefix('procurement')->name('procurement.')->group(function () use ($getOrg) {
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
        });

        // Security Department Portal
        Route::prefix('security')->name('security.')->group(function () use ($getOrg) {
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
        });

        // Warehouse/store Department Portal
        Route::prefix('warehouse')->name('warehouse.')->group(function () use ($getOrg) {
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

            Route::get('/sales-orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return redirect()->route('tenant.sales.orders', $orgSlug);
            })->name('sales-orders');
        });

        // Quality Portal
        Route::prefix('quality')->name('quality.')->group(function () use ($getOrg) {
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

        // Production Portal
        Route::prefix('production')->name('production.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.dashboard', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');

            Route::get('/orders', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.orders.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('orders');


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
        });

        // ====================================================================
        // SALES PORTAL
        // ====================================================================
        Route::prefix('sales')->name('sales.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.sales.dashboard', ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('dashboard');

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
        Route::prefix('customer')->name('customer.')->group(function () use ($getOrg) {
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
        Route::prefix('maintenance')->name('maintenance.')->group(function () use ($getOrg) {

            // Helper: session key scoped per org
            $sessionKey = fn($orgSlug, $key) => "maint_{$orgSlug}_{$key}";

            // ---- DASHBOARD ----
            Route::get('/dashboard', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                $assets     = session($sessionKey($orgSlug, 'assets'), []);
                $schedules  = session($sessionKey($orgSlug, 'schedules'), []);
                $today      = date('Y-m-d');
                $stats = [
                    'openWorkOrders' => count(array_filter($workOrders, fn($w) => in_array($w['status'], ['Assigned', 'In Progress']))),
                    'overdueOrders'  => count(array_filter($workOrders, fn($w) => isset($w['due']) && $w['due'] < $today && $w['status'] !== 'Closed')),
                    'totalAssets'    => count($assets),
                    'scheduledPM'    => count(array_filter($schedules, fn($pm) => isset($pm['next_due']) && $pm['next_due'] >= $today && $pm['next_due'] <= date('Y-m-d', strtotime('+7 days')) && $pm['status'] !== 'Done')),
                ];
                return view('tenant.maintenance.dashboard', compact('org', 'tenantType', 'stats') + ['organization' => $org]);
            })->name('dashboard');

            // ---- REQUESTS ----
            Route::get('/requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $requests = session($sessionKey($orgSlug, 'requests'), []);
                $assets   = session($sessionKey($orgSlug, 'assets'), []);
                return view('tenant.maintenance.requests.index', compact('requests', 'assets')
                    + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('requests');

            Route::post('/requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key      = $sessionKey($orgSlug, 'requests');
                $requests = session($key, []);
                $seq      = count($requests) + 1;
                $user     = session('auth_user_name', 'User');
                $requests[] = [
                    'id'        => 'MR-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                    'asset'     => request('asset'),
                    'asset_code'=> request('asset_code', ''),
                    'priority'  => request('priority'),
                    'issue'     => request('issue'),
                    'status'    => 'Pending Approval',
                    'raised_by' => $user,
                    'raised_on' => now()->format('Y-m-d'),
                ];
                session([$key => $requests]);
                return redirect()->route('tenant.maintenance.requests', $orgSlug)
                    ->with('success', 'Maintenance request submitted successfully.');
            })->name('requests.store');

            // ---- APPROVALS ----
            Route::get('/approvals', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $requests  = session($sessionKey($orgSlug, 'requests'), []);
                $approvals = array_values(array_filter($requests, fn($r) => $r['status'] === 'Pending Approval'));
                return view('tenant.maintenance.approvals.index', compact('approvals')
                    + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('approvals');

            Route::post('/approvals/{id}/approve', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key      = $sessionKey($orgSlug, 'requests');
                $requests = session($key, []);
                foreach ($requests as &$r) {
                    if ($r['id'] === $id) { $r['status'] = 'Approved'; $r['approved_on'] = now()->format('Y-m-d'); $r['remarks'] = request('remarks', ''); break; }
                }
                session([$key => $requests]);
                return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} approved.");
            })->name('approvals.approve');

            Route::post('/approvals/{id}/reject', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key      = $sessionKey($orgSlug, 'requests');
                $requests = session($key, []);
                foreach ($requests as &$r) {
                    if ($r['id'] === $id) { $r['status'] = 'Rejected'; $r['rejected_on'] = now()->format('Y-m-d'); $r['remarks'] = request('remarks', ''); break; }
                }
                session([$key => $requests]);
                return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} rejected.");
            })->name('approvals.reject');

            // ---- ASSIGNMENTS ----
            Route::get('/assignments', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $requests   = session($sessionKey($orgSlug, 'requests'), []);
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                $approved   = array_values(array_filter($requests, fn($r) => $r['status'] === 'Approved'));
                return view('tenant.maintenance.assignments.index',
                    compact('approved', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('assignments');

            Route::post('/assignments', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $reqKey     = $sessionKey($orgSlug, 'requests');
                $woKey      = $sessionKey($orgSlug, 'work_orders');
                $requests   = session($reqKey, []);
                $workOrders = session($woKey, []);
                $mrId       = request('request_id');
                $assetName  = '';
                foreach ($requests as &$r) {
                    if ($r['id'] === $mrId) { $r['status'] = 'Assigned'; $assetName = $r['asset']; break; }
                }
                session([$reqKey => $requests]);
                $seq = count($workOrders) + 1;
                $workOrders[] = [
                    'wo'          => 'WO-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                    'mr_id'       => $mrId,
                    'asset'       => $assetName,
                    'technician'  => request('technician'),
                    'team'        => request('team', 'Mechanical'),
                    'due'         => request('due_date'),
                    'priority'    => request('priority', 'Medium'),
                    'notes'       => request('notes', ''),
                    'status'      => 'Assigned',
                    'assigned_on' => now()->format('Y-m-d'),
                    'materials'   => [],
                ];
                session([$woKey => $workOrders]);
                return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', 'Work order created and technician assigned.');
            })->name('assignments.store');

            Route::post('/assignments/{wo}/update-status', function ($orgSlug, $wo) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $woKey      = $sessionKey($orgSlug, 'work_orders');
                $workOrders = session($woKey, []);
                foreach ($workOrders as &$w) {
                    if ($w['wo'] === $wo) { $w['status'] = request('status'); $w['engineer_notes'] = request('engineer_notes', ''); break; }
                }
                session([$woKey => $workOrders]);
                return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', "Work order {$wo} status updated.");
            })->name('assignments.update-status');

            // ---- WORK ORDERS ----
            Route::get('/work-orders', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                return view('tenant.maintenance.work-orders.index',
                    compact('workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('work-orders');

            // ---- MATERIAL REQUESTS (per WO) ----
            Route::get('/material-requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders   = session($sessionKey($orgSlug, 'work_orders'), []);
                $parts        = session($sessionKey($orgSlug, 'spare_parts'), []);
                $matRequests  = session($sessionKey($orgSlug, 'mat_requests'), []);
                return view('tenant.maintenance.material-requests.index',
                    compact('workOrders', 'parts', 'matRequests') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('material-requests');

            Route::post('/material-requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $mrKey      = $sessionKey($orgSlug, 'mat_requests');
                $partsKey   = $sessionKey($orgSlug, 'spare_parts');
                $matReqs    = session($mrKey, []);
                $parts      = session($partsKey, []);
                $partCode   = request('part_code');
                $qty        = (int) request('qty', 1);
                $woId       = request('wo_id');

                // Check stock
                $inStock = false;
                foreach ($parts as $p) {
                    if ($p['code'] === $partCode && $p['stock'] >= $qty) { $inStock = true; break; }
                }

                $seq = count($matReqs) + 1;
                $matReqs[] = [
                    'id'         => 'MMR-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                    'wo_id'      => $woId,
                    'part_code'  => $partCode,
                    'part_name'  => request('part_name'),
                    'qty'        => $qty,
                    'unit'       => request('unit', 'Nos'),
                    'in_stock'   => $inStock,
                    'status'     => $inStock ? 'Pending Issue' : 'Procurement Required',
                    'raised_on'  => now()->format('Y-m-d'),
                    'issued_on'  => null,
                ];
                session([$mrKey => $matReqs]);
                return redirect()->route('tenant.maintenance.material-requests', $orgSlug)
                    ->with('success', $inStock ? 'Material request raised. Stock available — ready to issue.' : 'Material not in stock. Procurement request flagged.');
            })->name('material-requests.store');

            Route::post('/material-requests/{id}/issue', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $mrKey    = $sessionKey($orgSlug, 'mat_requests');
                $partsKey = $sessionKey($orgSlug, 'spare_parts');
                $matReqs  = session($mrKey, []);
                $parts    = session($partsKey, []);

                $partCode = null; $qty = 0;
                foreach ($matReqs as &$m) {
                    if ($m['id'] === $id && $m['status'] === 'Pending Issue') {
                        $m['status']    = 'Issued';
                        $m['issued_on'] = now()->format('Y-m-d');
                        $partCode = $m['part_code'];
                        $qty      = $m['qty'];
                        break;
                    }
                }
                // Deduct from spare parts stock
                if ($partCode) {
                    foreach ($parts as &$p) {
                        if ($p['code'] === $partCode) { $p['stock'] = max(0, $p['stock'] - $qty); break; }
                    }
                    session([$partsKey => $parts]);
                }
                session([$mrKey => $matReqs]);
                return redirect()->route('tenant.maintenance.material-requests', $orgSlug)->with('success', "Material {$id} issued from stock.");
            })->name('material-requests.issue');

            // ---- CLOSURE ----
            Route::get('/closure', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                $closures   = array_values(array_filter($workOrders, fn($w) => in_array($w['status'], ['Completed', 'Closed'])));
                return view('tenant.maintenance.closure.index',
                    compact('closures') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('closure');

            Route::post('/closure/{wo}/close', function ($orgSlug, $wo) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $woKey      = $sessionKey($orgSlug, 'work_orders');
                $workOrders = session($woKey, []);
                foreach ($workOrders as &$w) {
                    if ($w['wo'] === $wo) {
                        $w['status']        = 'Closed';
                        $w['closed_on']     = now()->format('Y-m-d');
                        $w['verified_by']   = request('verified_by', 'Maintenance Lead');
                        $w['closure_notes'] = request('closure_notes', '');
                        // Update asset last_maintained
                        $assetsKey = $sessionKey($orgSlug, 'assets');
                        $assets    = session($assetsKey, []);
                        foreach ($assets as &$a) {
                            if ($a['name'] === $w['asset']) { $a['last_maintained'] = now()->format('Y-m-d'); break; }
                        }
                        session([$assetsKey => $assets]);
                        break;
                    }
                }
                session([$woKey => $workOrders]);
                return redirect()->route('tenant.maintenance.closure', $orgSlug)->with('success', "Work order {$wo} closed successfully.");
            })->name('closure.close');

            // ---- ASSETS ----
            Route::get('/assets', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $assets     = session($sessionKey($orgSlug, 'assets'), []);
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                $schedules  = session($sessionKey($orgSlug, 'schedules'), []);
                // Attach history counts per asset
                foreach ($assets as &$a) {
                    $a['wo_count'] = count(array_filter($workOrders, fn($w) => $w['asset'] === $a['name']));
                    $a['pm_count'] = count(array_filter($schedules, fn($pm) => $pm['asset'] === $a['name']));
                }
                return view('tenant.maintenance.assets.index', compact('assets') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('assets');

            Route::post('/assets', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key    = $sessionKey($orgSlug, 'assets');
                $assets = session($key, []);
                $seq    = count($assets) + 1;
                $assets[] = [
                    'code'            => request('code') ?: 'AST-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
                    'name'            => request('name'),
                    'category'        => request('category'),
                    'location'        => request('location', ''),
                    'model'           => request('model', ''),
                    'installed_on'    => request('installed_on', ''),
                    'last_maintained' => null,
                    'status'          => 'Active',
                ];
                session([$key => $assets]);
                return redirect()->route('tenant.maintenance.assets', $orgSlug)->with('success', 'Asset registered successfully.');
            })->name('assets.store');

            // ---- PM SCHEDULE ----
            Route::get('/schedule', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $schedules = session($sessionKey($orgSlug, 'schedules'), []);
                $assets    = session($sessionKey($orgSlug, 'assets'), []);
                $parts     = session($sessionKey($orgSlug, 'spare_parts'), []);
                foreach ($schedules as &$pm) {
                    if ($pm['status'] !== 'Done' && isset($pm['next_due']) && $pm['next_due'] < date('Y-m-d')) {
                        $pm['status'] = 'Overdue';
                    }
                }
                return view('tenant.maintenance.schedule.index', compact('schedules', 'assets', 'parts') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('schedule');

            Route::post('/schedule', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key       = $sessionKey($orgSlug, 'schedules');
                $schedules = session($key, []);
                $seq       = count($schedules) + 1;
                // Parse materials list
                $matNames  = request('mat_name', []);
                $matQtys   = request('mat_qty', []);
                $matUnits  = request('mat_unit', []);
                $materials = [];
                foreach ($matNames as $i => $mn) {
                    if (trim($mn)) $materials[] = ['name' => $mn, 'qty' => $matQtys[$i] ?? 1, 'unit' => $matUnits[$i] ?? 'Nos'];
                }
                $schedules[] = [
                    'id'          => 'PM-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                    'asset'       => request('asset'),
                    'task'        => request('task'),
                    'frequency'   => request('frequency'),
                    'assigned_to' => request('assigned_to', ''),
                    'next_due'    => request('next_due'),
                    'duration'    => request('duration', ''),
                    'materials'   => $materials,
                    'last_done'   => null,
                    'status'      => 'Scheduled',
                ];
                session([$key => $schedules]);
                return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', 'PM task scheduled successfully.');
            })->name('schedule.store');

            Route::post('/schedule/{id}/done', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $schKey   = $sessionKey($orgSlug, 'schedules');
                $partsKey = $sessionKey($orgSlug, 'spare_parts');
                $schedules = session($schKey, []);
                $parts     = session($partsKey, []);
                foreach ($schedules as &$pm) {
                    if ($pm['id'] === $id) {
                        $pm['status']    = 'Done';
                        $pm['last_done'] = now()->format('Y-m-d');
                        $pm['notes']     = request('notes', '');
                        // Auto-deduct materials used
                        foreach ($pm['materials'] ?? [] as $mat) {
                            foreach ($parts as &$p) {
                                if (strtolower($p['name']) === strtolower($mat['name'])) {
                                    $p['stock'] = max(0, $p['stock'] - (int)$mat['qty']);
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                session([$schKey => $schedules]);
                session([$partsKey => $parts]);
                return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', "PM task {$id} marked as done. Materials deducted from stock.");
            })->name('schedule.done');

            // ---- SPARE PARTS ----
            Route::get('/spare-parts', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $parts      = session($sessionKey($orgSlug, 'spare_parts'), []);
                $matReqs    = session($sessionKey($orgSlug, 'mat_requests'), []);
                $workOrders = session($sessionKey($orgSlug, 'work_orders'), []);
                return view('tenant.maintenance.spare-parts.index', compact('parts', 'matReqs', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('spare-parts');

            Route::post('/spare-parts', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key   = $sessionKey($orgSlug, 'spare_parts');
                $parts = session($key, []);
                $parts[] = [
                    'code'          => request('code'),
                    'name'          => request('name'),
                    'asset'         => request('asset', ''),
                    'stock'         => (int) request('stock', 0),
                    'reorder_level' => request('reorder_level') !== '' ? (int) request('reorder_level') : null,
                    'unit'          => request('unit', 'Nos'),
                ];
                session([$key => $parts]);
                return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', 'Spare part added successfully.');
            })->name('spare-parts.store');

            Route::post('/spare-parts/{code}/issue', function ($orgSlug, $code) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key   = $sessionKey($orgSlug, 'spare_parts');
                $parts = session($key, []);
                $qty   = (int) request('qty', 1);
                foreach ($parts as &$p) {
                    if ($p['code'] === $code) { $p['stock'] = max(0, $p['stock'] - $qty); break; }
                }
                session([$key => $parts]);
                return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', "{$qty} unit(s) of {$code} issued.");
            })->name('spare-parts.issue');

            Route::post('/spare-parts/{code}/receive', function ($orgSlug, $code) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $key   = $sessionKey($orgSlug, 'spare_parts');
                $parts = session($key, []);
                $qty   = (int) request('qty', 1);
                foreach ($parts as &$p) {
                    if ($p['code'] === $code) { $p['stock'] += $qty; break; }
                }
                session([$key => $parts]);
                return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', "{$qty} unit(s) of {$code} received into stock.");
            })->name('spare-parts.receive');
        });

        // ====================================================================
        // OTHER PAGES
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
