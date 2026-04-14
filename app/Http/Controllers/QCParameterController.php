<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreQCParameterRequest;
use App\Models\Tenant\QCParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QCParameterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QCParameter::with(['material', 'product', 'testType']);

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->integer('material_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('test_type_id')) {
            $query->where('test_type_id', $request->integer('test_type_id'));
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('parameter_code', 'like', "%{$search}%")
                    ->orWhere('parameter_name', 'like', "%{$search}%")
                    ->orWhere('test_method', 'like', "%{$search}%");
            });
        }

        $parameters = $query
            ->orderBy('display_order')
            ->orderBy('parameter_code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parameters,
            'message' => 'QC parameters retrieved successfully',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $parameter = QCParameter::with(['material', 'testType'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $parameter,
            'message' => 'QC parameter retrieved successfully',
        ]);
    }

    public function store(StoreQCParameterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $parameter = QCParameter::create([
                ...$validated,
                'parameter_code' => strtoupper($validated['parameter_code']),
                'is_critical' => $validated['is_critical'] ?? false,
                'display_order' => $validated['display_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => $request->input('auth_user_id'),
            ]);

            return response()->json([
                'success' => true,
                'data' => $parameter->load(['material', 'testType']),
                'message' => 'QC parameter created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_CREATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    public function update(int $id, StoreQCParameterRequest $request): JsonResponse
    {
        try {
            $parameter = QCParameter::findOrFail($id);
            $validated = $request->validated();

            $parameter->update([
                ...$validated,
                'parameter_code' => strtoupper($validated['parameter_code']),
                'is_critical' => $validated['is_critical'] ?? false,
                'display_order' => $validated['display_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? $parameter->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data' => $parameter->fresh()->load(['material', 'testType']),
                'message' => 'QC parameter updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_UPDATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $parameter = QCParameter::findOrFail($id);
            $parameterName = $parameter->parameter_name;
            $parameter->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'QC parameter deactivated successfully',
                'data' => [
                    'id' => $id,
                    'parameter_name' => $parameterName,
                    'is_active' => false,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_DELETE_FAILED', 'details' => []],
            ], 500);
        }
    }
}
