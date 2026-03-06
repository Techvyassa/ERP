<?php

namespace App\Http\Controllers;

use App\Models\Tenant\UOM;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UOMController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = UOM::with(['baseUom']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('uom_name', 'like', "%{$search}%")
                        ->orWhere('uom_code', 'like', "%{$search}%");
                });
            }

            $uoms = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'uoms' => $uoms
                ],
                'message' => 'UOMs retrieved successfully',
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
                'message' => 'Failed to retrieve UOMs: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $uom = UOM::with(['baseUom'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'UOM not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'uom_code' => 'required|string|max:10|unique:tenant.uom_master,uom_code',
            'uom_name' => 'required|string|max:50',
            'uom_type' => 'required|string|max:20',
            'base_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'conversion_factor' => 'nullable|numeric|min:0',
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
            $uom = UOM::create([
                'uom_code' => $request->input('uom_code'),
                'uom_name' => $request->input('uom_name'),
                'uom_type' => $request->input('uom_type'),
                'base_uom_id' => $request->input('base_uom_id'),
                'conversion_factor' => $request->input('conversion_factor', 1),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'uom_code' => 'sometimes|string|max:10|unique:tenant.uom_master,uom_code,' . $id . ',id',
            'uom_name' => 'sometimes|string|max:50',
            'uom_type' => 'sometimes|string|max:20',
            'base_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'conversion_factor' => 'nullable|numeric|min:0',
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
            $uom = UOM::findOrFail($id);

            if ($request->has('uom_code')) {
                $uom->uom_code = $request->input('uom_code');
            }
            if ($request->has('uom_name')) {
                $uom->uom_name = $request->input('uom_name');
            }
            if ($request->has('uom_type')) {
                $uom->uom_type = $request->input('uom_type');
            }
            if ($request->has('base_uom_id')) {
                $uom->base_uom_id = $request->input('base_uom_id');
            }
            if ($request->has('conversion_factor')) {
                $uom->conversion_factor = $request->input('conversion_factor');
            }
            if ($request->has('is_active')) {
                $uom->is_active = $request->input('is_active');
            }

            $uom->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $uom = UOM::findOrFail($id);
            $uom->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'UOM deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
