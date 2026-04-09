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

            // Prevent editing of system roles
            if ($role->is_system_role) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SYSTEM_ROLE_EDIT_FORBIDDEN',
                        'details' => []
                    ],
                    'message' => 'System roles cannot be edited or deactivated',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }

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
     * Download CSV template for roles
     * GET /api/v1/roles/template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="roles_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers (role_code removed - will be auto-generated)
            fputcsv($file, [
                'role_name',
                'description',
                'is_active'
            ]);
            
            // Sample data
            fputcsv($file, [
                'Manager',
                'Department Manager Role',
                'true'
            ]);
            
            fputcsv($file, [
                'Supervisor',
                'Team Supervisor Role',
                'true'
            ]);
            
            fputcsv($file, [
                'Team Leader',
                'Team Leader Role',
                'true'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate unique role code from role name
     */
    private function generateRoleCode(string $roleName): string
    {
        // Convert to uppercase and remove special characters
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $roleName));
        
        // Take first 10 characters or less
        $baseCode = substr($baseCode, 0, 10);
        
        // Check if code exists
        $code = $baseCode;
        $counter = 1;
        
        while (Role::where('role_code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Import roles from CSV
     * POST /api/v1/roles/import
     */
    public function import(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Validate file upload
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

                // Validate required fields (only role_name is required now)
                if (empty($data['role_name'])) {
                    $errors[] = "Row {$rowNumber}: role_name is required";
                    continue;
                }

                // Check for duplicate role_name
                $existingRole = Role::where('role_name', trim($data['role_name']))->first();
                if ($existingRole) {
                    $errors[] = "Row {$rowNumber}: Role name '{$data['role_name']}' already exists";
                    continue;
                }

                // Auto-generate role_code from role_name
                $roleCode = $this->generateRoleCode($data['role_name']);

                // Parse is_active
                $isActive = true;
                if (isset($data['is_active'])) {
                    $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
                }

                try {
                    // Create role
                    Role::create([
                        'role_code' => $roleCode,
                        'role_name' => trim($data['role_name']),
                        'description' => isset($data['description']) ? trim($data['description']) : null,
                        'is_active' => $isActive,
                        'is_system_role' => false,
                        'created_by' => $request->input('auth_user_id'),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} role(s)";
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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'IMPORT_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to import roles: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
