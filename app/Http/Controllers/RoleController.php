<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * List roles
     * GET /api/v1/roles
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Role::with(['permissions']);

            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('role_name', 'like', "%{$search}%")
                        ->orWhere('role_code', 'like', "%{$search}%");
                });
            }

            $roles = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles
                ],
                'message' => 'Roles retrieved successfully',
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
                'message' => 'Failed to retrieve roles: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single role
     * GET /api/v1/roles/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $role = Role::with(['permissions'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role
                ],
                'message' => 'Role retrieved successfully',
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

    /**
     * Create role
     * POST /api/v1/roles
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'role_code' => 'required|string|max:50|unique:tenant.role_master,role_code',
            'role_name' => 'required|string|max:100',
            'description' => 'nullable|string',
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
            // Create role
            $role = Role::create([
                'role_code' => $request->input('role_code'),
                'role_name' => $request->input('role_name'),
                'description' => $request->input('description'),
                'is_active' => true,
                'is_system_role' => false,
                'created_by' => $request->input('auth_user_id'),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role
                ],
                'message' => 'Role created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ROLE_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create role: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update role
     * PUT /api/v1/roles/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'role_code' => 'sometimes|string|max:50|unique:tenant.role_master,role_code,' . $id . ',id',
            'role_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
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
            $role = Role::findOrFail($id);

            // Update fields
            if ($request->has('role_code')) {
                $role->role_code = $request->input('role_code');
            }
            if ($request->has('role_name')) {
                $role->role_name = $request->input('role_name');
            }
            if ($request->has('description')) {
                $role->description = $request->input('description');
            }
            if ($request->has('is_active')) {
                $role->is_active = $request->input('is_active');
            }

            $role->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role
                ],
                'message' => 'Role updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ROLE_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update role: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete role
     * DELETE /api/v1/roles/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $role = Role::findOrFail($id);

            // Prevent deletion of system roles
            if ($role->is_system_role) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SYSTEM_ROLE_DELETE_FORBIDDEN',
                        'details' => []
                    ],
                    'message' => 'System roles cannot be deleted',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Role deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            // Check if it's a system role deletion error
            if (str_contains($e->getMessage(), 'System roles cannot be deleted')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SYSTEM_ROLE_DELETE_FORBIDDEN',
                        'details' => []
                    ],
                    'message' => 'System roles cannot be deleted',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ROLE_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete role: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Generate barcode for role
     * GET /api/v1/roles/{id}/barcode
     */

    /**
     * Generate Code128 barcode HTML
     */
}
