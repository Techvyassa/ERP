<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$org = \App\Models\Control\Organization::where('org_slug', 'test-org')->first();

if ($org) {
    echo "Organization: {$org->org_slug}\n";
    echo "Tenant DB: {$org->tenant_db_name}\n";
    echo "Status: {$org->registration_status}\n";
    
    // Check if database exists
    $result = \Illuminate\Support\Facades\DB::connection('control')
        ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$org->tenant_db_name]);
    
    echo "Database exists: " . (!empty($result) ? 'Yes' : 'No') . "\n";
} else {
    echo "Organization not found\n";
}
