<?php

namespace App\Http\Controllers;

use App\Models\Control\FeatureControl;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FeatureControlController extends Controller
{
    /**
     * List feature controls for an organization
     * GET /api/v1/admin/feature-controls
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'org_id' => 'required|integer|exists:organizations,org_id',
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
            $orgId = $request->input('org_id');
            $featureControls = FeatureControl::where('org_id', $orgId)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'feature_controls' => $featureControls
                ],
                'message' => 'Feature controls retrieved successfully',
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
                'message' => 'Failed to retrieve feature controls: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single feature control
     * GET /api/v1/admin/feature-controls/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $featureControl = FeatureControl::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'feature_control' => $featureControl
                ],
                'message' => 'Feature control retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_CONTROL_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Feature control not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create feature control
     * POST /api/v1/admin/feature-controls
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'org_id' => 'required|integer|exists:organizations,org_id',
            'feature_key' => 'required|string|max:100',
            'feature_type' => 'required|in:BOOLEAN,NUMERIC,TEXT,JSON',
            'feature_value' => 'required|string',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
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
            // Validate feature_value based on feature_type
            $featureType = $request->input('feature_type');
            $featureValue = $request->input('feature_value');

            if ($featureType === 'BOOLEAN' && !in_array(strtolower($featureValue), ['true', 'false', '1', '0'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_FEATURE_VALUE',
                        'details' => []
                    ],
                    'message' => 'Feature value must be a boolean for BOOLEAN type',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            if ($featureType === 'NUMERIC' && !is_numeric($featureValue)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_FEATURE_VALUE',
                        'details' => []
                    ],
                    'message' => 'Feature value must be numeric for NUMERIC type',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 400);
            }

            if ($featureType === 'JSON') {
                json_decode($featureValue);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_FEATURE_VALUE',
                            'details' => []
                        ],
                        'message' => 'Feature value must be valid JSON for JSON type',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 400);
                }
            }

            // Create feature control
            $featureControl = FeatureControl::create([
                'org_id' => $request->input('org_id'),
                'feature_key' => $request->input('feature_key'),
                'feature_type' => $featureType,
                'feature_value' => $featureValue,
                'effective_from' => $request->input('effective_from'),
                'effective_to' => $request->input('effective_to'),
                'granted_by' => $request->attributes->get('user_id'),
                'notes' => $request->input('notes'),
            ]);

            // Log feature control change
            Log::info('Feature control created', [
                'control_id' => $featureControl->control_id,
                'org_id' => $featureControl->org_id,
                'feature_key' => $featureControl->feature_key,
                'granted_by' => $featureControl->granted_by,
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Audit log feature control creation
            AuditLogger::logFeatureControlChange(
                $featureControl->org_id,
                $featureControl->feature_key,
                'created',
                $featureControl->granted_by,
                null,
                $featureValue
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'feature_control' => $featureControl
                ],
                'message' => 'Feature control created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_CONTROL_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create feature control: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update feature control
     * PUT /api/v1/admin/feature-controls/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'feature_value' => 'sometimes|string',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
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
            $featureControl = FeatureControl::findOrFail($id);
            
            // Store old value for audit log
            $oldValue = $featureControl->feature_value;

            // Validate feature_value if provided
            if ($request->has('feature_value')) {
                $featureValue = $request->input('feature_value');
                $featureType = $featureControl->feature_type;

                if ($featureType === 'BOOLEAN' && !in_array(strtolower($featureValue), ['true', 'false', '1', '0'])) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_FEATURE_VALUE',
                            'details' => []
                        ],
                        'message' => 'Feature value must be a boolean for BOOLEAN type',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 400);
                }

                if ($featureType === 'NUMERIC' && !is_numeric($featureValue)) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_FEATURE_VALUE',
                            'details' => []
                        ],
                        'message' => 'Feature value must be numeric for NUMERIC type',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 400);
                }

                if ($featureType === 'JSON') {
                    json_decode($featureValue);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return response()->json([
                            'success' => false,
                            'error' => [
                                'code' => 'INVALID_FEATURE_VALUE',
                                'details' => []
                            ],
                            'message' => 'Feature value must be valid JSON for JSON type',
                            'request_id' => $requestId,
                            'timestamp' => now()->toIso8601String()
                        ], 400);
                    }
                }

                $featureControl->feature_value = $featureValue;
            }

            if ($request->has('effective_from')) {
                $featureControl->effective_from = $request->input('effective_from');
            }
            if ($request->has('effective_to')) {
                $featureControl->effective_to = $request->input('effective_to');
            }
            if ($request->has('notes')) {
                $featureControl->notes = $request->input('notes');
            }

            $featureControl->save();

            // Log feature control change
            Log::info('Feature control updated', [
                'control_id' => $featureControl->control_id,
                'org_id' => $featureControl->org_id,
                'feature_key' => $featureControl->feature_key,
                'updated_by' => $request->attributes->get('user_id'),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Audit log feature control update
            AuditLogger::logFeatureControlChange(
                $featureControl->org_id,
                $featureControl->feature_key,
                'updated',
                $request->attributes->get('user_id'),
                $oldValue,
                $featureControl->feature_value
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'feature_control' => $featureControl
                ],
                'message' => 'Feature control updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_CONTROL_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update feature control: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete feature control
     * DELETE /api/v1/admin/feature-controls/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $featureControl = FeatureControl::findOrFail($id);
            
            // Store data for audit log before deletion
            $orgId = $featureControl->org_id;
            $featureKey = $featureControl->feature_key;
            $featureValue = $featureControl->feature_value;
            
            // Log before deletion
            Log::info('Feature control deleted', [
                'control_id' => $featureControl->control_id,
                'org_id' => $featureControl->org_id,
                'feature_key' => $featureControl->feature_key,
                'deleted_by' => $request->attributes->get('user_id'),
                'timestamp' => now()->toIso8601String(),
            ]);

            $featureControl->delete();
            
            // Audit log feature control deletion
            AuditLogger::logFeatureControlChange(
                $orgId,
                $featureKey,
                'deleted',
                $request->attributes->get('user_id'),
                $featureValue,
                null
            );

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Feature control deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_CONTROL_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete feature control: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
