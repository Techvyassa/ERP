<?php

use App\Models\Control\Organization;
use Illuminate\Support\Facades\Artisan;

/**
 * Bug Condition Exploration Test for Google Auth Token Missing
 * 
 * **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3**
 * 
 * This test explores the bug condition where the auth_token cookie is NOT available
 * to the WebJWTAuth middleware after Google OAuth authentication due to timing issues.
 * 
 * CRITICAL: This test MUST FAIL on unfixed code - failure confirms the bug exists.
 * 
 * Bug Condition:
 * - Provider: Google OAuth
 * - Cookie Set Method: Client-side JavaScript (document.cookie)
 * - Redirect Method: Immediate window.location.href
 * - Expected Outcome on UNFIXED code: Cookie NOT available on next request
 * 
 * Expected Behavior (after fix):
 * - Cookie should be set server-side in HTTP response
 * - Cookie should be available to WebJWTAuth middleware on next request
 */

/**
 * Property 1: Fault Condition - Cookie Available After Google OAuth
 * 
 * This property tests that after successful Google OAuth authentication,
 * the auth_token cookie is set server-side and available for the next request.
 * 
 * On UNFIXED code: This test will FAIL because the cookie is not set server-side
 * On FIXED code: This will PASS because the cookie is set server-side
 */
test('Google OAuth authentication sets auth_token cookie server-side', function () {
    // Setup: Create organization
    $organization = Organization::create([
        'org_slug' => 'test-org-' . uniqid(),
        'org_name' => 'Test Organization',
        'primary_email' => 'test@example.com',
        'registration_status' => 'ACTIVE',
        'tenant_db_name' => 'tenant_test',
        'primary_phone' => '+1234567890',
        'address_line1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'TS',
        'postal_code' => '12345',
        'country_code' => 'US',
        'timezone' => 'UTC',
        'currency_code' => 'USD',
        'max_users' => 10,
    ]);
    
    // Setup tenant database
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => 'database/migrations/tenant',
        '--force' => true,
    ]);

    // Simulate Google OAuth authentication request
    $response = $this->postJson('/api/v1/auth/firebase-login', [
        'firebase_token' => 'mock-firebase-token-' . uniqid(),
        'email' => 'test@example.com',
        'provider' => 'google',
        'display_name' => 'Test User',
        'photo_url' => 'https://example.com/photo.jpg',
        'firebase_uid' => 'google-uid-' . uniqid(),
    ]);

    // Assert authentication was successful
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    
    $data = $response->json('data');
    expect($data)->toHaveKey('access_token');
    expect($data)->toHaveKey('user');
    
    $accessToken = $data['access_token'];

    // CRITICAL ASSERTION: Cookie should be set server-side in the response
    // On UNFIXED code: This will FAIL - no cookie in response
    // On FIXED code: This will PASS - cookie is set server-side
    $response->assertCookie('auth_token', $accessToken);
    
    // Verify cookie attributes are correct
    $cookies = $response->headers->getCookies();
    $authCookie = collect($cookies)->firstWhere('getName', 'auth_token');
    
    expect($authCookie)->not->toBeNull('auth_token cookie should be present in response');
    expect($authCookie->getValue())->toBe($accessToken);
    expect($authCookie->getPath())->toBe('/');
    expect($authCookie->getMaxAge())->toBe(86400); // 24 hours
    
    // Simulate the next request to /dashboard with the cookie
    // This simulates what happens when the browser redirects after authentication
    $dashboardResponse = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    // CRITICAL ASSERTION: Middleware should find the cookie and NOT redirect to login
    // On UNFIXED code: This might FAIL if cookie timing causes issues
    // On FIXED code: This will PASS - cookie is available to middleware
    $dashboardResponse->assertStatus(200);
    $dashboardResponse->assertDontSee('No authentication token found');
});

/**
 * Property 1 (Negative Test): Verify middleware behavior without cookie
 * 
 * This test verifies that the WebJWTAuth middleware correctly rejects
 * requests without the auth_token cookie (baseline behavior).
 */
test('WebJWTAuth middleware rejects requests without auth_token cookie', function () {
    // Attempt to access protected route without cookie
    $response = $this->get('/dashboard');

    // Should redirect to login with error message
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'No authentication token found');
});
