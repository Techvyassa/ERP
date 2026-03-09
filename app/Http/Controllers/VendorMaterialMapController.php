<?php

namespace App\Http\Controllers;

use App\Models\Tenant\VendorMaterialMap;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorMaterialMapController extends Controller
{
    /**
     * List AVL entries
     * GET /api/v1/vendor-material-map
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = VendorMaterialMap::with(['vendor', 'material.uom']);

            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->input('vendor_id'));
            }
            if ($request->has('material_id')) {
                $query->where('material_id', $request->input('material_id'));
            }
            if ($request->has('is_preferred')) {
                $query->where('is_preferred', filter_var($request->input('is_preferred'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            $maps = $query->get();

            return response()->json([
                'success' => true,
                'data' => ['avl' => $maps],
                'message' => 'Approved Vendor List retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve AVL: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get single mapping
     * GET /api/v1/vendor-material-map/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $map = VendorMaterialMap::with(['vendor', 'material.uom'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['mapping' => $map],
                'message' => 'Vendor-material mapping retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MAPPING_NOT_FOUND', 'details' => []],
                'message' => 'Vendor-material mapping not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * Create AVL entry
     * POST /api/v1/vendor-material-map
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_id'            => 'required|integer|exists:tenant.vendor_master,id',
            'material_id'          => 'required|integer|exists:tenant.material_master,id',
            'vendor_material_code' => 'nullable|string|max:50',
            'last_purchase_price'  => 'nullable|numeric|min:0',
            'lead_time_days'       => 'nullable|integer|min:0',
            'min_order_qty'        => 'nullable|numeric|min:0',
            'is_preferred'         => 'boolean',
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

        // Check for duplicate vendor+material
        $exists = VendorMaterialMap::where('vendor_id', $request->input('vendor_id'))
            ->where('material_id', $request->input('material_id'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'DUPLICATE_MAPPING', 'details' => []],
                'message' => 'This vendor-material mapping already exists',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 409);
        }

        try {
            $map = VendorMaterialMap::create([
                'vendor_id'            => $request->input('vendor_id'),
                'material_id'          => $request->input('material_id'),
                'vendor_material_code' => $request->input('vendor_material_code'),
                'last_purchase_price'  => $request->input('last_purchase_price'),
                'lead_time_days'       => $request->input('lead_time_days'),
                'min_order_qty'        => $request->input('min_order_qty'),
                'is_preferred'         => $request->input('is_preferred', false),
                'is_active'            => true,
            ]);

            $map->load(['vendor', 'material.uom']);

            return response()->json([
                'success' => true,
                'data' => ['mapping' => $map],
                'message' => 'Vendor-material mapping created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MAPPING_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create mapping: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Update AVL entry
     * PUT /api/v1/vendor-material-map/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_material_code' => 'nullable|string|max:50',
            'last_purchase_price'  => 'nullable|numeric|min:0',
            'lead_time_days'       => 'nullable|integer|min:0',
            'min_order_qty'        => 'nullable|numeric|min:0',
            'is_preferred'         => 'sometimes|boolean',
            'is_active'            => 'sometimes|boolean',
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
            $map = VendorMaterialMap::findOrFail($id);

            foreach (['vendor_material_code', 'last_purchase_price', 'lead_time_days', 'min_order_qty', 'is_preferred', 'is_active'] as $field) {
                if ($request->has($field)) {
                    $map->$field = $request->input($field);
                }
            }
            $map->save();

            return response()->json([
                'success' => true,
                'data' => ['mapping' => $map],
                'message' => 'Vendor-material mapping updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MAPPING_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update mapping: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Remove AVL entry (deactivate)
     * DELETE /api/v1/vendor-material-map/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $map = VendorMaterialMap::findOrFail($id);
            $map->is_active = false;
            $map->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Vendor-material mapping deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MAPPING_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate mapping: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
