<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Department;
use App\Models\Tenant\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
            $query = Department::with(['parent']);

            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
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
            $department = Department::with(['parent'])->findOrFail($id);

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
            'dept_code' => 'required|string|max:50|unique:tenant.department_master,dept_code',
            'dept_name' => 'required|string|max:100',
            'parent_dept_id' => 'nullable|integer|exists:tenant.department_master,id',
            'role_id' => 'required|integer|exists:tenant.role_master,id',
            'cost_center_code' => 'nullable|string|max:50',
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
            // Create department
            $department = Department::create([
                'dept_code' => $request->input('dept_code'),
                'dept_name' => $request->input('dept_name'),
                'parent_dept_id' => $request->input('parent_dept_id'),
                'cost_center_code' => $request->input('cost_center_code'),
                'is_active' => true,
                'created_by' => $request->input('auth_user_id'),
            ]);

            // Insert department-role mapping
            DB::connection('tenant')->table('dept_role_map')->insert([
                'dept_id' => $department->id,
                'role_id' => $request->input('role_id'),
                'created_by' => $request->input('auth_user_id'),
                'created_at' => now(),
            ]);

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
            'role_id' => 'sometimes|integer|exists:tenant.role_master,id',
            'cost_center_code' => 'nullable|string|max:50',
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

            // Prevent editing of root department
            if ($department->dept_code === 'ROOT') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ROOT_DEPARTMENT_EDIT_FORBIDDEN',
                        'details' => []
                    ],
                    'message' => 'Root department cannot be edited',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

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

            // Update role mapping if role_id is provided
            if ($request->has('role_id')) {
                // Delete existing role mapping
                DB::connection('tenant')->table('dept_role_map')
                    ->where('dept_id', $id)
                    ->delete();

                // Insert new role mapping
                DB::connection('tenant')->table('dept_role_map')->insert([
                    'dept_id' => $id,
                    'role_id' => $request->input('role_id'),
                    'created_by' => $request->input('auth_user_id'),
                    'created_at' => now(),
                ]);
            }

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
     * Deactivate department
     * DELETE /api/v1/departments/{id}
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::findOrFail($id);

            // Prevent deactivation of root department
            if ($department->dept_code === 'ROOT') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ROOT_DEPARTMENT_DEACTIVATE_FORBIDDEN',
                        'details' => []
                    ],
                    'message' => 'Root department cannot be deactivated',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

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
                    'code' => 'DEPARTMENT_DEACTIVATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to deactivate department: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get department roles
     * GET /api/v1/departments/{id}/roles
     */
    public function roles(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $department = Department::findOrFail($id);
            
            // Get roles mapped to this department
            $roles = DB::connection('tenant')
                ->table('dept_role_map')
                ->join('role_master', 'dept_role_map.role_id', '=', 'role_master.id')
                ->where('dept_role_map.dept_id', $id)
                ->where('role_master.is_active', true)
                ->select(
                    'role_master.id as role_id',
                    'role_master.role_code',
                    'role_master.role_name',
                    'role_master.description'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles
                ],
                'message' => 'Department roles retrieved successfully',
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
                'message' => 'Failed to retrieve department roles: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    /**
     * Generate unique department code from department name
     */
    private function generateDeptCode(string $deptName): string
    {
        // Convert to uppercase and remove special characters
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $deptName));
        
        // Take first 10 characters or less
        $baseCode = substr($baseCode, 0, 10);
        
        // Check if code exists
        $code = $baseCode;
        $counter = 1;
        
        while (Department::where('dept_code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Download CSV template for department import
     * GET /api/v1/departments/import/template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="departments_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers (dept_code removed - will be auto-generated)
            fputcsv($file, [
                'dept_name',
                'parent_dept_code',
                'role_code',
                'cost_center_code',
                'is_active'
            ]);
            
            // Sample data
            fputcsv($file, [
                'Sales Department',
                '',
                'MANAGER',
                'CC-001',
                'true'
            ]);
            
            fputcsv($file, [
                'Marketing Department',
                '',
                'ADMIN',
                'CC-002',
                'true'
            ]);
            
            fputcsv($file, [
                'IT Department',
                '',
                'USER',
                'CC-003',
                'true'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import departments from CSV
     * POST /api/v1/departments/import
     */
    public function importCSV(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Invalid file upload',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $fileContent = file_get_contents($file->getRealPath());
            
            // Handle UTF-8 encoding
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            }
            
            // Remove BOM if present
            $fileContent = preg_replace('/^\x{FEFF}/u', '', $fileContent);
            
            // Parse CSV
            $rows = array_map('str_getcsv', explode("\n", $fileContent));
            $header = array_shift($rows);
            
            if (empty($header)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CSV',
                        'details' => []
                    ],
                    'message' => 'CSV file is empty or invalid',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            $imported = 0;
            $errors = [];
            $rowNumber = 1; // Start from 1 (header is row 0)

            DB::beginTransaction();

            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map row to associative array
                if (count($row) !== count($header)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch";
                    continue;
                }
                
                $data = array_combine($header, $row);

                // Validate required fields (only dept_name is required now)
                if (empty($data['dept_name'])) {
                    $errors[] = "Row {$rowNumber}: dept_name is required";
                    continue;
                }

                // Check for duplicate dept_name
                $existingDept = Department::where('dept_name', trim($data['dept_name']))->first();
                if ($existingDept) {
                    $errors[] = "Row {$rowNumber}: Department name '{$data['dept_name']}' already exists";
                    continue;
                }

                // Auto-generate dept_code from dept_name
                $deptCode = $this->generateDeptCode($data['dept_name']);

                // Resolve parent_dept_code to parent_dept_id
                $parentDeptId = null;
                if (!empty($data['parent_dept_code'])) {
                    $parentDept = Department::where('dept_code', trim($data['parent_dept_code']))->first();
                    if (!$parentDept) {
                        $errors[] = "Row {$rowNumber}: Parent department code '{$data['parent_dept_code']}' not found";
                        continue;
                    }
                    $parentDeptId = $parentDept->id;
                }

                // Resolve role_code to role_id
                $roleId = null;
                if (!empty($data['role_code'])) {
                    $role = Role::where('role_code', trim($data['role_code']))->where('is_active', true)->first();
                    if (!$role) {
                        $errors[] = "Row {$rowNumber}: Role code '{$data['role_code']}' not found or inactive";
                        continue;
                    }
                    $roleId = $role->id;
                } else {
                    $errors[] = "Row {$rowNumber}: role_code is required";
                    continue;
                }

                // Parse is_active
                $isActive = true;
                if (isset($data['is_active'])) {
                    $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
                }

                try {
                    // Create department
                    $department = Department::create([
                        'dept_code' => $deptCode,
                        'dept_name' => trim($data['dept_name']),
                        'parent_dept_id' => $parentDeptId,
                        'cost_center_code' => isset($data['cost_center_code']) ? trim($data['cost_center_code']) : null,
                        'is_active' => $isActive,
                        'created_by' => $request->input('auth_user_id'),
                    ]);

                    // Insert department-role mapping
                    DB::connection('tenant')->table('dept_role_map')->insert([
                        'dept_id' => $department->id,
                        'role_id' => $roleId,
                        'created_by' => $request->input('auth_user_id'),
                        'created_at' => now(),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Successfully imported {$imported} department(s)";
            if (!empty($errors)) {
                $message .= " with " . count($errors) . " error(s)";
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'errors' => $errors
                ],
                'message' => $message,
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'IMPORT_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to import departments: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
