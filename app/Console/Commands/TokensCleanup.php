<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokensCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired refresh tokens from cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired refresh tokens...');
        $this->newLine();
        
        try {
            $deletedCount = 0;
            $errorCount = 0;
            
            // Get Redis connection
            try {
                $redis = Cache::getRedis();
                
                // Find all refresh token keys
                // Pattern: refresh_token:* or auth:refresh_token:*
                $patterns = [
                    '*refresh_token:*',
                    '*auth:refresh:*',
                ];
                
                $allKeys = [];
                foreach ($patterns as $pattern) {
                    $keys = $redis->keys($pattern);
                    if (!empty($keys)) {
                        $allKeys = array_merge($allKeys, $keys);
                    }
                }
                
                if (empty($allKeys)) {
                    $this->info('No refresh token keys found in cache.');
                    return Command::SUCCESS;
                }
                
                $this->info("Found " . count($allKeys) . " refresh token key(s)");
                $this->newLine();
                
                foreach ($allKeys as $key) {
                    try {
                        // Check if key has TTL
                        $ttl = $redis->ttl($key);
                        
                        // TTL -2 means key doesn't exist
                        // TTL -1 means key exists but has no expiry
                        // TTL > 0 means key exists with expiry
                        
                        if ($ttl === -2) {
                            // Key doesn't exist (already expired and removed by Redis)
                            continue;
                        }
                        
                        if ($ttl === -1) {
                            // Key has no expiry - this shouldn't happen for refresh tokens
                            // Delete it as it's likely orphaned
                            $this->warn("Found token without expiry: {$key}");
                            $redis->del($key);
                            $deletedCount++;
                            continue;
                        }
                        
                        // If TTL is very small (< 60 seconds), consider it expired
                        if ($ttl < 60) {
                            $this->line("Deleting expired token: {$key} (TTL: {$ttl}s)");
                            $redis->del($key);
                            $deletedCount++;
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("Error processing key {$key}: {$e->getMessage()}");
                        $errorCount++;
                    }
                }
                
                $this->newLine();
                $this->info('=== Cleanup Summary ===');
                $this->info("Total keys checked: " . count($allKeys));
                $this->info("Tokens deleted: {$deletedCount}");
                
                if ($errorCount > 0) {
                    $this->warn("Errors encountered: {$errorCount}");
                }
                
                Log::info('Refresh token cleanup completed', [
                    'keys_checked' => count($allKeys),
                    'tokens_deleted' => $deletedCount,
                    'errors' => $errorCount
                ]);
                
                return Command::SUCCESS;
                
            } catch (\Exception $e) {
                // Redis not available or not configured
                $this->warn('Redis not available or not configured.');
                $this->warn('Refresh tokens are stored in Redis. Please ensure Redis is running.');
                $this->info('Note: Redis automatically removes expired keys, so manual cleanup may not be necessary.');
                
                Log::warning('tokens:cleanup: Redis not available', [
                    'error' => $e->getMessage()
                ]);
                
                return Command::SUCCESS;
            }
            
        } catch (\Exception $e) {
            $this->error('Token cleanup failed!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('tokens:cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
