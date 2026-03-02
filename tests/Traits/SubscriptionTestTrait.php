<?php

namespace Tests\Traits;

use App\Models\Control\Organization;
use App\Models\Control\SubscriptionPlan;
use App\Models\Control\OrgSubscription;
use App\Models\Control\ActiveSubscription;
use Carbon\Carbon;

/**
 * Trait for subscription setup in tests
 */
trait SubscriptionTestTrait
{
    /**
     * Create a trial subscription for an organization
     * 
     * @param Organization $organization The organization
     * @param int $daysRemaining Days remaining in trial (default: 14)
     * @return OrgSubscription The created subscription
     */
    protected function createTrialSubscription(
        Organization $organization,
        int $daysRemaining = 14
    ): OrgSubscription {
        $plan = SubscriptionPlan::factory()->basic()->create();

        $trialStart = now()->subDays(14 - $daysRemaining);
        $trialEnd = $trialStart->copy()->addDays(14);

        return OrgSubscription::factory()->trial()->create([
            'org_id' => $organization->org_id,
            'plan_id' => $plan->plan_id,
            'trial_start_date' => $trialStart,
            'trial_end_date' => $trialEnd,
            'current_period_start' => $trialStart,
            'current_period_end' => $trialEnd,
        ]);
    }

    /**
     * Create an active subscription for an organization
     * 
     * @param Organization $organization The organization
     * @param SubscriptionPlan|null $plan Optional plan to use
     * @param int $daysInPeriod Days in current billing period (default: 30)
     * @return OrgSubscription The created subscription
     */
    protected function createActiveSubscription(
        Organization $organization,
        ?SubscriptionPlan $plan = null,
        int $daysInPeriod = 30
    ): OrgSubscription {
        $plan = $plan ?? SubscriptionPlan::factory()->professional()->create();

        $periodStart = now()->subDays($daysInPeriod / 2);
        $periodEnd = $periodStart->copy()->addDays($daysInPeriod);
        $nextBilling = $periodEnd->copy()->addDay();

        return OrgSubscription::factory()->active()->create([
            'org_id' => $organization->org_id,
            'plan_id' => $plan->plan_id,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'next_billing_date' => $nextBilling,
        ]);
    }

    /**
     * Create an expired subscription for an organization
     * 
     * @param Organization $organization The organization
     * @param int $daysExpired Days since expiration (default: 7)
     * @return OrgSubscription The created subscription
     */
    protected function createExpiredSubscription(
        Organization $organization,
        int $daysExpired = 7
    ): OrgSubscription {
        $plan = SubscriptionPlan::factory()->basic()->create();

        $periodEnd = now()->subDays($daysExpired);
        $periodStart = $periodEnd->copy()->subDays(30);

        return OrgSubscription::factory()->expired()->create([
            'org_id' => $organization->org_id,
            'plan_id' => $plan->plan_id,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
        ]);
    }

    /**
     * Create a past due subscription for an organization
     * 
     * @param Organization $organization The organization
     * @param int $graceDaysRemaining Days remaining in grace period (default: 7)
     * @return OrgSubscription The created subscription
     */
    protected function createPastDueSubscription(
        Organization $organization,
        int $graceDaysRemaining = 7
    ): OrgSubscription {
        $plan = SubscriptionPlan::factory()->professional()->create();

        $periodEnd = now()->subDays(1);
        $periodStart = $periodEnd->copy()->subDays(30);
        $gracePeriod = now()->addDays($graceDaysRemaining);

        return OrgSubscription::factory()->pastDue()->create([
            'org_id' => $organization->org_id,
            'plan_id' => $plan->plan_id,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'grace_period_until' => $gracePeriod,
        ]);
    }

