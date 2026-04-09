#!/usr/bin/env php
<?php

/**
 * Find Tenant Database Name
 * 
 * This script helps you find the correct tenant database name to use
 * with the RBAC migration command.
 * 
 * Usage:
 *   php scripts/find_tenant_db.php
 *   php scripts/find_tenant_db.php --slug=your-org-slug
 *   php scripts/find_tenant_db.php --list
 */

// Load Laravel bootstrap
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Parse arguments
$args = getopt('', ['slug:', 'list']);

try {
    if (isset($args['list'])) {
        // List all active tenants
        echo "\n📋 Available Tenant Databases:\n";
        echo str_repeat('=', 80) . "\n\n";

        $tenants = DB::connection('control')
            ->table('organizations')
            ->select('org_id', 'org_name', 'org_slug', 'tenant_db_name', 'registration_status as status', 'created_at')
            ->whereIn('registration_status', ['ACTIVE', 'SUSPENDED'])
            ->orderBy('org_name')
            ->get();

        if ($tenants->isEmpty()) {
            echo "❌ No tenants found in control database.\n\n";
            exit(1);
        }

        printf("%-5s | %-30s | %-20s | %-30s | %-12s\n", 
            'ID', 'Organization', 'Slug', 'Database Name', 'Status');
        echo str_repeat('-', 80) . "\n";

        foreach ($tenants as $tenant) {
            printf("%-5s | %-30s | %-20s | %-30s | %-12s\n",
                $tenant->org_id,
                substr($tenant->org_name, 0, 30),
                substr($tenant->org_slug, 0, 20),
                substr($tenant->tenant_db_name, 0, 30),
                $tenant->status
            );
        }

        echo "\n";
        echo "Usage:\n";
        echo "  php artisan rbac:migrate-simplified --tenant-db=DATABASE_NAME\n\n";
        echo "Example:\n";
        echo "  php artisan rbac:migrate-simplified --tenant-db={$tenants[0]->tenant_db_name}\n\n";

    } elseif (isset($args['slug'])) {
        // Find specific tenant by slug
        $slug = $args['slug'];
        
        echo "\n🔍 Searching for tenant with slug: {$slug}\n\n";

        $tenant = DB::connection('control')
            ->table('organizations')
            ->where('org_slug', $slug)
            ->first();

        if (!$tenant) {
            echo "❌ Tenant not found with slug: {$slug}\n\n";
            echo "Available slugs:\n";
            
            $tenants = DB::connection('control')
                ->table('organizations')
                ->pluck('org_slug');
            
            foreach ($tenants as $s) {
                echo "  - {$s}\n";
            }
            echo "\n";
            exit(1);
        }

        echo "✅ Tenant Found!\n";
        echo str_repeat('=', 80) . "\n\n";
        echo "Organization: {$tenant->org_name}\n";
        echo "Slug:         {$tenant->org_slug}\n";
        echo "Database:     {$tenant->tenant_db_name}\n";
        echo "Status:       {$tenant->status}\n\n";
        echo str_repeat('=', 80) . "\n\n";
        
        echo "Command to run:\n";
        echo "  php artisan rbac:migrate-simplified --tenant-db={$tenant->tenant_db_name} --dry-run\n\n";

    } else {
        // Interactive mode
        echo "\n🏢 Tenant Database Finder\n";
        echo str_repeat('=', 80) . "\n\n";

        echo "Options:\n";
        echo "  1. List all tenants\n";
        echo "  2. Search by slug\n";
        echo "  3. Use default from .env\n\n";

        $choice = readline("Enter choice (1-3): ");

        switch ($choice) {
            case '1':
                echo "\n";
                exec('php ' . __FILE__ . ' --list');
                break;

            case '2':
                $slug = readline("Enter organization slug: ");
                echo "\n";
                exec('php ' . __FILE__ . " --slug={$slug}");
                break;

            case '3':
                $tenantDb = env('TENANT_DB_DATABASE');
                if ($tenantDb) {
                    echo "\n✅ Default tenant database from .env: {$tenantDb}\n\n";
                    echo "Command to run:\n";
                    echo "  php artisan rbac:migrate-simplified --dry-run\n\n";
                } else {
                    echo "\n❌ No TENANT_DB_DATABASE set in .env file\n\n";
                }
                break;

            default:
                echo "\n❌ Invalid choice\n\n";
        }
    }

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
