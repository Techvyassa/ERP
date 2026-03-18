<?php

namespace App\Http\Controllers;

use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\Product;
use App\Models\Tenant\UOM;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BOMHeaderController extends Controller
{
    /**
     * Get all BOM headers
     * GET /api/v1/bom-headers
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = BOMHeader::with(['product', 'outputUom']);

            // Filters
            if ($request->has('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }
            if ($request->has('bom_status')) {
                $query->where('bom_status', $request->input('bom_status'));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query = $query->where(function ($q) use ($search) {
                    $q->where('bom_code', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            $boms = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $boms->toArray(),
                'message' => 'BOM headers retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve BOM headers: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get specific BOM header
     * GET /api/v1/bom-headers/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            // Try to load with relationships
            $bom = BOMHeader::with(['product', 'outputUom', 'creator', 'approver'])->find($id);

            if (!$bom) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => ['id' => $id]],
                    'message' => 'BOM header not found with id: ' . $id,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Convert to array to ensure all data is serializable
            $bomData = $bom->toArray();

            return response()->json([
                'success' => true,
                'data' => $bomData,
                'message' => 'BOM header retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Show Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ERROR', 'details' => ['exception' => $e->getMessage()]],
                'message' => 'Failed to retrieve BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Create new BOM header
     * POST /api/v1/bom-headers
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'bom_code' => 'required|string|max:30',
            'product_id' => 'required|integer',
            'version' => 'required|integer|min:1',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'bom_status' => 'required|in:DRAFT,ACTIVE,OBSOLETE',
            'batch_size' => 'required|numeric|min:0.001',
            'output_uom_id' => 'required|integer',
            'remarks' => 'nullable|string|max:1000',
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
            // Verify product exists
            $product = Product::findOrFail($request->input('product_id'));

            // Verify UOM exists
            $uom = UOM::findOrFail($request->input('output_uom_id'));

            // Check unique constraint on bom_code
            $existingCode = BOMHeader::where('bom_code', $request->input('bom_code'))->first();
            if ($existingCode) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_CODE',
                        'details' => []
                    ],
                    'message' => 'BOM code already exists',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 409);
            }

            // Check unique constraint on product_id and version
            $existing = BOMHeader::where('product_id', $request->input('product_id'))
                ->where('version', $request->input('version'))
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_VERSION',
                        'details' => []
                    ],
                    'message' => 'BOM version already exists for this product',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 409);
            }

            // Create BOM header
            $bom = BOMHeader::create([
                'bom_code' => $request->input('bom_code'),
                'product_id' => $request->input('product_id'),
                'version' => $request->input('version'),
                'effective_from' => $request->input('effective_from'),
                'effective_to' => $request->input('effective_to'),
                'bom_status' => $request->input('bom_status'),
                'batch_size' => $request->input('batch_size'),
                'output_uom_id' => $request->input('output_uom_id'),
                'remarks' => $request->input('remarks'),
                'created_by' => $request->input('auth_user_id'),
            ]);

            $bom->load(['product', 'outputUom', 'creator']);

            return response()->json([
                'success' => true,
                'data' => ['bom' => $bom],
                'message' => 'BOM header created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update BOM header
     * PUT /api/v1/bom-headers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'bom_status' => 'nullable|in:DRAFT,ACTIVE,OBSOLETE',
            'batch_size' => 'nullable|numeric|min:0.001',
            'output_uom_id' => 'nullable|integer',
            'remarks' => 'nullable|string|max:1000',
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
            $bom = BOMHeader::findOrFail($id);

            // Verify UOM exists if provided
            if ($request->has('output_uom_id') && $request->input('output_uom_id')) {
                $uom = UOM::find($request->input('output_uom_id'));
                if (!$uom) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'details' => ['output_uom_id' => ['The selected UOM does not exist.']]
                        ],
                        'message' => 'Validation failed',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String()
                    ], 422);
                }
            }

            // Update only provided fields
            $updateData = $request->only([
                'effective_from',
                'effective_to',
                'bom_status',
                'batch_size',
                'output_uom_id',
                'remarks'
            ]);

            $bom->update($updateData);
            $bom->load(['product', 'outputUom', 'creator', 'approver']);

            return response()->json([
                'success' => true,
                'data' => $bom->toArray(),
                'message' => 'BOM header updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Update Error', [
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
                'message' => 'Failed to update BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete/Deactivate BOM header
     * DELETE /api/v1/bom-headers/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = BOMHeader::findOrFail($id);

            // Hard delete
            $bom->forceDelete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'BOM header deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
