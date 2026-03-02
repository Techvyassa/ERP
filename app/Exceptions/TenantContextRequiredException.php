<?php

namespace App\Exceptions;

/**
 * Exception thrown when tenant context (org_slug) is missing from request
 */
class TenantContextRequiredException extends ApiException
{
    public function __construct(
        string $message = 'Tenant context required',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'TENANT_CONTEXT_REQUIRED',
            $message,
            $details,
            400,
            $requestId
        );
    }
}
