<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Config;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Configuration Validator Test ===\n\n";

$validator = new \App\Services\ConfigValidator();

// Test 1: Validate all configuration
echo "Test 1: Validating all configuration...\n";
try {
    $validator->validateAll();
    echo "✓ All configuration is valid\n\n";
} catch (RuntimeException $e) {
    echo "✗ Configuration validation failed:\n";
    echo $e->getMessage() . "\n\n";
}

// Test 2: Test individual timezone validation
echo "Test 2: Testing timezone validation...\n";
$testTimezones = ['UTC', 'America/New_York', 'Invalid/Timezone', 'Asia/Kolkata'];
foreach ($testTimezones as $tz) {
    try {
        $validator->validateTimezone($tz);
        echo "  ✓ '{$tz}' is valid\n";
    } catch (InvalidArgumentException $e) {
        echo "  ✗ '{$tz}' is invalid: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 3: Test currency validation
echo "Test 3: Testing currency validation...\n";
$testCurrencies = ['USD', 'EUR', 'INR', 'XYZ', 'GBP'];
foreach ($testCurrencies as $currency) {
    try {
        $validator->validateCurrency($currency);
        echo "  ✓ '{$currency}' is valid\n";
    } catch (InvalidArgumentException $e) {
        echo "  ✗ '{$currency}' is invalid: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 4: Test country validation
echo "Test 4: Testing country validation...\n";
$testCountries = ['US', 'IN', 'GB', 'XX', 'AU'];
foreach ($testCountries as $country) {
    try {
        $validator->validateCountry($country);
        echo "  ✓ '{$country}' is valid\n";
    } catch (InvalidArgumentException $e) {
        echo "  ✗ '{$country}' is invalid: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 5: Check configuration values
echo "Test 5: Checking key configuration values...\n";
echo "  Control DB Host: " . Config::get('database.connections.control.host') . "\n";
echo "  Control DB Name: " . Config::get('database.connections.control.database') . "\n";
echo "  Tenant DB Prefix: " . Config::get('tenant.database_prefix') . "\n";
echo "  Default Timezone: " . Config::get('tenant.defaults.timezone') . "\n";
echo "  Default Currency: " . Config::get('tenant.defaults.currency_code') . "\n";
echo "  Trial Duration: " . Config::get('subscription.trial.duration_days') . " days\n";
echo "  Grace Period: " . Config::get('subscription.grace_period.duration_days') . " days\n";
echo "  Default Gateway: " . Config::get('payment.default_gateway') . "\n";
echo "\n";

// Test 6: Test isValid method
echo "Test 6: Testing isValid() method...\n";
if ($validator->isValid()) {
    echo "✓ Configuration is valid\n";
} else {
    echo "✗ Configuration has errors:\n";
    foreach ($validator->getErrors() as $error) {
        echo "  - {$error}\n";
    }
}
echo "\n";

echo "=== Configuration Validator Test Complete ===\n";
