<?php

namespace App\Exceptions;

/**
 * Exception thrown when an active subscription is required but not found
 */
class SubscriptionRequiredException extends ApiException
{
    public function __construct(
        string $message = 'Subscription required',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'SUBSCRIPTION_REQUIRED',
            $message,
            $details,
            402,
            $requestId
        );
    }
}
