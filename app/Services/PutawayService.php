<?php

namespace App\Services;

use App\Models\Tenant\PutawayTask;
use App\Models\Tenant\PutawayLine;
use App\Models\Tenant\GRN;
use App\Models\Tenant\GRNLineItem;
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

            $task = PutawayTask::create([
                'task_number' => $this->generateTaskNumber(),
                'grn_line_id' => $data['grn_line_id'],
                'material_id' => $data['material_id'],
                'batch_number' => $data['batch_number'] ?? $grnLineItem->batch_number,
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

        return DB::connection('tenant')->transaction(function () use ($task, $data, $userId, $destinationBinId) {
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
                    try {
                        $warehouseId = $grnLine->grn?->purchaseOrder?->warehouse_id ?? null;
                        if ($warehouseId) {
                            app(StockService::class)->transfer(
                                [
                                    'material_id'  => $grnLine->material_id,
                                    'uom_id'       => $grnLine->uom_id,
                                    'warehouse_id' => $warehouseId,
                                    'batch_number' => $grnLine->batch_number,
                                ],
                                'PUTAWAY_PENDING',  // from (staging area / warehouse-level)
                                'AVAILABLE',         // to (confirmed shelf bin)
                                (float) $task->quantity,
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
                    } catch (\Exception $e) {
                        Log::warning('[PutawayService] StockService transfer PUTAWAY_PENDING→AVAILABLE failed', [
                            'task_id' => $task->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('[PutawayService] Putaway completed — stock now AVAILABLE', [
                'task_id'           => $task->id,
                'completed_by'      => $userId,
                'destination_bin_id'=> $destinationBinId,
                'grn_line_id'       => $task->grn_line_id,
            ]);

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

        // Validate bin type matches material type if configured
        if ($bin->bin_type && $task->material->material_type && $bin->bin_type !== $task->material->material_type) {
            throw new \Exception("Bin type '{$bin->bin_type}' does not match material type '{$task->material->material_type}'");
        }

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
}
