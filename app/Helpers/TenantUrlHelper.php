<?php

namespace App\Helpers;

use App\Models\Control\Organization;

class TenantUrlHelper
{
    /**
     * Generate URL for a tenant route
     * 
     * @param string $route Route name or path
     * @param Organization|string $organization Organization model or slug
     * @param array $parameters Additional route parameters
     * @param bool $forceSubdomain Force subdomain URL even if path-based is default
     * @return string
     */
    public static function route(string $route, $organization, array $parameters = [], bool $forceSubdomain = false): string
    {
        $orgSlug = $organization instanceof Organization ? $organization->org_slug : $organization;
        
        // Determine if we should use subdomain or path-based URL
        $useSubdomain = $forceSubdomain || config('tenant.default_mode') === 'subdomain';
        
        if ($useSubdomain) {
            return self::subdomainUrl($route, $orgSlug, $parameters);
        } else {
            return self::pathUrl($route, $orgSlug, $parameters);
        }
    }
    
    /**
     * Generate subdomain-based URL
     * 
     * @param string $route
     * @param string $orgSlug
     * @param array $parameters
     * @return string
     */
    public static function subdomainUrl(string $route, string $orgSlug, array $parameters = []): string
    {
        $domain = config('app.domain');
        $protocol = config('app.url_protocol', 'https');
        
        $url = "{$protocol}://{$orgSlug}.{$domain}";
        
        // Remove leading slash from route
        $route = ltrim($route, '/');
        
        if ($route) {
            $url .= "/{$route}";
        }
        
        // Add query parameters
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
    }
    
    /**
     * Generate path-based URL
     * 
     * @param string $route
     * @param string $orgSlug
     * @param array $parameters
     * @return string
     */
    public static function pathUrl(string $route, string $orgSlug, array $parameters = []): string
    {
        $baseUrl = config('app.url');
        
        // Remove leading slash from route
        $route = ltrim($route, '/');
        
        $url = "{$baseUrl}/org/{$orgSlug}";
        
        if ($route) {
            $url .= "/{$route}";
        }
        
        // Add query parameters
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
    }
    
    /**
     * Get current tenant type (subdomain or path)
     * 
     * @return string|null
     */
    public static function getCurrentTenantType(): ?string
    {
        return request()->get('tenant_type');
    }
    
    /**
     * Get current organization slug
     * 
     * @return string|null
     */
    public static function getCurrentOrgSlug(): ?string
    {
        return request()->get('tenant_org_slug');
    }
    
    /**
     * Check if current request is in tenant context
     * 
     * @return bool
     */
    public static function isInTenantContext(): bool
    {
        return !empty(request()->get('tenant_org_slug'));
    }
}
