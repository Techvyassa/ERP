<?php

namespace App\Services;

use App\Models\Control\Organization;
use App\Models\Control\RefreshToken;
use App\Models\Tenant\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Illuminate\Support\Str;

class TokenService
{
    private const ACCESS_TOKEN_TTL = 1440; // 24 hours in minutes
    private const REFRESH_TOKEN_TTL = 43200; // 30 days in minutes
    
    /**
     * Generate access and refresh tokens for a user
     */
    public function generateTokens(User $user, Organization $organization, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $accessToken = $this->generateAccessToken($user, $organization);
        $refreshToken = $this->generateRefreshToken($user, $organization, $userAgent, $ipAddress);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TOKEN_TTL * 60, // Convert to seconds
            'token_type' => 'Bearer',
        ];
    }
    
    /**
     * Generate JWT access token
     */
    private function generateAccessToken(User $user, Organization $organization): string
    {
        $customClaims = [
            'sub' => $user->user_id,
            'org_id' => $organization->org_id,
            'org_slug' => $organization->org_slug,
            'type' => 'access'
        ];
        
        $payload = JWTFactory::customClaims($customClaims)
            ->ttl(self::ACCESS_TOKEN_TTL)
            ->make();
        
        return JWTAuth::manager()->encode($payload)->get();
    }
    
    /**
     * Generate refresh token and store in database
     */
    private function generateRefreshToken(User $user, Organization $organization, ?string $userAgent, ?string $ipAddress): string
    {
        $token = bin2hex(random_bytes(32));
        
        RefreshToken::create([
            'org_id' => $organization->org_id,
            'user_id' => $user->user_id,
            'token' => $token,
            'expires_at' => now()->addMinutes(self::REFRESH_TOKEN_TTL),
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);
        
        return $token;
    }
    
    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshTokenString): array
    {
        $refreshToken = RefreshToken::where('token', $refreshTokenString)->first();
        
        if (!$refreshToken || !$refreshToken->isValid()) {
            throw new \Exception('Invalid or expired refresh token');
        }
        
        // Update last used timestamp
        $refreshToken->updateLastUsed();
        
        // Get organization
        $organization = $refreshToken->organization;
        
        // Switch to tenant database and get user
        config(['database.connections.tenant.database' => $organization->tenant_db_name]);
        \DB::purge('tenant');
        \DB::reconnect('tenant');
        
        $user = User::find($refreshToken->user_id);
        
        if (!$user || !$user->is_active) {
            throw new \Exception('User not found or inactive');
        }
        
        // Generate new access token
        $accessToken = $this->generateAccessToken($user, $organization);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenString, // Return same refresh token
            'expires_in' => self::ACCESS_TOKEN_TTL * 60,
            'token_type' => 'Bearer',
        ];
    }
    
    /**
     * Revoke a refresh token
     */
    public function revokeRefreshToken(string $refreshTokenString): void
    {
        $refreshToken = RefreshToken::where('token', $refreshTokenString)->first();
        
        if ($refreshToken) {
            $refreshToken->revoke();
        }
    }
    
    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllUserTokens(int $orgId, int $userId): void
    {
        RefreshToken::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return RefreshToken::where('expires_at', '<', now())
            ->orWhere('is_revoked', true)
            ->delete();
    }
}
