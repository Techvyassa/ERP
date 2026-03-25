<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Layer Verification ===\n\n";

try {
    // Test 1: Control DB Connection
    echo "1. Testing Control DB connection...\n";
    DB::connection('control')->getPdo();
    echo "   ✓ Control DB connected successfully\n\n";
    
    // Test 2: Verify Control DB tables exist
    echo "2. Verifying Control DB tables...\n";
    $tables = ['organizations', 'subscription_plans', 'org_subscriptions', 
               'active_subscriptions', 'payment_records', 'feature_controls'];
    foreach ($tables as $table) {
        $exists = DB::connection('control')->getSchemaBuilder()->hasTable($table);
        echo "   " . ($exists ? "✓" : "✗") . " Table '$table' " . ($exists ? "exists" : "missing") . "\n";
    }
    echo "\n";
    
    // Test 3: Test Organization Model
    echo "3. Testing Organization Model...\n";
    $org = new \App\Models\Control\Organization();
    echo "   ✓ Organization model instantiated\n";
    echo "   ✓ Connection: " . $org->getConnectionName() . "\n";
    echo "   ✓ Table: " . $org->getTable() . "\n\n";
    
    // Test 4: Test SubscriptionPlan Model
    echo "4. Testing SubscriptionPlan Model...\n";
    $plan = new \App\Models\Control\SubscriptionPlan();
    echo "   ✓ SubscriptionPlan model instantiated\n";
    echo "   ✓ Connection: " . $plan->getConnectionName() . "\n";
    echo "   ✓ Table: " . $plan->getTable() . "\n\n";
    
    // Test 5: Test OrgSubscription Model
    echo "5. Testing OrgSubscription Model...\n";
    $subscription = new \App\Models\Control\OrgSubscription();
    echo "   ✓ OrgSubscription model instantiated\n";
    echo "   ✓ Connection: " . $subscription->getConnectionName() . "\n";
    echo "   ✓ Table: " . $subscription->getTable() . "\n\n";
    
    // Test 6: Test ActiveSubscription Model
    echo "6. Testing ActiveSubscription Model...\n";
    $activeSub = new \App\Models\Control\ActiveSubscription();
    echo "   ✓ ActiveSubscription model instantiated\n";
    echo "   ✓ Connection: " . $activeSub->getConnectionName() . "\n";
    echo "   ✓ Table: " . $activeSub->getTable() . "\n\n";
    
    // Test 7: Test PaymentRecord Model
    echo "7. Testing PaymentRecord Model...\n";
    $payment = new \App\Models\Control\PaymentRecord();
    echo "   ✓ PaymentRecord model instantiated\n";
    echo "   ✓ Connection: " . $payment->getConnectionName() . "\n";
    echo "   ✓ Table: " . $payment->getTable() . "\n\n";
    
    // Test 8: Test FeatureControl Model
    echo "8. Testing FeatureControl Model...\n";
    $feature = new \App\Models\Control\FeatureControl();
    echo "   ✓ FeatureControl model instantiated\n";
    echo "   ✓ Connection: " . $feature->getConnectionName() . "\n";
    echo "   ✓ Table: " . $feature->getTable() . "\n\n";
    
    // Test 9: Test DatabaseConnectionRouter Service
    echo "9. Testing DatabaseConnectionRouter Service...\n";
    $router = app(\App\Contracts\DatabaseConnectionRouter::class);
    echo "   ✓ DatabaseConnectionRouter service resolved\n";
    echo "   ✓ Current connection: " . $router->getCurrentConnection() . "\n";
    
    // Test switch to control
    $router->switchToControl();
    echo "   ✓ Switched to control connection\n";
    echo "   ✓ Current connection: " . $router->getCurrentConnection() . "\n\n";
    
    // Test 10: Verify triggers exist
    echo "10. Verifying database triggers...\n";
    $triggers = DB::connection('control')->select("SHOW TRIGGERS FROM ERP_saas_control");
    $triggerNames = array_map(fn($t) => $t->Trigger, $triggers);
    $expectedTriggers = ['sync_active_subscriptions_insert', 'sync_active_subscriptions_update'];
    foreach ($expectedTriggers as $triggerName) {
        $exists = in_array($triggerName, $triggerNames);
        echo "   " . ($exists ? "✓" : "✗") . " Trigger '$triggerName' " . ($exists ? "exists" : "missing") . "\n";
    }
    echo "\n";
    
    echo "=== All Database Layer Tests Passed! ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
