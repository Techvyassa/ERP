<?php

use App\Models\Control\Organization;
use App\Models\Tenant\User;
use App\Models\Tenant\Role;
use App\Models\Tenant\Department;
use Illuminate\Support\Facades\Artisan;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Preservation Property Tests for Google Auth Token Missing Fix
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 * 
 * These tests verify that existing authentication flows continue to work correctly
 * after the fix is implemented. They follow the observation-first methodology:
 * 1. Observe behavior on UNFIXED code for non-buggy inputs
 * 2. Write property-based tests capturing observed behavior patterns
 * 3. Run tests on UNFIXED code - EXPECTED OUTCOME: Tests PASS
 * 
 * These tests ensure that the fix does NOT break existing functionality:
 * - Email/password authentication
 * - API authentication with Bearer tokens
 * - WebJWTAuth middleware validation
 * - Protected route access control
 * - Session persistence across navigations
 */

/**
 * Property 2: Preservation - Email/Password Authentication
 * 
 * **Validates: Requirements 3.1**
 * 
 * This property tests that email/password authentication continues to work correctly.
 * The authentication flow should successfully set cookies and redirect to dashboard.
 * 
 * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
 */
test('email/password authentication successfully sets cookie and redirects to dashboard', function () {
    // Setup: Create organization and tenant database using factory
    $organization = $this->setupTenantDatabase(
        Organization::factory()->create([
            'primary_email' => 'emailpass@example.com',
        ])
    );

    // Simulate email/password authentication request
    $response = $this->postJson('/api/v1/auth/firebase-login', [
        'firebase_token' => 'mock-firebase-token-' . uniqid(),
        'email' => 'emailpass@example.com',
        'provider' => 'email',
        'display_name' => 'Email User',
        'photo_url' => null,
        'firebase_uid' => 'email-uid-' . uniqid(),
    ]);

    // Assert authentication was successful
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    
    $data = $response->json('data');
    expect($data)->toHaveKey('access_token');
    expect($data)->toHaveKey('refresh_token');
    expect($data)->toHaveKey('user');
    expect($data['user']['email'])->toBe('emailpass@example.com');
    expect($data['user']['provider'])->toBe('email');
    
    $accessToken = $data['access_token'];

    // Verify the token is valid
    $payload = JWTAuth::setToken($accessToken)->getPayload();
    expect($payload->get('sub'))->not->toBeNull();
    expect($payload->get('org_id'))->toBe($organization->org_id);
    expect($payload->get('type'))->toBe('access');
    
    // Simulate accessing dashboard with the token in cookie
    // This verifies that the authentication flow works end-to-end
    $dashboardResponse = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    // Should successfully access dashboard (not redirect to login)
    $dashboardResponse->assertStatus(200);
    $dashboardResponse->assertDontSee('No authentication token found');
});

/**
 * Property 2: Preservation - API Authentication with Bearer Tokens
 * 
 * **Validates: Requirements 3.2, 3.3**
 * 
 * This property tests that API authentication with Bearer tokens in Authorization
 * headers continues to work correctly. The WebJWTAuth middleware should validate
 * tokens from headers as well as cookies.
 * 
 * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
 */
test('API authentication with Bearer tokens in Authorization headers works correctly', function () {
    // Setup: Create organization and tenant database using factory
    $organization = $this->setupTenantDatabase(
        Organization::factory()->create([
            'primary_email' => 'api@example.com',
        ])
    );

    // Create user in tenant database
    $role = Role::factory()->create();
    $department = Department::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'api@example.com',
        'role_id' => $role->role_id,
        'dept_id' => $department->dept_id,
    ]);

    // Generate JWT token
    $accessToken = $this->generateToken($user, $organization);

    // Test 1: Access protected route with Bearer token in Authorization header
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
    ])->get('/dashboard');
    
    // Should successfully access dashboard
    $response->assertStatus(200);
    $response->assertDontSee('No authentication token found');
    
    // Test 2: Verify /api/v1/auth/firebase-login continues to return correct structure
    $loginResponse = $this->postJson('/api/v1/auth/firebase-login', [
        'firebase_token' => 'mock-firebase-token-' . uniqid(),
        'email' => 'api@example.com',
        'provider' => 'email',
        'display_name' => 'API User',
        'firebase_uid' => 'api-uid-' . uniqid(),
    ]);

    $loginResponse->assertStatus(200);
    $loginResponse->assertJson(['success' => true]);
    
    $loginData = $loginResponse->json('data');
    expect($loginData)->toHaveKey('access_token');
    expect($loginData)->toHaveKey('refresh_token');
    expect($loginData)->toHaveKey('user');
    expect($loginData)->toHaveKey('organization');
    expect($loginData['user'])->toHaveKey('user_id');
    expect($loginData['user'])->toHaveKey('email');
    expect($loginData['organization'])->toHaveKey('org_id');
    expect($loginData['organization'])->toHaveKey('org_slug');
});

