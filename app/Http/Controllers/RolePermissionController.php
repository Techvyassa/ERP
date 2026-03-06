<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use App\Contracts\RBACPermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    public function __construct(
        private RBACPermissionService $rbacService
    ) {}

    /**
     * Update role permissions
     * PUT /api/v1/roles/{id}/permissions
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*.module_code' => 'required|string|max:50',
            'permissions.*.can_view' => 'required|boolean',
            'permissions.*.can_create' => 'required|boolean',
            'permissions.*.can_edit' => 'required|boolean',
            'permissions.*.can_approve' => 'required|boolean',
            'permissions.*.can_delete' => 'required|boolean',
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
            $role = Role::findOrFail($id);

            DB::beginTransaction();

            // Update permissions
            foreach ($request->input('permissions') as $permissionData) {
                RolePermission::updateOrCreate(
                    [
                        'role_id' => $role->id,
                        'module_code' => $permissionData['module_code'],
                    ],
                    [
                        'can_view' => $permissionData['can_view'],
                        'can_create' => $permissionData['can_create'],
                        'can_edit' => $permissionData['can_edit'],
                        'can_approve' => $permissionData['can_approve'],
                        'can_delete' => $permissionData['can_delete'],
                        'created_by' => $request->attributes->get('user_id'),
                    ]
                );
            }

            // Invalidate permission cache for all users with this role
            $users = $role->users;
            foreach ($users as $user) {
                $this->rbacService->invalidateCache($user->id);
            }

            DB::commit();

            $role->load(['permissions']);

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role
                ],
                'message' => 'Role permissions updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PERMISSION_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update role permissions: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get role permissions
     * GET /api/v1/roles/{id}/permissions
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $role = Role::with(['permissions'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $role->id,
                    'role_code' => $role->role_code,
                    'role_name' => $role->role_name,
                    'permissions' => $role->permissions
                ],
                'message' => 'Role permissions retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ROLE_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Role not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }
}
