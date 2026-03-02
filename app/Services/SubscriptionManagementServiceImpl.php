<?php

namespace App\Services;

use App\Contracts\RenewalResult;
use App\Contracts\SubscriptionManagementService;
use App\Helpers\AuditLogger;
use App\Models\Control\ActiveSubscription;
use App\Models\Control\Organization;
use App\Models\Control\OrgSubscription;
use App\Models\Control\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionManagementServiceImpl implements SubscriptionManagementService
{
    /**
     * Create trial subscription for new tenant
     * Requirements: 6.1, 6.2
     */
    public function createTrialSubscription(int $orgId): OrgSubscription
    {
        Log::info("Creating trial subscription for org_id: {$orgId}");
        
        // Verify organization exists
        $organization = Organization::find($orgId);
        if (!$organization) {
            throw new \InvalidArgumentException("Organization not found: {$orgId}");
        }
        
        // Set trial dates
        $trialStartDate = Carbon::now();
        $trialEndDate = Carbon::now()->addDays(14);
        
        // Create trial subscription with a default plan (we'll use the first active plan)
        // In production, you might want to have a specific trial plan
        $defaultPlan = SubscriptionPlan::active()->first();
        if (!$defaultPlan) {
            throw new \RuntimeException("No active subscription plan available");
        }
        
        $subscription = OrgSubscription::create([
            'org_id' => $orgId,
            'plan_id' => $defaultPlan->plan_id,
            'subscription_status' => 'TRIAL',
            'trial_start_date' => $trialStartDate,
            'trial_end_date' => $trialEndDate,
            'current_period_start' => $trialStartDate,
            'current_period_end' => $trialEndDate,
            'next_billing_date' => null,
        ]);
        
        Log::info("Trial subscription created", [
            'subscription_id' => $subscription->subscription_id,
            'org_id' => $orgId,
            'trial_end_date' => $trialEndDate->toDateString(),
        ]);
        
        // Log subscription creation
        AuditLogger::logSubscriptionChange(
            $orgId,
            $subscription->subscription_id,
            'NONE',
            'TRIAL',
            'Trial subscription created',
            ['trial_end_date' => $trialEndDate->toDateString()]
        );
        
        return $subscription;
    }
    
    /**
     * Upgrade from trial to paid subscription
     * Requirements: 6.4, 6.5
     */
    public function upgradeToPaid(int $orgId, int $planId): OrgSubscription
    {
        Log::info("Upgrading to paid subscription", [
            'org_id' => $orgId,
            'plan_id' => $planId,
        ]);
        
        return DB::connection('control')->transaction(function () use ($orgId, $planId) {
            // Verify organization exists
            $organization = Organization::find($orgId);
            if (!$organization) {
                throw new \InvalidArgumentException("Organization not found: {$orgId}");
            }
            
            // Verify plan exists and is active
            $plan = SubscriptionPlan::active()->find($planId);
            if (!$plan) {
                throw new \InvalidArgumentException("Subscription plan not found or inactive: {$planId}");
            }
            
            // Find current trial subscription
            $currentSubscription = OrgSubscription::where('org_id', $orgId)
                ->where('subscription_status', 'TRIAL')
                ->latest('created_at')
                ->first();
            
            if ($currentSubscription) {
                // Expire the trial subscription
                $currentSubscription->update([
                    'subscription_status' => 'EXPIRED',
                ]);
                
                // Log trial expiration
                AuditLogger::logSubscriptionChange(
                    $orgId,
                    $currentSubscription->subscription_id,
                    'TRIAL',
                    'EXPIRED',
                    'Trial subscription expired due to upgrade'
                );
            }
            
            // Calculate billing dates based on billing cycle
            $currentPeriodStart = Carbon::now();
            $billingCycleDays = $plan->getBillingCycleDays();
            $currentPeriodEnd = Carbon::now()->addDays($billingCycleDays);
            $nextBillingDate = $currentPeriodEnd->copy();
            
            // Create new ACTIVE subscription
            $subscription = OrgSubscription::create([
                'org_id' => $orgId,
                'plan_id' => $planId,
                'subscription_status' => 'ACTIVE',
                'trial_start_date' => null,
                'trial_end_date' => null,
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
                'next_billing_date' => $nextBillingDate,
            ]);
            
            Log::info("Upgraded to paid subscription", [
                'subscription_id' => $subscription->subscription_id,
                'org_id' => $orgId,
                'plan_id' => $planId,
                'next_billing_date' => $nextBillingDate->toDateString(),
            ]);
            
            // Log subscription upgrade
            AuditLogger::logSubscriptionChange(
                $orgId,
                $subscription->subscription_id,
                'TRIAL',
                'ACTIVE',
                'Upgraded to paid subscription',
                [
                    'plan_id' => $planId,
                    'plan_code' => $plan->plan_code,
                    'next_billing_date' => $nextBillingDate->toDateString()
                ]
            );
            
            return $subscription;
        });
    }
    
    /**
     * Process subscription renewal
     * Requirements: 6.6, 6.7, 6.8, 6.9
     */
    public function processRenewal(int $subscriptionId): RenewalResult
    {
        Log::info("Processing subscription renewal", [
            'subscription_id' => $subscriptionId,
        ]);
        
        return DB::connection('control')->transaction(function () use ($subscriptionId) {
            $subscription = OrgSubscription::find($subscriptionId);
            
            if (!$subscription) {
                return new RenewalResult(
                    success: false,
                    errorMessage: "Subscription not found: {$subscriptionId}"
                );
            }
            
            // Only process ACTIVE subscriptions
            if ($subscription->subscription_status !== 'ACTIVE') {
                return new RenewalResult(
                    success: false,
                    subscription: $subscription,
                    errorMessage: "Subscription is not active: {$subscription->subscription_status}"
                );
            }
            
            // Check if renewal is due
            if (!$subscription->next_billing_date || Carbon::parse($subscription->next_billing_date)->isFuture()) {
                return new RenewalResult(
                    success: false,
                    subscription: $subscription,
                    errorMessage: "Renewal not yet due"
                );
            }
            
            // In a real implementation, this would attempt payment via payment gateway
            // For now, we'll simulate payment success/failure
            $paymentSuccess = $this->attemptPayment($subscription);
            
            if ($paymentSuccess) {
                // Payment succeeded - extend subscription period
                $plan = $subscription->plan;
                $billingCycleDays = $plan->getBillingCycleDays();
                
                $newPeriodStart = Carbon::parse($subscription->current_period_end)->addDay();
                $newPeriodEnd = $newPeriodStart->copy()->addDays($billingCycleDays);
                $newBillingDate = $newPeriodEnd->copy();
                
                $subscription->update([
                    'current_period_start' => $newPeriodStart,
                    'current_period_end' => $newPeriodEnd,
                    'next_billing_date' => $newBillingDate,
                    'subscription_status' => 'ACTIVE',
                ]);
                
                Log::info("Subscription renewed successfully", [
                    'subscription_id' => $subscriptionId,
                    'new_period_end' => $newPeriodEnd->toDateString(),
                ]);
                
                // Log successful renewal
                AuditLogger::logSubscriptionChange(
                    $subscription->org_id,
                    $subscriptionId,
                    'ACTIVE',
                    'ACTIVE',
                    'Subscription renewed successfully',
                    ['new_period_end' => $newPeriodEnd->toDateString()]
                );
                
                return new RenewalResult(
                    success: true,
                    subscription: $subscription->fresh(),
                    paymentStatus: 'SUCCESS'
                );
            } else {
                // Payment failed - set to PAST_DUE with grace period
                $gracePeriodUntil = Carbon::now()->addDays(7);
                
                $subscription->update([
                    'subscription_status' => 'PAST_DUE',
                    'grace_period_until' => $gracePeriodUntil,
                ]);
                
                Log::warning("Subscription renewal payment failed", [
                    'subscription_id' => $subscriptionId,
                    'grace_period_until' => $gracePeriodUntil->toDateTimeString(),
                ]);
                
                // Log payment failure and status change
                AuditLogger::logSubscriptionChange(
                    $subscription->org_id,
                    $subscriptionId,
                    'ACTIVE',
                    'PAST_DUE',
                    'Payment failed - grace period granted',
                    ['grace_period_until' => $gracePeriodUntil->toDateTimeString()]
                );
                
                return new RenewalResult(
                    success: false,
                    subscription: $subscription->fresh(),
                    errorMessage: "Payment failed",
                    paymentStatus: 'FAILED'
                );
            }
        });
    }
    
    /**
     * Cancel subscription
     * Requirements: 6.11, 6.12
     */
    public function cancelSubscription(int $subscriptionId, string $reason): bool
    {
        Log::info("Cancelling subscription", [
            'subscription_id' => $subscriptionId,
            'reason' => $reason,
        ]);
        
        return DB::connection('control')->transaction(function () use ($subscriptionId, $reason) {
            $subscription = OrgSubscription::find($subscriptionId);
            
            if (!$subscription) {
                Log::error("Subscription not found for cancellation", [
                    'subscription_id' => $subscriptionId,
                ]);
                return false;
            }
            
            // Only cancel ACTIVE or PAST_DUE subscriptions
            if (!in_array($subscription->subscription_status, ['ACTIVE', 'PAST_DUE'])) {
                Log::warning("Cannot cancel subscription with status", [
                    'subscription_id' => $subscriptionId,
                    'status' => $subscription->subscription_status,
                ]);
                return false;
            }
            
            // Set subscription to CANCELLED
            // Access is allowed until current_period_end
            $subscription->update([
                'subscription_status' => 'CANCELLED',
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);
            
            Log::info("Subscription cancelled", [
                'subscription_id' => $subscriptionId,
                'access_until' => $subscription->current_period_end,
            ]);
            
            // Log subscription cancellation
            AuditLogger::logSubscriptionChange(
                $subscription->org_id,
                $subscriptionId,
                $subscription->getOriginal('subscription_status'),
                'CANCELLED',
                $reason,
                ['access_until' => $subscription->current_period_end]
            );
            
            return true;
        });
    }
    
    /**
     * Check if organization has active subscription
     * Requirements: 6.13, 6.14
     */
    public function hasActiveSubscription(int $orgId): bool
    {
        $activeSubscription = ActiveSubscription::find($orgId);
        
        if (!$activeSubscription) {
            return false;
        }
        
        // Check if subscription is ACTIVE or TRIAL
        if (in_array($activeSubscription->subscription_status, ['ACTIVE', 'TRIAL'])) {
            return true;
        }
        
        // Check if PAST_DUE but within grace period
        if ($activeSubscription->subscription_status === 'PAST_DUE') {
            $subscription = OrgSubscription::find($activeSubscription->subscription_id);
            return $subscription && $subscription->isInGracePeriod();
        }
        
        return false;
    }
    
    /**
     * Get modules allowed for organization
     * Requirements: 6.14
     */
    public function getAllowedModules(int $orgId): array
    {
        $activeSubscription = ActiveSubscription::find($orgId);
        
        if (!$activeSubscription) {
            return [];
        }
        
        return $activeSubscription->modules_allowed ?? [];
    }
    
    /**
     * Simulate payment attempt
     * In production, this would integrate with payment gateway
     * 
     * @param OrgSubscription $subscription
     * @return bool
     */
    private function attemptPayment(OrgSubscription $subscription): bool
    {
        // This is a placeholder for actual payment gateway integration
        // In production, this would:
        // 1. Create payment intent with gateway
        // 2. Attempt to charge the customer
        // 3. Return success/failure based on gateway response
        
        // For now, we'll return true to simulate successful payment
        // In real implementation, this would be replaced with actual payment logic
        return true;
    }
}
