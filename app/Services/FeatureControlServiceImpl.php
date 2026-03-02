<?php

namespace App\Services;

use App\Contracts\FeatureControlService;
use App\Models\Control\FeatureControl;
use App\Models\Control\ActiveSubscription;
use App\Models\Control\SubscriptionPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureControlServiceImpl implements FeatureControlService
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'feature_control:org:';

    /**
     * Get feature value for an organization with type casting
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param mixed $defaultValue Default value if feature control not found or not effective
     * @return mixed Typed value based on feature_type
     */
    public function getFeatureValue(int $orgId, string $featureKey, mixed $defaultValue = null): mixed
    {
        $cacheKey = $this->getCacheKey($orgId, $featureKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId, $featureKey, $defaultValue) {
            $featureControl = FeatureControl::where('org_id', $orgId)
                ->where('feature_key', $featureKey)
                ->first();

            // If no feature control exists, return default
            if (!$featureControl) {
                Log::debug('Feature control not found, using default', [
                    'org_id' => $orgId,
                    'feature_key' => $featureKey,
                    'default_value' => $defaultValue
                ]);
                return $defaultValue;
            }

            // Check if feature control is currently effective
            if (!$featureControl->isEffective()) {
                Log::debug('Feature control not effective, using default', [
                    'org_id' => $orgId,
                    'feature_key' => $featureKey,
                    'effective_from' => $featureControl->effective_from,
                    'effective_to' => $featureControl->effective_to,
                    'default_value' => $defaultValue
                ]);
                return $defaultValue;
            }

            // Return typed value
            $typedValue = $featureControl->getTypedValue();
            
            Log::info('Feature control applied', [
                'org_id' => $orgId,
                'feature_key' => $featureKey,
                'feature_type' => $featureControl->feature_type,
                'value' => $typedValue
            ]);

            return $typedValue;
        });
    }

    /**
     * Check if a feature is enabled (for BOOLEAN features)
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to check
     * @param bool $defaultValue Default value if not found
     * @return bool
     */
    public function isFeatureEnabled(int $orgId, string $featureKey, bool $defaultValue = false): bool
    {
        $value = $this->getFeatureValue($orgId, $featureKey, $defaultValue);
        return (bool) $value;
    }

    /**
     * Get numeric feature value (for NUMERIC features)
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param int|null $defaultValue Default value if not found
     * @return int|null
     */
    public function getNumericFeature(int $orgId, string $featureKey, ?int $defaultValue = null): ?int
    {
        $value = $this->getFeatureValue($orgId, $featureKey, $defaultValue);
        return $value !== null ? (int) $value : null;
    }

    /**
     * Get all effective feature controls for an organization
     * 
     * @param int $orgId Organization ID
     * @return array Keyed by feature_key with typed values
     */
    public function getAllFeatures(int $orgId): array
    {
        $cacheKey = $this->getCacheKey($orgId, 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, FeatureControl> $featureControls */
            $featureControls = FeatureControl::where('org_id', $orgId)->get();

            $features = [];
            /** @var FeatureControl $control */
            foreach ($featureControls as $control) {
                // Only include effective feature controls
                if ($control->isEffective()) {
                    $features[$control->feature_key] = $control->getTypedValue();
                }
            }

            Log::debug('Retrieved all feature controls', [
                'org_id' => $orgId,
                'count' => count($features)
            ]);

            return $features;
        });
    }

    /**
     * Clear feature control cache for an organization
     * 
     * @param int $orgId Organization ID
     * @return void
     */
    public function clearCache(int $orgId): void
    {
        try {
            // Clear all feature control caches for this organization
            $pattern = self::CACHE_PREFIX . $orgId . ':*';
            
            // Try to use Redis if available
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        // Remove the Redis prefix from the key
                        $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
                        Cache::forget($cleanKey);
                    }
                }
                
                Log::info('Feature control cache cleared (Redis)', [
                    'org_id' => $orgId,
                    'keys_cleared' => count($keys ?? [])
                ]);
            } else {
                // For non-Redis stores, we need to clear individual keys
                // This is less efficient but works with array/file cache
                Cache::forget($this->getCacheKey($orgId, 'all'));
                
                Log::info('Feature control cache cleared (non-Redis)', [
                    'org_id' => $orgId
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear feature control cache', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get feature value with plan fallback
     * Checks feature_controls first, then falls back to subscription plan defaults
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key to retrieve
     * @param string $planField Field name in subscription_plans table
     * @return mixed
     */
    public function getFeatureWithPlanFallback(int $orgId, string $featureKey, string $planField): mixed
    {
        // First check for feature control override
        /** @var FeatureControl|null $featureControl */
        $featureControl = FeatureControl::where('org_id', $orgId)
            ->where('feature_key', $featureKey)
            ->first();

        if ($featureControl && $featureControl->isEffective()) {
            $value = $featureControl->getTypedValue();
            
            Log::info('Using feature control override', [
                'org_id' => $orgId,
                'feature_key' => $featureKey,
                'value' => $value
            ]);
            
            return $value;
        }

        // Fallback to plan default
        $activeSubscription = ActiveSubscription::find($orgId);
        
        if (!$activeSubscription) {
            Log::warning('No active subscription found for feature fallback', [
                'org_id' => $orgId,
                'feature_key' => $featureKey
            ]);
            return null;
        }

        $plan = SubscriptionPlan::find($activeSubscription->plan_id);
        
        if (!$plan || !isset($plan->$planField)) {
            Log::warning('Plan field not found for feature fallback', [
                'org_id' => $orgId,
                'plan_id' => $activeSubscription->plan_id,
                'plan_field' => $planField
            ]);
            return null;
        }

        $value = $plan->$planField;
        
        Log::debug('Using plan default for feature', [
            'org_id' => $orgId,
            'feature_key' => $featureKey,
            'plan_field' => $planField,
            'value' => $value
        ]);

        return $value;
    }

    /**
     * Get cache key for feature control
     * 
     * @param int $orgId Organization ID
     * @param string $featureKey Feature key
     * @return string
     */
    private function getCacheKey(int $orgId, string $featureKey): string
    {
        return self::CACHE_PREFIX . $orgId . ':' . $featureKey;
    }
}