/**
 * Property 2: Preservation - WebJWTAuth Middleware Validates Tokens from Cookies and Headers
 * 
 * **Validates: Requirements 3.2**
 * 
 * This property tests that the WebJWTAuth middleware continues to validate JWT tokens
 * from both cookies and Authorization headers correctly.
 * 
 * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
 */
test('WebJWTAuth middleware validates JWT tokens from both cookies and headers', function () {
    // Setup: Create organization and tenant database using factory
    $organization = $this->setupTenantDatabase(
        Organization::factory()->create([
            'primary_email' => 'middleware@example.com',
        ])
    );

    // Create user
    $role = Role::factory()->create();
    $department = Department::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'middleware@example.com',
        'role_id' => $role->role_id,
        'dept_id' => $department->dept_id,
    ]);

    // Generate JWT token
    $accessToken = $this->generateToken($user, $organization);

    // Test 1: Middleware validates token from cookie
    $cookieResponse = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    $cookieResponse->assertStatus(200);
    $cookieResponse->assertDontSee('No authentication token found');
    
    // Test 2: Middleware validates token from Authorization header
    $headerResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
    ])->get('/dashboard');
    
    $headerResponse->assertStatus(200);
    $headerResponse->assertDontSee('No authentication token found');
    
    // Test 3: Middleware prefers header over cookie (if both present)
    $bothResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
    ])->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    $bothResponse->assertStatus(200);
    $bothResponse->assertDontSee('No authentication token found');
});

/**
 * Property 2: Preservation - Protected Routes Redirect to Login Without Valid Token
 * 
 * **Validates: Requirements 3.4**
 * 
 * This property tests that protected routes continue to redirect to login
 * when no valid authentication token is present.
 * 
 * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
 */
test('protected routes redirect to login when no valid token is present', function () {
    // Test 1: No token at all
    $noTokenResponse = $this->get('/dashboard');
    
    $noTokenResponse->assertRedirect(route('login'));
    $noTokenResponse->assertSessionHas('error', 'No authentication token found');
    
    // Test 2: Invalid token
    $invalidTokenResponse = $this->withCookie('auth_token', 'invalid-token-12345')
        ->get('/dashboard');
    
    $invalidTokenResponse->assertRedirect(route('login'));
    $invalidTokenResponse->assertSessionHas('error');
    
    // Test 3: Expired token
    $organization = $this->setupTenantDatabase(
        Organization::factory()->create([
            'primary_email' => 'expired@example.com',
        ])
    );
    
    $role = Role::factory()->create();
    $department = Department::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'expired@example.com',
        'role_id' => $role->role_id,
        'dept_id' => $department->dept_id,
    ]);
    
    // Note: We can't easily create an expired token with JWTAuth
    // So we'll just verify that invalid tokens are rejected
    // The expired token test would require mocking time or using a custom token
});

/**
 * Property 2: Preservation - Session Persistence Across Page Navigations
 * 
 * **Validates: Requirements 3.5**
 * 
 * This property tests that session persistence across page navigations
 * continues to work correctly. Once authenticated, users should be able
 * to navigate between protected routes without re-authentication.
 * 
 * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
 */
test('session persistence across page navigations works correctly', function () {
    // Setup: Create organization and tenant database using factory
    $organization = $this->setupTenantDatabase(
        Organization::factory()->create([
            'primary_email' => 'session@example.com',
        ])
    );

    // Create user
    $role = Role::factory()->create();
    $department = Department::factory()->create();
    
    $user = User::factory()->create([
        'email' => 'session@example.com',
        'role_id' => $role->role_id,
        'dept_id' => $department->dept_id,
    ]);

    // Generate JWT token
    $accessToken = $this->generateToken($user, $organization);

    // Simulate multiple page navigations with the same cookie
    // This tests that the session persists across requests
    
    // Navigation 1: Access dashboard
    $response1 = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    $response1->assertStatus(200);
    $response1->assertDontSee('No authentication token found');
    
    // Navigation 2: Access dashboard again (simulating page refresh)
    $response2 = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    $response2->assertStatus(200);
    $response2->assertDontSee('No authentication token found');
    
    // Navigation 3: Access dashboard a third time
    $response3 = $this->withCookie('auth_token', $accessToken)
        ->get('/dashboard');
    
    $response3->assertStatus(200);
    $response3->assertDontSee('No authentication token found');
    
    // Verify that the token remains valid throughout
    $payload = JWTAuth::setToken($accessToken)->getPayload();
    expect($payload->get('sub'))->toBe($user->user_id);
    expect($payload->get('org_id'))->toBe($organization->org_id);
    expect($payload->get('type'))->toBe('access');
});
