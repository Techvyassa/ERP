<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreQCTestTypeRequest;
use App\Models\Tenant\QCTestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QCTestTypeController extends Controller
{
    /**
     * List all QC Test Types
     */
    public function index(Request $request): JsonResponse
    {
        $query = QCTestType::with('creator');

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        $testTypes = $query->orderBy('type_code')->get();

        return response()->json([
            'success' => true,
            'data'    => $testTypes,
            'message' => 'QC test types retrieved successfully',
        ]);
    }

    /**
     * Get single QC Test Type
     */
    public function show(int $id): JsonResponse
    {
        $testType = QCTestType::with(['creator', 'qcParameters'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $testType,
            'message' => 'QC test type retrieved successfully',
        ]);
    }

    /**
     * Create QC Test Type
     */
    public function store(StoreQCTestTypeRequest $request): JsonResponse
    {
        try {
            $userId   = $request->input('auth_user_id');
            $validated = $request->validated();

            $testType = QCTestType::create([
                'type_code'   => strtoupper($validated['type_code']),
                'type_name'   => $validated['type_name'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? true,
                'created_by'  => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $testType->load('creator'),
                'message' => 'QC test type created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QC test type: ' . $e->getMessage(),
                'error'   => ['code' => 'QC_TEST_TYPE_CREATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    /**
     * Update QC Test Type
     */
    public function update(int $id, StoreQCTestTypeRequest $request): JsonResponse
    {
        try {
            $testType  = QCTestType::findOrFail($id);
            $validated = $request->validated();

            $testType->update([
                'type_code'   => strtoupper($validated['type_code']),
                'type_name'   => $validated['type_name'],
                'description' => $validated['description'] ?? $testType->description,
                'is_active'   => $validated['is_active'] ?? $testType->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $testType->fresh()->load('creator'),
                'message' => 'QC test type updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update QC test type: ' . $e->getMessage(),
                'error'   => ['code' => 'QC_TEST_TYPE_UPDATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    /**
     * Deactivate QC Test Type
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $testType = QCTestType::findOrFail($id);
            $testType->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'QC test type deactivated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate QC test type: ' . $e->getMessage(),
            ], 500);
        }
    }
}
