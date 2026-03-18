<?php

/**
 * Clear permission cache for all users
 * Run: php clear_permission_cache.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Control\Organization;
use Illuminate\Support\Facades\DB;

echo "=== Clearing Permission Cache ===\n\n";

// Get organization
$orgSlug = 'techvyassa';
$org = Organization::where('org_slug', $orgSlug)->first();

if (!$org) {
    echo "❌ Organization '{$orgSlug}' not found!\n";
    exit(1);
}

echo "✓ Organization: {$org->org_name}\n";
echo "✓ Database: {$org->tenant_db_name}\n\n";

// Switch to tenant database
config(['database.connections.tenant.database' => $org->tenant_db_name]);
DB::purge('tenant');
DB::reconnect('tenant');

// Get all users
$users = DB::connection('tenant')->table('user_master')->get();

echo "Clearing cache for {$users->count()} users...\n\n";

$cleared = 0;
foreach ($users as $user) {
    $cacheKey = "rbac:user:{$user->id}:permissions";
    
    if (Cache::has($cacheKey)) {
        Cache::forget($cacheKey);
        echo "✓ Cleared cache for user {$user->employee_code} (ID: {$user->id})\n";
        $cleared++;
    }
}

echo "\n✅ Cache cleared for {$cleared} users\n";

// Also try to clear all rbac:* keys if using Redis
try {
    if (config('cache.default') === 'redis') {
        $redis = Redis::connection();
        $keys = $redis->keys('*rbac:user:*:permissions*');
        if ($keys) {
            foreach ($keys as $key) {
                // Remove the prefix that Redis adds
                $cleanKey = str_replace(config('database.redis.options.prefix'), '', $key);
                $redis->del($cleanKey);
            }
            echo "✓ Cleared " . count($keys) . " Redis keys\n";
        }
    }
} catch (\Exception $e) {
    echo "Note: Could not clear Redis keys: " . $e->getMessage() . "\n";
}

echo "\n=== Cache Clear Complete ===\n";
