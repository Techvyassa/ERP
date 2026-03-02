<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use App\Jobs\ProvisionTenantJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    /**
     * Register a new organization
     * POST /api/v1/organizations/register
     */
    public function register(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'org_name' => 'required|string|max:255',
            'org_slug' => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:organizations,org_slug',
            'primary_email' => 'required|email|max:255|unique:organizations,primary_email',
            'primary_phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'required|string|size:2',
            'timezone' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'max_users' => 'nullable|integer|min:1',
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
            DB::connection('control')->beginTransaction();

            // Create organization with PENDING status
            $organization = Organization::create([
                'org_slug' => $request->input('org_slug'),
                'org_name' => $request->input('org_name'),
                'tenant_db_name' => 'erp_' . $request->input('org_slug'),
                'registration_status' => 'PENDING',
                'primary_email' => $request->input('primary_email'),
                'primary_phone' => $request->input('primary_phone'),
                'address_line1' => $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'country_code' => $request->input('country_code'),
                'timezone' => $request->input('timezone', 'UTC'),
                'currency_code' => $request->input('currency_code', 'USD'),
                'max_users' => $request->input('max_users', 10),
            ]);

            DB::connection('control')->commit();

            // Queue tenant provisioning job (after commit)
            ProvisionTenantJob::dispatch($organization->org_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'org_id' => $organization->org_id,
                    'org_slug' => $organization->org_slug,
                    'org_name' => $organization->org_name,
                    'registration_status' => $organization->registration_status,
                    'tenant_db_name' => $organization->tenant_db_name,
                ],
                'message' => 'Organization registered successfully. Provisioning in progress.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            // Only rollback if transaction is still active
            if (DB::connection('control')->transactionLevel() > 0) {
                DB::connection('control')->rollBack();
            }
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to register organization: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