    /**
     * Create a cancelled subscription for an organization
     * 
     * @param Organization $organization The organization
     * @param int $daysUntilEnd Days until period end (default: 15)
     * @return OrgSubscription The created subscription
     */
    protected function createCancelledSubscription(
        Organization $organization,
        int $daysUntilEnd = 15
    ): OrgSubscription {
        $plan = SubscriptionPlan::factory()->professional()->create();

        $periodStart = now()->subDays(15);
        $periodEnd = now()->addDays($daysUntilEnd);

        return OrgSubscription::factory()->cancelled()->create([
            'org_id' => $organization->org_id,
            'plan_id' => $plan->plan_id,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'cancelled_at' => now()->subDays(5),
            'cancellation_reason' => 'Test cancellation',
        ]);
    }

    /**
     * Create an active subscription entry (denormalized table)
     * 
     * @param Organization $organization The organization
     * @param OrgSubscription $subscription The subscription
     * @return ActiveSubscription The created active subscription
     */
    protected function createActiveSubscriptionEntry(
        Organization $organization,
        OrgSubscription $subscription
    ): ActiveSubscription {
        $plan = $subscription->plan;

        return ActiveSubscription::create([
            'org_id' => $organization->org_id,
            'subscription_id' => $subscription->subscription_id,
            'plan_id' => $plan->plan_id,
            'plan_code' => $plan->plan_code,
            'subscription_status' => $subscription->subscription_status,
            'period_end_date' => $subscription->current_period_end,
            'modules_allowed' => $plan->modules_included,
            'max_users' => $plan->max_users,
            'tenant_db_name' => $organization->tenant_db_name,
            'is_in_trial' => $subscription->subscription_status === 'TRIAL',
            'refreshed_at' => now(),
        ]);
    }

    /**
     * Create a complete subscription setup (subscription + active entry)
     * 
     * @param Organization $organization The organization
     * @param string $status Subscription status (TRIAL, ACTIVE, PAST_DUE, etc.)
     * @return array ['subscription' => OrgSubscription, 'active' => ActiveSubscription]
     */
    protected function createCompleteSubscription(
        Organization $organization,
        string $status = 'ACTIVE'
    ): array {
        $subscription = match($status) {
            'TRIAL' => $this->createTrialSubscription($organization),
            'ACTIVE' => $this->createActiveSubscription($organization),
            'PAST_DUE' => $this->createPastDueSubscription($organization),
            'CANCELLED' => $this->createCancelledSubscription($organization),
            'EXPIRED' => $this->createExpiredSubscription($organization),
            default => $this->createActiveSubscription($organization),
        };

        $active = null;
        if (in_array($status, ['TRIAL', 'ACTIVE', 'PAST_DUE'])) {
            $active = $this->createActiveSubscriptionEntry($organization, $subscription);
        }

        return [
            'subscription' => $subscription,
            'active' => $active,
        ];
    }

    /**
     * Assert that an organization has an active subscription
     * 
     * @param Organization $organization The organization
     */
    protected function assertHasActiveSubscription(Organization $organization): void
    {
        $this->assertDatabaseHas('active_subscriptions', [
            'org_id' => $organization->org_id,
        ], 'control');
    }

    /**
     * Assert that an organization does not have an active subscription
     * 
     * @param Organization $organization The organization
     */
    protected function assertNoActiveSubscription(Organization $organization): void
    {
        $this->assertDatabaseMissing('active_subscriptions', [
            'org_id' => $organization->org_id,
        ], 'control');
    }

    /**
     * Assert that a subscription allows a specific module
     * 
     * @param OrgSubscription $subscription The subscription
     * @param string $moduleCode The module code to check
     */
    protected function assertSubscriptionAllowsModule(
        OrgSubscription $subscription,
        string $moduleCode
    ): void {
        $plan = $subscription->plan;
        $this->assertTrue(
            in_array($moduleCode, $plan->modules_included),
            "Subscription does not allow module: {$moduleCode}"
        );
    }

    /**
     * Assert that a subscription does not allow a specific module
     * 
     * @param OrgSubscription $subscription The subscription
     * @param string $moduleCode The module code to check
     */
    protected function assertSubscriptionDeniesModule(
        OrgSubscription $subscription,
        string $moduleCode
    ): void {
        $plan = $subscription->plan;
        $this->assertFalse(
            in_array($moduleCode, $plan->modules_included),
            "Subscription allows module: {$moduleCode}"
        );
    }
}
