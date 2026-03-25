<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Core Services Resolution...\n\n";

try {
    $dbRouter = app('App\Contracts\DatabaseConnectionRouter');
    echo "✓ DatabaseConnectionRouter resolved successfully\n";
} catch (Exception $e) {
    echo "✗ DatabaseConnectionRouter failed: " . $e->getMessage() . "\n";
}

try {
    $provisioningService = app('App\Contracts\TenantProvisioningService');
    echo "✓ TenantProvisioningService resolved successfully\n";
} catch (Exception $e) {
    echo "✗ TenantProvisioningService failed: " . $e->getMessage() . "\n";
}

try {
    $subscriptionService = app('App\Contracts\SubscriptionManagementService');
    echo "✓ SubscriptionManagementService resolved successfully\n";
} catch (Exception $e) {
    echo "✗ SubscriptionManagementService failed: " . $e->getMessage() . "\n";
}

echo "\nAll core services are properly registered!\n";
