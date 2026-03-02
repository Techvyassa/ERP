<?php

namespace App\Http\Controllers;

use App\Contracts\SubscriptionManagementService;
use App\Models\Control\ActiveSubscription;
use App\Models\Control\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionManagementService $subscriptionService
    ) {}

    /**
     * Get current subscription details
     * GET /api/v1/subscriptions/current
     */
    public function current(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $orgId = $request->attributes->get('org_id');
            $activeSub = ActiveSubscription::with(['organization', 'subscription'])->find($orgId);

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

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => [
                        'org_id' => $activeSub->org_id,
                        'subscription_id' => $activeSub->subscription_id,
                        'plan_code' => $activeSub->plan_code,
                        'subscription_status' => $activeSub->subscription_status,
                        'period_end_date' => $activeSub->period_end_date->toDateString(),
                        'modules_allowed' => $activeSub->modules_allowed,
                        'max_users' => $activeSub->max_users,
                        'is_in_trial' => $activeSub->is_in_trial,
                    ]
                ],
                'message' => 'Subscription details retrieved successfully',
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
                'message' => 'Failed to retrieve subscription: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Upgrade subscription
     * POST /api/v1/subscriptions/upgrade
     */
    public function upgrade(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:subscription_plans,plan_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $orgId = $request->attributes->get('org_id');
            $planId = $request->input('plan_id');

            // Verify plan exists and is active
            $plan = SubscriptionPlan::where('plan_id', $planId)
                ->where('is_active', true)
                ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PLAN',
                        'details' => []
                    ],
                    'message' => 'Invalid or inactive subscription plan',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            // Upgrade subscription
            $subscription = $this->subscriptionService->upgradeToPaid($orgId, $planId);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => [
                        'subscription_id' => $subscription->subscription_id,
                        'org_id' => $subscription->org_id,
                        'plan_id' => $subscription->plan_id,
                        'subscription_status' => $subscription->subscription_status,
                        'current_period_start' => $subscription->current_period_start->toDateString(),
                        'current_period_end' => $subscription->current_period_end->toDateString(),
                        'next_billing_date' => $subscription->next_billing_date?->toDateString(),
                    ]
                ],
                'message' => 'Subscription upgraded successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPGRADE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to upgrade subscription: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     * POST /api/v1/subscriptions/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $orgId = $request->attributes->get('org_id');
            $activeSub = ActiveSubscription::find($orgId);

            if (!$activeSub) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NO_ACTIVE_SUBSCRIPTION',
                        'details' => []
                    ],
                    'message' => 'No active subscription to cancel',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            $subscriptionId = $activeSub->subscription_id;
            $reason = $request->input('reason');

            // Cancel subscription
            $success = $this->subscriptionService->cancelSubscription($subscriptionId, $reason);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'message' => 'Subscription will remain active until the end of the current billing period',
                        'period_end_date' => $activeSub->period_end_date->toDateString(),
                    ],
                    'message' => 'Subscription cancelled successfully',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CANCELLATION_FAILED',
                        'details' => []
                    ],
                    'message' => 'Failed to cancel subscription',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANCELLATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * List available subscription plans
     * GET /api/v1/subscriptions/plans
     */
    public function plans(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $plans = SubscriptionPlan::where('is_active', true)
                ->where('is_public', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plans
                ],
                'message' => 'Subscription plans retrieved successfully',
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
                'message' => 'Failed to retrieve plans: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
