<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Material::with(['uom', 'purchaseUom', 'hsnCode', 'defaultWarehouse']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('material_type')) {
                $query->where('material_type', $request->input('material_type'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('material_code', 'like', "%{$search}%")
                        ->orWhere('material_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $materials = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'materials' => $materials->items(),
                    'pagination' => [
                        'current_page' => $materials->currentPage(),
                        'per_page' => $materials->perPage(),
                        'total' => $materials->total(),
                        'last_page' => $materials->lastPage(),
                    ]
                ],
                'message' => 'Materials retrieved successfully',
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
                'message' => 'Failed to retrieve materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $material = Material::with(['uom', 'purchaseUom', 'hsnCode', 'defaultWarehouse'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Material not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'material_code' => 'required|string|max:30|unique:tenant.material_master,material_code',
            'material_name' => 'required|string|max:200',
            'material_type' => 'required|string|max:20',
            'uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'purchase_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'required|integer|exists:tenant.hsn_codes,id',
            'default_warehouse_id' => 'nullable|integer|exists:tenant.warehouse_master,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'qc_required' => 'boolean',
            'inspection_type' => 'nullable|string|max:10',
            'is_batch_tracked' => 'boolean',
            'standard_cost' => 'nullable|numeric|min:0',
            'valuation_method' => 'nullable|string|max:10',
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
            $material = Material::create(array_merge(
                $request->all(),
                [
                    'created_by' => $request->input('auth_user_id'),
                    'is_active' => true
                ]
            ));

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'material_code' => 'sometimes|string|max:30|unique:tenant.material_master,material_code,' . $id . ',id',
            'material_name' => 'sometimes|string|max:200',
            'material_type' => 'sometimes|string|max:20',
            'uom_id' => 'sometimes|integer|exists:tenant.uom_master,id',
            'purchase_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'sometimes|integer|exists:tenant.hsn_codes,id',
            'default_warehouse_id' => 'nullable|integer|exists:tenant.warehouse_master,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'qc_required' => 'sometimes|boolean',
            'inspection_type' => 'nullable|string|max:10',
            'is_batch_tracked' => 'sometimes|boolean',
            'standard_cost' => 'nullable|numeric|min:0',
            'valuation_method' => 'nullable|string|max:10',
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
            $material = Material::findOrFail($id);
            $material->update(array_merge(
                $request->all(),
                ['updated_by' => $request->input('auth_user_id')]
            ));

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $material = Material::findOrFail($id);
            $material->is_active = false;
            $material->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Material deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to deactivate material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
