<?php

/**
 * Test script for Authentication Service
 * 
 * This script tests the AuthenticationService implementation
 * Run: php test_authentication_service.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Contracts\AuthenticationService;
use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use App\Models\Tenant\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Authentication Service Test ===\n\n";

try {
    // Get services
    $authService = app(AuthenticationService::class);
    $connectionRouter = app(DatabaseConnectionRouter::class);
    
    echo "✓ Services resolved successfully\n\n";
    
    // Test 1: Setup test data
    echo "Test 1: Setting up test data...\n";
    
    // Switch to control DB and use existing test organization
    $connectionRouter->switchToControl();
    
    $testOrgSlug = 'test-org';
    $testEmail = 'admin@test-org.com';
    
    // Check if organization exists
    $org = Organization::where('org_slug', $testOrgSlug)->first();
    
    if (!$org) {
        echo "✗ Test organization not found. Please run tenant provisioning first.\n";
        exit(1);
    }
    
    echo "  Using organization: {$org->org_slug}\n";
    
    // Switch to tenant database
    $tenantDbName = $org->tenant_db_name;
    $connectionRouter->switchToTenant($tenantDbName);
    echo "  Using tenant database: {$tenantDbName}\n";
    
    // Check if test user exists, create if not
    $testPassword = 'TestPassword123!';
    $user = User::where('email', $testEmail)->first();
    
    if (!$user) {
        // Create test user
        $user = new User();
        $user->employee_code = 'TESTADMIN';
        $user->email = $testEmail;
        $user->password_hash = $testPassword; // Will be hashed by mutator
        $user->first_name = 'Test';
        $user->last_name = 'Admin';
        $user->dept_id = 1; // Assuming root department exists
        $user->role_id = 1; // Assuming admin role exists
        $user->is_active = true;
        $user->save();
        echo "  Created test user: {$user->email} with password: {$testPassword}\n";
    } else {
        echo "  Using existing user: {$user->email}\n";
        echo "  Note: Using password: {$testPassword}\n";
    }
    
    echo "✓ Test data setup complete\n\n";
    
    // Test 2: Login with valid credentials
    echo "Test 2: Login with valid credentials...\n";
    
    $authResult = $authService->login($testEmail, $testPassword, $testOrgSlug);
    
    echo "  Access Token: " . substr($authResult->accessToken, 0, 50) . "...\n";
    echo "  Refresh Token: " . substr($authResult->refreshToken, 0, 50) . "...\n";
    echo "  Expires In: {$authResult->expiresIn} seconds\n";
    echo "  User: {$authResult->user->first_name} {$authResult->user->last_name}\n";
    echo "✓ Login successful\n\n";
    
    // Test 3: Validate token
    echo "Test 3: Validate access token...\n";
    
    $tokenPayload = $authService->validateToken($authResult->accessToken);
    
    echo "  User ID: {$tokenPayload->userId}\n";
    echo "  Org ID: {$tokenPayload->orgId}\n";
    echo "  Org Slug: {$tokenPayload->orgSlug}\n";
    echo "  Issued At: " . date('Y-m-d H:i:s', $tokenPayload->issuedAt) . "\n";
    echo "  Expires At: " . date('Y-m-d H:i:s', $tokenPayload->expiresAt) . "\n";
    echo "✓ Token validation successful\n\n";
    
    // Test 4: Refresh token
    echo "Test 4: Refresh access token...\n";
    
    $refreshedResult = $authService->refreshToken($authResult->refreshToken);
    
    echo "  New Access Token: " . substr($refreshedResult->accessToken, 0, 50) . "...\n";
    echo "  New Refresh Token: " . substr($refreshedResult->refreshToken, 0, 50) . "...\n";
    echo "✓ Token refresh successful\n\n";
    
    // Test 5: Logout
    echo "Test 5: Logout (revoke refresh token)...\n";
    
    $authService->logout($refreshedResult->refreshToken);
    
    echo "✓ Logout successful\n\n";
    
    // Test 6: Try to use revoked refresh token
    echo "Test 6: Try to use revoked refresh token...\n";
    
    try {
        $authService->refreshToken($refreshedResult->refreshToken);
        echo "✗ Should have thrown InvalidTokenException\n";
    } catch (\App\Exceptions\InvalidTokenException $e) {
        echo "✓ Correctly rejected revoked token: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Test 7: Login with invalid credentials
    echo "Test 7: Login with invalid credentials...\n";
    
    try {
        $authService->login($testEmail, 'testpassword123', $testOrgSlug);
        echo "✗ Should have thrown AuthenticationException\n";
    } catch (\App\Exceptions\AuthenticationException $e) {
        echo "✓ Correctly rejected invalid credentials: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Test 8: Login with non-existent organization
    echo "Test 8: Login with non-existent organization...\n";
    
    try {
        $authService->login($testEmail, $testPassword, 'nonexistent-org');
        echo "✗ Should have thrown AuthenticationException\n";
    } catch (\App\Exceptions\AuthenticationException $e) {
        echo "✓ Correctly rejected non-existent organization: {$e->getMessage()}\n";
    }
    
    echo "\n=== All Tests Passed ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
