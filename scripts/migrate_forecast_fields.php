<?php

/**
 * Script to run the forecast calculation fields migration on all active tenants
 * 
 * Usage: php scripts/migrate_forecast_fields.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Control\Organization;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "Forecast Fields Migration Script\n";
echo "========================================\n\n";

// Get all active organizations
$organizations = Organization::where('registration_status', 'ACTIVE')
    ->whereNotNull('tenant_db_name')
    ->get();

if ($organizations->isEmpty()) {
    echo "No active organizations found.\n";
    exit(0);
}

echo "Found " . $organizations->count() . " active organization(s).\n\n";

$success = 0;
$failed = 0;

foreach ($organizations as $org) {
    echo "Processing: {$org->org_name} ({$org->org_slug})\n";
    echo "  Database: {$org->tenant_db_name}\n";
    
    try {
        // Run tenant migration
        Artisan::call('tenant:migrate', [
            'org_slug' => $org->org_slug
        ]);
        
        echo "  ✓ Migration completed successfully\n\n";
        $success++;
        
    } catch (\Exception $e) {
        echo "  ✗ Migration failed: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo "========================================\n";
echo "Summary:\n";
echo "  Success: {$success}\n";
echo "  Failed:  {$failed}\n";
echo "========================================\n\n";

exit($failed > 0 ? 1 : 0);
