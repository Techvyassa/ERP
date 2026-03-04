<?php

namespace App\Http\Controllers;

use App\Contracts\AuthenticationService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authService,
        private TokenService $tokenService
    ) {}

    /**
     * User login
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'org_slug' => 'nullable|string', // Made optional
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password'),
                $request->input('org_slug') // Can be null
            );

            \Log::info('Login successful', [
                'user_id' => $result->user->user_id,
                'org_slug' => $result->organization->org_slug,
                'token_length' => strlen($result->accessToken),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $result->accessToken,
                    'refresh_token' => $result->refreshToken,
                    'expires_in' => $result->expiresIn,
                    'token_type' => 'Bearer',
                    'user' => [
                        'user_id' => $result->user->user_id,
                        'email' => $result->user->email,
                        'employee_code' => $result->user->employee_code,
                        'first_name' => $result->user->first_name,
                        'last_name' => $result->user->last_name,
                        'role_id' => $result->user->role_id,
                        'dept_id' => $result->user->dept_id,
                    ],
                    'organization' => [
                        'org_id' => $result->organization->org_id,
                        'org_slug' => $result->organization->org_slug,
                        'org_name' => $result->organization->org_name,
                    ]
                ],
                'message' => 'Login successful',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200)
            ->cookie(
                'auth_token',
                $result->accessToken,
                60 * 24, // 24 hours in minutes
                '/', // path
                null, // domain
                request()->secure(), // secure
                true, // httpOnly
                false, // raw
                'lax' // sameSite
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_FAILED',
                    'details' => []
                ],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 401);
        }
    }

    /**
     * Refresh access token
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $tokens = $this->tokenService->refreshAccessToken(
                $request->input('refresh_token')
            );

            return response()->json([
                'success' => true,
                'data' => $tokens,
                'message' => 'Token refreshed successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200)
            ->cookie(
                'auth_token',
                $tokens['access_token'],
                60 * 24, // 24 hours
                '/',
                null,
                request()->secure(),
                true,
                false,
                'lax'
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'details' => []
                ],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 401);
        }
    }

    /**
     * User logout
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $this->tokenService->revokeRefreshToken(
                $request->input('refresh_token')
            );

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Logout successful',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200)
            ->cookie(cookie()->forget('auth_token'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LOGOUT_FAILED',
                    'details' => []
                ],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 400);
        }
    }
}
