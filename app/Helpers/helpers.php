<?php

use App\Helpers\TenantUrlHelper;

if (!function_exists('tenantRoute')) {
    /**
     * Generate a tenant-aware route URL
     * 
     * @param string $route
     * @param string|null $orgSlug
     * @param array $parameters
     * @return string
     */
    function tenantRoute(string $route, ?string $orgSlug = null, array $parameters = []): string
    {
        // If no org slug provided, try to get from current request
        if (!$orgSlug) {
            $orgSlug = TenantUrlHelper::getCurrentOrgSlug();
        }
        
        if (!$orgSlug) {
            throw new \Exception('Organization slug is required for tenant routes');
        }
        
        return TenantUrlHelper::route($route, $orgSlug, $parameters);
    }
}

if (!function_exists('currentTenantType')) {
    /**
     * Get current tenant type (subdomain or path)
     * 
     * @return string|null
     */
    function currentTenantType(): ?string
    {
        return TenantUrlHelper::getCurrentTenantType();
    }
}

if (!function_exists('currentOrgSlug')) {
    /**
     * Get current organization slug
     * 
     * @return string|null
     */
    function currentOrgSlug(): ?string
    {
        return TenantUrlHelper::getCurrentOrgSlug();
    }
}

if (!function_exists('isInTenantContext')) {
    /**
     * Check if current request is in tenant context
     * 
     * @return bool
     */
    function isInTenantContext(): bool
    {
        return TenantUrlHelper::isInTenantContext();
    }
}
