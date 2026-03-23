<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreGateEntryRequest;
use App\Models\Tenant\GateEntry;
use App\Services\GateEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GateEntryController extends Controller
{
    protected GateEntryService $service;

    public function __construct(GateEntryService $service)
    {
        $this->service = $service;
    }

    /**
     * List all gate entries
     */
    public function index(Request $request): JsonResponse
    {
        $query = GateEntry::with(['purchaseOrder', 'vendor', 'creator', 'grn']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->has('po_id')) {
            $query->where('po_id', $request->po_id);
        }
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('arrived_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59',
            ]);
        }

        $entries = $query->orderBy('arrived_at', 'desc')->paginate(
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data'    => $entries,
            'message' => 'Gate entries retrieved successfully',
        ]);
    }

    /**
     * Get single gate entry
     */
    public function show(int $id): JsonResponse
    {
        $entry = GateEntry::with(['purchaseOrder', 'vendor', 'creator', 'grn.lineItems'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $entry,
            'message' => 'Gate entry retrieved successfully',
        ]);
    }

    /**
     * Create gate entry
     */
    public function store(StoreGateEntryRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $entry = $this->service->createGateEntry($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Gate entry created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GATE_ENTRY_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create gate entry: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get entries by vendor
     */
    public function byVendor(int $vendorId): JsonResponse
    {
        $entries = GateEntry::with(['purchaseOrder', 'vendor', 'grn'])
            ->byVendor($vendorId)
            ->orderBy('arrived_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $entries,
            'message' => 'Vendor gate entries retrieved successfully',
        ]);
    }

    /**
     * Get entries by PO
     */
    public function byPO(int $poId): JsonResponse
    {
        $entries = GateEntry::with(['purchaseOrder', 'vendor', 'grn'])
            ->byPO($poId)
            ->orderBy('arrived_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $entries,
            'message' => 'PO gate entries retrieved successfully',
        ]);
    }
}
