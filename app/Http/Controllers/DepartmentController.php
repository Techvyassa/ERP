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
     * Download CSV template for department import
     * GET /api/v1/departments/import/template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = [
            'dept_code',
            'dept_name',
            'parent_dept_id',
            'role_id',
            'cost_center_code',
            'is_active'
        ];

        // Get a sample role ID from existing roles
        $sampleRoleId = Role::where('is_active', true)->first()?->id ?? '1';

        $sampleData = [
            'SALES',
            'Sales Department',
            '',
            $sampleRoleId,
            'CC-001',
            'true'
        ];

        $csv = implode(',', $headers) . "\n" . implode(',', $sampleData);

        $fileName = 'department_import_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'department_template');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import departments from CSV
     * POST /api/v1/departments/import
     */
    public function importCSV(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        \Log::info('CSV Import Started', [
            'request_id' => $requestId,
            'user_id' => $request->input('auth_user_id'),
            'has_file' => $request->hasFile('file')
        ]);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            \Log::error('CSV Import Validation Failed', [
                'request_id' => $requestId,
                'errors' => $validator->errors()->toArray()
            ]);
            
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
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            
            \Log::info('CSV Import Debug', [
                'request_id' => $requestId,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'csv_rows' => count($csvData),
                'first_few_rows' => array_slice($csvData, 0, 3)
            ]);

            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'EMPTY_FILE', 'details' => []],
                    'message' => 'CSV file is empty',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            $headers = array_shift($csvData); // Remove header row
            $results = [
                'total_rows' => count($csvData),
                'successful' => 0,
                'failed' => 0,
                'errors' => []
            ];

            \Log::info('CSV Processing Started', [
                'request_id' => $requestId,
                'total_rows' => $results['total_rows'],
                'headers' => $headers
            ]);

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                try {
                    // Map CSV columns to department data
                    $departmentData = [
                        'dept_code' => $row[0] ?? '',
                        'dept_name' => $row[1] ?? '',
                        'parent_dept_id' => !empty($row[2]) ? (int)$row[2] : null,
                        'role_id' => !empty($row[3]) ? (int)$row[3] : null,
                        'cost_center_code' => $row[4] ?? '',
                        'is_active' => !empty($row[5]) ? filter_var($row[5], FILTER_VALIDATE_BOOLEAN) : true,
                        'created_by' => $request->input('auth_user_id'),
                    ];

                    \Log::info('Processing Row', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
                        'raw_data' => $row,
                        'mapped_data' => $departmentData
                    ]);

                    // Validate individual row
                    $rowValidator = Validator::make($departmentData, [
                        'dept_code' => 'required|string|max:50|unique:tenant.department_master,dept_code',
                        'dept_name' => 'required|string|max:100',
                        'parent_dept_id' => 'nullable|integer|exists:tenant.department_master,id',
                        'role_id' => 'required|integer|exists:tenant.role_master,id',
                        'cost_center_code' => 'nullable|string|max:50',
                    ]);

                    \Log::info('Row validation', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
                        'data' => $departmentData,
                        'validation_passed' => !$rowValidator->fails(),
                        'errors' => $rowValidator->errors()->toArray()
                    ]);

                    if ($rowValidator->fails()) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'errors' => $rowValidator->errors()->all()
                        ];
                        continue;
                    }

                    // Create department
                    $department = Department::create([
                        'dept_code' => $departmentData['dept_code'],
                        'dept_name' => $departmentData['dept_name'],
                        'parent_dept_id' => $departmentData['parent_dept_id'],
                        'cost_center_code' => $departmentData['cost_center_code'],
                        'is_active' => $departmentData['is_active'],
                        'created_by' => $departmentData['created_by'],
                    ]);

                    \Log::info('Department created', [
                        'request_id' => $requestId,
                        'department_id' => $department->id,
                        'dept_code' => $department->dept_code,
                        'dept_name' => $department->dept_name
                    ]);

                    // Insert department-role mapping
                    DB::connection('tenant')->table('dept_role_map')->insert([
                        'dept_id' => $department->id,
                        'role_id' => $departmentData['role_id'],
                        'created_by' => $departmentData['created_by'],
                        'created_at' => now(),
                    ]);

                    \Log::info('Department-role mapping created', [
                        'request_id' => $requestId,
                        'dept_id' => $department->id,
                        'role_id' => $departmentData['role_id']
                    ]);

                    $results['successful']++;

                } catch (\Exception $e) {
                    \Log::error('Department creation failed', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
                        'data' => $departmentData ?? [],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => ['Failed to create department: ' . $e->getMessage()]
                    ];
                }
            }

            DB::commit();

            \Log::info('CSV Import completed', [
                'request_id' => $requestId,
                'total_rows' => $results['total_rows'],
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'errors_count' => count($results['errors'])
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => "Import completed. {$results['successful']} departments created, {$results['failed']} failed.",
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('CSV Import Exception', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'IMPORT_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to import CSV: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
