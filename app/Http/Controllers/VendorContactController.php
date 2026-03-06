<?php

namespace App\Http\Controllers;

use App\Models\Tenant\VendorContact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorContactController extends Controller
{
    /**
     * List contacts (optionally filter by vendor_id)
     * GET /api/v1/vendor-contacts
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = VendorContact::with(['vendor']);

            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->input('vendor_id'));
            }
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('contact_type')) {
                $query->where('contact_type', $request->input('contact_type'));
            }

            $contacts = $query->get();

            return response()->json([
                'success' => true,
                'data' => ['contacts' => $contacts],
                'message' => 'Vendor contacts retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve contacts: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get single contact
     * GET /api/v1/vendor-contacts/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $contact = VendorContact::with(['vendor'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['contact' => $contact],
                'message' => 'Vendor contact retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CONTACT_NOT_FOUND', 'details' => []],
                'message' => 'Vendor contact not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * Create vendor contact
     * POST /api/v1/vendor-contacts
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_id'    => 'required|integer|exists:tenant.vendor_master,id',
            'contact_name' => 'required|string|max:100',
            'contact_type' => 'nullable|string|max:20',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:150',
            'is_primary'   => 'boolean',
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
            $contact = VendorContact::create([
                'vendor_id'    => $request->input('vendor_id'),
                'contact_name' => $request->input('contact_name'),
                'contact_type' => $request->input('contact_type', 'SALES'),
                'phone'        => $request->input('phone'),
                'email'        => $request->input('email'),
                'is_primary'   => $request->input('is_primary', false),
                'is_active'    => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['contact' => $contact],
                'message' => 'Vendor contact created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CONTACT_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create vendor contact: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Update vendor contact
     * PUT /api/v1/vendor-contacts/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'contact_name' => 'sometimes|string|max:100',
            'contact_type' => 'sometimes|string|max:20',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:150',
            'is_primary'   => 'sometimes|boolean',
            'is_active'    => 'sometimes|boolean',
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
            $contact = VendorContact::findOrFail($id);

            foreach (['contact_name', 'contact_type', 'phone', 'email', 'is_primary', 'is_active'] as $field) {
                if ($request->has($field)) {
                    $contact->$field = $request->input($field);
                }
            }
            $contact->save();

            return response()->json([
                'success' => true,
                'data' => ['contact' => $contact],
                'message' => 'Vendor contact updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CONTACT_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update vendor contact: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Delete vendor contact
     * DELETE /api/v1/vendor-contacts/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $contact = VendorContact::findOrFail($id);
            $contact->is_active = false;
            $contact->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Vendor contact deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CONTACT_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate vendor contact: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
