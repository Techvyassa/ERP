<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Control\Organization;
use App\Exceptions\TenantContextRequiredException;
use App\Exceptions\TenantNotFoundException;
use App\Exceptions\TenantSuspendedException;
use App\Exceptions\TenantTerminatedException;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Resolution Middleware - ResolveTenant
 * 
 * Extracts org_slug from request header or route parameter
 * Queries Control DB organizations table
 * Validates registration_status (returns 400/403/404/410 as needed)
 * Resolves tenant_db_name and stores in request context
 * 
 * Requirements: 4.2, 4.3, 4.5, 4.6, 4.7, 4.8
 */
class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract org_slug from request header, route parameter, OR the validated JWT token
        $orgSlug = $request->header('X-Org-Slug')
            ?? $request->route('org_slug');

        // If not in header or route, check if ValidateJWT middleware extracted it
        if (!$orgSlug && $request->has('auth_org_id')) {
            // Wait, we need the slug, not just ID. But we can fetch it via token's org_slug claim or ID
            // If the token payload has 'org_slug', ValidateJWT can extract it. But currently ValidateJWT only extracts auth_org_id.
            // But we can just lookup by ID if orgSlug is missing but auth_org_id is present.

            // Actually, wait, let's just lookup by org_id if present
            $organization = Organization::find($request->input('auth_org_id'));

            if ($organization) {
                $orgSlug = $organization->org_slug;
            }
        }

        // Requirement 4.5: If org_slug and organization is missing, throw exception
        if (!$orgSlug) {
            throw new TenantContextRequiredException();
        }

        // Query Control DB organizations table if not already queried
        if (!isset($organization)) {
            $organization = Organization::where('org_slug', $orgSlug)->first();
        }

        // Requirement 4.6: If tenant not found, throw exception
        if (!$organization) {
            throw new TenantNotFoundException();
        }

        // Validate registration_status
        // Requirement 4.7: If SUSPENDED, throw exception
        if ($organization->registration_status === 'SUSPENDED') {
            throw new TenantSuspendedException();
        }

        // Requirement 4.8: If TERMINATED, throw exception
        if ($organization->registration_status === 'TERMINATED') {
            throw new TenantTerminatedException();
        }

        // Only ACTIVE and PENDING organizations can proceed
        if (!\in_array($organization->registration_status, ['ACTIVE', 'PENDING'], true)) {
            throw new \App\Exceptions\ApiException(
                'INVALID_TENANT_STATUS',
                'Invalid tenant status',
                [],
                403
            );
        }

        // Requirement 4.6: Validate tenant_db_name exists
        if (!$organization->tenant_db_name) {
            throw new \App\Exceptions\ApiException(
                'TENANT_DB_NOT_CONFIGURED',
                'Tenant database not configured',
                [],
                500
            );
        }

        // Store tenant context in request for downstream middleware
        $request->merge([
            'tenant_org_id' => $organization->org_id,
            'tenant_org_slug' => $organization->org_slug,
            'tenant_db_name' => $organization->tenant_db_name,
            'tenant_organization' => $organization,
        ]);

        // Requirement 4.10: Log database connection context
        AuditLogger::logDatabaseSwitch(
            $organization->org_id,
            $organization->tenant_db_name,
            $organization->org_slug
        );

        return $next($request);
    }
}
