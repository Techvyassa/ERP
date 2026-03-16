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
// PUBLIC ROUTES
// ============================================================================

// Landing & Marketing Pages
Route::get('/', [PublicController::class, 'landing'])->name('home');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

// Authentication Pages
Route::get('/register', [PublicController::class, 'register'])->name('register');
Route::get('/login', [PublicController::class, 'login'])->name('login');

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
        
        Route::get('/users', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.organization.users.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('users.index');
        
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
        
        Route::get('/bom-detail', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.masters.bom.bom-detail.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('bom-detail.index');
        
        // ====================================================================
        // DEPARTMENTAL PORTALS (Organization-specific)
        // ====================================================================
        
        // Specialized Department Logins
        Route::get('/procurement/login', [PublicController::class, 'loginProcurement'])->name('procurement.login');
        Route::get('/warehouse/login', [PublicController::class, 'loginWarehouse'])->name('warehouse.login');
        Route::get('/quality/login', [PublicController::class, 'loginQuality'])->name('quality.login');
        Route::get('/admin/login', [PublicController::class, 'loginAdmin'])->name('admin.login');
        
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
                return "ASN Tracking Page WIP";
            })->name('asn');
            
            Route::get('/po-approval', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('procurement.po-approval.index', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('po-approval');
        });
        
        // Warehouse Portal
        Route::prefix('warehouse')->name('warehouse.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('dashboard.warehouse', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');
            
            Route::get('/gate-entry', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Gate Entry Page WIP";
            })->name('gate-entry');
            
            Route::get('/receipts', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Material Receipts Page WIP";
            })->name('receipts');
            
            Route::get('/putaway', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Putaway Page WIP";
            })->name('putaway');
        });
        
        // Quality Portal
        Route::prefix('quality')->name('quality.')->group(function () use ($getOrg) {
            Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return view('dashboard.quality', [
                    'organization' => $org,
                    'tenantType' => $tenantType
                ]);
            })->name('dashboard');
            
            Route::get('/inspections', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Inspections Page WIP";
            })->name('inspections');
            
            Route::get('/decisions', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Usage Decisions Page WIP";
            })->name('decisions');
            
            Route::get('/reports', function ($orgSlug) use ($getOrg) {
                extract($getOrg($orgSlug));
                return "Quality Reports Page WIP";
            })->name('reports');
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

    // ------------------------------------------------------------------------
    // LOGOUT
    // ------------------------------------------------------------------------
    Route::post('/logout', function () {
        return redirect()->route('home')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('success', 'You have been logged out successfully');
    })->name('logout');
});

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
