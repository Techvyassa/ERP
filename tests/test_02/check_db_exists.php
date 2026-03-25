<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tenantDbName = 'erp_test_org';

echo "Checking if database '{$tenantDbName}' exists...\n";

$result = DB::connection('control')
    ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenantDbName]);

if (!empty($result)) {
    echo "✓ Database exists\n";
    var_dump($result);
} else {
    echo "✗ Database does not exist\n";
}

// Try to connect directly
echo "\nTrying to connect to tenant database...\n";

try {
    \Illuminate\Support\Facades\Config::set('database.connections.tenant', [
        'driver' => 'mysql',
        'host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
        'database' => $tenantDbName,
        'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    $pdo = DB::connection('tenant')->getPdo();
    echo "✓ Successfully connected to tenant database\n";
    
    // Try a simple query
    $tables = DB::connection('tenant')->select('SHOW TABLES');
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - {$tableName}\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Failed to connect: " . $e->getMessage() . "\n";
}
