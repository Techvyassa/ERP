<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Department;
use App\Models\Tenant\DeptRoleMap;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    /**
     * List departments
     * GET /api/v1/departments
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Department::with(['parent', 'children']);

            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('parent_dept_id')) {
                $query->where('parent_dept_id', $request->input('parent_dept_id'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('dept_name', 'like', "%{$search}%")
                        ->orWhere('dept_code', 'like', "%{$search}%");
                });
            }

            $departments = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments
                ],
                'message' => 'Departments retrieved successfully',
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
                'message' => 'Failed to retrieve departments: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single department
     * GET /api/v1/departments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::with(['parent', 'children', 'users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department
                ],
                'message' => 'Department retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEPARTMENT_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Department not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create department
     * POST /api/v1/departments
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'dept_code' => 'required|string|max:50', // Allow existing departments for upsert 
            'dept_name' => 'required|string|max:100',
            'parent_dept_id' => 'nullable|integer|exists:tenant.department_master,id',
            'cost_center_code' => 'nullable|string|max:20',
            'role_code' => 'nullable|string|max:30', // Optional role mapping details
            'role_name' => 'nullable|string|max:100',
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
            // Validate parent_dept_id exists if provided
            if ($request->has('parent_dept_id') && $request->input('parent_dept_id')) {
                $parent = Department::find($request->input('parent_dept_id'));
                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_PARENT',
                            'details' => []
                        ],
                        'message' => 'Parent department not found',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 400);
                }
            }

            // Create or fetch department (Upsert mode to allow adding roles to existing)
            $department = Department::firstOrCreate(
                ['dept_code' => $request->input('dept_code')],
                [
                    'dept_name' => $request->input('dept_name'),
                    'parent_dept_id' => $request->input('parent_dept_id'),
                    'cost_center_code' => $request->input('cost_center_code'),
                    'is_active' => true,
                    'created_by' => $request->input('auth_user_id'),
                ]
            );

            // If role_code is provided, automatically create/fetch the role and map it to this department
            if ($request->has('role_code') && $request->input('role_code')) {
                $roleName = $request->input('role_name', $request->input('role_code')); // default to code
                $role = \App\Models\Tenant\Role::firstOrCreate(
                    ['role_code' => $request->input('role_code')],
                    [
                        'role_name' => $roleName,
                        'description' => $request->input('description'),
                        'is_active' => true,
                        'is_system_role' => false,
                        'created_by' => $request->input('auth_user_id'),
                    ]
                );

                \App\Models\Tenant\DeptRoleMap::firstOrCreate(
                    [
                        'dept_id' => $department->id,
                        'role_id' => $role->id,
                    ],
                    [
                        'created_by' => $request->input('auth_user_id')
                    ]
                );
            }

            $department->load(['parent']);

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department
                ],
                'message' => 'Department created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            // Check if it's a circular hierarchy error
            if (str_contains($e->getMessage(), 'Circular department hierarchy')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CIRCULAR_HIERARCHY',
                        'details' => []
                    ],
                    'message' => 'Circular department hierarchy detected',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEPARTMENT_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create department: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update department
     * PUT /api/v1/departments/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'dept_code' => 'sometimes|string|max:50|unique:tenant.department_master,dept_code,' . $id . ',id',
            'dept_name' => 'sometimes|string|max:100',
            'parent_dept_id' => 'nullable|integer|exists:tenant.department_master,id',
            'cost_center_code' => 'nullable|string|max:20',
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
            $department = Department::findOrFail($id);

            // Validate parent_dept_id if provided
            if ($request->has('parent_dept_id') && $request->input('parent_dept_id')) {
                $parent = Department::find($request->input('parent_dept_id'));
                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_PARENT',
                            'details' => []
                        ],
                        'message' => 'Parent department not found',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 400);
                }
            }

            // Update fields
            if ($request->has('dept_code')) {
                $department->dept_code = $request->input('dept_code');
            }
            if ($request->has('dept_name')) {
                $department->dept_name = $request->input('dept_name');
            }
            if ($request->has('parent_dept_id')) {
                $department->parent_dept_id = $request->input('parent_dept_id');
            }
            if ($request->has('cost_center_code')) {
                $department->cost_center_code = $request->input('cost_center_code');
            }
            if ($request->has('is_active')) {
                $department->is_active = $request->input('is_active');
            }

            $department->save();
            $department->load(['parent']);

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department
                ],
                'message' => 'Department updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            // Check if it's a circular hierarchy error
            if (str_contains($e->getMessage(), 'Circular department hierarchy')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CIRCULAR_HIERARCHY',
                        'details' => []
                    ],
                    'message' => 'Circular department hierarchy detected',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEPARTMENT_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update department: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Deactivate department (soft delete by setting is_active to false)
     * DELETE /api/v1/departments/{id}
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::findOrFail($id);
            $department->is_active = false;
            $department->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Department deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEPARTMENT_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to deactivate department: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get roles valid for a department (via dept_role_map).
     * Used by admin user-creation form to populate the Role dropdown.
     * GET /api/v1/departments/{id}/roles
     */
    public function roles(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::findOrFail($id);

            // Load roles mapped to this department through dept_role_map
            $roles = DeptRoleMap::with('role')
                ->where('dept_id', $id)
                ->get()
                ->map(function ($mapping) {
                    return [
                        'role_id'   => $mapping->role->id,
                        'role_code' => $mapping->role->role_code,
                        'role_name' => $mapping->role->role_name,
                        'description' => $mapping->role->description,
                        'is_active' => $mapping->role->is_active,
                    ];
                })
                ->filter(fn($r) => $r['is_active']) // only active roles
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => [
                        'dept_id'   => $department->id,
                        'dept_code' => $department->dept_code,
                        'dept_name' => $department->dept_name,
                    ],
                    'roles' => $roles,
                ],
                'message' => 'Roles retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve roles for department: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Generate barcode for department
     * GET /api/v1/departments/{id}/barcode
     */
    public function barcode(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::findOrFail($id);

            $barcodeHtml = $this->bar128($department->dept_code);

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => [
                        'id' => $department->id,
                        'dept_code' => $department->dept_code,
                        'dept_name' => $department->dept_name,
                        'description' => $department->description,
                        'is_active' => $department->is_active,
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
