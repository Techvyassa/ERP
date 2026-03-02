<?php

use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('landing');
})->name('home');

// Firebase test page (for debugging)
Route::get('/test-firebase', function () {
    return view('test-firebase');
})->name('test.firebase');

// New Flow: Subscription → Register → Login → Dashboard

// Step 1: Subscription selection (public)
Route::get('/pricing', function () {
    return view('subscription.select');
})->name('pricing');

// Step 2: Registration with selected plan (public)
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Step 3: Login (public)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

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
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard.index');
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
