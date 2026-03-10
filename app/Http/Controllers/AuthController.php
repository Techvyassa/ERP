<?php

namespace App\Http\Controllers;

use App\Contracts\AuthenticationService;
use App\Services\TokenService;
use App\Models\Tenant\User;
use App\Models\Tenant\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authService,
        private TokenService $tokenService
    ) {}

    /**
     * Dashboard route per role_code.
     * Frontend uses this value to redirect to the correct department dashboard.
     */
    private const DASHBOARD_ROUTES = [
        'PROC_EXE'       => '/dashboard/procurement',
        'PROC_MGR'       => '/dashboard/procurement',
        'SECURITY_GUARD' => '/dashboard/security',
        'SECURITY_SUPVR' => '/dashboard/security',
        'STOREKEEPER'    => '/dashboard/warehouse',
        'STORE_MGR'      => '/dashboard/warehouse',
        'QC_TECH'        => '/dashboard/quality',
        'QC_MGR'         => '/dashboard/quality',
        'AP_CLERK'       => '/dashboard/finance',
        'FIN_MGR'        => '/dashboard/finance',
        'CFO'            => '/dashboard/finance',
        'PPC_USER'       => '/dashboard/ppc',
        'ADMIN'          => '/dashboard/admin',
    ];

    /**
     * Build the enriched user payload (includes dept, role, dashboard_route).
     * Shared by login() and me().
     */
    private function buildUserPayload(User $user): array
    {
        $user->loadMissing(['department', 'role']);

        $roleCode = optional($user->role)->role_code;

        return [
            'user_id'         => $user->id,
            'email'           => $user->email,
            'employee_code'   => $user->employee_code,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'phone'           => $user->phone,
            // Department context
            'dept_id'         => $user->dept_id,
            'dept_code'       => optional($user->department)->dept_code,
            'dept_name'       => optional($user->department)->dept_name,
            // Role context
            'role_id'         => $user->role_id,
            'role_code'       => $roleCode,
            'role_name'       => optional($user->role)->role_name,
            // Dashboard routing
            'dashboard_route' => self::DASHBOARD_ROUTES[$roleCode] ?? '/dashboard',
        ];
    }

    /**
     * Load all role permissions for a user, keyed by module_code.
     * Result is cached for 15 minutes (same key as CheckModulePermission middleware).
     */
    private function loadPermissions(User $user): array
    {
        $cacheKey = "rbac:user:{$user->id}:permissions";

        return Cache::remember($cacheKey, 900, function () use ($user) {
            $perms = RolePermission::where('role_id', $user->role_id)->get();

            $result = [];
            foreach ($perms as $p) {
                $result[$p->module_code] = [
                    'can_view'    => (bool) $p->can_view,
                    'can_create'  => (bool) $p->can_create,
                    'can_edit'    => (bool) $p->can_edit,
                    'can_approve' => (bool) $p->can_approve,
                    'can_delete'  => (bool) $p->can_delete,
                ];
            }

            return $result;
        });
    }

    /**
     * User login
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
            'org_slug' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message'    => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 422);
        }

        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password'),
                $request->input('org_slug')
            );

            Log::info('Login successful', [
                'user_id'      => $result->user->id,
                'org_slug'     => $result->organization->org_slug,
                'token_length' => strlen($result->accessToken),
            ]);

            $userPayload = $this->buildUserPayload($result->user);

            return response()->json([
                'success' => true,
                'data'    => [
                    'access_token'  => $result->accessToken,
                    'refresh_token' => $result->refreshToken,
                    'expires_in'    => $result->expiresIn,
                    'token_type'    => 'Bearer',
                    'user'          => $userPayload,
                    'organization'  => [
                        'org_id'   => $result->organization->org_id,
                        'org_slug' => $result->organization->org_slug,
                        'org_name' => $result->organization->org_name,
                    ],
                ],
                'message'    => 'Login successful',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 200)
            ->cookie(
                'auth_token',
                $result->accessToken,
                60 * 24,
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
                'error'   => [
                    'code'    => 'AUTHENTICATION_FAILED',
                    'details' => []
                ],
                'message'    => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 401);
        }
    }

    /**
     * Get current authenticated user profile + permissions
     * GET /api/v1/auth/me
     *
     * Returns the same user payload as login plus the full permissions map
     * so the frontend can show/hide actions without extra round-trips.
     */
    public function me(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'error'   => ['code' => 'UNAUTHENTICATED', 'details' => []],
                    'message' => 'Authentication required',
                    'request_id' => $requestId,
                    'timestamp'  => now()->toIso8601String()
                ], 401);
            }

            $user = User::with(['department', 'role'])->findOrFail($userId);

            $userPayload  = $this->buildUserPayload($user);
            $permissions  = $this->loadPermissions($user);

            return response()->json([
                'success' => true,
                'data'    => [
                    'user'            => $userPayload,
                    'permissions'     => $permissions,
                    'dashboard_route' => $userPayload['dashboard_route'],
                ],
                'message'    => 'User profile retrieved',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'USER_NOT_FOUND', 'details' => []],
                'message' => 'User not found',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 404);
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
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message'    => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 422);
        }

        try {
            $tokens = $this->tokenService->refreshAccessToken(
                $request->input('refresh_token')
            );

            return response()->json([
                'success' => true,
                'data'    => $tokens,
                'message' => 'Token refreshed successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 200)
            ->cookie(
                'auth_token',
                $tokens['access_token'],
                60 * 24,
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
                'error'   => ['code' => 'TOKEN_REFRESH_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
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
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message'    => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 422);
        }

        try {
            $this->tokenService->revokeRefreshToken(
                $request->input('refresh_token')
            );

            // Also clear the RBAC cache for this user if user_id is known
            $userId = $request->input('auth_user_id');
            if ($userId) {
                Cache::forget("rbac:user:{$userId}:permissions");
            }

            return response()->json([
                'success' => true,
                'data'    => [],
                'message' => 'Logout successful',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 200)
            ->cookie(cookie()->forget('auth_token'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'LOGOUT_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String()
            ], 400);
        }
    }
}
