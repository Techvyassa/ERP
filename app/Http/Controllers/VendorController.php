<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    /**
     * List vendors
     * GET /api/v1/vendors
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Vendor::with(['currency', 'contacts']);

            if ($request->has('vendor_type')) {
                $query->where('vendor_type', $request->input('vendor_type'));
            }
            if ($request->has('is_approved')) {
                $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('blacklisted')) {
                $query->where('blacklisted', filter_var($request->input('blacklisted'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('vendor_code', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $vendors = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'vendors' => $vendors->items(),
                    'pagination' => [
                        'current_page' => $vendors->currentPage(),
                        'per_page' => $vendors->perPage(),
                        'total' => $vendors->total(),
                        'last_page' => $vendors->lastPage(),
                    ],
                ],
                'message' => 'Vendors retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve vendors: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get single vendor
     * GET /api/v1/vendors/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $vendor = Vendor::with(['currency', 'contacts', 'materialMaps.material'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_NOT_FOUND', 'details' => []],
                'message' => 'Vendor not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * Create vendor
     * POST /api/v1/vendors
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_code'      => 'required|string|max:20|unique:tenant.vendor_master,vendor_code',
            'vendor_name'      => 'required|string|max:200',
            'vendor_type'      => 'nullable|string|max:20',
            'gstin'            => 'nullable|string|max:20|unique:tenant.vendor_master,gstin',
            'pan_number'       => 'nullable|string|max:10',
            'msme_category'    => 'nullable|string|max:10',
            'payment_terms'    => 'nullable|string|max:30',
            'credit_days'      => 'nullable|integer|min:0',
            'currency_id'      => 'required|integer|exists:tenant.currency_master,id',
            'delivery_terms'   => 'nullable|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'bank_account_no'  => 'nullable|string|max:30',
            'ifsc_code'        => 'nullable|string|max:11',
            'rating_score'     => 'nullable|numeric|min:0|max:100',
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
            $vendor = Vendor::create(array_merge(
                $request->only([
                    'vendor_code',
                    'vendor_name',
                    'vendor_type',
                    'gstin',
                    'pan_number',
                    'msme_category',
                    'payment_terms',
                    'credit_days',
                    'currency_id',
                    'delivery_terms',
                    'bank_name',
                    'bank_account_no',
                    'ifsc_code',
                    'rating_score',
                ]),
                ['is_approved' => false, 'blacklisted' => false]
            ));

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Update vendor
     * PUT /api/v1/vendors/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_code'      => 'sometimes|string|max:20|unique:tenant.vendor_master,vendor_code,' . $id . ',id',
            'vendor_name'      => 'sometimes|string|max:200',
            'vendor_type'      => 'sometimes|string|max:20',
            'gstin'            => 'nullable|string|max:20|unique:tenant.vendor_master,gstin,' . $id . ',id',
            'pan_number'       => 'nullable|string|max:10',
            'msme_category'    => 'nullable|string|max:10',
            'payment_terms'    => 'nullable|string|max:30',
            'credit_days'      => 'nullable|integer|min:0',
            'currency_id'      => 'sometimes|integer|exists:tenant.currency_master,id',
            'delivery_terms'   => 'nullable|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'bank_account_no'  => 'nullable|string|max:30',
            'ifsc_code'        => 'nullable|string|max:11',
            'is_approved'      => 'sometimes|boolean',
            'approved_date'    => 'nullable|date',
            'rating_score'     => 'nullable|numeric|min:0|max:100',
            'blacklisted'      => 'sometimes|boolean',
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
            $vendor = Vendor::findOrFail($id);

            $fields = [
                'vendor_code',
                'vendor_name',
                'vendor_type',
                'gstin',
                'pan_number',
                'msme_category',
                'payment_terms',
                'credit_days',
                'currency_id',
                'delivery_terms',
                'bank_name',
                'bank_account_no',
                'ifsc_code',
                'is_approved',
                'approved_date',
                'rating_score',
                'blacklisted',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $vendor->$field = $request->input($field);
                }
            }

            // Auto-set approved_by when approving
            if ($request->has('is_approved') && $request->input('is_approved')) {
                $vendor->approved_by = $request->input('auth_user_id');
                if (!$vendor->approved_date) {
                    $vendor->approved_date = now()->toDateString();
                }
            }

            $vendor->save();

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Deactivate vendor (blacklist)
     * DELETE /api/v1/vendors/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->blacklisted = true;
            $vendor->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Vendor blacklisted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to blacklist vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
