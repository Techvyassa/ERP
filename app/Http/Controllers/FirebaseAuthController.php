<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class FirebaseAuthController extends Controller
{
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
            'has_token' => !empty($request->input('firebase_token'))
        ]);
        
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'email' => 'required|email',
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
            
            // Find organization by email
            $organization = Organization::where('primary_email', $email)->first();
            
            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ORGANIZATION_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => 'No organization found with this email. Please register first.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            // Check if organization is active
            if ($organization->registration_status !== 'ACTIVE') {
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

            // Find or create user in tenant database
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                // Extract name from display_name or email
                $displayName = $request->input('display_name', '');
                $nameParts = explode(' ', $displayName);
                $firstName = $nameParts[0] ?? explode('@', $email)[0];
                $lastName = implode(' ', array_slice($nameParts, 1)) ?: 'User';
                
                // Create new user
                $user = User::create([
                    'employee_code' => 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'email' => $email,
                    'password_hash' => Hash::make(Str::random(32)), // Random password for OAuth users
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => null,
                    'dept_id' => null,
                    'role_id' => null,
                    'is_active' => true,
                    'created_by' => null,
                ]);
            }

            // Update last login
            $user->updateLastLogin();

            // Generate JWT access token
            $accessTokenPayload = [
                'sub' => $user->user_id,
                'org_id' => $organization->org_id,
                'org_slug' => $organization->org_slug,
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60), // 24 hours
                'type' => 'access',
            ];
            $accessToken = JWTAuth::claims($accessTokenPayload)->fromUser($user);

            // Generate JWT refresh token
            $refreshTokenPayload = [
                'sub' => $user->user_id,
                'org_id' => $organization->org_id,
                'org_slug' => $organization->org_slug,
                'iat' => time(),
                'exp' => time() + (30 * 24 * 60 * 60), // 30 days
                'type' => 'refresh',
            ];
            $refreshToken = JWTAuth::claims($refreshTokenPayload)->fromUser($user);

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
                    ]
                ],
                'message' => 'Authentication successful',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
            
        } catch (\Exception $e) {
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
}
