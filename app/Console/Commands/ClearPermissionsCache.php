<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ClearPermissionsCache extends Command
{
    protected $signature = 'cache:clear-permissions {user_id?}';
    protected $description = 'Clear RBAC permissions cache for a user or all users';

    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            // Clear cache for specific user
            $cacheKey = "rbac:user:{$userId}:permissions";
            Cache::forget($cacheKey);
            $this->info("✓ Cleared permissions cache for user {$userId}");
        } else {
            // Clear all permission caches
            try {
                $keys = Redis::keys('rbac:user:*:permissions');
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        // Remove the Redis prefix if present
                        $cleanKey = str_replace(config('database.redis.options.prefix'), '', $key);
                        Redis::del($cleanKey);
                    }
                    $this->info("✓ Cleared permissions cache for " . count($keys) . " users");
                } else {
                    $this->info("No permission caches found");
                }
            } catch (\Exception $e) {
                $this->error("Failed to clear Redis cache: " . $e->getMessage());
                $this->info("Trying Laravel Cache facade...");
                
                // Fallback to clearing all cache
                Cache::flush();
                $this->info("✓ Cleared all cache");
            }
        }

        return 0;
    }
}
