<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Control\ActiveSubscription;
use App\Exceptions\SubscriptionRequiredException;
use App\Exceptions\SubscriptionExpiredException;
use App\Exceptions\ModuleNotAvailableException;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Gate Middleware - ValidateSubscription
 * 
 * Queries active_subscriptions table by org_id
 * Validates subscription_status (handles EXPIRED, CANCELLED, PAST_DUE)
 * Checks grace period for PAST_DUE status
 * Verifies module_code in modules_allowed array
 * Caches subscription data for 5 minutes
 * Returns 402/403 errors as appropriate
 * 
 * Requirements: 11.1-11.11
 */
class ValidateSubscription
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
        
        if (!$orgId) {
            throw new ApiException(
                'ORG_CONTEXT_REQUIRED',
                'Organization context required',
                [],
                400
            );
        }
        
        // Get module_code from route parameter or header
        $moduleCode = $request->route('module_code') ?? $request->header('X-Module-Code');
        
        // Requirement 11.11: Cache subscription data for 5 minutes
        $cacheKey = "subscription:org:{$orgId}";
        $activeSubscription = Cache::remember($cacheKey, 300, fn() => ActiveSubscription::find($orgId));
        
        // Requirement 11.2, 11.3: If no active subscription exists, throw exception
        if (!$activeSubscription) {
            throw new SubscriptionRequiredException();
        }
        
        // Requirement 11.4: If subscription_status is EXPIRED, throw exception
        if ($activeSubscription->subscription_status === 'EXPIRED') {
            throw new SubscriptionExpiredException();
        }
        
        // Requirement 11.5, 11.6: Handle CANCELLED subscriptions
        if ($activeSubscription->subscription_status === 'CANCELLED') {
            // Check if current date is after period_end_date
            if (now()->isAfter($activeSubscription->period_end_date)) {
                throw new ApiException(
                    'SUBSCRIPTION_ENDED',
                    'Subscription ended',
                    [],
                    402
                );
            }
            // If before period_end_date, allow access
        }
        
        // Requirement 11.7, 11.8: Handle PAST_DUE subscriptions
        if ($activeSubscription->subscription_status === 'PAST_DUE') {
            // Get grace_period_until from the full subscription record
            $subscription = $activeSubscription->subscription;
            
            if ($subscription?->grace_period_until) {
                // Check if grace period has expired
                if (now()->isAfter($subscription->grace_period_until)) {
                    throw new ApiException(
                        'SUBSCRIPTION_PAST_DUE',
                        'Subscription payment overdue',
                        [],
                        402
                    );
                }
                // Within grace period - allow read-only access
                // Note: Read-only enforcement would be in the controller layer
                $request->merge(['subscription_read_only' => true]);
            } else {
                // No grace period set, deny access
                throw new ApiException(
                    'SUBSCRIPTION_PAYMENT_REQUIRED',
                    'Subscription payment required',
                    [],
                    402
                );
            }
        }
        
        // Requirement 11.9, 11.10: Verify module access if module_code is provided
        if ($moduleCode) {
            if (!$activeSubscription->hasModule($moduleCode)) {
                throw new ModuleNotAvailableException();
            }
        }
        
        // Store subscription context in request
        $request->merge([
            'active_subscription' => $activeSubscription,
            'subscription_status' => $activeSubscription->subscription_status,
            'modules_allowed' => $activeSubscription->modules_allowed,
        ]);
        
        Log::debug('Subscription validated', [
            'org_id' => $orgId,
            'subscription_status' => $activeSubscription->subscription_status,
            'module_code' => $moduleCode,
            'timestamp' => now()->toIso8601String(),
        ]);
        
        return $next($request);
    }
}
