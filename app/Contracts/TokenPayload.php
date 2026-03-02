<?php

namespace App\Contracts;

/**
 * JWT Token Payload DTO
 * 
 * Contains decoded JWT token claims
 */
class TokenPayload
{
    /**
     * @param int $userId User ID
     * @param int $orgId Organization ID
     * @param string $orgSlug Organization slug
     * @param int $issuedAt Token issued at timestamp
     * @param int $expiresAt Token expires at timestamp
     */
    public function __construct(
        public int $userId,
        public int $orgId,
        public string $orgSlug,
        public int $issuedAt,
        public int $expiresAt
    ) {}
}
