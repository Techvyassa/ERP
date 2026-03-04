<?php

namespace App\Http\Controllers;

use App\Models\Control\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    /**
     * Get all public subscription plans
     * GET /api/v1/subscription-plans
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $plans = SubscriptionPlan::active()
                ->public()
                ->orderBy('price_amount', 'asc')
                ->get()
                ->map(function (SubscriptionPlan $plan) {
                    return [
                        'plan_id' => $plan->plan_id,
                        'plan_code' => $plan->plan_code,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'billing_cycle' => $plan->billing_cycle,
                        'price_amount' => $plan->price_amount,
                        'currency_code' => $plan->currency_code,
                        'max_users' => $plan->max_users,
                        'max_warehouses' => $plan->max_warehouses,
                        'max_materials' => $plan->max_materials,
                        'storage_gb' => $plan->storage_gb,
                        'api_rate_limit_day' => $plan->api_rate_limit_day,
                        'modules_included' => $plan->modules_included,
                        'billing_cycle_days' => $plan->getBillingCycleDays(),
                    ];
                });
            
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
                'message' => 'Failed to retrieve subscription plans: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    
    /**
     * Get a specific subscription plan by code
     * GET /api/v1/subscription-plans/{planCode}
     */
    public function show(string $planCode): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $plan = SubscriptionPlan::where('plan_code', $planCode)
                ->active()
                ->public()
                ->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'PLAN_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => 'Subscription plan not found',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => [
                        'plan_id' => $plan->plan_id,
                        'plan_code' => $plan->plan_code,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'billing_cycle' => $plan->billing_cycle,
                        'price_amount' => $plan->price_amount,
                        'currency_code' => $plan->currency_code,
                        'max_users' => $plan->max_users,
                        'max_warehouses' => $plan->max_warehouses,
                        'max_materials' => $plan->max_materials,
                        'storage_gb' => $plan->storage_gb,
                        'api_rate_limit_day' => $plan->api_rate_limit_day,
                        'modules_included' => $plan->modules_included,
                        'billing_cycle_days' => $plan->getBillingCycleDays(),
                    ]
                ],
                'message' => 'Subscription plan retrieved successfully',
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
                'message' => 'Failed to retrieve subscription plan: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
