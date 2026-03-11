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
    public function barcode(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $role = Role::findOrFail($id);

            $barcodeHtml = $this->bar128($role->role_code);

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'role_code' => $role->role_code,
                        'role_name' => $role->role_name,
                        'description' => $role->description,
                        'is_active' => $role->is_active,
                        'is_system_role' => $role->is_system_role,
                    ],
                    'barcode' => $barcodeHtml
                ],
                'message' => 'Barcode generated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BARCODE_GENERATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to generate barcode: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Generate Code128 barcode HTML
     */
    private function bar128($code)
    {
        $code = str_replace(' ', '', $code);
        $enc = '';
        $sum = 104;
        
        for ($i = 0; $i < strlen($code); $i++) {
            $c = ord($code[$i]);
            if ($c >= 32 && $c <= 126) {
                $enc .= chr($c);
                $sum += $c * ($i + 2);
            }
        }
        
        $check = ($sum % 103) + 32;
        if ($check == 32) $check = 92;
        $enc .= chr($check);
        $enc .= chr(211);
        
        $html = '<div style="font-family: monospace; padding: 10px; text-align: center; background: white;">';
        $html .= '<div style="margin-bottom: 5px; font-size: 12px;">' . htmlspecialchars($code) . '</div>';
        $html .= '<div style="letter-spacing: -1px; line-height: 1;">';
        
        for ($i = 0; $i < strlen($enc); $i++) {
            $c = ord($enc[$i]);
            if ($c == 211) {
                $html .= '<span style="display: inline-block; width: 2px; height: 40px; background: black;"></span>';
            } else {
                $bar = '';
                for ($j = 0; $j < 11; $j++) {
                    if (($c >> (10 - $j)) & 1) {
                        $bar .= '1';
                    } else {
                        $bar .= '0';
                    }
                }
                $width = 0;
                for ($j = 0; $j < strlen($bar); $j++) {
                    if ($bar[$j] == '1') {
                        $width++;
                    } else {
                        if ($width > 0) {
                            $html .= '<span style="display: inline-block; width: ' . $width . 'px; height: 40px; background: black;"></span>';
                            $width = 0;
                        }
                        $html .= '<span style="display: inline-block; width: 1px; height: 40px;"></span>';
                    }
                }
                if ($width > 0) {
                    $html .= '<span style="display: inline-block; width: ' . $width . 'px; height: 40px; background: black;"></span>';
                }
            }
        }
        
        $html .= '</div>';
        $html .= '<div style="margin-top: 5px; font-size: 10px;">' . htmlspecialchars($code) . '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
