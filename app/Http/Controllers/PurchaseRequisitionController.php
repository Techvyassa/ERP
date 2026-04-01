<?php

namespace App\Http\Controllers;

use App\Models\Tenant\PurchaseRequisition;
use App\Models\Tenant\PrLineItem;
use App\Models\Tenant\Material;
use App\Models\Tenant\UOM;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\User;
use App\Models\Control\Organization;
use App\Mail\PurchaseRequisitionMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PurchaseRequisitionController extends Controller
{
    /**
     * GET /api/v1/purchase-requisitions
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $query = PurchaseRequisition::with(['requestedBy', 'department', 'suggestedVendor']);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->input('priority'));
            }
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where('pr_number', 'like', "%{$s}%")
                      ->orWhere('justification', 'like', "%{$s}%");
            }

            $perPage = $request->input('per_page', 15);
            $prs = $query->orderByDesc('id')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $prs,
                'message' => 'Purchase requisitions retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve purchase requisitions: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/purchase-requisitions/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $pr = PurchaseRequisition::with([
                'requestedBy',
                'department',
                'suggestedVendor',
                'lineItems.material.uom',
                'lineItems.uom',
                'lineItems.warehouse',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => ['purchase_requisition' => $pr],
                'message' => 'Purchase requisition retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NOT_FOUND', 'details' => []],
                'message' => 'Purchase requisition not found',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * POST /api/v1/purchase-requisitions
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Get authenticated user from session
        $authUserId = $request->input('auth_user_id');
        $authUser = User::with('department')->find($authUserId);
        
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'AUTH_ERROR', 'details' => []],
                'message' => 'Authenticated user not found',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 401);
        }

        // Check if user has a department assigned
        if (!$authUser->dept_id) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => []],
                'message' => 'User must be assigned to a department to create purchase requisitions',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'required_date'       => 'required|date|after_or_equal:today',
            'priority'            => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
            'budget_code'         => 'nullable|string|max:50',
            'suggested_vendor_id' => 'nullable|integer|exists:tenant.vendor_master,id',
            'justification'       => 'nullable|string',
            'remarks'             => 'nullable|string',
            'line_items'          => 'required|array|min:1',
            'line_items.*.item_name'            => 'required|string|max:200',
            'line_items.*.description'          => 'required|string',
            'line_items.*.quantity'             => 'required|numeric|min:0.001',
            'line_items.*.uom_id'               => 'required|integer|exists:tenant.uom_master,id',
            'line_items.*.material_id'          => 'nullable|integer|exists:tenant.material_master,id',
            'line_items.*.estimated_unit_price' => 'nullable|numeric|min:0',
            'line_items.*.warehouse_id'         => 'nullable|integer|exists:tenant.warehouse_master,id',
            'line_items.*.purpose'              => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Auto-populate from authenticated user
            $requestedBy = $authUserId;
            $departmentId = $authUser->dept_id;
            
            // Get cost center code from department
            $costCenterCode = null;
            if ($authUser->department) {
                $costCenterCode = $authUser->department->cost_center_code;
            }

            $pr = PurchaseRequisition::create([
                'pr_number'           => PurchaseRequisition::generatePrNumber(),
                'requested_by'        => $requestedBy,
                'department_id'       => $departmentId,
                'cost_center_code'    => $costCenterCode,
                'pr_date'             => now()->toDateString(),
                'required_date'       => $request->input('required_date'),
                'priority'            => $request->input('priority'),
                'budget_code'         => $request->input('budget_code'),
                'suggested_vendor_id' => $request->input('suggested_vendor_id') ?: null,
                'status'              => $request->input('status', 'DRAFT'),
                'justification'       => $request->input('justification'),
                'remarks'             => $request->input('remarks'),
                'created_by'          => $authUserId,
            ]);

            foreach ($request->input('line_items') as $idx => $lineData) {
                $unitPrice = $lineData['estimated_unit_price'] ?? null;
                PrLineItem::create([
                    'pr_id'                 => $pr->id,
                    'line_number'           => $idx + 1,
                    'material_id'           => $lineData['material_id'] ?: null,
                    'item_name'             => $lineData['item_name'],
                    'description'           => $lineData['description'],
                    'quantity'              => $lineData['quantity'],
                    'uom_id'                => $lineData['uom_id'],
                    'estimated_unit_price'  => $unitPrice,
                    'warehouse_id'          => $lineData['warehouse_id'] ?: null,
                    'purpose'               => $lineData['purpose'] ?? null,
                    'sort_order'            => $idx + 1,
                ]);
            }

            DB::commit();

            $pr->load(['requestedBy', 'department', 'lineItems.material', 'lineItems.uom']);

            return response()->json([
                'success' => true,
                'data'    => ['purchase_requisition' => $pr],
                'message' => 'Purchase requisition created successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CREATE_FAILED', 'details' => []],
                'message' => 'Failed to create purchase requisition: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/purchase-requisitions/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $pr = PurchaseRequisition::find($id);
        if (!$pr) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NOT_FOUND', 'details' => []],
                'message' => 'Purchase requisition not found',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 404);
        }

        if ($pr->status !== 'DRAFT' && $pr->status !== 'PENDING_APPROVAL') {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NOT_EDITABLE', 'details' => []],
                'message' => 'Only DRAFT or PENDING_APPROVAL purchase requisitions can be edited',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'requested_by'        => 'required|integer|exists:tenant.users,id',
            'department_id'       => 'required|integer|exists:tenant.department_master,id',
            'required_date'       => 'required|date',
            'priority'            => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
            'status'              => 'nullable|in:DRAFT,PENDING_APPROVAL,PENDING',
            'cost_center_code'    => 'nullable|string|max:20',
            'budget_code'         => 'nullable|string|max:50',
            'suggested_vendor_id' => 'nullable|integer|exists:tenant.vendor_master,id',
            'justification'       => 'nullable|string',
            'remarks'             => 'nullable|string',
            'line_items'          => 'required|array|min:1',
            'line_items.*.item_name'            => 'required|string|max:200',
            'line_items.*.description'          => 'required|string',
            'line_items.*.quantity'             => 'required|numeric|min:0.001',
            'line_items.*.uom_id'               => 'required|integer|exists:tenant.uom_master,id',
            'line_items.*.material_id'          => 'nullable|integer|exists:tenant.material_master,id',
            'line_items.*.estimated_unit_price' => 'nullable|numeric|min:0',
            'line_items.*.warehouse_id'         => 'nullable|integer|exists:tenant.warehouse_master,id',
            'line_items.*.purpose'              => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pr->update([
                'requested_by'        => $request->input('requested_by'),
                'department_id'       => $request->input('department_id'),
                'cost_center_code'    => $request->input('cost_center_code'),
                'required_date'       => $request->input('required_date'),
                'priority'            => $request->input('priority'),
                'budget_code'         => $request->input('budget_code'),
                'suggested_vendor_id' => $request->input('suggested_vendor_id') ?: null,
                'status'              => $request->input('status', 'DRAFT'),
                'justification'       => $request->input('justification'),
                'remarks'             => $request->input('remarks'),
            ]);

            // Delete existing line items and re-create
            PrLineItem::where('pr_id', $pr->id)->delete();

            foreach ($request->input('line_items') as $idx => $lineData) {
                PrLineItem::create([
                    'pr_id'                 => $pr->id,
                    'line_number'           => $idx + 1,
                    'material_id'           => $lineData['material_id'] ?: null,
                    'item_name'             => $lineData['item_name'],
                    'description'           => $lineData['description'],
                    'quantity'              => $lineData['quantity'],
                    'uom_id'                => $lineData['uom_id'],
                    'estimated_unit_price'  => $lineData['estimated_unit_price'] ?? null,
                    'warehouse_id'          => $lineData['warehouse_id'] ?: null,
                    'purpose'               => $lineData['purpose'] ?? null,
                    'sort_order'            => $idx + 1,
                ]);
            }

            DB::commit();

            $pr->load(['requestedBy', 'department', 'lineItems.material', 'lineItems.uom']);

            return response()->json([
                'success' => true,
                'data'    => ['purchase_requisition' => $pr],
                'message' => 'Purchase requisition updated successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update purchase requisition: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/purchase-requisitions/{id}/submit
     * DRAFT → PENDING_APPROVAL
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $pr = PurchaseRequisition::with('lineItems')->findOrFail($id);
            if ($pr->status !== 'DRAFT') {
                return response()->json(['success' => false, 'message' => 'Only DRAFT PRs can be submitted.', 'request_id' => $requestId], 422);
            }
            if ($pr->lineItems->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Cannot submit a PR with no line items.', 'request_id' => $requestId], 422);
            }
            $pr->update(['status' => 'PENDING_APPROVAL']);
            return response()->json(['success' => true, 'data' => ['purchase_requisition' => $pr], 'message' => 'PR submitted for approval.', 'request_id' => $requestId]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage(), 'request_id' => $requestId], 500);
        }
    }

    /**
     * PATCH /api/v1/purchase-requisitions/{id}/approve
     * PENDING_APPROVAL → APPROVED
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $pr = PurchaseRequisition::findOrFail($id);
            if ($pr->status !== 'PENDING_APPROVAL') {
                return response()->json(['success' => false, 'message' => 'Only PENDING_APPROVAL PRs can be approved.', 'request_id' => $requestId], 422);
            }
            DB::beginTransaction();
            $pr->update(['status' => 'APPROVED']);
            // Record in pr_approvals
            DB::connection('tenant')->table('pr_approvals')->updateOrInsert(
                ['pr_id' => $pr->id, 'approval_level' => 1],
                [
                    'approved_by'   => $request->input('auth_user_id'),
                    'status'        => 'APPROVED',
                    'comments'      => $request->input('comments'),
                    'approval_date' => now(),
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );
            DB::commit();
            return response()->json(['success' => true, 'data' => ['purchase_requisition' => $pr], 'message' => 'PR approved successfully.', 'request_id' => $requestId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage(), 'request_id' => $requestId], 500);
        }
    }

    /**
     * PATCH /api/v1/purchase-requisitions/{id}/reject
     * PENDING_APPROVAL → REJECTED
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()], 'message' => 'Rejection reason is required.', 'request_id' => $requestId], 422);
        }
        try {
            $pr = PurchaseRequisition::findOrFail($id);
            if ($pr->status !== 'PENDING_APPROVAL') {
                return response()->json(['success' => false, 'message' => 'Only PENDING_APPROVAL PRs can be rejected.', 'request_id' => $requestId], 422);
            }
            DB::beginTransaction();
            $pr->update(['status' => 'REJECTED']);
            DB::connection('tenant')->table('pr_approvals')->updateOrInsert(
                ['pr_id' => $pr->id, 'approval_level' => 1],
                [
                    'approved_by'   => $request->input('auth_user_id'),
                    'status'        => 'REJECTED',
                    'comments'      => $request->input('rejection_reason'),
                    'approval_date' => now(),
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );
            DB::commit();
            return response()->json(['success' => true, 'data' => ['purchase_requisition' => $pr], 'message' => 'PR rejected.', 'request_id' => $requestId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage(), 'request_id' => $requestId], 500);
        }
    }

    /**
     * GET /api/v1/purchase-requisitions/materials
     * Returns materials with uom, warehouse for line item dropdowns
     */
    public function getMaterials(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $materials = Material::with(['uom', 'purchaseUom', 'defaultWarehouse'])
                ->where('is_active', true)
                ->orderBy('material_code')
                ->get(['id', 'material_code', 'material_name', 'material_type',
                       'uom_id', 'purchase_uom_id', 'default_warehouse_id', 'standard_cost']);

            return response()->json([
                'success' => true,
                'data'    => ['materials' => $materials],
                'message' => 'Materials retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/purchase-requisitions/uoms
     */
    public function getUoms(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $uoms = UOM::where('is_active', true)->orderBy('uom_name')->get(['id', 'uom_code', 'uom_name']);
            return response()->json([
                'success' => true,
                'data'    => ['uoms' => $uoms],
                'message' => 'UOMs retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve UOMs: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/purchase-requisitions/warehouses
     */
    public function getWarehouses(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $warehouses = Warehouse::where('is_active', true)->orderBy('warehouse_name')
                ->get(['id', 'warehouse_code', 'warehouse_name']);
            return response()->json([
                'success' => true,
                'data'    => ['warehouses' => $warehouses],
                'message' => 'Warehouses retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve warehouses: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/purchase-requisitions/users
     */
    public function getUsers(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $users = User::where('is_active', true)->orderBy('first_name')
                ->get(['id', 'employee_code', 'first_name', 'last_name', 'dept_id']);
            return response()->json([
                'success' => true,
                'data'    => ['users' => $users],
                'message' => 'Users retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Send PR to vendor(s) via email
     * POST /api/v1/purchase-requisitions/{id}/send-to-vendor
     */
    public function sendToVendor(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:tenant.vendor_master,id',
            'message' => 'nullable|string',
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
            $pr = PurchaseRequisition::with([
                'requestedBy',
                'department',
                'suggestedVendor',
                'lineItems.material',
                'lineItems.uom'
            ])->findOrFail($id);

            // Only APPROVED PRs can be sent
            if ($pr->status !== 'APPROVED') {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'PR_NOT_APPROVED', 'details' => ['current_status' => $pr->status]],
                    'message' => 'Only approved purchase requisitions can be sent to vendors',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $vendorIds = $request->input('vendor_ids');
            $customMessage = $request->input('message');
            $sentCount = 0;
            $errors = [];

            // Resolve org name and slug from tenant DB name
            $orgName = config('app.name');
            $orgSlug = '';
            $tenantDb = $request->input('tenant_db_name');
            $org = null;
            if ($tenantDb) {
                $org = Organization::where('tenant_db_name', $tenantDb)->first();
                if ($org) {
                    $orgName = $org->org_name;
                    $orgSlug = $org->org_slug;
                }
            }

            foreach ($vendorIds as $vendorId) {
                $vendor = \App\Models\Tenant\Vendor::with('contacts')->find($vendorId);
                
                if (!$vendor) {
                    $errors[] = "Vendor ID {$vendorId} not found";
                    continue;
                }

                // Find primary active contact with email
                $contact = $vendor->contacts
                    ->where('is_active', true)
                    ->where('is_primary', true)
                    ->whereNotNull('email')
                    ->first()
                    ?? $vendor->contacts
                        ->where('is_active', true)
                        ->whereNotNull('email')
                        ->first();

                if (!$contact || !$contact->email) {
                    $errors[] = "No email found for vendor: {$vendor->vendor_name}";
                    continue;
                }

                // Send email
                try {
                    Mail::to($contact->email, $contact->contact_name)
                        ->send(new PurchaseRequisitionMail(
                            $pr,
                            $contact->contact_name,
                            $orgName,
                            $customMessage,
                            \App\Http\Controllers\VendorPortalController::generatePRToken($orgSlug, $pr->id)
                        ));
                    
                    Log::info("PR {$pr->pr_number} sent to {$contact->email} ({$contact->contact_name})");
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to send to {$vendor->vendor_name}: " . $e->getMessage();
                }
            }

            if ($sentCount === 0) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'EMAIL_SEND_FAILED', 'details' => $errors],
                    'message' => 'Failed to send PR to any vendors. ' . implode('; ', $errors),
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sent_count' => $sentCount,
                    'errors' => $errors
                ],
                'message' => "PR sent to {$sentCount} vendor(s) successfully",
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'SEND_FAILED', 'details' => []],
                'message' => 'Failed to send PR: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
