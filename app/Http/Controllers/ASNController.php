<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ASN;
use App\Services\ASNService;
use App\Http\Requests\Tenant\StoreASNRequest;
use App\Http\Requests\Tenant\UpdateASNRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ASNController extends Controller
{
    public function __construct(
        private ASNService $asnService
    ) {}

    /**
     * List all ASNs with filters
     * GET /api/v1/asn
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = ASN::with(['purchaseOrder', 'vendor', 'warehouse', 'lineItems']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->input('vendor_id'));
            }

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->input('warehouse_id'));
            }

            if ($request->has('po_id')) {
                $query->where('po_id', $request->input('po_id'));
            }

            if ($request->has('from_date')) {
                $query->whereDate('ship_date', '>=', $request->input('from_date'));
            }

            if ($request->has('to_date')) {
                $query->whereDate('ship_date', '<=', $request->input('to_date'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('asn_number', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_number', 'like', "%{$search}%");
                });
            }

            $asns = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => [
                    'asns' => $asns->items(),
                    'pagination' => [
                        'current_page' => $asns->currentPage(),
                        'per_page' => $asns->perPage(),
                        'total' => $asns->total(),
                        'last_page' => $asns->lastPage(),
                    ]
                ],
                'message' => 'ASNs retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve ASNs: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single ASN
     * GET /api/v1/asn/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $asn = ASN::with([
                'purchaseOrder',
                'vendor',
                'warehouse',
                'lineItems.material',
                'lineItems.uom',
                'lineItems.poLineItem',
                'documents',
                'creator',
                'updater'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'asn' => $asn
                ],
                'message' => 'ASN retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASN_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'ASN not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create new ASN
     * POST /api/v1/asn
     */
    public function store(StoreASNRequest $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $asn = $this->asnService->createASN($request->validated(), $userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'asn' => $asn
                ],
                'message' => 'ASN created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASN_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create ASN: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update ASN
     * PUT /api/v1/asn/{id}
     */
    public function update(UpdateASNRequest $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $asn = $this->asnService->updateASN($id, $request->validated(), $userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'asn' => $asn
                ],
                'message' => 'ASN updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASN_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update ASN: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Cancel ASN
     * DELETE /api/v1/asn/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $this->asnService->cancelASN($id, $userId);

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'ASN cancelled successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASN_CANCEL_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to cancel ASN: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Mark ASN as sent
     * PATCH /api/v1/asn/{id}/send
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $asn = $this->asnService->changeStatus($id, 'SENT', $userId);

            return response()->json([
                'success' => true,
                'data' => ['asn' => $asn],
                'message' => 'ASN marked as sent',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'STATUS_CHANGE_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 400);
        }
    }

    /**
     * Mark ASN as in transit
     * PATCH /api/v1/asn/{id}/in-transit
     */
    public function markInTransit(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $asn = $this->asnService->changeStatus($id, 'IN_TRANSIT', $userId);

            return response()->json([
                'success' => true,
                'data' => ['asn' => $asn],
                'message' => 'ASN marked as in transit',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'STATUS_CHANGE_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 400);
        }
    }

    /**
     * Mark ASN as arrived
     * PATCH /api/v1/asn/{id}/arrived
     */
    public function markArrived(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $userId = $request->input('auth_user_id');
            $data = $request->only(['actual_arrival']);
            $asn = $this->asnService->changeStatus($id, 'ARRIVED', $userId, $data);

            return response()->json([
                'success' => true,
                'data' => ['asn' => $asn],
                'message' => 'ASN marked as arrived',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'STATUS_CHANGE_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 400);
        }
    }

    /**
     * Get ASNs arriving today
     * GET /api/v1/asn/arriving-today
     */
    public function arrivingToday(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $asns = ASN::with(['purchaseOrder', 'vendor', 'warehouse'])
                ->arrivingToday()
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['asns' => $asns],
                'message' => 'ASNs arriving today retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get overdue ASNs
     * GET /api/v1/asn/overdue
     */
    public function overdue(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $asns = ASN::with(['purchaseOrder', 'vendor', 'warehouse'])
                ->overdue()
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['asns' => $asns],
                'message' => 'Overdue ASNs retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get ASNs by PO
     * GET /api/v1/asn/by-po/{poId}
     */
    public function getByPO(Request $request, int $poId): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $asns = ASN::with(['vendor', 'warehouse', 'lineItems'])
                ->byPO($poId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['asns' => $asns],
                'message' => 'ASNs retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Upload ASN via CSV for a specific PO
     * POST /api/v1/asn/upload-csv
     * CSV columns: po_line_id, material_id, shipped_qty, uom_id, batch_number, lot_number, manufacturing_date, expiry_date
     */
    public function uploadCSV(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $request->validate([
            'file'         => 'required|file|mimes:csv,txt|max:2048',
            'po_id'        => 'required|integer',
            'vendor_id'    => 'required|integer',
            'warehouse_id' => 'required|integer',
            'ship_date'    => 'required|date',
            'eta'          => 'required|date',
            'carrier_name'     => 'nullable|string|max:100',
            'tracking_number'  => 'nullable|string|max:100',
            'vehicle_number'   => 'nullable|string|max:50',
            'remarks'          => 'nullable|string|max:500',
        ]);

        try {
            $userId = $request->input('auth_user_id');

            // Parse CSV
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            $headers = array_map('trim', fgetcsv($handle));

            $required = ['po_line_id', 'material_id', 'shipped_qty', 'uom_id'];
            foreach ($required as $col) {
                if (!in_array($col, $headers)) {
                    fclose($handle);
                    return response()->json([
                        'success' => false,
                        'error' => ['code' => 'INVALID_CSV', 'details' => []],
                        'message' => "CSV missing required column: {$col}. Required: " . implode(', ', $required),
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }
            }

            $lineItems = [];
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) < count($headers)) continue;
                $data = array_combine($headers, array_map('trim', $row));

                if (empty($data['po_line_id']) || empty($data['material_id']) || empty($data['shipped_qty']) || empty($data['uom_id'])) {
                    fclose($handle);
                    return response()->json([
                        'success' => false,
                        'error' => ['code' => 'INVALID_CSV_ROW', 'details' => ['row' => $rowNum]],
                        'message' => "Row {$rowNum}: po_line_id, material_id, shipped_qty, uom_id are required",
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

                $lineItems[] = [
                    'po_line_id'         => (int) $data['po_line_id'],
                    'material_id'        => (int) $data['material_id'],
                    'shipped_qty'        => (float) $data['shipped_qty'],
                    'uom_id'             => (int) $data['uom_id'],
                    'batch_number'       => $data['batch_number'] ?? null,
                    'lot_number'         => $data['lot_number'] ?? null,
                    'manufacturing_date' => !empty($data['manufacturing_date']) ? $data['manufacturing_date'] : null,
                    'expiry_date'        => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                    'pallet_id'          => $data['pallet_id'] ?? null,
                    'sscc'               => $data['sscc'] ?? null,
                    'gross_weight'       => !empty($data['gross_weight']) ? (float) $data['gross_weight'] : null,
                    'net_weight'         => !empty($data['net_weight']) ? (float) $data['net_weight'] : null,
                ];
            }
            fclose($handle);

            if (empty($lineItems)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'EMPTY_CSV', 'details' => []],
                    'message' => 'CSV file has no data rows',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $asnData = [
                'po_id'          => (int) $request->input('po_id'),
                'vendor_id'      => (int) $request->input('vendor_id'),
                'warehouse_id'   => (int) $request->input('warehouse_id'),
                'ship_date'      => $request->input('ship_date'),
                'eta'            => $request->input('eta'),
                'carrier_name'   => $request->input('carrier_name'),
                'tracking_number'=> $request->input('tracking_number'),
                'vehicle_number' => $request->input('vehicle_number'),
                'remarks'        => $request->input('remarks'),
                'line_items'     => $lineItems,
            ];

            $asn = $this->asnService->createASN($asnData, $userId);

            return response()->json([
                'success' => true,
                'data' => ['asn' => $asn],
                'message' => 'ASN created from CSV with ' . count($lineItems) . ' line item(s)',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ASN_CSV_FAILED', 'details' => []],
                'message' => 'Failed to create ASN: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
