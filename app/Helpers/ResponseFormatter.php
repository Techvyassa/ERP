<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ResponseFormatter
{
    /**
     * Format a successful API response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     * @param string|null $requestId Optional request ID (auto-generated if not provided)
     * @return JsonResponse
     */
    public static function success(
        mixed $data = [],
        string $message = 'Success',
        int $statusCode = 200,
        ?string $requestId = null
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'request_id' => $requestId ?? Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], $statusCode);
    }

    /**
     * Format an error API response
     *
     * @param string $code Error code (e.g., 'VALIDATION_ERROR', 'TENANT_NOT_FOUND')
     * @param string $message Error message
     * @param mixed $details Additional error details (field errors, etc.)
     * @param int $statusCode HTTP status code
     * @param string|null $requestId Optional request ID (auto-generated if not provided)
     * @return JsonResponse
     */
    public static function error(
        string $code,
        string $message,
        mixed $details = [],
        int $statusCode = 400,
        ?string $requestId = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'details' => $details
            ],
            'message' => $message,
            'request_id' => $requestId ?? Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], $statusCode);
    }

    /**
     * Format a validation error response
     *
     * @param array $errors Validation errors (typically from validator->errors())
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'VALIDATION_ERROR',
            $message,
            $errors,
            422,
            $requestId
        );
    }

    /**
     * Format an unauthorized error response
     *
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function unauthorized(
        string $message = 'Unauthorized',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'UNAUTHORIZED',
            $message,
            [],
            401,
            $requestId
        );
    }

    /**
     * Format a forbidden error response
     *
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function forbidden(
        string $message = 'Forbidden',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'FORBIDDEN',
            $message,
            [],
            403,
            $requestId
        );
    }

    /**
     * Format a not found error response
     *
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function notFound(
        string $message = 'Resource not found',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'NOT_FOUND',
            $message,
            [],
            404,
            $requestId
        );
    }

    /**
     * Format a payment required error response (subscription issues)
     *
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function paymentRequired(
        string $message = 'Payment required',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'PAYMENT_REQUIRED',
            $message,
            [],
            402,
            $requestId
        );
    }

    /**
     * Format a rate limit exceeded error response
     *
     * @param int $retryAfter Seconds until rate limit resets
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function rateLimitExceeded(
        int $retryAfter,
        string $message = 'Rate limit exceeded',
        ?string $requestId = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'details' => [
                    'retry_after' => $retryAfter
                ]
            ],
            'message' => $message,
            'request_id' => $requestId ?? Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Format an internal server error response
     *
     * @param string $message Error message
     * @param string|null $requestId Optional request ID
     * @return JsonResponse
     */
    public static function serverError(
        string $message = 'Internal server error',
        ?string $requestId = null
    ): JsonResponse {
        return self::error(
            'INTERNAL_SERVER_ERROR',
            $message,
            [],
            500,
            $requestId
        );
    }
}
