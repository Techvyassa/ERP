<?php

namespace App\Http\Controllers;

use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\MIRIssueTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MIRLineController extends Controller
{
    /**
     * Get MIR line details with transaction history
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $line = MIRLineItem::with(['material', 'uom', 'transactions.issuer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $line->id,
                'mir_id' => $line->mir_id,
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
                'last_issued_at' => $line->last_issued_at,
                'rejected_reason' => $line->rejected_reason,
                'is_fully_picked' => $line->isFullyPicked(),
                'transactions' => $line->transactions->map(fn($t) => [
                    'id' => $t->id,
                    'issued_qty' => $t->issued_qty,
                    'issued_by' => $t->issuer?->first_name . ' ' . $t->issuer?->last_name,
                    'issued_at' => $t->issued_at,
                    'notes' => $t->notes,
                ]),
            ],
        ]);
    }

    /**
     * Approve MIR line (PENDING → APPROVED)
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $line = MIRLineItem::findOrFail($id);

        if (!$line->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => "Line cannot be approved. Current status: {$line->status}",
            ], 422);
        }

        $line->update([
            'status' => 'APPROVED',
        ]);

        // Update MIR header status
        $mir = $line->mir;
        $mir->updateHeaderStatus();

        return response()->json([
            'success' => true,
            'message' => 'Line approved successfully',
            'data' => [
                'id' => $line->id,
                'status' => $line->status,
                'mir_status' => $mir->status,
            ],
        ]);
    }

    /**
     * Reject MIR line (PENDING → REJECTED)
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

        $line = MIRLineItem::findOrFail($id);

        if (!$line->canReject()) {
            return response()->json([
                'success' => false,
                'message' => "Line cannot be rejected. Current status: {$line->status}",
            ], 422);
        }

        $line->update([
            'status' => 'REJECTED',
            'rejected_reason' => $validated['rejection_reason'],
        ]);

        // Update MIR header status (will become REJECTED)
        $mir = $line->mir;
        $mir->updateHeaderStatus();

        return response()->json([
            'success' => true,
            'message' => 'Line rejected successfully',
            'data' => [
                'id' => $line->id,
                'status' => $line->status,
                'rejected_reason' => $line->rejected_reason,
                'mir_status' => $mir->status,
            ],
        ]);
    }

    /**
     * Issue material (partial or full)
     * Creates transaction record and updates line status
     */
    public function issue(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'issued_qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $line = MIRLineItem::findOrFail($id);

        // Validate preconditions
        if (!$line->canIssue()) {
            return response()->json([
                'success' => false,
                'message' => "Line cannot be issued. Current status: {$line->status}. Must be APPROVED or PARTIALLY_PICKED.",
            ], 422);
        }

        $remaining = $line->getRemainingQty();
        if ($validated['issued_qty'] > $remaining) {
            return response()->json([
                'success' => false,
                'message' => "Issued qty ({$validated['issued_qty']}) exceeds remaining qty ({$remaining})",
            ], 422);
        }

        // Create transaction record
        $transaction = MIRIssueTransaction::create([
            'mir_line_id' => $line->id,
            'issued_qty' => $validated['issued_qty'],
            'issued_by' => $request->input('auth_user_id'),
            'issued_at' => now(),
            'notes' => $validated['notes'],
        ]);

        // Update line issued_qty
        $line->issued_qty += $validated['issued_qty'];
        $line->updateStatus();

        // Recalculate MIR header status
        $mir = $line->mir;
        $mir->updateHeaderStatus();

        return response()->json([
            'success' => true,
            'message' => 'Material issued successfully',
            'data' => [
                'line' => [
                    'id' => $line->id,
                    'required_qty' => $line->required_qty,
                    'issued_qty' => $line->issued_qty,
                    'remaining_qty' => $line->getRemainingQty(),
                    'status' => $line->status,
                ],
                'transaction' => [
                    'id' => $transaction->id,
                    'issued_qty' => $transaction->issued_qty,
                    'issued_at' => $transaction->issued_at,
                ],
                'mir_status' => $mir->status,
            ],
        ]);
    }
}
