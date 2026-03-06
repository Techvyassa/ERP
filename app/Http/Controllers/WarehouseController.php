<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Warehouse::with(['inchargeUser']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('warehouse_type')) {
                $query->where('warehouse_type', $request->input('warehouse_type'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('warehouse_name', 'like', "%{$search}%")
                        ->orWhere('warehouse_code', 'like', "%{$search}%");
                });
            }

            $warehouses = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses
                ],
                'message' => 'Warehouses retrieved successfully',
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
                'message' => 'Failed to retrieve warehouses: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $warehouse = Warehouse::with(['inchargeUser'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Warehouse not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'warehouse_code' => 'required|string|max:20|unique:tenant.warehouse_master,warehouse_code',
            'warehouse_name' => 'required|string|max:100',
            'warehouse_type' => 'required|string|max:20',
            'address' => 'nullable|string',
            'incharge_user_id' => 'nullable|integer|exists:tenant.users,id',
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
            $warehouse = Warehouse::create([
                'warehouse_code' => $request->input('warehouse_code'),
                'warehouse_name' => $request->input('warehouse_name'),
                'warehouse_type' => $request->input('warehouse_type'),
                'address' => $request->input('address'),
                'incharge_user_id' => $request->input('incharge_user_id'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'warehouse_code' => 'sometimes|string|max:20|unique:tenant.warehouse_master,warehouse_code,' . $id . ',id',
            'warehouse_name' => 'sometimes|string|max:100',
            'warehouse_type' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'incharge_user_id' => 'nullable|integer|exists:tenant.users,id',
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
            $warehouse = Warehouse::findOrFail($id);

            if ($request->has('warehouse_code')) {
                $warehouse->warehouse_code = $request->input('warehouse_code');
            }
            if ($request->has('warehouse_name')) {
                $warehouse->warehouse_name = $request->input('warehouse_name');
            }
            if ($request->has('warehouse_type')) {
                $warehouse->warehouse_type = $request->input('warehouse_type');
            }
            if ($request->has('address')) {
                $warehouse->address = $request->input('address');
            }
            if ($request->has('incharge_user_id')) {
                $warehouse->incharge_user_id = $request->input('incharge_user_id');
            }
            if ($request->has('is_active')) {
                $warehouse->is_active = $request->input('is_active');
            }

            $warehouse->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $warehouse = Warehouse::findOrFail($id);
            $warehouse->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Warehouse deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
