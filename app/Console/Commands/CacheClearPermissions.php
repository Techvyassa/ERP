<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheClearPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-permissions {--user= : Clear cache for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear RBAC permission cache for all users or a specific user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user');
        
        try {
            if ($userId) {
                // Clear cache for specific user
                $this->info("Clearing permission cache for user ID: {$userId}");
                
                $cacheKey = "rbac:user:{$userId}:permissions";
                Cache::forget($cacheKey);
                
                $this->info('✓ Permission cache cleared for user.');
                
                Log::info('Permission cache cleared for user', [
                    'user_id' => $userId,
                    'cache_key' => $cacheKey
                ]);
                
            } else {
                // Clear all RBAC permission caches
                $this->info('Clearing all RBAC permission caches...');
                
                // Get all cache keys matching the RBAC pattern
                // Note: This is a simplified approach. In production with Redis,
                // you might want to use SCAN to find all matching keys
                
                // For now, we'll clear the entire cache store
                // In production, you might want to be more selective
                $this->warn('This will clear all permission caches.');
                
                if (!$this->confirm('Do you want to continue?')) {
                    $this->info('Operation cancelled.');
                    return Command::SUCCESS;
                }
                
                // Clear cache tags if using Redis with tags support
                // Otherwise, we need to track keys or clear entire cache
                try {
                    // Try to use cache tags (requires Redis)
                    Cache::tags(['rbac', 'permissions'])->flush();
                    $this->info('✓ All permission caches cleared using tags.');
                } catch (\Exception $e) {
                    // Fallback: clear by pattern (requires Redis)
                    $this->warn('Cache tags not supported. Attempting pattern-based clearing...');
                    
                    try {
                        $redis = Cache::getRedis();
                        $keys = $redis->keys('*rbac:user:*:permissions*');
                        
                        if (!empty($keys)) {
                            foreach ($keys as $key) {
                                // Remove prefix if present
                                $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                                Cache::forget($cleanKey);
                            }
                            $this->info("✓ Cleared {count($keys)} permission cache entries.");
                        } else {
                            $this->info('No permission cache entries found.');
                        }
                    } catch (\Exception $e2) {
                        $this->warn('Pattern-based clearing not available.');
                        $this->warn('Please clear cache manually or restart cache service.');
                    }
                }
                
                Log::info('All RBAC permission caches cleared');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to clear permission cache!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('cache:clear-permissions command failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}
