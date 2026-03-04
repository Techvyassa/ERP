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
