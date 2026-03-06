<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/**
 * Authentication Middleware - ValidateJWT
 * 
 * Validates JWT token signature and expiration
 * Extracts user_id and org_id from token claims
 * Returns 401 for invalid/expired tokens
 * 
 * Requirements: 10.1, 10.2, 10.6
 */
class ValidateJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // If token is not in header but in cookie, explicitly set it
            if (! $request->bearerToken() && $request->hasCookie('auth_token')) {
                JWTAuth::setToken($request->cookie('auth_token'));
            } else {
                // Otherwise let it parse from the expected Authorization header
                JWTAuth::parseToken();
            }

            // Parse and validate JWT token
            $payload = JWTAuth::getPayload();

            // Extract user_id and org_id from token claims
            $userId = $payload->get('sub'); // Subject claim contains user_id
            $orgId = $payload->get('org_id');

            if (!$userId || !$orgId) {
                return $this->errorResponse('Invalid token claims', 'INVALID_TOKEN_CLAIMS', 401);
            }

            // Attach user_id and org_id to request for downstream middleware
            $request->merge([
                'auth_user_id' => $userId,
                'auth_org_id' => $orgId,
            ]);
        } catch (TokenExpiredException $e) {
            return $this->errorResponse('Token expired', 'TOKEN_EXPIRED', 401);
        } catch (TokenInvalidException $e) {
            return $this->errorResponse('Invalid token', 'TOKEN_INVALID', 401);
        } catch (JWTException $e) {
            return $this->errorResponse('Token required', 'TOKEN_REQUIRED', 401);
        }

        return $next($request);
    }

    /**
     * Return consistent error response
     */
    private function errorResponse(string $message, string $code, int $status): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'details' => []
            ],
            'message' => $message,
            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], $status);
    }
}
