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
 * Web JWT Authentication Middleware
 * 
 * Validates JWT token from Authorization header or cookie for web routes
 * Redirects to login page if token is invalid or missing
 */
class WebJWTAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Try to get token from Authorization header first
            $token = $request->bearerToken();

            // If not in header, try cookie
            if (!$token) {
                $token = $request->cookie('auth_token');
            }

            // Debug logging
            \Log::info('WebJWTAuth Debug', [
                'has_bearer_token' => !empty($request->bearerToken()),
                'has_cookie_token' => !empty($request->cookie('auth_token')),
                'cookie_value' => $request->cookie('auth_token') ? substr($request->cookie('auth_token'), 0, 20) . '...' : null,
                'all_cookies' => array_keys($request->cookies->all()),
            ]);

            if (!$token) {
                \Log::warning('No authentication token found', [
                    'url' => $request->fullUrl(),
                    'cookies' => array_keys($request->cookies->all()),
                ]);
                return $this->redirectToLogin($request, 'No authentication token found');
            }

            // Set token for JWT parsing
            JWTAuth::setToken($token);

            // Parse and validate JWT token
            $payload = JWTAuth::getPayload();

            // Extract user_id and org_id from token claims
            $userId = $payload->get('sub');
            $orgId = $payload->get('org_id');

            if (!$userId || !$orgId) {
                return $this->redirectToLogin($request, 'Invalid token claims');
            }

            // Attach user_id and org_id to request
            $request->merge([
                'auth_user_id' => $userId,
                'auth_org_id' => $orgId,
            ]);
        } catch (TokenExpiredException $e) {
            // Attempt a silent token refresh within the refresh window
            try {
                $newToken = JWTAuth::setToken($token)->refresh();

                // Re-parse the new token to get claims
                JWTAuth::setToken($newToken);
                $payload = JWTAuth::getPayload();

                $userId = $payload->get('sub');
                $orgId  = $payload->get('org_id');

                if (!$userId || !$orgId) {
                    return $this->redirectToLogin($request, 'Invalid token claims');
                }

                $request->merge([
                    'auth_user_id' => $userId,
                    'auth_org_id'  => $orgId,
                ]);

                // Continue the request and attach the refreshed token as a new cookie
                $response = $next($request);

                return $response->cookie(
                    'auth_token',
                    $newToken,
                    60 * 24, // 24 hours
                    '/',
                    null,
                    $request->secure(),
                    true,
                    false,
                    'lax'
                );
            } catch (\Exception $refreshEx) {
                \Log::warning('Token refresh failed', ['error' => $refreshEx->getMessage()]);
                return $this->redirectToLogin($request, 'Your session has expired. Please login again.');
            }
        } catch (TokenInvalidException $e) {
            \Log::warning('Token invalid', ['error' => $e->getMessage()]);
            return $this->redirectToLogin($request, 'Invalid authentication token');
        } catch (JWTException $e) {
            \Log::error('JWT Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->redirectToLogin($request, 'Authentication failed');
        }

        return $next($request);
    }

    /**
     * Redirect to login with error message
     */
    private function redirectToLogin(Request $request, string $message): Response
    {
        // Try to identify if we are in a tenant context to provide a better redirect
        $orgSlug = $request->get('tenant_org_slug') ?? $request->route('org_slug');

        if ($orgSlug) {
            $path = $request->path(); // e.g., org/slug/quality/dashboard
            $segments = explode('/', $path);

            // Expected segments for tenant path: [0] => 'org', [1] => 'slug', [2] => 'department', ...
            if (isset($segments[2])) {
                $module = $segments[2];
                $loginRoute = "tenant.{$module}.login";

                if (\Route::has($loginRoute)) {
                    return redirect()->route($loginRoute, ['org_slug' => $orgSlug])
                        ->withCookie(cookie()->forget('auth_token'))
                        ->with('error', $message);
                }

                // Fallback to admin login in that org if module login not found
                if (\Route::has('tenant.admin.login')) {
                    return redirect()->route('tenant.admin.login', ['org_slug' => $orgSlug])
                        ->withCookie(cookie()->forget('auth_token'))
                        ->with('error', $message);
                }
            }
        }

        // Global fallback
        return redirect()->route('login')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('error', $message);
    }
}
