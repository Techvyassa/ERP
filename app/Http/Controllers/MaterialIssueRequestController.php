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

        $query = MaterialIssueRequest::with(['batchRun', 'lines']);

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

        return response()->json([
            'success' => true,
            'data' => $mirs->map(fn($mir) => [
                'id' => $mir->id,
                'mir_no' => $mir->mir_no,
                'batch_run_id' => $mir->batch_run_id,
                'production_order_id' => $mir->production_order_id,
                'status' => $mir->status,
                'lines_count' => $mir->lines()->count(),
                'fully_picked_count' => $mir->lines()->where('status', 'FULLY_PICKED')->count(),
                'created_at' => $mir->created_at,
                'approved_at' => $mir->approved_at,
                'fully_issued_at' => $mir->fully_issued_at,
            ]),
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

        $mir = MaterialIssueRequest::with(['batchRun', 'productionOrder', 'lines.material', 'lines.uom', 'lines.transactions.issuer'])->findOrFail($id);

        $lines = $mir->lines->map(fn($line) => [
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
            'status' => $line->status,
            'rejected_reason' => $line->rejected_reason,
            'last_issued_at' => $line->last_issued_at,
            'is_fully_picked' => $line->isFullyPicked(),
            'transactions_count' => $line->transactions()->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $mir->id,
                'mir_no' => $mir->mir_no,
                'batch_run_id' => $mir->batch_run_id,
                'production_order_id' => $mir->production_order_id,
                'status' => $mir->status,
                'created_at' => $mir->created_at,
                'approved_at' => $mir->approved_at,
                'fully_issued_at' => $mir->fully_issued_at,
                'closed_at' => $mir->closed_at,
                'lines' => $lines,
                'summary' => [
                    'total_lines' => $mir->lines()->count(),
                    'pending_lines' => $mir->lines()->where('status', 'PENDING')->count(),
                    'approved_lines' => $mir->lines()->where('status', 'APPROVED')->count(),
                    'partially_picked_lines' => $mir->lines()->where('status', 'PARTIALLY_PICKED')->count(),
                    'fully_picked_lines' => $mir->lines()->where('status', 'FULLY_PICKED')->count(),
                    'rejected_lines' => $mir->lines()->where('status', 'REJECTED')->count(),
                ],
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
     * All lines must be individually approved first
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $mir = MaterialIssueRequest::findOrFail($id);

        if (!$mir->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => 'MIR cannot be approved. Status must be PENDING and all lines must be approved.',
                'current_status' => $mir->status,
                'unapproved_lines' => $mir->lines()
                    ->whereNotIn('status', ['APPROVED', 'PARTIALLY_PICKED', 'FULLY_PICKED'])
                    ->count(),
            ], 422);
        }

        $mir->update([
            'status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => $request->input('auth_user_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MIR approved successfully',
            'data' => [
                'id' => $mir->id,
                'status' => $mir->status,
                'approved_at' => $mir->approved_at,
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
