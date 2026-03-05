<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Models\Control\Organization;

// Landing page with subscription plans
Route::get('/', [PublicController::class, 'landing'])->name('home');

// New Flow: Subscription → Register → Login → Dashboard

// Step 1: Subscription selection (public)
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

// Step 2: Registration with selected plan (public)
Route::get('/register', [PublicController::class, 'register'])->name('register');

// Step 3: Login (public)
Route::get('/login', [PublicController::class, 'login'])->name('login');

// Google OAuth routes
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

// Protected routes (require authentication)
Route::middleware(['web.jwt'])->group(function () {
    // Main dashboard route (redirects to org-specific dashboard)
    Route::get('/dashboard', function () {
        return view('dashboard.main');
    })->name('dashboard');
    
    // Super Admin / Control Panel Routes
    Route::prefix('control')->name('control.')->group(function () {
        Route::get('/dashboard', function () {
            return view('control.dashboard');
        })->name('dashboard');
        
        Route::get('/organizations', function () {
            return view('control.organizations.index');
        })->name('organizations.index');
        
        Route::get('/subscriptions', function () {
            return view('control.subscriptions.index');
        })->name('subscriptions.index');
        
        Route::get('/plans', function () {
            return view('control.plans.index');
        })->name('plans.index');
        
        Route::get('/payments', function () {
            return view('control.payments.index');
        })->name('payments.index');
        
        Route::get('/features', function () {
            return view('control.features.index');
        })->name('features.index');
        
        Route::get('/settings', function () {
            return view('control.settings');
        })->name('settings');
        
        Route::get('/profile', function () {
            return view('control.profile');
        })->name('profile');
    });
    
    // Organization-specific routes (path-based tenant routing)
    Route::prefix('org/{org_slug}')->group(function () {
        // Helper function to get organization
        $getOrg = function ($orgSlug) {
            $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
            $tenantType = 'path';
            return compact('org', 'tenantType');
        };
        
        // Main Dashboard
        Route::get('/dashboard', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.dashboard', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.dashboard');
        
        // Profile Completion
        Route::get('/profile-completion', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.profile-completion', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.profile-completion');
        
        // Master Data Setup Dashboard
        Route::get('/master-setup', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.master-setup', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.master-setup');
        
        // Organization Masters
        Route::get('/departments', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.departments.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.departments.index');
        
        Route::get('/roles', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.roles.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.roles.index');
        
        Route::get('/users', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.users.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.users.index');
        
        Route::get('/approval-matrix', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.approval-matrix.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.approval-matrix.index');
        
        // Inventory Masters
        Route::get('/materials', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.materials.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.materials.index');
        
        Route::get('/products', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.products.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.products.index');
        
        Route::get('/warehouses', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.warehouses.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.warehouses.index');
        
        Route::get('/bin-locations', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.bin-locations.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.bin-locations.index');
        
        Route::get('/uom', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.uom.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.uom.index');
        
        // Vendor Masters
        Route::get('/vendors', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.vendors.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.vendors.index');
        
        Route::get('/vendor-contacts', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.vendor-contacts.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.vendor-contacts.index');
        
        Route::get('/vendor-material-map', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.vendor-material-map.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.vendor-material-map.index');
        
        // Tax Masters
        Route::get('/hsn-codes', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.hsn-codes.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.hsn-codes.index');
        
        Route::get('/gst-taxes', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.gst-taxes.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.gst-taxes.index');
        
        Route::get('/currency', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.currency.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.currency.index');
        
        // BOM Masters
        Route::get('/bom-header', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.bom-header.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.bom-header.index');
        
        Route::get('/bom-detail', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.bom-detail.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.bom-detail.index');
        
        // Other Pages
        Route::get('/reports', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.reports.index', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.reports.index');
        
        Route::get('/settings', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.settings', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.settings');
        
        Route::get('/profile', function ($orgSlug) use ($getOrg) {
            extract($getOrg($orgSlug));
            return view('tenant.profile', [
                'organization' => $org,
                'tenantType' => $tenantType
            ]);
        })->name('tenant.profile');
    });

    // Logout
    Route::post('/logout', function () {
        return redirect()->route('home')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('success', 'You have been logged out successfully');
    })->name('logout');
});

// Test routes (for debugging)
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




// Firebase test page (for debugging)
Route::get('/test-firebase', function () {
    return view('test-firebase');
})->name('test.firebase');

// Cookie test page
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

// Tenant diagnostic page
Route::get('/test-tenant', function () {
    $host = request()->getHost();
    $mainDomain = config('app.domain', 'localhost');

    // Extract subdomain
    $hostParts = explode(':', $host)[0];
    $pattern = '/^(.+)\.' . preg_quote($mainDomain, '/') . '$/';
    $subdomain = null;
    if (preg_match($pattern, $hostParts, $matches)) {
        $subdomain = $matches[1];
    }

    // Get all organizations
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
