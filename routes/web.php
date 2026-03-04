<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Models\Control\Organization;

// Landing page with subscription plans
Route::get('/', [PublicController::class, 'landing'])->name('home');

// Firebase test page (for debugging)
Route::get('/test-firebase', function () {
    return view('test-firebase');
})->name('test.firebase');

// New Flow: Subscription → Register → Login → Dashboard

// Step 1: Subscription selection (public)
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

// Step 2: Registration with selected plan (public)
Route::get('/register', [PublicController::class, 'register'])->name('register');

// Step 3: Login (public)
Route::get('/login', [PublicController::class, 'login'])->name('login');

// Cookie test page
Route::get('/test-cookie', function () {
    return view('test-cookie');
})->name('test.cookie');

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

// Google OAuth routes
Route::get('/auth/google', function () {
    // Redirect to Google OAuth
    return redirect()->away('https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'redirect_uri' => url('/auth/google/callback'),
        'response_type' => 'code',
        'scope' => 'email profile',
    ]));
})->name('auth.google');

Route::get('/auth/google/callback', function () {
    // Handle Google OAuth callback
    // This will be implemented with proper OAuth handling
    return redirect()->route('dashboard');
})->name('auth.google.callback');

// Protected routes (require authentication)
Route::middleware(['web.jwt'])->group(function () {
    // Old dashboard route (will redirect to tenant dashboard)
    Route::get('/dashboard', function () {
        $orgSlug = request()->get('tenant_org_slug') ?? currentOrgSlug();
        
        if (!$orgSlug) {
            // Try to get from localStorage via redirect
            return view('redirect-to-tenant');
        }
        
        // Redirect to tenant dashboard
        $tenantMode = config('tenant.default_mode');
        if ($tenantMode === 'subdomain') {
            $domain = config('app.domain');
            $protocol = config('app.url_protocol');
            return redirect("{$protocol}://{$orgSlug}.{$domain}/dashboard");
        } else {
            return redirect("/org/{$orgSlug}/dashboard");
        }
    })->name('dashboard');
    
    // Organization setup
    Route::get('/setup/organization', function () {
        return view('setup.organization');
    })->name('setup.organization');
    
    // Logout
    Route::post('/logout', function () {
        return redirect()->route('home')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('success', 'You have been logged out successfully');
    })->name('logout');
});
