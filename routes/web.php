<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Models\Control\Organization;
use Illuminate\Support\Facades\DB;

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
    Route::prefix('org/{org_slug}')->middleware(['resolve.tenant', 'switch.tenant.db'])->name('tenant.')->group(function () {
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

            Route::get('/mir', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('tenant.production.mir.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('mir');

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
                $today      = date('Y-m-d');
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->get();
                $assets     = DB::connection('tenant')->table('maint_assets')->get();
                $schedules  = DB::connection('tenant')->table('maint_pm_schedules')->get();
                $stats = [
                    'openWorkOrders' => $workOrders->filter(fn($w) => in_array($w->status, ['Assigned', 'In Progress'], true))->count(),
                    'overdueOrders'  => $workOrders->filter(fn($w) => $w->due_date && $w->due_date < $today && $w->status !== 'Closed')->count(),
                    'totalAssets'    => $assets->count(),
                    'scheduledPM'    => $schedules->filter(fn($pm) => $pm->next_due && $pm->next_due >= $today && $pm->next_due <= date('Y-m-d', strtotime('+7 days')) && $pm->status !== 'Done')->count(),
                ];
                return view('tenant.maintenance.dashboard', compact('org', 'tenantType', 'stats') + ['organization' => $org]);
            })->name('dashboard');

            // ---- REQUESTS ----
            Route::get('/requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $requests = DB::connection('tenant')->table('maint_requests')->orderByDesc('id')->get()->map(function ($r) {
                    return [
                        'id' => $r->request_no,
                        'asset' => $r->asset_name,
                        'asset_code' => '',
                        'priority' => $r->priority,
                        'issue' => $r->issue,
                        'status' => $r->status,
                        'raised_by' => $r->raised_by,
                        'raised_on' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
                    ];
                })->all();
                $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
                    'code' => $a->code,
                    'name' => $a->name,
                ])->all();
                return view('tenant.maintenance.requests.index', compact('requests', 'assets')
                    + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('requests');

            Route::post('/requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $seq  = (int) (DB::connection('tenant')->table('maint_requests')->max('id') ?? 0) + 1;
                $user = session('auth_user_name', 'User');
                $assetName = (string) request('asset');
                $assetCode = (string) request('asset_code', '');
                $assetRow = null;
                if ($assetCode !== '') {
                    $assetRow = DB::connection('tenant')->table('maint_assets')->where('code', $assetCode)->first();
                }
                if (!$assetRow && $assetName !== '') {
                    $assetRow = DB::connection('tenant')->table('maint_assets')->where('name', $assetName)->first();
                }

                DB::connection('tenant')->table('maint_requests')->insert([
                    'request_no' => 'MR-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'asset_id' => $assetRow?->id,
                    'asset_name' => $assetRow?->name ?? $assetName,
                    'priority' => request('priority'),
                    'issue' => request('issue'),
                    'status' => 'Pending Approval',
                    'raised_by' => $user,
                    'raised_by_id' => request()->get('auth_user_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.requests', $orgSlug)
                    ->with('success', 'Maintenance request submitted successfully.');
            })->name('requests.store');

            // ---- APPROVALS ----
            Route::get('/approvals', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $approvals = DB::connection('tenant')->table('maint_requests')
                    ->where('status', 'Pending Approval')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn($r) => [
                        'id' => $r->request_no,
                        'asset' => $r->asset_name,
                        'priority' => $r->priority,
                        'issue' => $r->issue,
                        'status' => $r->status,
                        'raised_by' => $r->raised_by,
                        'raised_on' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
                    ])
                    ->all();
                return view('tenant.maintenance.approvals.index', compact('approvals')
                    + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('approvals');

            Route::post('/approvals/{id}/approve', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                DB::connection('tenant')->table('maint_requests')->where('request_no', $id)->update([
                    'status' => 'Approved',
                    'approved_on' => now()->format('Y-m-d'),
                    'remarks' => request('remarks', ''),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} approved.");
            })->name('approvals.approve');

            Route::post('/approvals/{id}/reject', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                DB::connection('tenant')->table('maint_requests')->where('request_no', $id)->update([
                    'status' => 'Rejected',
                    'rejected_on' => now()->format('Y-m-d'),
                    'remarks' => request('remarks', ''),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} rejected.");
            })->name('approvals.reject');

            // ---- ASSIGNMENTS ----
            Route::get('/assignments', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $approved = DB::connection('tenant')->table('maint_requests')->where('status', 'Approved')->orderByDesc('id')->get()->map(fn($r) => [
                    'id' => $r->request_no,
                    'asset' => $r->asset_name,
                    'priority' => $r->priority,
                    'issue' => $r->issue,
                    'status' => $r->status,
                ])->all();
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
                    'wo' => $w->wo_no,
                    'mr_id' => $w->request_id ? (DB::connection('tenant')->table('maint_requests')->where('id', $w->request_id)->value('request_no')) : null,
                    'asset' => $w->asset_name,
                    'technician' => $w->technician,
                    'team' => $w->team,
                    'due' => $w->due_date,
                    'priority' => $w->priority,
                    'notes' => $w->notes,
                    'status' => $w->status,
                    'assigned_on' => $w->assigned_on,
                ])->all();
                return view('tenant.maintenance.assignments.index',
                    compact('approved', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('assignments');

            Route::post('/assignments', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $mrNo = request('request_id');
                $reqRow = DB::connection('tenant')->table('maint_requests')->where('request_no', $mrNo)->first();
                if ($reqRow) {
                    DB::connection('tenant')->table('maint_requests')->where('id', $reqRow->id)->update([
                        'status' => 'Assigned',
                        'updated_at' => now(),
                    ]);
                }

                $seq = (int) (DB::connection('tenant')->table('maint_work_orders')->max('id') ?? 0) + 1;
                DB::connection('tenant')->table('maint_work_orders')->insert([
                    'wo_no' => 'WO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'request_id' => $reqRow?->id,
                    'asset_id' => $reqRow?->asset_id,
                    'asset_name' => $reqRow?->asset_name ?? '',
                    'technician' => request('technician'),
                    'team' => request('team', 'Mechanical'),
                    'due_date' => request('due_date'),
                    'priority' => $reqRow?->priority ?? request('priority', 'Medium'),
                    'notes' => request('notes', ''),
                    'status' => 'Assigned',
                    'assigned_on' => now()->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', 'Work order created and technician assigned.');
            })->name('assignments.store');

            Route::post('/assignments/{wo}/update-status', function ($orgSlug, $wo) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $wo)->update([
                    'status' => request('status'),
                    'engineer_notes' => request('engineer_notes', ''),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', "Work order {$wo} status updated.");
            })->name('assignments.update-status');

            // ---- WORK ORDERS ----
            Route::get('/work-orders', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
                    'wo' => $w->wo_no,
                    'mr_id' => $w->request_id ? (DB::connection('tenant')->table('maint_requests')->where('id', $w->request_id)->value('request_no')) : null,
                    'asset' => $w->asset_name,
                    'technician' => $w->technician,
                    'team' => $w->team,
                    'due' => $w->due_date,
                    'priority' => $w->priority,
                    'notes' => $w->notes,
                    'status' => $w->status,
                    'assigned_on' => $w->assigned_on,
                ])->all();
                return view('tenant.maintenance.work-orders.index',
                    compact('workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('work-orders');

            // ---- MATERIAL REQUESTS (per WO) ----
            Route::get('/material-requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
                    'wo' => $w->wo_no,
                    'asset' => $w->asset_name,
                    'status' => $w->status,
                ])->all();
                $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'stock' => $p->stock,
                    'reorder_level' => $p->reorder_level,
                    'unit' => $p->unit,
                ])->all();
                $matRequests = DB::connection('tenant')->table('maint_material_requests')->orderByDesc('id')->get()->map(fn($m) => [
                    'id' => $m->mmr_no,
                    'wo_id' => $m->wo_no,
                    'part_code' => $m->part_code,
                    'part_name' => $m->part_name,
                    'qty' => $m->qty,
                    'unit' => $m->unit,
                    'in_stock' => (bool) $m->in_stock,
                    'status' => $m->status,
                    'raised_on' => $m->raised_on,
                    'issued_on' => $m->issued_on,
                ])->all();
                return view('tenant.maintenance.material-requests.index',
                    compact('workOrders', 'parts', 'matRequests') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('material-requests');

            Route::post('/material-requests', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $woNo = request('wo_id');
                $woRow = DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $woNo)->first();

                $items = request('items');
                if (!is_array($items) || count($items) === 0) {
                    $items = [[
                        'part_code' => request('part_code'),
                        'part_name' => request('part_name'),
                        'unit' => request('unit', 'Nos'),
                        'qty' => request('qty', 1),
                    ]];
                }

                $items = array_values(array_filter($items, fn ($i) => is_array($i) && !empty($i['part_code'])));

                if (!$woNo || count($items) === 0) {
                    return redirect()->route('tenant.maintenance.material-requests', $orgSlug)
                        ->with('success', 'No material items provided.');
                }

                $now = now();
                $today = $now->format('Y-m-d');
                $seq = (int) (DB::connection('tenant')->table('maint_material_requests')->max('id') ?? 0) + 1;

                $createdCount = 0;
                $anyProcurement = false;
                $anyPendingIssue = false;

                DB::connection('tenant')->transaction(function () use (
                    $items,
                    $woNo,
                    $woRow,
                    $today,
                    $now,
                    &$seq,
                    &$createdCount,
                    &$anyProcurement,
                    &$anyPendingIssue
                ) {
                    foreach ($items as $item) {
                        $partCode = $item['part_code'] ?? null;
                        $qty = max(1, (int) ($item['qty'] ?? 1));

                        $partRow = $partCode
                            ? DB::connection('tenant')->table('maint_spare_parts')->where('code', $partCode)->first()
                            : null;

                        $inStock = $partRow ? ((int) $partRow->stock >= $qty) : false;
                        $status = $inStock ? 'Pending Issue' : 'Procurement Required';

                        $anyPendingIssue = $anyPendingIssue || $inStock;
                        $anyProcurement = $anyProcurement || !$inStock;

                        DB::connection('tenant')->table('maint_material_requests')->insert([
                            'mmr_no' => 'MMR-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                            'wo_id' => $woRow?->id,
                            'wo_no' => $woRow?->wo_no ?? $woNo,
                            'part_id' => $partRow?->id,
                            'part_code' => $partCode,
                            'part_name' => ($item['part_name'] ?? null) ?: ($partRow?->name ?? $partCode),
                            'qty' => $qty,
                            'unit' => ($item['unit'] ?? null) ?: ($partRow?->unit ?? 'Nos'),
                            'in_stock' => $inStock,
                            'status' => $status,
                            'raised_on' => $today,
                            'issued_on' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $seq++;
                        $createdCount++;
                    }
                });

                $msg = "Material request raised for {$createdCount} item(s).";
                if ($anyPendingIssue && $anyProcurement) {
                    $msg .= ' Some items are in stock (Pending Issue) and some require procurement.';
                } elseif ($anyPendingIssue) {
                    $msg .= ' Stock available — ready to issue.';
                } else {
                    $msg .= ' Material not in stock. Procurement request flagged.';
                }

                return redirect()->route('tenant.maintenance.material-requests', $orgSlug)->with('success', $msg);
            })->name('material-requests.store');

            Route::post('/material-requests/{id}/issue', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $row = DB::connection('tenant')->table('maint_material_requests')->where('mmr_no', $id)->first();
                if ($row && $row->status === 'Pending Issue') {
                    DB::connection('tenant')->table('maint_material_requests')->where('id', $row->id)->update([
                        'status' => 'Issued',
                        'issued_on' => now()->format('Y-m-d'),
                        'updated_at' => now(),
                    ]);

                    if ($row->part_id) {
                        DB::connection('tenant')->table('maint_spare_parts')->where('id', $row->part_id)->update([
                            'stock' => DB::raw('GREATEST(0, stock - ' . ((int) $row->qty) . ')'),
                            'updated_at' => now(),
                        ]);
                    }
                }
                return redirect()->route('tenant.maintenance.material-requests', $orgSlug)->with('success', "Material {$id} issued from stock.");
            })->name('material-requests.issue');

            // ---- CLOSURE ----
            Route::get('/closure', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $closures = DB::connection('tenant')->table('maint_work_orders')
                    ->whereIn('status', ['Completed', 'Closed'])
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn($w) => [
                        'wo' => $w->wo_no,
                        'mr_id' => $w->request_id ? (DB::connection('tenant')->table('maint_requests')->where('id', $w->request_id)->value('request_no')) : null,
                        'asset' => $w->asset_name,
                        'technician' => $w->technician,
                        'due' => $w->due_date,
                        'priority' => $w->priority,
                        'notes' => $w->notes,
                        'status' => $w->status,
                        'assigned_on' => $w->assigned_on,
                        'closed_on' => $w->closed_on,
                        'verified_by' => $w->verified_by,
                    ])
                    ->all();
                return view('tenant.maintenance.closure.index',
                    compact('closures') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('closure');

            Route::post('/closure/{wo}/close', function ($orgSlug, $wo) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $woRow = DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $wo)->first();
                if ($woRow) {
                    DB::connection('tenant')->table('maint_work_orders')->where('id', $woRow->id)->update([
                        'status' => 'Closed',
                        'closed_on' => now()->format('Y-m-d'),
                        'verified_by' => request('verified_by', 'Maintenance Lead'),
                        'closure_notes' => request('closure_notes', ''),
                        'updated_at' => now(),
                    ]);
                    if ($woRow->asset_id) {
                        DB::connection('tenant')->table('maint_assets')->where('id', $woRow->asset_id)->update([
                            'last_maintained' => now()->format('Y-m-d'),
                            'updated_at' => now(),
                        ]);
                    }
                }
                return redirect()->route('tenant.maintenance.closure', $orgSlug)->with('success', "Work order {$wo} closed successfully.");
            })->name('closure.close');

            // ---- ASSETS ----
            Route::get('/assets', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->get();
                $schedules = DB::connection('tenant')->table('maint_pm_schedules')->get();
                $assets = DB::connection('tenant')->table('maint_assets')->orderByDesc('id')->get()->map(function ($a) use ($workOrders, $schedules) {
                    $woCount = $workOrders->filter(fn($w) => $w->asset_id === $a->id)->count();
                    $pmCount = $schedules->filter(fn($pm) => $pm->asset_id === $a->id)->count();
                    return [
                        'code' => $a->code,
                        'name' => $a->name,
                        'category' => $a->category,
                        'location' => $a->location,
                        'model' => $a->model,
                        'installed_on' => $a->installed_on,
                        'last_maintained' => $a->last_maintained,
                        'status' => $a->status,
                        'wo_count' => $woCount,
                        'pm_count' => $pmCount,
                    ];
                })->all();
                return view('tenant.maintenance.assets.index', compact('assets') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('assets');

            Route::post('/assets', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $seq = (int) (DB::connection('tenant')->table('maint_assets')->max('id') ?? 0) + 1;
                DB::connection('tenant')->table('maint_assets')->insert([
                    'code' => request('code') ?: 'AST-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
                    'name' => request('name'),
                    'category' => request('category'),
                    'location' => request('location', ''),
                    'model' => request('model', ''),
                    'installed_on' => request('installed_on') ?: null,
                    'last_maintained' => null,
                    'status' => 'Active',
                    'created_by' => request()->get('auth_user_id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.assets', $orgSlug)->with('success', 'Asset registered successfully.');
            })->name('assets.store');

            // ---- PM SCHEDULE ----
            Route::get('/schedule', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
                    'code' => $a->code,
                    'name' => $a->name,
                ])->all();
                $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
                    'code' => $p->code,
                    'name' => $p->name,
                    'stock' => $p->stock,
                    'unit' => $p->unit,
                ])->all();

                $materialsByPm = DB::connection('tenant')->table('maint_pm_materials')->get()->groupBy('pm_id');
                $schedules = DB::connection('tenant')->table('maint_pm_schedules')->orderByDesc('id')->get()->map(function ($pm) use ($materialsByPm) {
                    $mats = ($materialsByPm[$pm->id] ?? collect())->map(fn($m) => [
                        'name' => $m->part_name,
                        'qty' => $m->qty,
                        'unit' => $m->unit,
                    ])->values()->all();

                    $status = $pm->status;
                    if ($status !== 'Done' && $pm->next_due && $pm->next_due < date('Y-m-d')) {
                        $status = 'Overdue';
                    }

                    return [
                        'id' => $pm->pm_no,
                        'asset' => $pm->asset_name,
                        'task' => $pm->task,
                        'frequency' => $pm->frequency,
                        'assigned_to' => $pm->assigned_to,
                        'next_due' => $pm->next_due,
                        'duration' => $pm->duration,
                        'materials' => $mats,
                        'last_done' => $pm->last_done,
                        'status' => $status,
                        'notes' => $pm->notes,
                    ];
                })->all();
                return view('tenant.maintenance.schedule.index', compact('schedules', 'assets', 'parts') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('schedule');

            Route::post('/schedule', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                // Parse materials list
                $matNames  = request('mat_name', []);
                $matQtys   = request('mat_qty', []);
                $matUnits  = request('mat_unit', []);
                $materials = [];
                foreach ($matNames as $i => $mn) {
                    if (trim($mn)) $materials[] = ['name' => $mn, 'qty' => $matQtys[$i] ?? 1, 'unit' => $matUnits[$i] ?? 'Nos'];
                }

                $assetName = (string) request('asset');
                $assetRow = DB::connection('tenant')->table('maint_assets')->where('name', $assetName)->first();

                $seq = (int) (DB::connection('tenant')->table('maint_pm_schedules')->max('id') ?? 0) + 1;
                $pmNo = 'PM-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $pmId = DB::connection('tenant')->table('maint_pm_schedules')->insertGetId([
                    'pm_no' => $pmNo,
                    'asset_id' => $assetRow?->id,
                    'asset_name' => $assetRow?->name ?? $assetName,
                    'task' => request('task'),
                    'frequency' => request('frequency'),
                    'assigned_to' => request('assigned_to', ''),
                    'next_due' => request('next_due'),
                    'duration' => request('duration', ''),
                    'status' => 'Scheduled',
                    'last_done' => null,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($materials as $mat) {
                    DB::connection('tenant')->table('maint_pm_materials')->insert([
                        'pm_id' => $pmId,
                        'part_name' => $mat['name'],
                        'qty' => (int) ($mat['qty'] ?? 1),
                        'unit' => $mat['unit'] ?? 'Nos',
                    ]);
                }
                return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', 'PM task scheduled successfully.');
            })->name('schedule.store');

            Route::post('/schedule/{id}/done', function ($orgSlug, $id) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $pmRow = DB::connection('tenant')->table('maint_pm_schedules')->where('pm_no', $id)->first();
                if ($pmRow) {
                    DB::connection('tenant')->table('maint_pm_schedules')->where('id', $pmRow->id)->update([
                        'status' => 'Done',
                        'last_done' => now()->format('Y-m-d'),
                        'notes' => request('notes', ''),
                        'updated_at' => now(),
                    ]);

                    $materials = DB::connection('tenant')->table('maint_pm_materials')->where('pm_id', $pmRow->id)->get();
                    foreach ($materials as $mat) {
                        DB::connection('tenant')->table('maint_spare_parts')
                            ->whereRaw('LOWER(name) = ?', [strtolower($mat->part_name)])
                            ->update([
                                'stock' => DB::raw('GREATEST(0, stock - ' . ((int) $mat->qty) . ')'),
                                'updated_at' => now(),
                            ]);
                    }
                }
                return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', "PM task {$id} marked as done. Materials deducted from stock.");
            })->name('schedule.done');

            // ---- SPARE PARTS ----
            Route::get('/spare-parts', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $parts = DB::connection('tenant')->table('maint_spare_parts')->orderByDesc('id')->get()->map(fn($p) => [
                    'code' => $p->code,
                    'name' => $p->name,
                    'asset' => $p->compatible_asset,
                    'stock' => $p->stock,
                    'reorder_level' => $p->reorder_level,
                    'unit' => $p->unit,
                ])->all();
                $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
                    'code' => $a->code,
                    'name' => $a->name,
                    'category' => $a->category,
                    'status' => $a->status,
                ])->all();
                $matReqs = DB::connection('tenant')->table('maint_material_requests')->orderByDesc('id')->get()->map(fn($m) => [
                    'id' => $m->mmr_no,
                    'wo_id' => $m->wo_no,
                    'part_code' => $m->part_code,
                    'part_name' => $m->part_name,
                    'qty' => $m->qty,
                    'unit' => $m->unit,
                    'status' => $m->status,
                ])->all();
                $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
                    'wo' => $w->wo_no,
                    'asset' => $w->asset_name,
                    'status' => $w->status,
                ])->all();
                return view('tenant.maintenance.spare-parts.index', compact('parts', 'assets', 'matReqs', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
            })->name('spare-parts');

            Route::post('/spare-parts', function ($orgSlug) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                DB::connection('tenant')->table('maint_spare_parts')->insert([
                    'code' => request('code'),
                    'name' => request('name'),
                    'compatible_asset' => request('asset', ''),
                    'stock' => (int) request('stock', 0),
                    'reorder_level' => request('reorder_level') !== '' ? (int) request('reorder_level') : null,
                    'unit' => request('unit', 'Nos'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', 'Spare part added successfully.');
            })->name('spare-parts.store');

            Route::post('/spare-parts/{code}/issue', function ($orgSlug, $code) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $qty   = (int) request('qty', 1);
                DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->update([
                    'stock' => DB::raw('GREATEST(0, stock - ' . $qty . ')'),
                    'updated_at' => now(),
                ]);
                return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', "{$qty} unit(s) of {$code} issued.");
            })->name('spare-parts.issue');

            Route::post('/spare-parts/{code}/receive', function ($orgSlug, $code) use ($getOrg, $sessionKey) {
                extract($getOrg($orgSlug));
                $qty   = (int) request('qty', 1);
                DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->update([
                    'stock' => DB::raw('stock + ' . $qty),
                    'updated_at' => now(),
                ]);
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
