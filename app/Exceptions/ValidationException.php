<?php

namespace App\Exceptions;

/**
 * Exception thrown for validation errors
 */
class ValidationException extends ApiException
{
    public function __construct(
        array $errors,
        string $message = 'Validation failed',
        ?string $requestId = null
    ) {
        parent::__construct(
            'VALIDATION_ERROR',
            $message,
            $errors,
            422,
            $requestId
        );
    }
}
