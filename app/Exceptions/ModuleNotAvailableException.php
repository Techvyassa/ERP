<?php

namespace App\Exceptions;

/**
 * Exception thrown when a module is not available in the subscription plan
 */
class ModuleNotAvailableException extends ApiException
{
    public function __construct(
        string $message = 'Module not available in your plan',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'MODULE_NOT_AVAILABLE',
            $message,
            $details,
            403,
            $requestId
        );
    }
}
