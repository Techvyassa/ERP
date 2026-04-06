<?php

use App\Helpers\TenantUrlHelper;
use Illuminate\Support\Str;

if (!function_exists('generateEmployeeCode')) {
    /**
     * Generate a unique employee code based on department name
     * 
     * @param string $deptName Department name
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @return string Generated employee code
     */
    function generateEmployeeCode(string $deptName, string $firstName = '', string $lastName = ''): string
    {
        // Create prefix from department name (first 3 letters, uppercase)
        $prefix = strtoupper(substr(trim($deptName), 0, 3));
        if (empty($prefix)) {
            $prefix = 'EMP';
        }
        
        // If first and last name are provided, use first letter of each for uniqueness
        if (!empty($firstName) && !empty($lastName)) {
            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
            return $prefix . '-' . $initials . '-' . date('Ymd');
        }
        
        // Otherwise, use timestamp for uniqueness
        return $prefix . '-' . date('Ymd') . '-' . substr(mt_rand(1000, 9999), 0, 4);
    }
}

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
