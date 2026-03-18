<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreMaterialReceiptRequest;
use App\Http\Requests\Tenant\UpdateMaterialReceiptRequest;
use App\Models\Tenant\MaterialReceipt;
use App\Services\MaterialReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialReceiptController extends Controller
{
    protected MaterialReceiptService $service;

    public function __construct(MaterialReceiptService $service)
    {
        $this->service = $service;
    }

    /**
     * List all material receipts
     */
    public function index(Request $request): JsonResponse
    {
        $query = MaterialReceipt::with([
            'gateEntry', 
            'purchaseOrder', 
            'vendor', 
            'lineItems.material',
            'lineItems.uom',
            'creator'
        ]);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by PO
        if ($request->has('po_id')) {
            $query->where('po_id', $request->po_id);
        }
        
        // Filter by gate entry
        if ($request->has('ge_id')) {
            $query->where('ge_id', $request->ge_id);
        }
        
        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }
        
        $receipts = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $receipts,
            'message' => 'Material receipts retrieved successfully',
        ]);
    }

    /**
     * Get single material receipt
     */
    public function show(int $id): JsonResponse
    {
        $receipt = MaterialReceipt::with([
            'gateEntry',
            'purchaseOrder.lineItems',
            'vendor',
            'lineItems.material',
            'lineItems.uom',
            'lineItems.provisionalBin',
            'creator',
            'updater'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $receipt,
            'message' => 'Material receipt retrieved successfully',
        ]);
    }

    /**
     * Create material receipt
     */
    public function store(StoreMaterialReceiptRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $receipt = $this->service->createMaterialReceipt($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'data' => $receipt,
                'message' => 'Material receipt created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MR_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create material receipt: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update material receipt
     */
    public function update(int $id, UpdateMaterialReceiptRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $receipt = $this->service->updateMaterialReceipt($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'data' => $receipt,
                'message' => 'Material receipt updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MR_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update material receipt: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get MRs by gate entry
     */
    public function byGateEntry(int $geId): JsonResponse
    {
        $receipts = MaterialReceipt::with([
            'lineItems.material',
            'lineItems.uom',
            'purchaseOrder', 
            'vendor'
        ])
            ->byGateEntry($geId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $receipts,
            'message' => 'Material receipts retrieved successfully',
        ]);
    }

    /**
     * Get MRs by PO
     */
    public function byPO(int $poId): JsonResponse
    {
        $receipts = MaterialReceipt::with([
            'lineItems.material',
            'lineItems.uom',
            'gateEntry', 
            'vendor'
        ])
            ->byPO($poId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $receipts,
            'message' => 'Material receipts retrieved successfully',
        ]);
    }

    /**
     * Get pending GRN
     */
    public function pendingGRN(Request $request): JsonResponse
    {
        $receipts = MaterialReceipt::with([
            'gateEntry', 
            'purchaseOrder', 
            'vendor', 
            'lineItems.material',
            'lineItems.uom'
        ])
            ->pendingGRN()
            ->orderBy('created_at', 'asc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $receipts,
            'message' => 'Pending GRN receipts retrieved successfully',
        ]);
    }

    /**
     * Start unloading
     */
    public function startUnloading(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $receipt = $this->service->startUnloading($id, $userId);
            
            return response()->json([
                'success' => true,
                'data' => $receipt,
                'message' => 'Unloading started successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'START_UNLOADING_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to start unloading: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete unloading
     */
    public function completeUnloading(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $receipt = $this->service->completeUnloading($id, $userId);
            
            return response()->json([
                'success' => true,
                'data' => $receipt,
                'message' => 'Unloading completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPLETE_UNLOADING_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to complete unloading: ' . $e->getMessage(),
            ], 400);
        }
    }
}
