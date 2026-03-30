<?php

namespace App\Http\Controllers;

use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\BinLocation;
use App\Models\Tenant\Material;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialIssueRequestController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    private function switchTenantDb(Request $request): void
    {
        $dbName = $request->input('tenant_db_name');
        if (!$dbName) return;
        config(['database.connections.tenant.database' => $dbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    private function formatMir(MaterialIssueRequest $m): array
    {
        return [
            'id'               => $m->id,
            'mir_no'           => $m->mir_no,
            'order_no'         => $m->productionOrder?->order_no,
            'product_name'     => $m->productionOrder?->product?->product_name,
            'product_code'     => $m->productionOrder?->product?->product_code,
            'target_qty'       => $m->productionOrder?->target_qty,
            'uom'              => $m->productionOrder?->bom?->outputUom?->uom_code,
            'status'           => $m->status,
            'rejection_reason' => $m->rejection_reason,
            'created_at'       => $m->created_at?->format('Y-m-d H:i'),
            'lines'            => $m->lines->map(fn($l) => [
                'id'              => $l->id,
                'material_id'     => $l->material_id,
                'material_name'   => $l->material?->material_name,
                'material_code'   => $l->material?->material_code,
                'required_qty'    => $l->required_qty,
                'uom'             => $l->uom?->uom_code,
                'scan_status'     => $l->scan_status,
                'bin_barcode'     => $l->bin_barcode,
                'material_barcode'=> $l->material_barcode,
                'bin_id'          => $l->bin_id,
                'warehouse_id'    => $l->warehouse_id,
                'scanned_at'      => $l->scanned_at,
            ])->values(),
        ];
    }

    /** GET /api/v1/material-issue-requests */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $query = MaterialIssueRequest::with(['productionOrder.product', 'productionOrder.bom.outputUom', 'lines.material', 'lines.uom']);

            if ($request->filled('status')) $query->where('status', $request->input('status'));
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(fn($q) => $q
                    ->where('mir_no', 'like', "%{$s}%")
                    ->orWhereHas('productionOrder', fn($p) => $p->where('order_no', 'like', "%{$s}%"))
                    ->orWhereHas('productionOrder.product', fn($p) => $p->where('product_name', 'like', "%{$s}%"))
                );
            }

            $mirs = $query->orderByDesc('created_at')->get()->map(fn($m) => $this->formatMir($m));

            return response()->json(['success' => true, 'data' => ['mirs' => $mirs],
                'message' => 'OK', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'ERROR', 'details' => []],
                'message' => $e->getMessage(), 'request_id' => $requestId ?? '', 'timestamp' => now()->toIso8601String()], 500);
        }
    }

    /** GET /api/v1/material-issue-requests/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $mir = MaterialIssueRequest::with(['productionOrder.product', 'productionOrder.bom.outputUom', 'lines.material', 'lines.uom'])
                ->findOrFail($id);
            return response()->json(['success' => true, 'data' => ['mir' => $this->formatMir($mir)],
                'message' => 'OK', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'details' => []],
                'message' => 'MIR not found', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 404);
        }
    }

    /**
     * POST /api/v1/material-issue-requests/{id}/approve
     * Store team approves — marks MIR APPROVED, lines ready for scanning.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $mir = MaterialIssueRequest::with('lines')->findOrFail($id);

            if ($mir->status !== 'PENDING') {
                return response()->json(['success' => false, 'error' => ['code' => 'INVALID_STATUS', 'details' => []],
                    'message' => 'Only PENDING MIRs can be approved.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            $mir->update([
                'status'      => 'APPROVED',
                'approved_by' => $request->input('auth_user_id'),
                'approved_at' => now(),
            ]);

            return response()->json(['success' => true, 'data' => ['status' => 'APPROVED'],
                'message' => 'MIR approved. Operator can now scan RM bins.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'ERROR', 'details' => []],
                'message' => $e->getMessage(), 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 500);
        }
    }

    /**
     * POST /api/v1/material-issue-requests/{id}/reject
     * Store team rejects with reason — production is notified.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            if (!$request->filled('reason')) {
                return response()->json(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'details' => ['reason' => ['Rejection reason is required.']]],
                    'message' => 'Rejection reason is required.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            $mir = MaterialIssueRequest::findOrFail($id);

            if ($mir->status !== 'PENDING') {
                return response()->json(['success' => false, 'error' => ['code' => 'INVALID_STATUS', 'details' => []],
                    'message' => 'Only PENDING MIRs can be rejected.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            $mir->update([
                'status'           => 'REJECTED',
                'rejection_reason' => $request->input('reason'),
            ]);

            return response()->json(['success' => true, 'data' => ['status' => 'REJECTED'],
                'message' => 'MIR rejected. Production team has been notified.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'ERROR', 'details' => []],
                'message' => $e->getMessage(), 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 500);
        }
    }

    /**
     * POST /api/v1/material-issue-requests/{id}/lines/{lineId}/scan
     * Operator enters/scans bin barcode + material barcode.
     * Validates both match the line's material and bin stock.
     * On success: deducts RM stock via StockService::post().
     */
    public function scan(Request $request, int $id, int $lineId): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $mir = MaterialIssueRequest::findOrFail($id);
            if ($mir->status !== 'APPROVED') {
                return response()->json(['success' => false, 'error' => ['code' => 'INVALID_STATUS', 'details' => []],
                    'message' => 'MIR must be APPROVED before scanning.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            $line = MIRLineItem::with(['material', 'uom'])->where('mir_id', $id)->findOrFail($lineId);
            if ($line->scan_status === 'ISSUED') {
                return response()->json(['success' => false, 'error' => ['code' => 'ALREADY_ISSUED', 'details' => []],
                    'message' => 'This line has already been issued.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            $binBarcode      = trim($request->input('bin_barcode', ''));
            $materialBarcode = trim($request->input('material_barcode', ''));

            if (!$binBarcode || !$materialBarcode) {
                return response()->json(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR',
                    'details' => ['bin_barcode' => !$binBarcode ? ['Required'] : [], 'material_barcode' => !$materialBarcode ? ['Required'] : []]],
                    'message' => 'Both bin barcode and material barcode are required.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            // Validate bin barcode → resolve bin
            $bin = BinLocation::where('bin_code', $binBarcode)->where('is_active', true)->first();
            if (!$bin) {
                return response()->json(['success' => false, 'error' => ['code' => 'BIN_NOT_FOUND', 'details' => []],
                    'message' => "Bin '{$binBarcode}' not found or inactive.", 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            // Validate material barcode → resolve material
            $material = Material::where(function ($q) use ($materialBarcode) {
                $q->where('material_code', $materialBarcode)->orWhere('barcode', $materialBarcode);
            })->first();

            if (!$material) {
                return response()->json(['success' => false, 'error' => ['code' => 'MATERIAL_NOT_FOUND', 'details' => []],
                    'message' => "Material '{$materialBarcode}' not found.", 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            // Validate scanned material matches the MIR line
            if ($material->id !== $line->material_id) {
                return response()->json(['success' => false, 'error' => ['code' => 'MATERIAL_MISMATCH', 'details' => []],
                    'message' => "Scanned material '{$material->material_name}' does not match expected '{$line->material?->material_name}'.",
                    'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            // Check stock availability in that bin
            $stock = StockBalance::where('material_id', $material->id)
                ->where('bin_id', $bin->id)
                ->where('bucket', 'AVAILABLE')
                ->first();

            $availableQty = $stock ? max(0, (float)$stock->qty_on_hand - (float)$stock->qty_reserved) : 0;

            if ($availableQty < (float)$line->required_qty) {
                return response()->json(['success' => false, 'error' => ['code' => 'INSUFFICIENT_STOCK', 'details' => [
                    'available' => $availableQty, 'required' => $line->required_qty, 'bin' => $binBarcode]],
                    'message' => "Insufficient stock in bin '{$binBarcode}'. Available: {$availableQty}, Required: {$line->required_qty}.",
                    'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 422);
            }

            // All validations passed — deduct stock
            DB::connection('tenant')->transaction(function () use ($line, $bin, $material, $mir, $request) {
                $this->stockService->post(
                    item: [
                        'material_id'  => $material->id,
                        'uom_id'       => $line->uom_id,
                        'warehouse_id' => $bin->warehouse_id,
                        'batch_number' => null,
                    ],
                    bucket:          'AVAILABLE',
                    qtyChange:       -(float)$line->required_qty,
                    transactionType: 'MATERIAL_ISSUE',
                    referenceType:   'MaterialIssueRequest',
                    referenceId:     $mir->id,
                    referenceNumber: $mir->mir_no,
                    userId:          (int)($request->input('auth_user_id') ?? 0),
                    binId:           $bin->id,
                    remarks:         "MIR issue for {$mir->mir_no}"
                );

                $line->update([
                    'bin_barcode'      => $bin->bin_code,
                    'material_barcode' => $material->material_code,
                    'scan_status'      => 'ISSUED',
                    'bin_id'           => $bin->id,
                    'warehouse_id'     => $bin->warehouse_id,
                    'scanned_at'       => now(),
                ]);
            });

            // Check if all lines are issued → mark MIR fully issued
            $allIssued = MIRLineItem::where('mir_id', $id)->where('scan_status', '!=', 'ISSUED')->doesntExist();
            if ($allIssued) {
                $mir->productionOrder?->update(['status' => 'IN_PROGRESS']);
            }

            return response()->json(['success' => true, 'data' => [
                'line_id'     => $line->id,
                'scan_status' => 'ISSUED',
                'all_issued'  => $allIssued,
            ], 'message' => 'Stock deducted successfully.', 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'ERROR', 'details' => []],
                'message' => $e->getMessage(), 'request_id' => $requestId, 'timestamp' => now()->toIso8601String()], 500);
        }
    }
}
