<?php

namespace App\Http\Controllers;

use App\Models\Tenant\User;
use App\Models\Tenant\Department;
use App\Models\Tenant\Role;
use App\Models\Tenant\DeptRoleMap;
use App\Models\Control\ActiveSubscription;
use App\Models\Control\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $orgId = $request->input('tenant_org_id');
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

            // Generate employee code if not provided
            $employeeCode = $request->input('employee_code');
            if (empty($employeeCode)) {
                $deptName = '';
                if ($deptId) {
                    $dept = Department::find($deptId);
                    $deptName = $dept?->dept_name ?? $dept?->dept_code ?? '';
                }
                $employeeCode = generateEmployeeCode(
                    $deptName,
                    $request->input('first_name'),
                    $request->input('last_name')
                );
            }

            // Create user - use 'password' to trigger the setPasswordHashAttribute mutator
            $user = User::create([
                'employee_code' => $employeeCode,
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

            // Send welcome email with credentials and department-specific login URL
            try {
                $org = $request->input('tenant_organization') ?? Organization::find($orgId);
                if ($org) {
                    $user->load('department');
                    $deptName = strtolower($user->department?->dept_name ?? $user->department?->dept_code ?? '');
                    $portal = 'admin';
                    if (str_contains($deptName, 'procurement') || str_contains($deptName, 'purchase')) $portal = 'procurement';
                    elseif (str_contains($deptName, 'warehouse') || str_contains($deptName, 'store')) $portal = 'warehouse';
                    elseif (str_contains($deptName, 'quality') || str_contains($deptName, 'qc')) $portal = 'quality';
                    elseif (str_contains($deptName, 'security') || str_contains($deptName, 'guard')) $portal = 'security';

                    $loginUrl = config('app.url') . '/org/' . $org->org_slug . '/' . $portal . '/login';
                    Mail::to($user->email)->send(
                        new \App\Mail\UserWelcomeEmail(
                            $org,
                            $user->first_name,
                            $user->email,
                            $request->input('password'),
                            $loginUrl
                        )
                    );
                }
            } catch (\Exception $mailEx) {
                Log::warning('Failed to send user welcome email: ' . $mailEx->getMessage(), [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'org_id'  => $orgId ?? null,
                    'trace'   => $mailEx->getTraceAsString(),
                ]);
            }

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

    /**
     * Download CSV template for user import
     * GET /api/v1/users/import/template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = [
            'employee_code',
            'email',
            'first_name',
            'last_name',
            'phone',
            'dept_id',
            'role_id',
            'password',
            'is_active'
        ];

        // Get the first available department as default
        $defaultDept = Department::where('is_active', true)->first();
        $defaultDeptId = $defaultDept?->id ?? '1';

        $sampleData = [
            'EMP001',
            'user@example.com',
            'John',
            'Doe',
            '+91 9876543210',
            $defaultDeptId, // Use actual department ID
            '', // Leave role blank - assign via edit form  
            'password123',
            'true'
        ];

        $csv = implode(',', $headers) . "\n" . implode(',', $sampleData);

        $fileName = 'users_import_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'users_template');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import users from CSV
     * POST /api/v1/users/import
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
                'skipped_assignments' => 0,
                'errors' => []
            ];

            \Log::info('CSV Processing Started', [
                'request_id' => $requestId,
                'total_rows' => $results['total_rows'],
                'headers' => $headers
            ]);

            // Check user capacity limit
            $orgId = $request->input('tenant_org_id');
            $activeSub = ActiveSubscription::find($orgId);
            $maxUsers = $activeSub ? $activeSub->max_users : 999999;
            $currentActiveUsers = User::where('is_active', true)->count();

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                try {
                    // Check user limit before creating each user
                    if ($currentActiveUsers + $results['successful'] >= $maxUsers) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'errors' => ['User limit reached for your subscription plan']
                        ];
                        continue;
                    }

                    // Map CSV columns to user data
                    $userData = [
                        'employee_code' => $row[0] ?? '',
                        'email' => $row[1] ?? '',
                        'first_name' => $row[2] ?? '',
                        'last_name' => $row[3] ?? '',
                        'phone' => !empty($row[4]) ? $row[4] : null,
                        'dept_id' => !empty($row[5]) && is_numeric($row[5]) ? (int)$row[5] : null,
                        'role_id' => !empty($row[6]) && is_numeric($row[6]) ? (int)$row[6] : null,
                        'password' => $row[7] ?? 'password123',
                        'is_active' => !empty($row[8]) ? filter_var($row[8], FILTER_VALIDATE_BOOLEAN) : true,
                        'created_by' => $request->input('auth_user_id'),
                    ];

                    // Ensure we have a valid department ID (required by database)
                    if (!$userData['dept_id']) {
                        $defaultDept = Department::where('is_active', true)->first();
                        $userData['dept_id'] = $defaultDept?->id ?? 1;
                    }

                    \Log::info('Processing Row', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
                        'raw_data' => $row,
                        'mapped_data' => array_merge($userData, ['password' => '[HIDDEN]'])
                    ]);

                    // Validate individual row
                    $rowValidator = Validator::make($userData, [
                        'employee_code' => 'required|string|max:50|unique:tenant.users,employee_code',
                        'email' => 'required|email|max:255|unique:tenant.users,email',
                        'first_name' => 'required|string|max:100',
                        'last_name' => 'required|string|max:100',
                        'phone' => 'nullable|string|max:20',
                        'dept_id' => 'required|integer|exists:tenant.department_master,id',
                        'role_id' => 'nullable|integer|exists:tenant.role_master,id',
                        'password' => 'required|string|min:8',
                    ]);

                    \Log::info('Row validation', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
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

                    // Validate role belongs to department if both are provided
                    if ($userData['dept_id'] && $userData['role_id']) {
                        if (!DeptRoleMap::isValidForDepartment((int)$userData['dept_id'], (int)$userData['role_id'])) {
                            \Log::warning('Department-role mapping invalid, removing role assignment', [
                                'request_id' => $requestId,
                                'row' => $rowNumber,
                                'dept_id' => $userData['dept_id'],
                                'role_id' => $userData['role_id']
                            ]);
                            
                            // Keep department, remove role assignment
                            $userData['role_id'] = null;
                            $results['skipped_assignments']++;
                        }
                    }

                    // Create user
                    $user = User::create([
                        'employee_code' => $userData['employee_code'],
                        'email' => $userData['email'],
                        'password' => $userData['password'], // This will trigger the password hash mutator
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'phone' => $userData['phone'],
                        'dept_id' => $userData['dept_id'],
                        'role_id' => $userData['role_id'],
                        'is_active' => $userData['is_active'],
                        'created_by' => $userData['created_by'],
                    ]);

                    \Log::info('User created', [
                        'request_id' => $requestId,
                        'user_id' => $user->id,
                        'employee_code' => $user->employee_code,
                        'email' => $user->email
                    ]);

                    $results['successful']++;

                } catch (\Exception $e) {
                    \Log::error('User creation failed', [
                        'request_id' => $requestId,
                        'row' => $rowNumber,
                        'data' => $userData ?? [],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => ['Failed to create user: ' . $e->getMessage()]
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

            $message = "Import completed. {$results['successful']} users created";
            if ($results['failed'] > 0) {
                $message .= ", {$results['failed']} failed";
            }
            if ($results['skipped_assignments'] > 0) {
                $message .= ". {$results['skipped_assignments']} department/role assignments were skipped due to invalid mappings";
            }
            $message .= ". Use edit form to assign departments and roles.";

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => $message,
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
