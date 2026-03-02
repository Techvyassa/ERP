<?php

namespace App\Exceptions;

use App\Helpers\ResponseFormatter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Base API Exception class
 * All custom API exceptions should extend this class
 */
class ApiException extends Exception
{
    protected string $errorCode;
    protected mixed $details;
    protected int $statusCode;
    protected ?string $requestId;

    /**
     * Create a new API exception instance
     *
     * @param string $errorCode Error code (e.g., 'TENANT_NOT_FOUND')
     * @param string $message Human-readable error message
     * @param mixed $details Additional error details
     * @param int $statusCode HTTP status code
     * @param string|null $requestId Optional request ID
     */
    public function __construct(
        string $errorCode,
        string $message,
        mixed $details = [],
        int $statusCode = 400,
        ?string $requestId = null
    ) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->details = $details;
        $this->statusCode = $statusCode;
        $this->requestId = $requestId ?? Str::uuid()->toString();
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the error details
     */
    public function getDetails(): mixed
    {
        return $this->details;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the request ID
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Render the exception as a JSON response
     */
    public function render(): JsonResponse
    {
        return ResponseFormatter::error(
            $this->errorCode,
            $this->message,
            $this->details,
            $this->statusCode,
            $this->requestId
        );
    }
}
