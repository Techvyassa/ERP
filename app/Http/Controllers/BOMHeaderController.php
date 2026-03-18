<?php

namespace App\Http\Controllers;

use App\Models\Tenant\BOMHeader;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BOMHeaderController extends Controller
{
    /**
     * List BOM headers
     * GET /api/v1/bom-headers
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = BOMHeader::with(['product.uom']);

            // Filters
            if ($request->has('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('is_approved')) {
                $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('bom_code', 'like', "%{$search}%")
                        ->orWhere('bom_description', 'like', "%{$search}%");
                });
            }

            $boms = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => ['boms' => $boms],
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
     * Get single BOM header
     * GET /api/v1/bom-headers/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = BOMHeader::with(['product.uom', 'bomDetails.material.uom', 'approver'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['bom' => $bom],
                'message' => 'BOM header retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BOM_NOT_FOUND', 'details' => []],
                'message' => 'BOM header not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * Create BOM header
     * POST /api/v1/bom-headers
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'bom_code'         => 'nullable|string|max:50|unique:tenant.bom_header,bom_code',
            'bom_version'      => 'nullable|string|max:20',
            'product_id'       => 'required|integer|exists:tenant.product_master,id',
            'bom_description'  => 'nullable|string|max:500',
            'effective_from'   => 'nullable|date',
            'effective_to'     => 'nullable|date|after_or_equal:effective_from',
            'batch_size'       => 'nullable|numeric|min:0',
            'is_active'        => 'boolean',
            'is_approved'      => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            // Auto-generate BOM code if not provided
            $bomCode = $request->input('bom_code');
            if (empty($bomCode)) {
                $bomCode = $this->generateBOMCode();
            }

            $bom = BOMHeader::create([
                'bom_code'         => $bomCode,
                'bom_version'      => $request->input('bom_version', 'v1.0'),
                'product_id'       => $request->input('product_id'),
                'bom_description'  => $request->input('bom_description'),
                'effective_from'   => $request->input('effective_from'),
                'effective_to'     => $request->input('effective_to'),
                'batch_size'       => $request->input('batch_size', 1),
                'is_active'        => $request->input('is_active', true),
                'is_approved'      => $request->input('is_approved', false),
                'created_by'       => auth()->id(),
            ]);

            $bom->load(['product.uom']);

            return response()->json([
                'success' => true,
                'data' => ['bom' => $bom],
                'message' => 'BOM header created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BOM_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
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
            'bom_code'         => 'sometimes|string|max:50|unique:tenant.bom_header,bom_code,' . $id,
            'bom_version'      => 'sometimes|string|max:20',
            'product_id'       => 'sometimes|integer|exists:tenant.product_master,id',
            'bom_description'  => 'nullable|string|max:500',
            'effective_from'   => 'nullable|date',
            'effective_to'     => 'nullable|date|after_or_equal:effective_from',
            'batch_size'       => 'nullable|numeric|min:0',
            'is_active'        => 'sometimes|boolean',
            'is_approved'      => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $bom = BOMHeader::findOrFail($id);

            $updateData = $request->only([
                'bom_code',
                'bom_version',
                'product_id',
                'bom_description',
                'effective_from',
                'effective_to',
                'batch_size',
                'is_active',
                'is_approved',
            ]);
            $updateData['updated_by'] = auth()->id();

            $bom->update($updateData);
            $bom->load(['product.uom']);

            return response()->json([
                'success' => true,
                'data' => ['bom' => $bom],
                'message' => 'BOM header updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BOM_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Delete BOM header (soft delete)
     * DELETE /api/v1/bom-headers/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = BOMHeader::findOrFail($id);
            $bom->is_active = false;
            $bom->save();
            $bom->delete(); // Soft delete

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'BOM header deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BOM_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to delete BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Generate unique BOM code
     */
    private function generateBOMCode(): string
    {
        $prefix = 'BOM';
        
        // Get the last BOM with this prefix
        $lastBOM = BOMHeader::where('bom_code', 'like', $prefix . '-%')
            ->orderBy('bom_code', 'desc')
            ->first();

        if ($lastBOM) {
            // Extract number from last code (e.g., BOM-0123 -> 123)
            $lastCode = $lastBOM->bom_code;
            $parts = explode('-', $lastCode);
            $lastNumber = isset($parts[1]) ? intval($parts[1]) : 0;
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format with leading zeros (e.g., 0001)
        return sprintf('%s-%04d', $prefix, $newNumber);
    }
}
