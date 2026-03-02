<?php

namespace App\Exceptions;

/**
 * Exception thrown when a tenant is suspended
 */
class TenantSuspendedException extends ApiException
{
    public function __construct(
        string $message = 'Tenant suspended',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'TENANT_SUSPENDED',
            $message,
            $details,
            403,
            $requestId
        );
    }
}
