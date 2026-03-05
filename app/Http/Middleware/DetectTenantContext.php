<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Control\Organization;

/**
 * Detect Tenant Context Middleware
 * 
 * Detects organization from either:
 * 1. Subdomain (e.g., acme.yoursite.com)
 * 2. URL path (e.g., /org/acme/dashboard)
 * 
 * Sets tenant context for the request
 */
class DetectTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orgSlug = null;
        $tenantType = null;
        
        // Method 1: Check for subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);
        
        // Debug logging
        \Log::info('DetectTenantContext Debug', [
            'host' => $host,
            'extracted_subdomain' => $subdomain,
            'main_domain' => config('app.domain'),
            'full_url' => $request->fullUrl(),
            'path' => $request->path(),
        ]);
        
        if ($subdomain && $subdomain !== 'www') {
            $orgSlug = $subdomain;
            $tenantType = 'subdomain';
        }
        
        // Method 2: Check for /org/{slug} in URL path
        if (!$orgSlug && $request->is('org/*')) {
            $segments = $request->segments();
            if (isset($segments[1])) {
                $orgSlug = $segments[1];
                $tenantType = 'path';
            }
        }
        
        // If no tenant context found, continue without setting it
        if (!$orgSlug) {
            \Log::warning('No tenant context detected', [
                'host' => $host,
                'path' => $request->path(),
            ]);
            return $next($request);
        }
        
        \Log::info('Tenant context detected', [
            'org_slug' => $orgSlug,
            'tenant_type' => $tenantType,
        ]);
        
        // Validate organization exists
        $organization = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$organization) {
            \Log::error('Organization not found', ['org_slug' => $orgSlug]);
            abort(404, 'Organization not found: ' . $orgSlug);
        }
        
        // Check organization status
        if ($organization->isSuspended()) {
            abort(403, 'This organization has been suspended');
        }
        
        if ($organization->isTerminated()) {
            abort(410, 'This organization has been terminated');
        }
        
        // Store tenant context in request
        $request->merge([
            'tenant_org_slug' => $orgSlug,
            'tenant_org_id' => $organization->org_id,
            'tenant_type' => $tenantType,
            'tenant_organization' => $organization,
        ]);
        
        // Share with views
        view()->share('currentOrg', $organization);
        view()->share('organization', $organization); // Alias for consistency
        view()->share('tenantType', $tenantType);
        
        \Log::info('Tenant context set successfully', [
            'org_id' => $organization->org_id,
            'org_name' => $organization->org_name,
        ]);
        
        return $next($request);
    }
    
    /**
     * Extract subdomain from host
     * 
     * @param string $host
     * @return string|null
     */
    private function extractSubdomain(string $host): ?string
    {
        // Get the main domain from config
        $mainDomain = config('app.domain', 'localhost');
        
        // Remove port if present
        $host = explode(':', $host)[0];
        
        // If host is exactly the main domain, no subdomain
        if ($host === $mainDomain) {
            return null;
        }
        
        // Extract subdomain
        $pattern = '/^(.+)\.' . preg_quote($mainDomain, '/') . '$/';
        if (preg_match($pattern, $host, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
