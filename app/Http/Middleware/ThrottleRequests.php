<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Control\FeatureControl;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limit Middleware - ThrottleRequests
 * 
 * Tracks request count per org_id per day in Redis
 * Compares against api_rate_limit_day from subscription
 * Checks for feature_control override
 * Returns 429 with Retry-After header when exceeded
 * Resets counters at midnight UTC
 * 
 * Requirements: 10.9, 10.10, 20.1-20.10
 */
class ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get org_id from request (set by ResolveTenant middleware)
        $orgId = $request->input('tenant_org_id');
        
        // Get subscription data (set by ValidateSubscription middleware)
        $activeSubscription = $request->input('active_subscription');
        
        if (!$orgId || !$activeSubscription) {
            // Skip rate limiting if context is missing
            return $next($request);
        }
        
        // Requirement 20.1: Get api_rate_limit_day from subscription
        // Note: We need to get this from the subscription plan
        $rateLimit = 10000; // Default fallback
        
        if ($activeSubscription) {
            // Load the plan relationship to get api_rate_limit_day
            $plan = $activeSubscription->plan;
            if ($plan) {
                $rateLimit = $plan->api_rate_limit_day ?? 10000;
            }
        }
        
        // Requirement 20.8: Check for feature_control override
        $featureControl = FeatureControl::where('org_id', $orgId)
            ->where('feature_key', 'api_rate_limit_override')
            ->first();
        
        if ($featureControl && $featureControl->isEffective()) {
            $rateLimit = (int) $featureControl->getTypedValue();
        }
        
        // Requirement 20.2: Track request count per org_id per day
        $today = now()->format('Y-m-d');
        $redisKey = "rate_limit:org:{$orgId}:{$today}";
        
        // Increment counter
        $requestCount = Redis::incr($redisKey);
        
        // Set expiry on first request of the day (24 hours from now)
        if ($requestCount === 1) {
            // Requirement 20.5: Reset at midnight UTC
            $secondsUntilMidnight = now()->endOfDay()->diffInSeconds();
            Redis::expire($redisKey, $secondsUntilMidnight);
        }
        
        // Requirement 20.3: Check if rate limit exceeded
        if ($requestCount > $rateLimit) {
            // Requirement 20.9: Log rate limit violations
            Log::warning('Rate limit exceeded', [
                'org_id' => $orgId,
                'request_count' => $requestCount,
                'rate_limit' => $rateLimit,
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Requirement 20.4: Calculate seconds until reset
            $resetTime = now()->endOfDay()->diffInSeconds();
            
            // Requirement 20.4: Return 429 with Retry-After header
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'details' => [
                        'limit' => $rateLimit,
                        'current_count' => $requestCount,
                        'reset_in_seconds' => $resetTime,
                    ]
                ],
                'message' => 'Rate limit exceeded',
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                'timestamp' => now()->toIso8601String()
            ], 429)->header('Retry-After', $resetTime);
        }
        
        // Add rate limit info to response headers
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Limit', $rateLimit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $rateLimit - $requestCount));
        $response->headers->set('X-RateLimit-Reset', now()->endOfDay()->timestamp);
        
        return $response;
    }
}
