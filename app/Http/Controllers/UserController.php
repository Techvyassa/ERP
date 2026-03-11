<?php

namespace App\Http\Controllers;

use App\Models\Tenant\User;
use App\Models\Tenant\DeptRoleMap;
use App\Models\Control\ActiveSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List users
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = User::with(['department', 'role']);

            // Apply filters
            if ($request->has('dept_id')) {
                $query->where('dept_id', $request->input('dept_id'));
            }

            if ($request->has('role_id')) {
                $query->where('role_id', $request->input('role_id'));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'last_page' => $users->lastPage(),
                    ]
                ],
                'message' => 'Users retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single user
     * GET /api/v1/users/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $user = User::with(['department', 'role'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ],
                'message' => 'User retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'User not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create user
     * POST /api/v1/users
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|max:50|unique:tenant.users,employee_code',
            'email' => 'required|email|max:255|unique:tenant.users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'dept_id' => 'nullable|integer|exists:tenant.department_master,id',
            'role_id' => 'nullable|integer|exists:tenant.role_master,id',
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
            // Check user capacity limit
            $orgId = $request->attributes->get('org_id');
            $activeSub = ActiveSubscription::find($orgId);

            if ($activeSub) {
                $activeUserCount = User::where('is_active', true)->count();
                $maxUsers = $activeSub->max_users;

                if ($activeUserCount >= $maxUsers) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'USER_LIMIT_REACHED',
                            'details' => [
                                'current_users' => $activeUserCount,
                                'max_users' => $maxUsers
                            ]
                        ],
                        'message' => 'User limit reached for your plan',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 403);
                }
            }

            // Validate role belongs to the selected department (via dept_role_map)
            $deptId = $request->input('dept_id');
            $roleId = $request->input('role_id');
            if ($deptId && $roleId) {
                if (!DeptRoleMap::isValidForDepartment((int) $deptId, (int) $roleId)) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code'    => 'ROLE_DEPT_MISMATCH',
                            'details' => [
                                'dept_id' => $deptId,
                                'role_id' => $roleId,
                                'hint'    => 'Use GET /api/v1/departments/{id}/roles to see valid roles for this department.',
                            ]
                        ],
                        'message' => 'The selected role is not valid for the chosen department.',
                        'request_id' => $requestId,
                        'timestamp'  => now()->toIso8601String()
                    ], 422);
                }
            }

            // Create user - use 'password' to trigger the setPasswordHashAttribute mutator
            $user = User::create([
                'employee_code' => $request->input('employee_code'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'phone' => $request->input('phone'),
                'dept_id' => $request->input('dept_id'),
                'role_id' => $request->input('role_id'),
                'is_active' => true,
                'created_by' => $request->input('auth_user_id'),
            ]);

            $user->load(['department', 'role']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ],
                'message' => 'User created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create user: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update user
     * PUT /api/v1/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'employee_code' => 'sometimes|string|max:50|unique:tenant.users,employee_code,' . $id . ',id',
            'email' => 'sometimes|email|max:255|unique:tenant.users,email,' . $id . ',id',
            'password' => 'sometimes|string|min:8',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'dept_id' => 'sometimes|nullable|integer|exists:tenant.department_master,id',
            'role_id' => 'sometimes|nullable|integer|exists:tenant.role_master,id',
            'is_active' => 'sometimes|boolean',
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
            $user = User::findOrFail($id);

            // Validate role belongs to the (new or existing) department via dept_role_map
            $deptId = $request->has('dept_id') ? $request->input('dept_id') : $user->dept_id;
            $roleId = $request->has('role_id') ? $request->input('role_id') : $user->role_id;
            if ($deptId && $roleId && ($request->has('dept_id') || $request->has('role_id'))) {
                if (!DeptRoleMap::isValidForDepartment((int) $deptId, (int) $roleId)) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code'    => 'ROLE_DEPT_MISMATCH',
                            'details' => [
                                'dept_id' => $deptId,
                                'role_id' => $roleId,
                                'hint'    => 'Use GET /api/v1/departments/{id}/roles to see valid roles for this department.',
                            ]
                        ],
                        'message' => 'The selected role is not valid for the chosen department.',
                        'request_id' => $requestId,
                        'timestamp'  => now()->toIso8601String()
                    ], 422);
                }
            }

            // Update fields
            if ($request->has('employee_code')) {
                $user->employee_code = $request->input('employee_code');
            }
            if ($request->has('email')) {
                $user->email = $request->input('email');
            }
            if ($request->has('password')) {
                $user->password = $request->input('password');
            }
            if ($request->has('first_name')) {
                $user->first_name = $request->input('first_name');
            }
            if ($request->has('last_name')) {
                $user->last_name = $request->input('last_name');
            }
            if ($request->has('phone')) {
                $user->phone = $request->input('phone');
            }
            if ($request->has('dept_id')) {
                $user->dept_id = $request->input('dept_id');
            }
            if ($request->has('role_id')) {
                $user->role_id = $request->input('role_id');
            }
            if ($request->has('is_active')) {
                $user->is_active = $request->input('is_active');
            }

            $user->save();
            $user->load(['department', 'role']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ],
                'message' => 'User updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update user: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * deactivate user (soft delete by setting is_active to false)
     * DELETE /api/v1/users/{id}
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $user = User::findOrFail($id);
            $user->is_active = false;
            $user->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'User deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to deactivate user: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Generate barcode for user
     * GET /api/v1/users/{id}/barcode
     */

    /**
     * Generate Code128 barcode HTML
     */
}
