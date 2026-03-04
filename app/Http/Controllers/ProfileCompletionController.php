<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProfileCompletionController extends Controller
{
    /**
     * Get profile completion status
     * GET /api/v1/profile-completion/status
     */
    public function status(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            // Get org_id from request attributes (set by middleware)
            $orgId = $request->attributes->get('org_id');
            
            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ORG_ID_MISSING',
                        'details' => []
                    ],
                    'message' => 'Organization ID not found in request',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }
            
            $organization = Organization::findOrFail($orgId);
            
            $completion = $this->calculateProfileCompletion($organization);
            
            return response()->json([
                'success' => true,
                'data' => $completion,
                'message' => 'Profile completion status retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Profile completion status error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve profile completion status: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update organization profile
     * PUT /api/v1/profile-completion/organization
     */
    public function updateOrganization(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'org_name' => 'sometimes|string|max:255',
            'primary_phone' => 'sometimes|string|max:20',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country_code' => 'sometimes|string|size:2',
            'timezone' => 'sometimes|string|max:50',
            'currency_code' => 'sometimes|string|size:3',
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
            $orgId = $request->attributes->get('org_id');
            $organization = Organization::findOrFail($orgId);
            
            $organization->fill($request->only([
                'org_name', 'primary_phone', 'address_line1', 'address_line2',
                'city', 'state', 'postal_code', 'country_code', 'timezone', 'currency_code'
            ]));
            
            $organization->save();
            
            // Recalculate completion
            $completion = $this->calculateProfileCompletion($organization);
            $organization->profile_completion = json_encode($completion);
            $organization->profile_completion_percentage = $completion['percentage'];
            
            if ($completion['percentage'] === 100 && !$organization->profile_completed_at) {
                $organization->profile_completed_at = now();
            }
            
            $organization->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'organization' => $organization,
                    'completion' => $completion
                ],
                'message' => 'Organization profile updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update organization profile: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get master data setup status
     * GET /api/v1/profile-completion/master-data-status
     */
    public function masterDataStatus(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $orgId = $request->attributes->get('org_id');
            
            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ORG_ID_MISSING',
                        'details' => []
                    ],
                    'message' => 'Organization ID not found in request',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }
            
            $organization = Organization::findOrFail($orgId);
            
            // Switch to tenant database
            $tenantDbName = $organization->tenant_db_name;
            
            if (!$tenantDbName) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TENANT_DB_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => 'Tenant database not configured for this organization',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }
            
            config(['database.connections.tenant.database' => $tenantDbName]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            $masterDataStatus = $this->getMasterDataStatus();
            
            return response()->json([
                'success' => true,
                'data' => $masterDataStatus,
                'message' => 'Master data status retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Master data status error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'org_id' => $orgId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve master data status: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(Organization $organization): array
    {
        $fields = [
            'basic_info' => [
                'org_name' => !empty($organization->org_name),
                'primary_email' => !empty($organization->primary_email),
                'primary_phone' => !empty($organization->primary_phone),
            ],
            'address' => [
                'address_line1' => !empty($organization->address_line1),
                'city' => !empty($organization->city),
                'state' => !empty($organization->state),
                'postal_code' => !empty($organization->postal_code),
                'country_code' => !empty($organization->country_code),
            ],
            'regional' => [
                'timezone' => !empty($organization->timezone),
                'currency_code' => !empty($organization->currency_code),
            ],
        ];
        
        $totalFields = 0;
        $completedFields = 0;
        $sections = [];
        
        foreach ($fields as $section => $sectionFields) {
            $sectionTotal = count($sectionFields);
            $sectionCompleted = count(array_filter($sectionFields));
            $totalFields += $sectionTotal;
            $completedFields += $sectionCompleted;
            
            $sections[$section] = [
                'completed' => $sectionCompleted,
                'total' => $sectionTotal,
                'percentage' => $sectionTotal > 0 ? round(($sectionCompleted / $sectionTotal) * 100) : 0,
                'fields' => $sectionFields
            ];
        }
        
        $percentage = $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
        
        return [
            'percentage' => $percentage,
            'completed_fields' => $completedFields,
            'total_fields' => $totalFields,
            'sections' => $sections,
            'is_complete' => $percentage === 100
        ];
    }

    /**
     * Get master data setup status
     */
    private function getMasterDataStatus(): array
    {
        $masters = [
            [
                'key' => 'departments',
                'name' => 'Departments',
                'table' => 'department_master',
                'group' => 'Organization',
                'icon' => 'building',
                'color' => 'blue',
                'critical' => false,
                'description' => 'Business departments with cost centre mapping'
            ],
            [
                'key' => 'roles',
                'name' => 'Roles',
                'table' => 'role_master',
                'group' => 'Organization',
                'icon' => 'user-shield',
                'color' => 'purple',
                'critical' => false,
                'description' => 'System roles and permissions'
            ],
            [
                'key' => 'users',
                'name' => 'Users',
                'table' => 'users',
                'group' => 'Organization',
                'icon' => 'users',
                'color' => 'green',
                'critical' => true,
                'description' => 'System users mapped to departments and roles'
            ],
        ];
        
        // Only include tables that actually exist
        $existingMasters = [];
        foreach ($masters as $master) {
            try {
                // Check if table exists
                $tableExists = DB::connection('tenant')
                    ->getSchemaBuilder()
                    ->hasTable($master['table']);
                
                if ($tableExists) {
                    $existingMasters[] = $master;
                }
            } catch (\Exception $e) {
                \Log::warning("Could not check table: {$master['table']}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $groupedMasters = [];
        $totalCount = 0;
        $setupCount = 0;
        
        foreach ($existingMasters as $master) {
            try {
                $count = DB::connection('tenant')->table($master['table'])->count();
                $master['count'] = $count;
                $master['is_setup'] = $count > 0;
                
                if ($count > 0) {
                    $setupCount++;
                }
                $totalCount++;
            } catch (\Exception $e) {
                \Log::error("Error counting records in {$master['table']}", [
                    'error' => $e->getMessage()
                ]);
                $master['count'] = 0;
                $master['is_setup'] = false;
                $master['error'] = 'Error reading table';
                $totalCount++;
            }
            
            $group = $master['group'];
            if (!isset($groupedMasters[$group])) {
                $groupedMasters[$group] = [
                    'name' => $group,
                    'masters' => []
                ];
            }
            
            $groupedMasters[$group]['masters'][] = $master;
        }
        
        $percentage = $totalCount > 0 ? round(($setupCount / $totalCount) * 100) : 0;
        
        return [
            'percentage' => $percentage,
            'setup_count' => $setupCount,
            'total_count' => $totalCount,
            'groups' => array_values($groupedMasters),
            'is_complete' => $percentage === 100
        ];
    }
}
