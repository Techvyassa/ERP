<?php

namespace App\Http\Controllers;

use App\Models\Tenant\GSTTax;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GSTTaxController extends Controller
{
    /**
     * List GST taxes
     * GET /api/v1/gst-taxes
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $query = GSTTax::query();

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('tax_type')) {
                $query->where('tax_type', $request->input('tax_type'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('tax_name', 'like', "%{$search}%");
            }

            $gstTaxes = $query->orderBy('tax_name')->get();

            return response()->json([
                'success' => true,
                'data' => ['gst_taxes' => $gstTaxes],
                'message' => 'GST taxes retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve GST taxes: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single GST tax
     * GET /api/v1/gst-taxes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $gstTax = GSTTax::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['gst_tax' => $gstTax],
                'message' => 'GST tax retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'GST_TAX_NOT_FOUND', 'details' => []],
                'message' => 'GST tax not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create GST tax
     * POST /api/v1/gst-taxes
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'tax_name' => 'required|string|max:100',
            'tax_type' => 'required|in:CGST_SGST,IGST,CESS',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'cess_rate' => 'nullable|numeric|min:0|max:100',
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
            $gstTax = GSTTax::create([
                'tax_name' => $request->input('tax_name'),
                'tax_type' => $request->input('tax_type'),
                'cgst_rate' => $request->input('cgst_rate', 0),
                'sgst_rate' => $request->input('sgst_rate', 0),
                'igst_rate' => $request->input('igst_rate', 0),
                'cess_rate' => $request->input('cess_rate', 0),
                'is_active' => true,
                'created_by' => $request->attributes->get('user_id'),
            ]);

            return response()->json([
                'success' => true,
                'data' => ['gst_tax' => $gstTax],
                'message' => 'GST tax created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'GST_TAX_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create GST tax: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update GST tax
     * PUT /api/v1/gst-taxes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'tax_name' => 'sometimes|string|max:100',
            'tax_type' => 'sometimes|in:CGST_SGST,IGST,CESS',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'cess_rate' => 'nullable|numeric|min:0|max:100',
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
            $gstTax = GSTTax::findOrFail($id);
            $gstTax->update($request->only(['tax_name', 'tax_type', 'cgst_rate', 'sgst_rate', 'igst_rate', 'cess_rate', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['gst_tax' => $gstTax],
                'message' => 'GST tax updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'GST_TAX_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update GST tax: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete GST tax
     * DELETE /api/v1/gst-taxes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $gstTax = GSTTax::findOrFail($id);
            $gstTax->is_active = false;
            $gstTax->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'GST tax deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'GST_TAX_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate GST tax: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
