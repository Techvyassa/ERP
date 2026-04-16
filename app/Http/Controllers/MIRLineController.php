<?php

namespace App\Http\Controllers;

use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\MIRIssueTransaction;
use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\StockBalance;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MIRLineController extends Controller
{
    protected StockService $stockService;

    public function __construct()
    {
        $this->stockService = app(StockService::class);
    }
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

        // Check if status column exists
        $hasStatusColumn = \DB::connection('tenant')->getSchemaBuilder()->hasColumn('mir_line_items', 'status');
        
        $status = $hasStatusColumn ? $line->status : null;
        $isFullyPicked = false;
        
        if (!$hasStatusColumn) {
            // Calculate based on issued_qty
            $isFullyPicked = ($line->issued_qty ?? 0) >= $line->required_qty;
        } else {
            $isFullyPicked = $line->isFullyPicked();
        }

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
                'status' => $status,
                'last_issued_at' => $line->last_issued_at,
                'rejected_reason' => $line->rejected_reason,
                'is_fully_picked' => $isFullyPicked,
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
     * Creates transaction record, deducts stock, and updates line status
     */
    public function issue(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'issued_qty' => 'required|numeric|min:0.001',
            'bin_id' => 'nullable|integer',
            'batch_number' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        $line = MIRLineItem::with(['material', 'mir'])->findOrFail($id);

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

        // Get material and warehouse info
        $material = $line->material;
        $mir = $line->mir;
        
        // Determine warehouse - use default or from MIR
        $warehouseId = 1; // Default warehouse - can be made configurable
        $binId = $validated['bin_id'] ?? null;
        $batchNumber = $validated['batch_number'] ?? null;

        // Check available stock
        $availableStock = $this->getAvailableStockForMaterial($material->id, $warehouseId, $binId);
        
        if ($availableStock < $validated['issued_qty']) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Available: {$availableStock}, Requested: {$validated['issued_qty']}",
            ], 422);
        }

        // Use database transaction for atomic stock deduction
        try {
            DB::connection('tenant')->transaction(function () use (
                $line, $mir, $material, $warehouseId, $binId, $batchNumber, $validated, $request
            ) {
                $userId = $request->input('auth_user_id') ?? 1;
                $issuedQty = $validated['issued_qty'];
                
                // If no specific bin/batch provided, use FIFO - get oldest stock
                if (!$binId || !$batchNumber) {
                    $stockAllocation = $this->allocateStockFIFO(
                        $material->id,
                        $warehouseId,
                        $issuedQty,
                        $line->uom_id
                    );
                    
                    if (!$stockAllocation) {
                        throw new \Exception('No stock available for allocation');
                    }
                    
                    $binId = $stockAllocation['bin_id'];
                    $batchNumber = $stockAllocation['batch_number'];
                    $allocatedQty = $stockAllocation['qty'];
                } else {
                    $allocatedQty = $issuedQty;
                }

                // Deduct stock using StockService
                $this->stockService->post(
                    item: [
                        'material_id' => $material->id,
                        'uom_id' => $line->uom_id,
                        'warehouse_id' => $warehouseId,
                        'batch_number' => $batchNumber,
                    ],
                    bucket: 'AVAILABLE',
                    qtyChange: -$allocatedQty,
                    transactionType: 'PRODUCTION_ISSUE',
                    referenceType: 'MaterialIssueRequest',
                    referenceId: $mir->id,
                    referenceNumber: $mir->mir_no,
                    userId: $userId,
                    binId: $binId,
                    remarks: $validated['notes'] ?? "MIR Issue - {$mir->mir_no}"
                );

                // Create MIR transaction record
                $transaction = MIRIssueTransaction::create([
                    'mir_line_id' => $line->id,
                    'issued_qty' => $issuedQty,
                    'issued_by' => $userId,
                    'issued_at' => now(),
                    'notes' => $validated['notes'] ?? "Bin: {$binId}, Batch: {$batchNumber}",
                ]);

                // Update line issued_qty
                $line->issued_qty += $issuedQty;
                $line->updateStatus();

                // Recalculate MIR header status
                $mir->updateHeaderStatus();
            });
        } catch (\Exception $e) {
            Log::error('MIRLineController@issue: Stock deduction failed', [
                'line_id' => $line->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue material: ' . $e->getMessage(),
            ], 500);
        }

        // Reload line to get updated values
        $line->refresh();

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
                    'id' => null,
                    'issued_qty' => $validated['issued_qty'],
                    'issued_at' => now(),
                ],
                'mir_status' => $mir->status,
            ],
        ]);
    }

    /**
     * Get available stock for a material
     */
    private function getAvailableStockForMaterial(int $materialId, int $warehouseId, ?int $binId = null): float
    {
        $query = StockBalance::where('material_id', $materialId)
            ->where('warehouse_id', $warehouseId)
            ->where('bucket', 'AVAILABLE');

        if ($binId) {
            $query->where('bin_id', $binId);
        }

        return (float) $query->sum('qty_on_hand');
    }

    /**
     * Allocate stock using FIFO (First In First Out) method
     * Returns the bin and batch with the oldest stock
     */
    private function allocateStockFIFO(int $materialId, int $warehouseId, float $requiredQty, int $uomId): ?array
    {
        $stocks = StockBalance::where('material_id', $materialId)
            ->where('warehouse_id', $warehouseId)
            ->where('bucket', 'AVAILABLE')
            ->where('qty_on_hand', '>', 0)
            ->orderBy('last_transaction_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($stocks->isEmpty()) {
            return null;
        }

        $allocatedQty = 0;
        $allocations = [];

        foreach ($stocks as $stock) {
            if ($allocatedQty >= $requiredQty) break;

            $takeQty = min($stock->qty_on_hand, $requiredQty - $allocatedQty);
            $allocations[] = [
                'bin_id' => $stock->bin_id,
                'batch_number' => $stock->batch_number,
                'qty' => $takeQty,
            ];
            $allocatedQty += $takeQty;
        }

        if ($allocatedQty < $requiredQty) {
            return null;
        }

        // Return first allocation (FIFO)
        return $allocations[0];
    }
}
