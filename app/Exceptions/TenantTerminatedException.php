<?php

namespace App\Exceptions;

/**
 * Exception thrown when a tenant is terminated
 */
class TenantTerminatedException extends ApiException
{
    public function __construct(
        string $message = 'Tenant terminated',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'TENANT_TERMINATED',
            $message,
            $details,
            410,
            $requestId
        );
    }
}
