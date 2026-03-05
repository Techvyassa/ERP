<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for tenant contexts (both subdomain and path-based)
| They require authentication and tenant context
|
| Subdomain: company1.yoursite.com/dashboard
| Path-based: yoursite.com/org/company1/dashboard
|
*/

Route::middleware(['web', 'detect.tenant', 'web.jwt'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.dashboard', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('tenant.dashboard');
    
    // Profile Completion
    Route::get('/profile-completion', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.profile-completion', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('tenant.profile-completion');
    
    // Master Data Setup
    Route::get('/master-setup', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.master-setup', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('tenant.master-setup');
    
    // Debug Profile (for troubleshooting)
    Route::get('/debug-profile', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.debug-profile', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('tenant.debug-profile');
    
    // Organization Settings
    Route::get('/settings', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.settings', ['organization' => $org]);
    })->name('tenant.settings');
    
    // Users Management
    Route::prefix('users')->name('tenant.users.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.users.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
        
        Route::get('/create', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.users.create', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('create');
        
        Route::get('/{id}', function ($id) {
            $org = request()->get('tenant_organization');
            return view('tenant.users.show', [
                'userId' => $id,
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('show');
    });
    
    // Departments Management
    Route::prefix('departments')->name('tenant.departments.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.departments.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
        
        Route::get('/create', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.departments.create', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('create');
    });
    
    // Roles Management
    Route::prefix('roles')->name('tenant.roles.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.roles.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
        
        Route::get('/create', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.roles.create', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('create');
    });
    
    // Reports
    Route::prefix('reports')->name('tenant.reports.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.reports.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Materials Management
    Route::prefix('materials')->name('tenant.materials.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.materials.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Products Management
    Route::prefix('products')->name('tenant.products.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.products.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Warehouses Management
    Route::prefix('warehouses')->name('tenant.warehouses.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.warehouses.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // UOM Management
    Route::prefix('uom')->name('tenant.uom.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.uom.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Vendors Management
    Route::prefix('vendors')->name('tenant.vendors.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.vendors.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Approval Matrix Management
    Route::prefix('approval-matrix')->name('tenant.approval-matrix.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.approval-matrix.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
        
        Route::get('/create', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.approval-matrix.create', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('create');
    });
    
    // Bin Locations Management
    Route::prefix('bin-locations')->name('tenant.bin-locations.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.bin-locations.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // HSN Codes Management
    Route::prefix('hsn-codes')->name('tenant.hsn-codes.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.hsn-codes.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // GST Taxes Management
    Route::prefix('gst-taxes')->name('tenant.gst-taxes.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.gst-taxes.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Currency Management
    Route::prefix('currency')->name('tenant.currency.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.currency.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Vendor Contacts Management
    Route::prefix('vendor-contacts')->name('tenant.vendor-contacts.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.vendor-contacts.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Vendor Material Map Management
    Route::prefix('vendor-material-map')->name('tenant.vendor-material-map.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.vendor-material-map.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // BOM Header Management
    Route::prefix('bom-header')->name('tenant.bom-header.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.bom-header.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // BOM Detail Management
    Route::prefix('bom-detail')->name('tenant.bom-detail.')->group(function () {
        Route::get('/', function () {
            $org = request()->get('tenant_organization');
            return view('tenant.bom-detail.index', [
                'organization' => $org,
                'tenantType' => request()->get('tenant_type')
            ]);
        })->name('index');
    });
    
    // Profile
    Route::get('/profile', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.profile', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('tenant.profile');
    
    // Logout
    Route::post('/logout', function () {
        return redirect()->route('login')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('success', 'You have been logged out successfully');
    })->name('tenant.logout');
});
