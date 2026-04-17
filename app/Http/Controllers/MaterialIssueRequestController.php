<?php

namespace App\Http\Controllers;

use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\MIRLineItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MaterialIssueRequestController extends Controller
{
    /**
     * List Material Issue Requests with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $query = MaterialIssueRequest::with(['batchRun', 'lines', 'productionOrder.product', 'productionRequest']);

        // Filter by batch_run_id
        if ($request->filled('batch_run_id')) {
            $query->where('batch_run_id', $request->input('batch_run_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by production_order_id
        if ($request->filled('production_order_id')) {
            $query->where('production_order_id', $request->input('production_order_id'));
        }

        $mirs = $query->orderByDesc('created_at')->paginate(20);

        // Check if status column exists in mir_line_items
        $hasStatusColumn = \DB::connection('tenant')->getSchemaBuilder()->hasColumn('mir_line_items', 'status');

        return response()->json([
            'success' => true,
            'data' => $mirs->map(function ($mir) use ($hasStatusColumn) {
                $fullyPickedCount = 0;
                
                if ($hasStatusColumn) {
                    $fullyPickedCount = $mir->lines()->where('status', 'FULLY_PICKED')->count();
                } else {
                    // Fallback: calculate based on issued_qty if status column doesn't exist
                    $fullyPickedCount = $mir->lines()
                        ->whereRaw('COALESCE(issued_qty, 0) >= required_qty')
                        ->count();
                }

                return [
                    'id' => $mir->id,
                    'mir_no' => $mir->mir_no,
                    'batch_run_id' => $mir->batch_run_id,
                    'production_order_id' => $mir->production_order_id,
                    'production_request_id' => $mir->production_request_id,
                    'order_no' => $mir->productionOrder?->order_no,
                    'request_no' => $mir->productionRequest?->request_no,
                    'product_name' => $mir->productionOrder?->product?->product_name ?? $mir->productionRequest?->product?->product_name,
                    'product_code' => $mir->productionOrder?->product?->product_code ?? $mir->productionRequest?->product?->product_code,
                    'status' => $mir->status,
                    'lines_count' => $mir->lines()->count(),
                    'fully_picked_count' => $fullyPickedCount,
                    'created_at' => $mir->created_at,
                    'approved_at' => $mir->approved_at,
                    'fully_issued_at' => $mir->fully_issued_at,
                ];
            }),
            'pagination' => [
                'total' => $mirs->total(),
                'per_page' => $mirs->perPage(),
                'current_page' => $mirs->currentPage(),
                'last_page' => $mirs->lastPage(),
            ],
        ]);
    }

    /**
     * Get MIR details with all lines
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $mir = MaterialIssueRequest::with([
            'batchRun',
            'productionOrder.product',
            'productionOrder.bom.outputUom',
            'productionRequest.product',
            'productionRequest.bom.outputUom',
            'lines.material',
            'lines.uom',
            'lines.transactions.issuer',
        ])->findOrFail($id);

        // Check if status column exists in mir_line_items
        $hasStatusColumn = \DB::connection('tenant')->getSchemaBuilder()->hasColumn('mir_line_items', 'status');

        $lines = $mir->lines->map(function ($line) use ($hasStatusColumn) {
            $status = $hasStatusColumn ? $line->status : null;
            $isFullyPicked = false;
            
            if (!$hasStatusColumn) {
                // Calculate status based on issued_qty
                $isFullyPicked = ($line->issued_qty ?? 0) >= $line->required_qty;
            } else {
                $isFullyPicked = $line->isFullyPicked();
            }

            return [
                'id' => $line->id,
                'material_id' => $line->material_id,
                'material' => [
                    'id' => $line->material->id,
                    'name' => $line->material->material_name,
                    'code' => $line->material->material_code,
                ],
                'required_qty' => $line->required_qty,
                'issued_qty' => $line->issued_qty,
                'remaining_qty' => $line->getRemainingQty(),
                'uom' => $line->uom?->uom_code,
                'uom_name' => $line->uom?->uom_name ?? $line->uom?->uom_code,
                'status' => $status,
                'rejected_reason' => $line->rejected_reason,
                'last_issued_at' => $line->last_issued_at,
                'is_fully_picked' => $isFullyPicked,
                'transactions_count' => $line->transactions()->count(),
                // Include transactions with parsed bin info for issued lines
                'transactions' => $line->transactions->map(fn($t) => [
                    'id'         => $t->id,
                    'issued_qty' => $t->issued_qty,
                    'issued_at'  => $t->issued_at,
                    'notes'      => $t->notes,
                    // Parse "bin_code | material_code" from notes
                    'bin_code'   => $t->notes ? trim(explode('|', $t->notes)[0]) : null,
                    'issued_by'  => $t->issuer?->first_name . ' ' . $t->issuer?->last_name,
                ]),
            ];
        });

        // Calculate summary
        $summary = [
            'total_lines' => $mir->lines()->count(),
            'pending_lines' => 0,
            'approved_lines' => 0,
            'partially_picked_lines' => 0,
            'fully_picked_lines' => 0,
            'rejected_lines' => 0,
        ];

        if ($hasStatusColumn) {
            $summary['pending_lines'] = $mir->lines()->where('status', 'PENDING')->count();
            $summary['approved_lines'] = $mir->lines()->where('status', 'APPROVED')->count();
            $summary['partially_picked_lines'] = $mir->lines()->where('status', 'PARTIALLY_PICKED')->count();
            $summary['fully_picked_lines'] = $mir->lines()->where('status', 'FULLY_PICKED')->count();
            $summary['rejected_lines'] = $mir->lines()->where('status', 'REJECTED')->count();
        } else {
            // Fallback: calculate based on issued_qty
            $summary['fully_picked_lines'] = $mir->lines()->whereRaw('COALESCE(issued_qty, 0) >= required_qty')->count();
            $summary['partially_picked_lines'] = $mir->lines()
                ->whereRaw('COALESCE(issued_qty, 0) > 0')
                ->whereRaw('COALESCE(issued_qty, 0) < required_qty')
                ->count();
            $summary['pending_lines'] = $mir->lines()->whereRaw('COALESCE(issued_qty, 0) = 0')->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $mir->id,
                'mir_no'               => $mir->mir_no,
                'batch_run_id'         => $mir->batch_run_id,
                'production_order_id'  => $mir->production_order_id,
                'production_request_id' => $mir->production_request_id,
                // Production order fields the UI needs
                'order_no'             => $mir->productionOrder?->order_no,
                'request_no'           => $mir->productionRequest?->request_no,
                'product_name'         => $mir->productionOrder?->product?->product_name ?? $mir->productionRequest?->product?->product_name,
                'product_code'         => $mir->productionOrder?->product?->product_code ?? $mir->productionRequest?->product?->product_code,
                'target_qty'           => $mir->productionOrder?->target_qty ?? $mir->productionRequest?->target_qty,
                'uom'                  => $mir->productionOrder?->bom?->outputUom?->uom_code ?? $mir->productionRequest?->bom?->outputUom?->uom_code,
                'uom_name'             => $mir->productionOrder?->bom?->outputUom?->uom_name
                                          ?? $mir->productionOrder?->bom?->outputUom?->uom_code
                                          ?? $mir->productionRequest?->bom?->outputUom?->uom_name
                                          ?? $mir->productionRequest?->bom?->outputUom?->uom_code,
                // MIR fields
                'status'               => $mir->status,
                'rejection_reason'     => $mir->rejection_reason,
                'created_at'           => $mir->created_at,
                'approved_at'          => $mir->approved_at,
                'fully_issued_at'      => $mir->fully_issued_at,
                'closed_at'            => $mir->closed_at,
                'lines'                => $lines,
                'summary'              => $summary,
            ],
        ]);
    }

    /**
     * Get all MIR lines with details
     */
    public function lines(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $mir = MaterialIssueRequest::findOrFail($id);
        $lines = $mir->lines()->with(['material', 'uom', 'transactions.issuer'])->get();

        return response()->json([
            'success' => true,
            'data' => $lines->map(fn($line) => [
                'id' => $line->id,
                'material' => [
                    'id' => $line->material->id,
                    'name' => $line->material->material_name,
                    'code' => $line->material->material_code,
                ],
                'required_qty' => $line->required_qty,
                'issued_qty' => $line->issued_qty,
                'remaining_qty' => $line->getRemainingQty(),
                'uom' => $line->uom?->uom_code,
                'uom_name' => $line->uom?->uom_name ?? $line->uom?->uom_code,
                'status' => $line->status,
                'rejected_reason' => $line->rejected_reason,
                'last_issued_at' => $line->last_issued_at,
                'is_fully_picked' => $line->isFullyPicked(),
                'transactions' => $line->transactions->map(fn($t) => [
                    'id' => $t->id,
                    'issued_qty' => $t->issued_qty,
                    'issued_by' => $t->issuer?->first_name . ' ' . $t->issuer?->last_name,
                    'issued_at' => $t->issued_at,
                    'notes' => $t->notes,
                ]),
            ]),
        ]);
    }

    /**
     * Approve MIR (PENDING → APPROVED)
     * Approves all lines and the MIR header in one action.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $mir = MaterialIssueRequest::with('lines')->findOrFail($id);

        if ($mir->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => "MIR cannot be approved. Current status: {$mir->status}",
            ], 422);
        }

        if ($mir->lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'MIR has no lines to approve.',
            ], 422);
        }

        // Approve all PENDING lines in one query
        $mir->lines()->where('status', 'PENDING')->update(['status' => 'APPROVED']);

        // Approve the MIR header
        $mir->update([
            'status'      => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => $request->input('auth_user_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MIR and all lines approved successfully',
            'data' => [
                'id'          => $mir->id,
                'status'      => 'APPROVED',
                'approved_at' => $mir->approved_at,
                'lines_approved' => $mir->lines()->count(),
            ],
        ]);
    }

    /**
     * Reject MIR (PENDING → REJECTED)
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $mir = MaterialIssueRequest::findOrFail($id);

        if (!$mir->canReject()) {
            return response()->json([
                'success' => false,
                'message' => "MIR cannot be rejected. Current status: {$mir->status}",
            ], 422);
        }

        $mir->update([
            'status' => 'REJECTED',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Update associated Production Request status if linked
        // Check both production_request_id (new) and mir_id (old relationship from production_requests table)
        $productionRequest = null;
        
        // First try: check production_request_id in MIR (new relationship)
        if ($mir->production_request_id) {
            $productionRequest = \App\Models\Tenant\ProductionRequest::find($mir->production_request_id);
        }
        
        // Second try: find Production Request that has this MIR's id as mir_id (old relationship)
        if (!$productionRequest) {
            $productionRequest = \App\Models\Tenant\ProductionRequest::where('mir_id', $mir->id)->first();
        }
        
        if ($productionRequest) {
            $productionRequest->update([
                'status' => 'REJECTED',
                // 'mir_id' => null, // Unlink the MIR
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'MIR rejected successfully',
            'data' => [
                'id' => $mir->id,
                'status' => $mir->status,
                'rejection_reason' => $mir->rejection_reason,
            ],
        ]);
    }
}
