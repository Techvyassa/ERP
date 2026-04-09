<?php

namespace App\Contracts;

use App\Models\Tenant\User;

interface AuthenticationService
{
    /**
     * Authenticate user and issue tokens
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @param string $orgSlug Organization slug
     * @return AuthResult Authentication result with tokens
     * @throws \App\Exceptions\AuthenticationException
     */
    public function login(string $email, string $password, ?string $orgSlug = null, ?string $portalCode = null, bool $rememberMe = false): AuthResult;
    
    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken Refresh token
     * @return AuthResult New authentication result with refreshed tokens
     * @throws \App\Exceptions\InvalidTokenException
     */
    public function refreshToken(string $refreshToken): AuthResult;
    
    /**
     * Revoke refresh token (logout)
     * 
     * @param string $refreshToken Refresh token to revoke
     * @return void
     */
    public function logout(string $refreshToken): void;
    
    /**
     * Validate JWT token
     * 
     * @param string $token JWT token
     * @return TokenPayload Token payload data
     * @throws \App\Exceptions\InvalidTokenException
     */
    public function validateToken(string $token): TokenPayload;
}
