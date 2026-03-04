<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class FirebaseAuthController extends Controller
{
    private const ACCESS_TOKEN_TTL = 1440; // 24 hours in minutes
    private const REFRESH_TOKEN_TTL = 43200; // 30 days in minutes
    private const CACHE_REFRESH_TOKEN_PREFIX = 'refresh_token:';

    /**
     * Handle Firebase authentication (Login)
     * POST /api/v1/auth/firebase-login
     */
    public function firebaseLogin(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        // Log the incoming request for debugging
        \Log::info('Firebase login attempt', [
            'email' => $request->input('email'),
            'provider' => $request->input('provider'),
            'org_slug' => $request->input('org_slug'),
            'has_token' => !empty($request->input('firebase_token'))
        ]);
        
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'email' => 'required|email',
            'org_slug' => 'nullable|string',
            'provider' => 'required|string|in:google,email',
            'display_name' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'firebase_uid' => 'nullable|string',
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
            $email = $request->input('email');
            $orgSlug = $request->input('org_slug');
            
            // Try to find organization by slug first, then by email
            if ($orgSlug) {
                $organization = Organization::where('org_slug', $orgSlug)->first();
            } else {
                // If no slug provided, try to find by primary email
                $organization = Organization::where('primary_email', $email)->first();
            }
            
            if (!$organization) {
                $message = $orgSlug 
                    ? 'Organization not found. Please check your organization URL.'
                    : 'No organization found with this email. Please register first or provide your organization slug.';
                    
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ORGANIZATION_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => $message,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            // Check if organization is active or pending (allow login during provisioning)
            if (!in_array($organization->registration_status, ['ACTIVE', 'PENDING'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ORGANIZATION_NOT_ACTIVE',
                        'details' => []
                    ],
                    'message' => 'Organization is not active. Status: ' . $organization->registration_status,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

            // Switch to tenant database
            config(['database.connections.tenant.database' => $organization->tenant_db_name]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Find user in tenant database
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => 'User not found in this organization. Please contact your administrator.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'USER_INACTIVE',
                        'details' => []
                    ],
                    'message' => 'Your account is inactive. Please contact your administrator.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

            // Update last login
            $user->updateLastLogin();

            // Generate JWT access token
            $accessToken = $this->generateAccessToken($user, $organization);
            
            // Generate refresh token (random string, not JWT)
            $refreshToken = $this->generateRefreshToken();
            
            // Store refresh token in Cache with user_id and org_id mapping
            $this->storeRefreshToken($refreshToken, $user->user_id, $organization->org_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_in' => 86400, // 24 hours in seconds
                    'token_type' => 'Bearer',
                    'user' => [
                        'user_id' => $user->user_id,
                        'email' => $user->email,
                        'employee_code' => $user->employee_code,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'role_id' => $user->role_id,
                        'dept_id' => $user->dept_id,
                        'photo_url' => $request->input('photo_url'),
                        'provider' => $request->input('provider'),
                    ],
                    'organization' => [
                        'org_id' => $organization->org_id,
                        'org_slug' => $organization->org_slug,
                        'org_name' => $organization->org_name,
                        'organization_url' => url('/' . $organization->org_slug),
                    ]
                ],
                'message' => 'Authentication successful',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200)
            ->cookie(
                'auth_token',
                $accessToken,
                60 * 24, // 24 hours in minutes
                '/', // path
                null, // domain
                false, // secure (false for localhost, true for production)
                true, // httpOnly
                false, // raw
                'lax' // sameSite
            );
            
        } catch (\Exception $e) {
            \Log::error('Firebase authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_FAILED',
                    'details' => []
                ],
                'message' => 'Firebase authentication failed: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 401);
        }
    }

    /**
     * Generate JWT access token
     * 
     * @param User $user
     * @param Organization $organization
     * @return string JWT token
     */
    private function generateAccessToken(User $user, Organization $organization): string
    {
        $customClaims = [
            'sub' => $user->user_id,
            'org_id' => $organization->org_id,
            'org_slug' => $organization->org_slug,
            'type' => 'access'
        ];
        
        $payload = JWTFactory::customClaims($customClaims)->make();
        
        // Set TTL to 24 hours
        return JWTAuth::manager()->encode($payload)->get();
    }
    
    /**
     * Generate refresh token (random string)
     * 
     * @return string Refresh token
     */
    private function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Store refresh token in Cache
     * 
     * @param string $refreshToken
     * @param int $userId
     * @param int $orgId
     * @return void
     */
    private function storeRefreshToken(string $refreshToken, int $userId, int $orgId): void
    {
        $key = self::CACHE_REFRESH_TOKEN_PREFIX . $refreshToken;
        $data = [
            'user_id' => $userId,
            'org_id' => $orgId,
            'created_at' => time()
        ];
        
        // Store with 30-day expiration
        Cache::put($key, $data, self::REFRESH_TOKEN_TTL * 60);
    }
}
