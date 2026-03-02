<?php

namespace App\Contracts;

use App\Models\Control\OrgSubscription;

interface SubscriptionManagementService
{
    /**
     * Create trial subscription for new tenant
     * @param int $orgId
     * @return OrgSubscription
     */
    public function createTrialSubscription(int $orgId): OrgSubscription;
    
    /**
     * Upgrade from trial to paid subscription
     * @param int $orgId
     * @param int $planId
     * @return OrgSubscription
     */
    public function upgradeToPaid(int $orgId, int $planId): OrgSubscription;
    
    /**
     * Process subscription renewal
     * @param int $subscriptionId
     * @return RenewalResult
     */
    public function processRenewal(int $subscriptionId): RenewalResult;
    
    /**
     * Cancel subscription
     * @param int $subscriptionId
     * @param string $reason
     * @return bool
     */
    public function cancelSubscription(int $subscriptionId, string $reason): bool;
    
    /**
     * Check if organization has active subscription
     * @param int $orgId
     * @return bool
     */
    public function hasActiveSubscription(int $orgId): bool;
    
    /**
     * Get modules allowed for organization
     * @param int $orgId
     * @return array Module codes
     */
    public function getAllowedModules(int $orgId): array;
}
