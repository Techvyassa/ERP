<?php

namespace App\Contracts;

use App\Models\Tenant\User;

/**
 * Authentication Result DTO
 * 
 * Contains JWT tokens and user information after successful authentication
 */
class AuthResult
{
    /**
     * @param string $accessToken JWT access token (24-hour expiry)
     * @param string $refreshToken Refresh token (30-day expiry)
     * @param int $expiresIn Seconds until access token expires
     * @param User $user Authenticated user
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public User $user
    ) {}
}
