<?php

namespace App\Http\Controllers;

use App\Models\Tenant\BOMDetail;
use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\Material;
use App\Models\Tenant\UOM;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BOMDetailController extends Controller
{
    /**
     * Get all BOM details
     * GET /api/v1/bom-details
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = BOMDetail::with(['bomHeader', 'material', 'uom', 'substituteMaterial']);

            // Filter by BOM ID if provided
            if ($request->has('bom_id')) {
                $query->where('bom_id', $request->input('bom_id'));
            }

            // Filter by critical if provided
            if ($request->has('is_critical')) {
                $query->where('is_critical', $request->input('is_critical'));
            }

            $details = $query->orderBy('line_no', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $details->toArray(),
                'message' => 'BOM details retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Details Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve BOM details: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get specific BOM detail
     * GET /api/v1/bom-details/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $detail = BOMDetail::with(['bomHeader', 'material', 'uom', 'substituteMaterial'])->find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => ['id' => $id]],
                    'message' => 'BOM detail not found with id: ' . $id,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $detail->toArray(),
                'message' => 'BOM detail retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Detail Show Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'ERROR', 'details' => ['exception' => $e->getMessage()]],
                'message' => 'Failed to retrieve BOM detail: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Create new BOM detail
     * POST /api/v1/bom-details
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'bom_id' => 'required|integer',
            'material_id' => 'required|integer',
            'qty_required' => 'required|numeric|min:0.0001',
            'uom_id' => 'required|integer',
            'scrap_percent' => 'nullable|numeric|min:0|max:100',
            'line_no' => 'required|integer|min:1',
            'substitute_material_id' => 'nullable|integer',
            'is_critical' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',
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
            // Verify BOM header exists
            $bomHeader = BOMHeader::find($request->input('bom_id'));
            if (!$bomHeader) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'details' => ['bom_id' => ['The selected BOM does not exist.']]
                    ],
                    'message' => 'Validation failed',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            // Verify material exists
            $material = Material::find($request->input('material_id'));
            if (!$material) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'details' => ['material_id' => ['The selected material does not exist.']]
                    ],
                    'message' => 'Validation failed',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            // Verify UOM exists
            $uom = UOM::find($request->input('uom_id'));
            if (!$uom) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'details' => ['uom_id' => ['The selected UOM does not exist.']]
                    ],
                    'message' => 'Validation failed',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            // Verify substitute material if provided
            if ($request->has('substitute_material_id') && $request->input('substitute_material_id')) {
                $substituteMaterial = Material::find($request->input('substitute_material_id'));
                if (!$substituteMaterial) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'details' => ['substitute_material_id' => ['The selected substitute material does not exist.']]
                        ],
                        'message' => 'Validation failed',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 422);
                }
            }

            // Calculate effective quantity
            $qtyRequired = $request->input('qty_required');
            $scrapPercent = $request->input('scrap_percent', 0);
            // Note: effective_qty is a generated column in the database, no need to calculate

            // Create BOM detail
            $detail = BOMDetail::create([
                'bom_id' => $request->input('bom_id'),
                'material_id' => $request->input('material_id'),
                'qty_required' => $qtyRequired,
                'uom_id' => $request->input('uom_id'),
                'scrap_percent' => $scrapPercent,
                'substitute_material_id' => $request->input('substitute_material_id'),
                'is_critical' => $request->input('is_critical', false),
                'line_no' => $request->input('line_no'),
                'remarks' => $request->input('remarks'),
            ]);

            $detail->load(['bomHeader', 'material', 'uom', 'substituteMaterial']);

            return response()->json([
                'success' => true,
                'data' => $detail->toArray(),
                'message' => 'BOM detail created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            Log::error('BOM Detail Create Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create BOM detail: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update BOM detail
     * PUT /api/v1/bom-details/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'qty_required' => 'nullable|numeric|min:0.0001',
            'uom_id' => 'nullable|integer',
            'scrap_percent' => 'nullable|numeric|min:0|max:100',
            'line_no' => 'nullable|integer|min:1',
            'substitute_material_id' => 'nullable|integer',
            'is_critical' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',
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
            $detail = BOMDetail::find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => ['id' => $id]],
                    'message' => 'BOM detail not found with id: ' . $id,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Verify UOM exists if provided
            if ($request->has('uom_id') && $request->input('uom_id')) {
                $uom = UOM::find($request->input('uom_id'));
                if (!$uom) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'details' => ['uom_id' => ['The selected UOM does not exist.']]
                        ],
                        'message' => 'Validation failed',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 422);
                }
            }

            // Verify substitute material if provided
            if ($request->has('substitute_material_id') && $request->input('substitute_material_id')) {
                $substituteMaterial = Material::find($request->input('substitute_material_id'));
                if (!$substituteMaterial) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'details' => ['substitute_material_id' => ['The selected substitute material does not exist.']]
                        ],
                        'message' => 'Validation failed',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 422);
                }
            }

            // Prepare update data
            $updateData = $request->only([
                'qty_required',
                'uom_id',
                'scrap_percent',
                'line_no',
                'substitute_material_id',
                'is_critical',
                'remarks'
            ]);

            // Note: effective_qty is a generated column, don't include it in updates
            $detail->update($updateData);
            $detail->load(['bomHeader', 'material', 'uom', 'substituteMaterial']);

            return response()->json([
                'success' => true,
                'data' => $detail->toArray(),
                'message' => 'BOM detail updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Detail Update Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update BOM detail: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete BOM detail
     * DELETE /api/v1/bom-details/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $detail = BOMDetail::find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => ['id' => $id]],
                    'message' => 'BOM detail not found with id: ' . $id,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Hard delete
            $detail->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'BOM detail deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Detail Delete Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete BOM detail: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
