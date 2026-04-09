<?php

namespace App\Services;

use App\Contracts\AuthenticationService;
use App\Contracts\AuthResult;
use App\Contracts\TokenPayload;
use App\Contracts\DatabaseConnectionRouter;
use App\Exceptions\AuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\Helpers\AuditLogger;
use App\Models\Control\Organization;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Authentication Service Implementation
 * 
 * Handles user authentication and JWT token management
 * Requirements: 10.1-10.7
 */
class AuthenticationServiceImpl implements AuthenticationService
{
    public function __construct(
        private DatabaseConnectionRouter $connectionRouter,
        private TokenService $tokenService
    ) {}
    
    /**
     * Authenticate user and issue tokens
     * 
     * Authentication Flow:
     * 1. Search all organizations to find which one has this user email
     * 2. Validate organization is ACTIVE
     * 3. Switch to Tenant DB for that organization
     * 4. Query users table by email
     * 5. Verify password using Hash::check()
     * 6. Update last_login_at timestamp
     * 7. Generate access token (24h) and refresh token (30d)
     * 8. Store refresh token in database
     * 9. Return tokens to client
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @param string|null $orgSlug Organization slug (optional, will be auto-detected)
     * @param string|null $portalCode Portal context (MAIN, PROCUREMENT, STORE, QC, etc.)
     * @param bool $rememberMe
     * @return AuthResult Authentication result with tokens
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, ?string $orgSlug = null, ?string $portalCode = null, bool $rememberMe = false): AuthResult
    {
        // Step 1: Switch to Control DB and find organization
        $this->connectionRouter->switchToControl();
        
        $organization = null;
        
        if ($orgSlug) {
            // If org_slug provided, use it directly
            $organization = Organization::where('org_slug', $orgSlug)->first();
            
            if (!$organization) {
                AuditLogger::logAuthAttempt($email, $orgSlug, false, 'Organization not found');
                throw new AuthenticationException('Organization not found', 404);
            }
        } else {
            // Auto-detect organization by searching for user email across all active organizations
            $organization = $this->findOrganizationByUserEmail($email);
            
            if (!$organization) {
                AuditLogger::logAuthAttempt($email, 'auto-detect', false, 'No organization found for this email');
                throw new AuthenticationException('No account found with this email address', 404);
            }
        }
        
        // Step 2: Validate organization is ACTIVE
        if (!$organization->isActive()) {
            $reason = 'Organization is not active';
            if ($organization->isSuspended()) {
                $reason = 'Organization is suspended';
                AuditLogger::logAuthAttempt($email, $organization->org_slug, false, $reason);
                throw new AuthenticationException($reason, 403);
            }
            if ($organization->isTerminated()) {
                $reason = 'Organization is terminated';
                AuditLogger::logAuthAttempt($email, $organization->org_slug, false, $reason);
                throw new AuthenticationException($reason, 410);
            }
            AuditLogger::logAuthAttempt($email, $organization->org_slug, false, $reason);
            throw new AuthenticationException($reason, 403);
        }
        
        // Step 3-4: Switch to Tenant DB and lookup user
        $this->connectionRouter->switchToTenant($organization->tenant_db_name);
        
        $user = User::with(['role', 'department'])->where('email', $email)->first();
        
        if (!$user) {
            AuditLogger::logAuthAttempt($email, $organization->org_slug, false, 'Invalid credentials');
            throw new AuthenticationException('Invalid credentials', 401);
        }

        // --- Portal-Based Isolation Policy ---
        $roleCode = optional($user->role)->role_code;
        
        // If they hit the MAIN portal (usually /login), they MUST be an ADMIN.
        if (!$portalCode || strtoupper($portalCode) === 'MAIN') {
            if ($roleCode !== 'ADMIN') {
                AuditLogger::logAuthAttempt($email, $organization->org_slug, false, "Unauthorized portal access (MAIN)", null, $user->id);
                throw new AuthenticationException('Access Denied. Please use your departmental portal.', 403);
            }
        } else {
             // If they hit a SPECIALIZED portal, ensure their role matches or they are an ADMIN
             // Mapping portal codes to required role codes where necessary
             $portalCode = strtoupper($portalCode);
             if ($roleCode !== 'ADMIN' && $roleCode !== $portalCode) {
                 // Specialized logic for some portal/role names if they don't match 1:1
                 // Example: WAREHOUSE portal uses STOREKEEPER/STORE_MGR roles
                 $isAuthorized = false;
                 
                 if ($portalCode === 'STORE' && in_array($roleCode, ['STOREKEEPER', 'STORE_MGR'])) $isAuthorized = true;
                 if ($portalCode === 'QC' && in_array($roleCode, ['QC_TECH', 'QC_MGR'])) $isAuthorized = true;
                 if ($portalCode === 'PROCUREMENT' && in_array($roleCode, ['PROC_EXE', 'PROC_MGR'])) $isAuthorized = true;
                 if ($portalCode === 'SECURITY' && in_array($roleCode, ['SECURITY_GUARD', 'SECURITY_SUPVR'])) $isAuthorized = true;
                 if ($portalCode === 'PRODUCTION' && in_array($roleCode, ['PRODUCTION_EXE', 'PRODUCTION_MGR'])) $isAuthorized = true;
                 if ($portalCode === 'MAINTENANCE' && in_array($roleCode, ['MAINTENANCE_TECH', 'MAINT_MGR'])) $isAuthorized = true;
                 
                 // If role code exactly matches portal code, it's also authorized
                 if ($roleCode === $portalCode) $isAuthorized = true;

                 if (!$isAuthorized) {
                     AuditLogger::logAuthAttempt($email, $organization->org_slug, false, "Unauthorized portal access ($portalCode)", null, $user->id);
                     throw new AuthenticationException("Unauthorized access to this departmental portal.", 403);
                 }
             }
        }
        
        // Check if user is active
        if (!$user->is_active) {
            AuditLogger::logAuthAttempt($email, $organization->org_slug, false, 'User account is inactive', null, $user->id);
            throw new AuthenticationException('User account is inactive', 403);
        }
        
        // Step 5: Verify password
        if (!$user->verifyPassword($password)) {
            AuditLogger::logAuthAttempt($email, $organization->org_slug, false, 'Invalid credentials', null, $user->id);
            throw new AuthenticationException('Invalid credentials', 401);
        }
        
        // Step 6: Update last_login_at timestamp
        $user->updateLastLogin();
        
        // Step 7-8: Generate and store tokens using TokenService
        $tokens = $this->tokenService->generateTokens($user, $organization, null, null, $rememberMe);
        
        // Log successful authentication
        AuditLogger::logAuthAttempt($email, $organization->org_slug, true, null, null, $user->id);
        
        // Step 9: Return tokens to client
        return new AuthResult(
            accessToken: $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            expiresIn: $tokens['expires_in'],
            user: $user,
            organization: $organization
        );
    }
    
    /**
     * Find organization by searching for user email across all tenant databases
     * 
     * @param string $email User email
     * @return Organization|null
     */
    private function findOrganizationByUserEmail(string $email): ?Organization
    {
        // Get all active and pending organizations
        $organizations = Organization::whereIn('registration_status', ['ACTIVE', 'PENDING'])->get();
        
        \Log::info("Searching for user email across organizations", [
            'email' => $email,
            'total_organizations' => $organizations->count()
        ]);
        
        foreach ($organizations as $organization) {
            try {
                \Log::info("Checking organization", [
                    'org_slug' => $organization->org_slug,
                    'tenant_db' => $organization->tenant_db_name,
                    'status' => $organization->registration_status
                ]);
                
                // Switch to this organization's tenant database
                $this->connectionRouter->switchToTenant($organization->tenant_db_name);
                
                // Check if user exists in this tenant database
                $userExists = User::where('email', $email)->exists();
                
                \Log::info("User search result", [
                    'org_slug' => $organization->org_slug,
                    'user_exists' => $userExists
                ]);
                
                if ($userExists) {
                    \Log::info("User found in organization", [
                        'org_slug' => $organization->org_slug,
                        'email' => $email
                    ]);
                    return $organization;
                }
            } catch (\Exception $e) {
                // Skip this organization if there's a database connection error
                \Log::warning("Failed to check user in organization", [
                    'org_slug' => $organization->org_slug,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
        
        \Log::warning("User not found in any organization", ['email' => $email]);
        return null;
    }
    
    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken Refresh token
     * @return AuthResult New authentication result with refreshed tokens
     * @throws InvalidTokenException
     */
    public function refreshToken(string $refreshToken): AuthResult
    {
        try {
            $tokens = $this->tokenService->refreshAccessToken($refreshToken);
            
            // Get user data for response (tokens already contain org info)
            // We need to fetch user to return in AuthResult
            // The TokenService already validated everything
            
            return new AuthResult(
                accessToken: $tokens['access_token'],
                refreshToken: $tokens['refresh_token'],
                expiresIn: $tokens['expires_in'],
                user: null // User data not needed for refresh
            );
        } catch (\Exception $e) {
            throw new InvalidTokenException($e->getMessage(), 401);
        }
    }
    
    /**
     * Revoke refresh token (logout)
     * 
     * @param string $refreshToken Refresh token to revoke
     * @return void
     */
    public function logout(string $refreshToken): void
    {
        $this->tokenService->revokeRefreshToken($refreshToken);
    }
    
    /**
     * Validate JWT token
     * 
     * @param string $token JWT token
     * @return TokenPayload Token payload data
     * @throws InvalidTokenException
     */
    public function validateToken(string $token): TokenPayload
    {
        try {
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            
            return new TokenPayload(
                userId: (int) $payload->get('sub'),
                orgId: (int) $payload->get('org_id'),
                orgSlug: $payload->get('org_slug'),
                issuedAt: $payload->get('iat'),
                expiresAt: $payload->get('exp')
            );
        } catch (JWTException $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage(), 401);
        }
    }
}
