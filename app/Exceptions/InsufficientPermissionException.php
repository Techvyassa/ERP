<?php

namespace App\Exceptions;

/**
 * Exception thrown when a user lacks required permissions
 */
class InsufficientPermissionException extends ApiException
{
    public function __construct(
        string $message = 'Insufficient permissions',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'INSUFFICIENT_PERMISSION',
            $message,
            $details,
            403,
            $requestId
        );
    }
}
