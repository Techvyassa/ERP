<?php

namespace App\Http\Controllers;

use App\Models\Control\ActiveSubscription;
use App\Models\Control\FeatureControl;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

class RateLimitController extends Controller
{
    /**
     * Get rate limit status for current organization
     * GET /api/v1/rate-limit/status
     */
    public function status(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $orgId = $request->attributes->get('org_id');
            
            // Get subscription rate limit
            $activeSub = ActiveSubscription::find($orgId);
            
            if (!$activeSub) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NO_ACTIVE_SUBSCRIPTION',
                        'details' => []
                    ],
                    'message' => 'No active subscription found',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            // Get rate limit from subscription plan
            $plan = $activeSub->plan;
            $rateLimit = $plan ? $plan->api_rate_limit_day : 1000;

            // Check for feature control override
            $featureControl = FeatureControl::where('org_id', $orgId)
                ->where('feature_key', 'api_rate_limit_override')
                ->first();

            if ($featureControl && $featureControl->isEffective()) {
                $rateLimit = $featureControl->getTypedValue();
            }

            // Get current usage from Redis
            $key = "rate_limit:org:{$orgId}:" . now()->format('Y-m-d');
            $currentUsage = (int) Redis::get($key) ?? 0;

            // Calculate remaining and reset time
            $remaining = max(0, $rateLimit - $currentUsage);
            $resetTime = now()->endOfDay();
            $resetInSeconds = $resetTime->diffInSeconds(now());

            return response()->json([
                'success' => true,
                'data' => [
                    'rate_limit' => [
                        'limit' => $rateLimit,
                        'current_usage' => $currentUsage,
                        'remaining' => $remaining,
                        'reset_at' => $resetTime->toIso8601String(),
                        'reset_in_seconds' => $resetInSeconds,
                        'percentage_used' => $rateLimit > 0 ? round(($currentUsage / $rateLimit) * 100, 2) : 0,
                    ]
                ],
                'message' => 'Rate limit status retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve rate limit status: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
