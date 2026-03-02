<?php

namespace App\Exceptions;

/**
 * Exception thrown when user capacity limit is reached
 */
class UserLimitReachedException extends ApiException
{
    public function __construct(
        string $message = 'User limit reached for your plan',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'USER_LIMIT_REACHED',
            $message,
            $details,
            403,
            $requestId
        );
    }
}
