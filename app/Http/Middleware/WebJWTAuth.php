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
            
            if (!$token) {
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
            return $this->redirectToLogin($request, 'Your session has expired. Please login again.');
            
        } catch (TokenInvalidException $e) {
            return $this->redirectToLogin($request, 'Invalid authentication token');
            
        } catch (JWTException $e) {
            return $this->redirectToLogin($request, 'Authentication failed');
        }
        
        return $next($request);
    }
    
    /**
     * Redirect to login with error message
     */
    private function redirectToLogin(Request $request, string $message): Response
    {
        // Clear any existing auth cookies
        return redirect()->route('login')
            ->withCookie(cookie()->forget('auth_token'))
            ->with('error', $message);
    }
}
