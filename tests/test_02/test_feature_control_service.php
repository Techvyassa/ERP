<?php

/**
 * Test script for FeatureControlService
 * 
 * This script tests the FeatureControlService implementation
 * Run: php test_feature_control_service.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Contracts\FeatureControlService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use array cache for testing (doesn't require Redis)
Config::set('cache.default', 'array');

echo "=== FeatureControlService Test ===\n\n";

try {
    $service = app(FeatureControlService::class);
    
    // Setup: Ensure we have a test organization
    echo "Setup: Checking for test organization\n";
    $org = DB::connection('control')->table('organizations')->where('org_id', 1)->first();
    
    if (!$org) {
        echo "Creating test organization...\n";
        DB::connection('control')->table('organizations')->insert([
            'org_id' => 1,
            'org_slug' => 'test-org',
            'org_name' => 'Test Organization',
            'tenant_db_name' => 'erp_test_org',
            'registration_status' => 'ACTIVE',
            'primary_email' => 'test@example.com',
            'country_code' => 'US',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'max_users' => 10,
            'created_at' => now(),
        ]);
        echo "✓ Test organization created\n\n";
    } else {
        echo "✓ Test organization exists\n\n";
    }
    
    // Test 1: Get feature value that doesn't exist (should return default)
    echo "Test 1: Get non-existent feature (should return default)\n";
    $value = $service->getFeatureValue(1, 'non_existent_feature', 'default_value');
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: 'default_value'\n";
    echo ($value === 'default_value' ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 2: Create a test feature control
    echo "Test 2: Create test feature control\n";
    DB::connection('control')->table('feature_controls')->insert([
        'org_id' => 1,
        'feature_key' => 'test_boolean_feature',
        'feature_type' => 'BOOLEAN',
        'feature_value' => '1',
        'effective_from' => null,
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Feature control created\n\n";
    
    // Test 3: Get boolean feature
    echo "Test 3: Get boolean feature\n";
    $value = $service->isFeatureEnabled(1, 'test_boolean_feature', false);
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: true\n";
    echo ($value === true ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 4: Create numeric feature control
    echo "Test 4: Create numeric feature control\n";
    DB::connection('control')->table('feature_controls')->insert([
        'org_id' => 1,
        'feature_key' => 'max_users_override',
        'feature_type' => 'NUMERIC',
        'feature_value' => '100',
        'effective_from' => null,
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Numeric feature control created\n\n";
    
    // Test 5: Get numeric feature
    echo "Test 5: Get numeric feature\n";
    $value = $service->getNumericFeature(1, 'max_users_override', 10);
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: 100\n";
    echo ($value === 100 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 6: Create feature with effective dates (not yet effective)
    echo "Test 6: Create feature with future effective_from date\n";
    DB::connection('control')->table('feature_controls')->insert([
        'org_id' => 1,
        'feature_key' => 'future_feature',
        'feature_type' => 'BOOLEAN',
        'feature_value' => '1',
        'effective_from' => now()->addDays(7)->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Future feature control created\n\n";
    
    // Test 7: Get future feature (should return default)
    echo "Test 7: Get future feature (should return default)\n";
    Cache::flush(); // Clear cache to force fresh query
    $value = $service->isFeatureEnabled(1, 'future_feature', false);
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: false (not yet effective)\n";
    echo ($value === false ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 8: Create feature with past effective_to date
    echo "Test 8: Create feature with past effective_to date\n";
    DB::connection('control')->table('feature_controls')->insert([
        'org_id' => 1,
        'feature_key' => 'expired_feature',
        'feature_type' => 'BOOLEAN',
        'feature_value' => '1',
        'effective_from' => now()->subDays(14)->toDateString(),
        'effective_to' => now()->subDays(7)->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Expired feature control created\n\n";
    
    // Test 9: Get expired feature (should return default)
    echo "Test 9: Get expired feature (should return default)\n";
    Cache::flush(); // Clear cache to force fresh query
    $value = $service->isFeatureEnabled(1, 'expired_feature', false);
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: false (expired)\n";
    echo ($value === false ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 10: Get all features
    echo "Test 10: Get all effective features\n";
    Cache::flush(); // Clear cache to force fresh query
    $features = $service->getAllFeatures(1);
    echo "Result: " . json_encode($features, JSON_PRETTY_PRINT) . "\n";
    echo "Expected: Should contain test_boolean_feature and max_users_override\n";
    $hasBoolean = isset($features['test_boolean_feature']) && $features['test_boolean_feature'] === true;
    $hasNumeric = isset($features['max_users_override']) && $features['max_users_override'] === 100;
    echo ($hasBoolean && $hasNumeric ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 11: Test caching
    echo "Test 11: Test caching (second call should be faster)\n";
    $start = microtime(true);
    $value1 = $service->getNumericFeature(1, 'max_users_override', 10);
    $time1 = microtime(true) - $start;
    
    $start = microtime(true);
    $value2 = $service->getNumericFeature(1, 'max_users_override', 10);
    $time2 = microtime(true) - $start;
    
    echo "First call: " . ($time1 * 1000) . "ms\n";
    echo "Second call (cached): " . ($time2 * 1000) . "ms\n";
    echo "Values match: " . ($value1 === $value2 ? "Yes" : "No") . "\n";
    echo ($value1 === $value2 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 12: Clear cache
    echo "Test 12: Clear cache\n";
    $service->clearCache(1);
    echo "✓ Cache cleared\n\n";
    
    // Test 13: Test JSON feature type
    echo "Test 13: Create and get JSON feature\n";
    DB::connection('control')->table('feature_controls')->insert([
        'org_id' => 1,
        'feature_key' => 'custom_integrations',
        'feature_type' => 'JSON',
        'feature_value' => json_encode(['STRIPE', 'RAZORPAY', 'PAYPAL']),
        'effective_from' => null,
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Cache::flush();
    $value = $service->getFeatureValue(1, 'custom_integrations', []);
    echo "Result: " . json_encode($value) . "\n";
    echo "Expected: Array with 3 payment gateways\n";
    echo (is_array($value) && count($value) === 3 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Test 14: Test feature with plan fallback
    echo "Test 14: Test feature with plan fallback\n";
    // This assumes there's an active subscription and plan
    $value = $service->getFeatureWithPlanFallback(1, 'max_users_override', 'max_users');
    echo "Result: " . var_export($value, true) . "\n";
    echo "Expected: 100 (from feature control override)\n";
    echo ($value === 100 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    
    // Cleanup
    echo "Cleanup: Removing test feature controls\n";
    DB::connection('control')->table('feature_controls')
        ->where('org_id', 1)
        ->whereIn('feature_key', [
            'test_boolean_feature',
            'max_users_override',
            'future_feature',
            'expired_feature',
            'custom_integrations'
        ])
        ->delete();
    Cache::flush();
    echo "✓ Cleanup complete\n\n";
    
    echo "=== All Tests Complete ===\n";
    
} catch (\Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
