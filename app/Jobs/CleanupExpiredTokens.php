<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired Refresh Tokens Job
 * 
 * Removes expired refresh tokens from Redis
 * Note: Redis automatically expires keys with TTL, but this job
 * provides additional cleanup and logging
 * 
 * Requirements: 10.7
 */
class CleanupExpiredTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting cleanup of expired refresh tokens');
        
        // Redis automatically handles TTL expiration, so we just log the cleanup
        // In a production environment, you might want to scan for orphaned keys
        // or perform additional cleanup tasks
        
        $pattern = 'refresh_token:*';
        $cursor = 0;
        $count = 0;
        
        do {
            // Scan for refresh token keys
            $result = Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);
            $cursor = $result[0];
            $keys = $result[1] ?? [];
            
            foreach ($keys as $key) {
                // Check if key has TTL (should always have one)
                $ttl = Redis::ttl($key);
                
                // If TTL is -1 (no expiration) or -2 (key doesn't exist), something is wrong
                if ($ttl === -1) {
                    Log::warning("Refresh token without TTL found: {$key}");
                    // Optionally delete keys without TTL
                    Redis::del($key);
                    $count++;
                }
            }
        } while ($cursor !== 0);
        
        if ($count > 0) {
            Log::info("Cleaned up {$count} refresh tokens without TTL");
        } else {
            Log::info('No expired refresh tokens to clean up');
        }
    }
}
