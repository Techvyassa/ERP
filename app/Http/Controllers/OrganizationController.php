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
     * Check if organization slug is available
     * GET /api/v1/organizations/check-slug/{slug}
     */
    public function checkSlug(string $slug): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return response()->json([
                'success' => false,
                'data' => [
                    'available' => false,
                    'message' => 'Slug must contain only lowercase letters, numbers, and hyphens'
                ],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        }
        
        $exists = Organization::where('org_slug', $slug)->exists();
        
        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$exists,
                'slug' => $slug,
                'message' => $exists ? 'This slug is already taken' : 'This slug is available'
            ],
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String()
        ], 200);
    }
    
    /**
     * Generate a suggested slug from organization name
     * POST /api/v1/organizations/suggest-slug
     */
    public function suggestSlug(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'org_name' => 'required|string|max:255',
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
        
        $orgName = $request->input('org_name');
        $baseSlug = Str::slug($orgName);
        $slug = $baseSlug;
        $counter = 1;
        
        // Find available slug
        while (Organization::where('org_slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'suggested_slug' => $slug,
                'base_slug' => $baseSlug,
            ],
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String()
        ], 200);
    }

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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required_without:firebase_uid|string|min:8',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'required|string|size:2',
            'timezone' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'max_users' => 'nullable|integer|min:1',
            'firebase_uid' => 'nullable|string',
            'firebase_token' => 'nullable|string',
            'provider' => 'nullable|string|in:google,email',
            'photo_url' => 'nullable|string',
            'selected_plan' => 'nullable|string',
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
                'tenant_db_name' => 'erp_' . str_replace('-', '_', $request->input('org_slug')),
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

            // Provision tenant immediately (synchronous) for better error handling
            $provisioningService = app(\App\Contracts\TenantProvisioningService::class);
            $result = $provisioningService->provisionTenant(
                $organization->org_id,
                [
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'email' => $request->input('primary_email'),
                    'password' => $request->input('password'),
                    'firebase_uid' => $request->input('firebase_uid'),
                    'provider' => $request->input('provider', 'email'),
                    'photo_url' => $request->input('photo_url'),
                ]
            );
            
            if (!$result->success) {
                throw new \Exception("Provisioning failed: " . $result->errorMessage);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'org_id' => $organization->org_id,
                    'org_slug' => $organization->org_slug,
                    'org_name' => $organization->org_name,
                    'registration_status' => 'ACTIVE', // Now ACTIVE after successful provisioning
                    'tenant_db_name' => $organization->tenant_db_name,
                    'primary_email' => $organization->primary_email,
                    'organization_url' => url('/' . $organization->org_slug),
                ],
                'message' => 'Organization registered and provisioned successfully. You can now login.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            // Only rollback if transaction is still active
            if (DB::connection('control')->transactionLevel() > 0) {
                DB::connection('control')->rollBack();
            }
            
            \Log::error('Organization registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
