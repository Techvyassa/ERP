<?php

namespace App\Contracts;

interface FeatureControlService
{
    /**
     * Get feature value for an organization with type casting
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param mixed $defaultValue Default value if feature control not found or not effective
     * @return mixed Typed value based on feature_type
     */
    public function getFeatureValue(int $orgId, string $featureKey, mixed $defaultValue = null): mixed;

    /**
     * Check if a feature is enabled (for BOOLEAN features)
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to check
     * @param bool $defaultValue Default value if not found
     * @return bool
     */
    public function isFeatureEnabled(int $orgId, string $featureKey, bool $defaultValue = false): bool;

    /**
     * Get numeric feature value (for NUMERIC features)
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param int|null $defaultValue Default value if not found
     * @return int|null
     */
    public function getNumericFeature(int $orgId, string $featureKey, ?int $defaultValue = null): ?int;

    /**
     * Get all effective feature controls for an organization
     * 
     * @param int $orgId Organization ID
     * @return array Keyed by feature_key with typed values
     */
    public function getAllFeatures(int $orgId): array;

    /**
     * Clear feature control cache for an organization
     * 
     * @param int $orgId Organization ID
     * @return void
     */
    public function clearCache(int $orgId): void;

    /**
     * Get feature value with plan fallback
     * Checks feature_controls first, then falls back to subscription plan defaults
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param string $planField Field name in subscription_plans table
     * @return mixed
     */
    public function getFeatureWithPlanFallback(int $orgId, string $featureKey, string $planField): mixed;
}
