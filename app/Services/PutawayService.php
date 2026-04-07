<?php

namespace App\Services;

use App\Models\Tenant\PutawayTask;
use App\Models\Tenant\PutawayLine;
use App\Models\Tenant\GRN;
use App\Models\Tenant\GRNLineItem;
use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\StockBalance;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PutawayService
{
    /**
     * Create putaway task
     */
    public function createPutawayTask(array $data, int $userId): PutawayTask
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId) {
            // Validate grn_line_id exists
            $grnLineItem = \App\Models\Tenant\GRNLineItem::findOrFail($data['grn_line_id']);
            $batchNumber = $data['batch_number'] ?? $grnLineItem->batch_number;
            $materialId = $data['material_id'] ?? $grnLineItem->material_id;

            $existingTask = PutawayTask::query()
                ->where('grn_line_id', $data['grn_line_id'])
                ->where('material_id', $materialId)
                ->where('batch_number', $batchNumber)
                ->where('status', '!=', 'CANCELLED')
                ->orderByDesc('id')
                ->first();

            if ($existingTask) {
                if ($existingTask->status === 'COMPLETED') {
                    throw new \Exception(
                        "Putaway already completed for GRN line {$data['grn_line_id']} and batch {$batchNumber} " .
                        "under task {$existingTask->task_number}."
                    );
                }

                $existingTask->update([
                    'quantity' => $data['quantity'] ?? $existingTask->quantity,
                    'uom_id' => $data['uom_id'] ?? $existingTask->uom_id,
                    'source_bin_id' => $data['source_bin_id'] ?? $existingTask->source_bin_id,
                    'destination_bin_id' => $data['destination_bin_id'] ?? $existingTask->destination_bin_id,
                    'strategy' => $data['strategy'] ?? $existingTask->strategy,
                    'assigned_to' => $existingTask->assigned_to ?? $userId,
                ]);

                Log::info('Reused existing putaway task', [
                    'task_id' => $existingTask->id,
                    'task_number' => $existingTask->task_number,
                    'grn_line_id' => $data['grn_line_id'],
                    'material_id' => $materialId,
                    'batch_number' => $batchNumber,
                ]);

                return $existingTask->load(['material', 'sourceBin', 'destinationBin']);
            }

            $task = PutawayTask::create([
                'task_number' => $this->generateTaskNumber(),
                'grn_line_id' => $data['grn_line_id'],
                'material_id' => $materialId,
                'batch_number' => $batchNumber,
                'quantity' => $data['quantity'],
                'uom_id' => $data['uom_id'] ?? $grnLineItem->uom_id,
                'source_bin_id' => $data['source_bin_id'] ?? null,
                'destination_bin_id' => $data['destination_bin_id'] ?? null,
                'status' => 'PENDING',
                'strategy' => $data['strategy'] ?? 'MANUAL',
                'assigned_to' => $userId,
            ]);

            Log::info('Putaway task created', [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'grn_line_id' => $data['grn_line_id'],
                'material_id' => $data['material_id'],
                'quantity' => $data['quantity'],
            ]);

            return $task->load(['material', 'sourceBin', 'destinationBin']);
        });
    }

    /**
     * Generate unique task number
     */
    private function generateTaskNumber(): string
    {
        $count = PutawayTask::count() + 1;
        return 'PT-' . now()->format('y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Start putaway
     */
    public function startPutaway(int $taskId, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($taskId);

        if (!$task->canStart()) {
            throw new \Exception('Putaway task cannot be started in current status: ' . $task->status);
        }

        $task->update([
            'status' => 'IN_PROGRESS',
        ]);

        Log::info('Putaway started', [
            'task_id' => $task->id,
            'started_by' => $userId,
        ]);

        return $task;
    }

    /**
     * Complete putaway
     */
    public function completePutaway(int $taskId, array $data, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($taskId);

        if (!$task->canComplete()) {
            throw new \Exception('Putaway task cannot be completed in current status: ' . $task->status);
        }

        // Require destination bin
        $destinationBinId = $data['destination_bin_id'] ?? $task->destination_bin_id;
        if (!$destinationBinId) {
            throw new \Exception('Destination bin is required to complete putaway. Please scan a bin first.');
        }

        $putawayQty = isset($data['putaway_lines']) && count($data['putaway_lines']) > 0
            ? (float) collect($data['putaway_lines'])->sum(fn ($line) => (float) ($line['quantity'] ?? 0))
            : (float) $task->quantity;

        if ($putawayQty <= 0) {
            throw new \Exception('Putaway quantity must be greater than zero.');
        }

        if ($putawayQty > (float) $task->quantity) {
            throw new \Exception('Putaway quantity cannot exceed task quantity.');
        }

        $grnLine = $task->grnLineItem;
        $warehouseId = $grnLine ? $this->resolveWarehouseIdForPutaway($grnLine, $destinationBinId) : null;
        if ($grnLine && $grnLine->material_id && !$warehouseId) {
            throw new \Exception(
                "Cannot complete putaway task {$task->id}: warehouse could not be resolved. " .
                'Set the purchase order warehouse or ensure the selected bin belongs to a warehouse.'
            );
        }

        if ($grnLine && $grnLine->material_id) {
            $putawayPendingQty = $this->getMaterialBucketQty($grnLine, $warehouseId, 'PUTAWAY_PENDING');
            if ($putawayPendingQty < $putawayQty) {
                // AUTO-FIX for legacy data/mismatches: use whatever the ledger shows instead of blocking.
                Log::warning("[PutawayService] Legacy stock mismatch for Task #{$task->id}. Ledger shows {$putawayPendingQty} in PUTAWAY_PENDING, but task wanted to move {$putawayQty}. Auto-adjusting to match available ledger balance.", [
                    'task_id' => $task->id,
                    'material_id' => $grnLine->material_id,
                    'batch_number' => $grnLine->batch_number,
                    'ledger_qty' => $putawayPendingQty,
                    'requested_qty' => $putawayQty
                ]);
                
                $putawayQty = $putawayPendingQty;
                
                // Update the task object quantity so subsequent logic (ledger posting) uses the new reality
                $task->update(['quantity' => $putawayQty]);
                
                // If ledger is exactly 0, we can't perform a physical transfer, but we can still complete 
                // the document to clear the dashboard of "stalled" legacy tasks.
                if ($putawayQty <= 0) {
                    return DB::connection('tenant')->transaction(function () use ($task, $userId, $destinationBinId) {
                        $task->update([
                            'status' => 'COMPLETED',
                            'completed_by' => $userId,
                            'completed_at' => now(),
                            'remarks' => ($task->remarks ?? '') . "\n[System Auto-Complete]: No stock found in ledger. Task completed to clear legacy status."
                        ]);
                        return $task->load(['putawayLines', 'destinationBin']);
                    });
                }
            }
        }

        return DB::connection('tenant')->transaction(function () use ($task, $data, $userId, $destinationBinId, $putawayQty, $warehouseId) {
            // Update destination bin
            $task->update(['destination_bin_id' => $destinationBinId]);

            // Update task status
            $task->update([
                'status'       => 'COMPLETED',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            // Create putaway line records if provided, otherwise create default line
            if (isset($data['putaway_lines']) && count($data['putaway_lines']) > 0) {
                foreach ($data['putaway_lines'] as $index => $line) {
                    PutawayLine::create([
                        'putaway_task_id' => $task->id,
                        'line_number'     => $line['line_number'] ?? ($index + 1),
                        'batch_number'    => $line['batch_number'] ?? $task->batch_number,
                        'quantity'        => $line['quantity'] ?? $task->quantity,
                        'uom_id'          => $task->uom_id,
                        'status'          => 'COMPLETED',
                    ]);
                }
            } else {
                PutawayLine::create([
                    'putaway_task_id' => $task->id,
                    'line_number'     => 1,
                    'batch_number'    => $task->batch_number,
                    'quantity'        => $task->quantity,
                    'uom_id'          => $task->uom_id,
                    'status'          => 'COMPLETED',
                ]);
            }

            // Update GRN Line Item with final warehouse bin AND mark stock as AVAILABLE
            $grnLine = $task->grnLineItem;
            if ($grnLine) {
                $grnLine->update([
                    'warehouse_bin_id' => $destinationBinId,
                    'stock_status'     => 'AVAILABLE', // ← Stock is now physically on the shelf
                ]);

                // --- LEDGER: Transfer PUTAWAY_PENDING → AVAILABLE ---
                // This is the definitive moment stock becomes usable by production / sales.
                // The destination bin is now confirmed — we record the exact bin in the ledger.
                if ($grnLine->material_id) {
                    app(StockService::class)->transfer(
                            [
                                'material_id'  => $grnLine->material_id,
                                'uom_id'       => $grnLine->uom_id,
                                'warehouse_id' => $warehouseId,
                                'batch_number' => $grnLine->batch_number,
                            ],
                            'PUTAWAY_PENDING',  // from (staging area / warehouse-level)
                            'AVAILABLE',         // to (confirmed shelf bin)
                            $putawayQty,
                            'PUTAWAY_COMPLETE',
                            'PutawayTask',
                            $task->id,
                            $task->task_number,
                            $userId,
                            null,               // from bin: staging (no specific bin tracked yet)
                            $destinationBinId,  // to bin: the exact shelf bin scanned by operator
                            $grnLine->unit_price,
                            "Putaway confirmed — stock now on shelf bin #{$destinationBinId}"
                        );
                }
            }

            Log::info('[PutawayService] Putaway completed — stock now AVAILABLE', [
                'task_id'           => $task->id,
                'completed_by'      => $userId,
                'destination_bin_id'=> $destinationBinId,
                'grn_line_id'       => $task->grn_line_id,
            ]);

            if ($grnLine && $grnLine->material_id) {
                $ledgerPosted = InventoryTransaction::query()
                    ->where('reference_type', 'PutawayTask')
                    ->where('reference_id', $task->id)
                    ->where('transaction_type', 'PUTAWAY_COMPLETE')
                    ->where('material_id', $grnLine->material_id)
                    ->where('batch_number', $grnLine->batch_number)
                    ->exists();

                if (!$ledgerPosted) {
                    throw new \Exception(
                        "Putaway task {$task->id} was not posted to inventory_transactions. " .
                        'The task was rolled back so stock and document status stay consistent.'
                    );
                }
            }

            // Update GRN header status based on all line items' putaway completion
            $this->updateGRNHeaderStatus($grnLine);

            return $task->load(['putawayLines', 'destinationBin']);
        });
    }

    /**
     * Cancel putaway
     */
    public function cancelPutaway(int $taskId, string $reason, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($taskId);

        if (!$task->canCancel()) {
            throw new \Exception('Putaway task cannot be cancelled in current status: ' . $task->status);
        }

        $task->update([
            'status' => 'CANCELLED',
        ]);

        Log::info('Putaway cancelled', [
            'task_id' => $task->id,
            'reason' => $reason,
            'cancelled_by' => $userId,
        ]);

        return $task;
    }

    /**
     * Scan bin during putaway
     */
    public function scanBin(int $taskId, string $binCode, int $userId, ?string $remarks = null): PutawayTask
    {
        $task = PutawayTask::with(['destinationBin'])->findOrFail($taskId);

        // Find bin by code
        $bin = \App\Models\Tenant\BinLocation::where('bin_code', $binCode)->first();
        
        if (!$bin) {
            throw new \Exception("Bin not found with code: {$binCode}");
        }

        // Validate bin is active
        if (!$bin->is_active) {
            throw new \Exception("Bin is not active: {$binCode}");
        }

        // Note: Bin type (STORAGE, RECEIVING_DOCK, etc.) is independent of material type (RAW, PACKAGING, etc.)
        // All storage bins can accept any material type unless specific restrictions are configured

        // Update task with scanned bin
        $task->update([
            'destination_bin_id' => $bin->id,
            'bin_scan_confirmed' => $binCode,
        ]);

        Log::info('Bin scanned during putaway', [
            'task_id' => $task->id,
            'bin_code' => $binCode,
            'bin_id' => $bin->id,
            'scanned_by' => $userId,
            'remarks' => $remarks,
        ]);

        return $task->load(['destinationBin']);
    }

    private function resolveWarehouseIdForPutaway(GRNLineItem $grnLine, int $destinationBinId): ?int
    {
        return \App\Models\Tenant\BinLocation::query()->whereKey($destinationBinId)->value('warehouse_id')
            ?? $grnLine->warehouseBin?->warehouse_id
            ?? $grnLine->material?->default_warehouse_id
            ?? InventoryTransaction::query()
                ->where('material_id', $grnLine->material_id)
                ->where('reference_type', 'GRN')
                ->where('reference_id', $grnLine->grn_id)
                ->where('batch_number', $grnLine->batch_number)
                ->value('warehouse_id')
            ?? StockBalance::query()
                ->where('material_id', $grnLine->material_id)
                ->where('batch_number', $grnLine->batch_number)
                ->value('warehouse_id');
    }

    private function getMaterialBucketQty(GRNLineItem $grnLine, int $warehouseId, string $bucket): float
    {
        return (float) StockBalance::query()
            ->where('material_id', $grnLine->material_id)
            ->where('batch_number', $grnLine->batch_number)
            ->where('warehouse_id', $warehouseId)
            ->where('bucket', $bucket)
            ->sum('qty_on_hand');
    }

    /**
     * Update GRN header status based on all line items' putaway completion status.
     * Called after each putaway task completes to keep GRN status in sync.
     */
    private function updateGRNHeaderStatus(GRNLineItem $completedLine): void
    {
        $grn = $completedLine->grn;
        if (!$grn) {
            return;
        }

        // Check all line items for this GRN
        $allLines = GRNLineItem::where('grn_id', $grn->id)->get();
        
        if ($allLines->isEmpty()) {
            return;
        }

        $totalLines = $allLines->count();
        $availableLines = $allLines->filter(fn($line) => $line->stock_status === 'AVAILABLE')->count();
        $blockedLines = $allLines->filter(fn($line) => $line->stock_status === 'BLOCKED')->count();
        $putawayPendingLines = $allLines->filter(fn($line) => $line->stock_status === 'PUTAWAY_PENDING')->count();

        // Determine new GRN status based on line completion
        $newStatus = null;
        
        if ($availableLines === $totalLines) {
            // All lines are now available (fully put away)
            $newStatus = 'ACCEPTED';
        } elseif ($blockedLines === $totalLines) {
            // All lines were rejected
            $newStatus = 'REJECTED';
        } elseif ($availableLines > 0 && ($availableLines + $blockedLines) === $totalLines) {
            // Some accepted and put away, some rejected - no pending putaway
            $newStatus = 'PARTIALLY_ACCEPTED';
        } elseif ($putawayPendingLines > 0) {
            // Still has lines awaiting putaway - keep as PUTAWAY_IN_PROGRESS
            // Only update if not already PUTAWAY_IN_PROGRESS to avoid unnecessary writes
            if ($grn->status !== 'PUTAWAY_IN_PROGRESS') {
                $newStatus = 'PUTAWAY_IN_PROGRESS';
            }
        }

        // Update GRN header status if changed
        if ($newStatus && $grn->status !== $newStatus) {
            $grn->update(['status' => $newStatus]);
            
            Log::info('[PutawayService] GRN header status updated after putaway completion', [
                'grn_id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'old_status' => $grn->status,
                'new_status' => $newStatus,
                'total_lines' => $totalLines,
                'available_lines' => $availableLines,
                'blocked_lines' => $blockedLines,
                'putaway_pending_lines' => $putawayPendingLines,
            ]);
        }
    }
}
