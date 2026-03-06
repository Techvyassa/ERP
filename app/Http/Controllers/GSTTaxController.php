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

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('tax_code', 'like', "%{$search}%")
                      ->orWhere('tax_name', 'like', "%{$search}%");
                });
            }

            $gstTaxes = $query->orderBy('tax_code')->get();

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
            'tax_code' => 'required|string|max:20|unique:tenant.gst_taxes,tax_code',
            'tax_name' => 'required|string|max:60',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'ugst_rate' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
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
                'tax_code' => $request->input('tax_code'),
                'tax_name' => $request->input('tax_name'),
                'cgst_rate' => $request->input('cgst_rate', 0),
                'sgst_rate' => $request->input('sgst_rate', 0),
                'igst_rate' => $request->input('igst_rate', 0),
                'ugst_rate' => $request->input('ugst_rate', 0),
                'effective_from' => $request->input('effective_from'),
                'effective_to' => $request->input('effective_to'),
                'is_active' => true,
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
            'tax_code' => "sometimes|string|max:20|unique:tenant.gst_taxes,tax_code,{$id}",
            'tax_name' => 'sometimes|string|max:60',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'ugst_rate' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'sometimes|date',
            'effective_to' => 'nullable|date|after:effective_from',
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
            $gstTax->update($request->only(['tax_code', 'tax_name', 'cgst_rate', 'sgst_rate', 'igst_rate', 'ugst_rate', 'effective_from', 'effective_to', 'is_active']));

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
