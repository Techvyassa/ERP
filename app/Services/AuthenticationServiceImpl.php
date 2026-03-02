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
use Illuminate\Support\Facades\Redis;
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
    private const ACCESS_TOKEN_TTL = 1440; // 24 hours in minutes
    private const REFRESH_TOKEN_TTL = 43200; // 30 days in minutes
    private const REDIS_REFRESH_TOKEN_PREFIX = 'refresh_token:';
    
    public function __construct(
        private DatabaseConnectionRouter $connectionRouter
    ) {}
    
    /**
     * Authenticate user and issue tokens
     * 
     * Authentication Flow:
     * 1. Switch to Control DB, resolve org_id from org_slug
     * 2. Validate organization is ACTIVE
     * 3. Switch to Tenant DB for that organization
     * 4. Query users table by email
     * 5. Verify password using Hash::check()
     * 6. Update last_login_at timestamp
     * 7. Generate access token (24h) and refresh token (30d)
     * 8. Store refresh token in Redis with user_id mapping
     * 9. Return tokens to client
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @param string $orgSlug Organization slug
     * @return AuthResult Authentication result with tokens
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, string $orgSlug): AuthResult
    {
        // Step 1-2: Switch to Control DB and resolve organization
        $this->connectionRouter->switchToControl();
        
        $organization = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$organization) {
            AuditLogger::logAuthAttempt($email, $orgSlug, false, 'Organization not found');
            throw new AuthenticationException('Organization not found', 404);
        }
        
        // Validate organization is ACTIVE
        if (!$organization->isActive()) {
            $reason = 'Organization is not active';
            if ($organization->isSuspended()) {
                $reason = 'Organization is suspended';
                AuditLogger::logAuthAttempt($email, $orgSlug, false, $reason);
                throw new AuthenticationException($reason, 403);
            }
            if ($organization->isTerminated()) {
                $reason = 'Organization is terminated';
                AuditLogger::logAuthAttempt($email, $orgSlug, false, $reason);
                throw new AuthenticationException($reason, 410);
            }
            AuditLogger::logAuthAttempt($email, $orgSlug, false, $reason);
            throw new AuthenticationException($reason, 403);
        }
        
        // Step 3-4: Switch to Tenant DB and lookup user
        $this->connectionRouter->switchToTenant($organization->tenant_db_name);
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            AuditLogger::logAuthAttempt($email, $orgSlug, false, 'Invalid credentials');
            throw new AuthenticationException('Invalid credentials', 401);
        }
        
        // Check if user is active
        if (!$user->is_active) {
            AuditLogger::logAuthAttempt($email, $orgSlug, false, 'User account is inactive', null, $user->user_id);
            throw new AuthenticationException('User account is inactive', 403);
        }
        
        // Step 5: Verify password
        if (!$user->verifyPassword($password)) {
            AuditLogger::logAuthAttempt($email, $orgSlug, false, 'Invalid credentials', null, $user->user_id);
            throw new AuthenticationException('Invalid credentials', 401);
        }
        
        // Step 6: Update last_login_at timestamp
        $user->updateLastLogin();
        
        // Step 7: Generate JWT access token and refresh token
        $accessToken = $this->generateAccessToken($user, $organization);
        $refreshToken = $this->generateRefreshToken();
        
        // Step 8: Store refresh token in Redis
        $this->storeRefreshToken($refreshToken, $user->user_id, $organization->org_id);
        
        // Log successful authentication
        AuditLogger::logAuthAttempt($email, $orgSlug, true, null, null, $user->user_id);
        
        // Step 9: Return tokens to client
        return new AuthResult(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: self::ACCESS_TOKEN_TTL * 60, // Convert to seconds
            user: $user
        );
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
        // Validate refresh token exists in Redis
        $tokenData = $this->getRefreshTokenData($refreshToken);
        
        if (!$tokenData) {
            throw new InvalidTokenException('Invalid or expired refresh token', 401);
        }
        
        $userId = $tokenData['user_id'];
        $orgId = $tokenData['org_id'];
        
        // Switch to Control DB to get organization
        $this->connectionRouter->switchToControl();
        $organization = Organization::find($orgId);
        
        if (!$organization || !$organization->isActive()) {
            throw new InvalidTokenException('Organization is not active', 403);
        }
        
        // Switch to Tenant DB to get user
        $this->connectionRouter->switchToTenant($organization->tenant_db_name);
        $user = User::find($userId);
        
        if (!$user || !$user->is_active) {
            throw new InvalidTokenException('User is not active', 403);
        }
        
        // Generate new tokens
        $newAccessToken = $this->generateAccessToken($user, $organization);
        $newRefreshToken = $this->generateRefreshToken();
        
        // Revoke old refresh token and store new one
        $this->revokeRefreshToken($refreshToken);
        $this->storeRefreshToken($newRefreshToken, $userId, $orgId);
        
        return new AuthResult(
            accessToken: $newAccessToken,
            refreshToken: $newRefreshToken,
            expiresIn: self::ACCESS_TOKEN_TTL * 60,
            user: $user
        );
    }
    
    /**
     * Revoke refresh token (logout)
     * 
     * @param string $refreshToken Refresh token to revoke
     * @return void
     */
    public function logout(string $refreshToken): void
    {
        $this->revokeRefreshToken($refreshToken);
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
    
    /**
     * Generate JWT access token
     * 
     * Token structure:
     * {
     *   "sub": "user_id",
     *   "org_id": 123,
     *   "org_slug": "acme",
     *   "iat": 1234567890,
     *   "exp": 1234654290,
     *   "type": "access"
     * }
     * 
     * @param User $user
     * @param Organization $organization
     * @return string JWT token
     */
    private function generateAccessToken(User $user, Organization $organization): string
    {
        $customClaims = [
            'sub' => $user->user_id,
            'org_id' => $organization->org_id,
            'org_slug' => $organization->org_slug,
            'type' => 'access'
        ];
        
        $payload = JWTFactory::customClaims($customClaims)->make();
        
        // Set TTL to 24 hours
        return JWTAuth::manager()->encode($payload)->get();
    }
    
    /**
     * Generate refresh token (random string)
     * 
     * @return string Refresh token
     */
    private function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Store refresh token in Redis
     * 
     * @param string $refreshToken
     * @param int $userId
     * @param int $orgId
     * @return void
     */
    private function storeRefreshToken(string $refreshToken, int $userId, int $orgId): void
    {
        $key = self::REDIS_REFRESH_TOKEN_PREFIX . $refreshToken;
        $data = json_encode([
            'user_id' => $userId,
            'org_id' => $orgId,
            'created_at' => time()
        ]);
        
        // Store with 30-day expiration
        Redis::setex($key, self::REFRESH_TOKEN_TTL * 60, $data);
    }
    
    /**
     * Get refresh token data from Redis
     * 
     * @param string $refreshToken
     * @return array|null Token data or null if not found
     */
    private function getRefreshTokenData(string $refreshToken): ?array
    {
        $key = self::REDIS_REFRESH_TOKEN_PREFIX . $refreshToken;
        $data = Redis::get($key);
        
        if (!$data) {
            return null;
        }
        
        return json_decode($data, true);
    }
    
    /**
     * Revoke refresh token from Redis
     * 
     * @param string $refreshToken
     * @return void
     */
    private function revokeRefreshToken(string $refreshToken): void
    {
        $key = self::REDIS_REFRESH_TOKEN_PREFIX . $refreshToken;
        Redis::del($key);
    }
}
