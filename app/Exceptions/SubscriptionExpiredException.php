<?php

namespace App\Exceptions;

/**
 * Exception thrown when a subscription has expired
 */
class SubscriptionExpiredException extends ApiException
{
    public function __construct(
        string $message = 'Subscription expired',
        mixed $details = [],
        ?string $requestId = null
    ) {
        parent::__construct(
            'SUBSCRIPTION_EXPIRED',
            $message,
            $details,
            402,
            $requestId
        );
    }
}
