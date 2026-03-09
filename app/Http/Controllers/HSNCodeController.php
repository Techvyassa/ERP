<?php

namespace App\Http\Controllers;

use App\Models\Tenant\HSNCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HSNCodeController extends Controller
{
    /**
     * List HSN codes
     * GET /api/v1/hsn-codes
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $query = HSNCode::with('defaultGst');

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('hsn_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $hsnCodes = $query->orderBy('hsn_code')->get();

            return response()->json([
                'success' => true,
                'data' => ['hsn_codes' => $hsnCodes],
                'message' => 'HSN codes retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve HSN codes: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single HSN code
     * GET /api/v1/hsn-codes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $hsnCode = HSNCode::with('defaultGst')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_NOT_FOUND', 'details' => []],
                'message' => 'HSN code not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create HSN code
     * POST /api/v1/hsn-codes
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'hsn_code' => 'required|string|max:10|unique:tenant.hsn_codes,hsn_code',
            'description' => 'required|string|max:300',
            'default_gst_id' => 'required|integer|exists:tenant.gst_taxes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $hsnCode = HSNCode::create([
                'hsn_code' => $request->input('hsn_code'),
                'description' => $request->input('description'),
                'default_gst_id' => $request->input('default_gst_id'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update HSN code
     * PUT /api/v1/hsn-codes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'hsn_code' => "sometimes|string|max:10|unique:tenant.hsn_codes,hsn_code,{$id}",
            'description' => 'sometimes|string|max:300',
            'default_gst_id' => 'sometimes|integer|exists:tenant.gst_taxes,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $hsnCode = HSNCode::findOrFail($id);
            $hsnCode->update($request->only(['hsn_code', 'description', 'default_gst_id', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete HSN code
     * DELETE /api/v1/hsn-codes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $hsnCode = HSNCode::findOrFail($id);
            $hsnCode->is_active = false;
            $hsnCode->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'HSN code deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
