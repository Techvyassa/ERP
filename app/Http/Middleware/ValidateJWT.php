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
 * Attempts silent refresh when token is expired but refresh token is available
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
            // Try to refresh the token using the refresh token from cookie or header
            $refreshToken = $request->input('refresh_token') ?? $request->cookie('refresh_token');
            
            if ($refreshToken) {
                try {
                    $tokens = $this->refreshToken($refreshToken, $request);
                    
                    // Re-parse the new token to get claims
                    JWTAuth::setToken($tokens['access_token']);
                    $payload = JWTAuth::getPayload();

                    $userId = $payload->get('sub');
                    $orgId = $payload->get('org_id');

                    if (!$userId || !$orgId) {
                        return $this->errorResponse('Invalid token claims', 'INVALID_TOKEN_CLAIMS', 401);
                    }

                    $request->merge([
                        'auth_user_id' => $userId,
                        'auth_org_id' => $orgId,
                    ]);

                    // Continue the request and attach the refreshed tokens
                    $response = $next($request);
                    
                    // Attach new tokens to response
                    return $this->attachTokensToResponse($response, $tokens, $request);
                } catch (\Exception $refreshEx) {
                    \Log::warning('Token refresh failed in ValidateJWT', ['error' => $refreshEx->getMessage()]);
                    return $this->errorResponse('Token expired and refresh failed', 'TOKEN_EXPIRED', 401);
                }
            }
            
            return $this->errorResponse('Token expired', 'TOKEN_EXPIRED', 401);
        } catch (TokenInvalidException $e) {
            return $this->errorResponse('Invalid token', 'TOKEN_INVALID', 401);
        } catch (JWTException $e) {
            return $this->errorResponse('Token required', 'TOKEN_REQUIRED', 401);
        }

        return $next($request);
    }

    /**
     * Refresh access token
     */
    private function refreshToken(string $refreshToken, Request $request): array
    {
        $tokenService = app(\App\Services\TokenService::class);
        $tokens = $tokenService->refreshAccessToken($refreshToken);
        
        return $tokens;
    }

    /**
     * Attach tokens to response as cookies
     */
    private function attachTokensToResponse($response, array $tokens, Request $request): Response
    {
        $response = response()->json([
            'success' => true,
            'data' => [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'expires_in' => $tokens['expires_in'],
                'token_type' => 'Bearer',
            ],
            'message' => 'Token refreshed',
            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], 200);

        // Set access token cookie (24 hours)
        $response->cookie(
            'auth_token',
            $tokens['access_token'],
            60 * 24,
            '/',
            null,
            $request->secure(),
            true,
            false,
            'lax'
        );

        // Set refresh token cookie if available (30 days)
        if (!empty($tokens['refresh_token'])) {
            $response->cookie(
                'refresh_token',
                $tokens['refresh_token'],
                60 * 24 * 30,
                '/',
                null,
                $request->secure(),
                true,
                false,
                'lax'
            );
        }

        return $response;
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
