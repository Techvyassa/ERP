<?php

namespace App\Exceptions;

/**
 * Exception thrown when API rate limit is exceeded
 */
class RateLimitExceededException extends ApiException
{
    protected int $retryAfter;

    public function __construct(
        int $retryAfter,
        string $message = 'Rate limit exceeded',
        ?string $requestId = null
    ) {
        $this->retryAfter = $retryAfter;
        
        parent::__construct(
            'RATE_LIMIT_EXCEEDED',
            $message,
            ['retry_after' => $retryAfter],
            429,
            $requestId
        );
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Render the exception with Retry-After header
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return parent::render()->header('Retry-After', $this->retryAfter);
    }
}
