<?php

namespace App\Exceptions;

/**
 * Exception thrown when a tenant organization is not found
 */
class TenantNotFoundException extends ApiException
{
    public function __construct(
        string $message = 'Tenant not found',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'TENANT_NOT_FOUND',
            $message,
            $details,
            404,
            $requestId
        );
    }
}
