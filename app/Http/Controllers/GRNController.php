<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreGRNRequest;
use App\Http\Requests\Tenant\UpdateGRNRequest;
use App\Models\Tenant\GRN;
use App\Services\GRNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GRNController extends Controller
{
    protected GRNService $grnService;

    public function __construct(GRNService $grnService)
    {
        $this->grnService = $grnService;
    }

    /**
     * List all GRNs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = GRN::with(['materialReceipt', 'purchaseOrder', 'vendor', 'lineItems']);
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by date range
            if ($request->has('grn_date_from')) {
                $query->where('grn_date', '>=', $request->grn_date_from);
            }
            if ($request->has('grn_date_to')) {
                $query->where('grn_date', '<=', $request->grn_date_to);
            }
            
            $perPage = $request->input('per_page', 15);
            $grns = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single GRN
     */
    public function show(int $id): JsonResponse
    {
        try {
            $grn = GRN::with([
                'lineItems.material.hsnCode',
                'lineItems.uom',
                'lineItems.warehouseBin',
                'lineItems.mrLineItem.poLineItem',
                'materialReceipt.creator',
                'purchaseOrder',
                'vendor',
                'creator',
                'approver'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $grn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'GRN not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create new GRN
     */
    public function store(StoreGRNRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $grn = $this->grnService->createGRN($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'GRN created successfully',
                'data' => $grn,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create GRN: ' . $e->getMessage(),
                'error' => [
                    'code' => 'GRN_CREATE_FAILED',
                    'details' => [],
                ],
            ], 500);
        }
    }

    /**
     * Update GRN
     */
    public function update(int $id, UpdateGRNRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $grn = $this->grnService->updateGRN($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'GRN updated successfully',
                'data' => $grn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update GRN: ' . $e->getMessage(),
                'error' => [
                    'code' => 'GRN_UPDATE_FAILED',
                    'details' => [],
                ],
            ], 500);
        }
    }

    /**
     * Get GRNs by Material Receipt
     */
    public function byMaterialReceipt(int $mrId): JsonResponse
    {
        try {
            $grns = GRN::with(['lineItems', 'purchaseOrder', 'vendor'])
                ->byMR($mrId)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get GRNs by Purchase Order
     */
    public function byPO(int $poId): JsonResponse
    {
        try {
            $grns = GRN::with(['lineItems', 'materialReceipt', 'vendor'])
                ->byPO($poId)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get GRNs by Vendor
     */
    public function byVendor(int $vendorId): JsonResponse
    {
        try {
            $grns = GRN::with(['lineItems', 'materialReceipt', 'purchaseOrder'])
                ->byVendor($vendorId)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get provisional GRNs
     */
    public function provisional(): JsonResponse
    {
        try {
            $grns = GRN::with(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor'])
                ->provisional()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provisional GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QC pending GRNs
     */
    public function qcPending(): JsonResponse
    {
        try {
            $grns = GRN::with(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor'])
                ->qcPending()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $grns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch QC pending GRNs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve GRN
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $grn = $this->grnService->approveGRN($id, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'GRN approved successfully',
                'data' => $grn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve GRN: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel GRN
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        try {
            $userId = $request->input('auth_user_id');
            $grn = $this->grnService->cancelGRN($id, $request->reason, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'GRN cancelled successfully',
                'data' => $grn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel GRN: ' . $e->getMessage(),
            ], 500);
        }
    }
}
