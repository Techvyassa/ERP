<?php

namespace App\Services;

use App\Models\Tenant\PutawayTask;
use App\Models\Tenant\GRNLineItem;
use App\Models\Tenant\BinLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PutawayService
{
    /**
     * Create putaway task
     */
    public function createPutawayTask(array $data, int $userId): PutawayTask
    {
        // Get GRN line item
        $grnLineItem = GRNLineItem::findOrFail($data['grn_line_id']);
        
        // Check if task already exists for this GRN line
        $existingTask = PutawayTask::where('grn_line_id', $data['grn_line_id'])->first();
        if ($existingTask) {
            throw new \Exception('Putaway task already exists for this GRN line item');
        }
        
        return DB::connection('tenant')->transaction(function () use ($data, $grnLineItem, $userId) {
            // Determine destination bin based on strategy
            $destinationBinId = $data['destination_bin_id'] ?? $this->determineDestinationBin(
                $data['material_id'],
                $data['strategy'] ?? 'MANUAL'
            );
            
            // Create putaway task
            $task = PutawayTask::create([
                'task_number' => PutawayTask::generateTaskNumber(),
                'grn_line_id' => $data['grn_line_id'],
                'material_id' => $data['material_id'],
                'batch_number' => $data['batch_number'] ?? $grnLineItem->batch_number,
                'quantity' => $data['quantity'],
                'uom_id' => $data['uom_id'],
                'source_bin_id' => $data['source_bin_id'] ?? null,
                'destination_bin_id' => $destinationBinId,
                'strategy' => $data['strategy'] ?? 'MANUAL',
                'status' => 'PENDING',
                'assigned_to' => $data['assigned_to'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
            
            Log::info('Putaway task created', [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'grn_line_id' => $grnLineItem->id,
                'created_by' => $userId,
            ]);
            
            return $task->load(['grnLineItem', 'material', 'sourceBin', 'destinationBin']);
        });
    }

    /**
     * Update putaway task
     */
    public function updatePutawayTask(int $id, array $data, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($id);
        
        if (!$task->canEdit()) {
            throw new \Exception('Putaway task cannot be edited in current status: ' . $task->status);
        }
        
        $task->update([
            'destination_bin_id' => $data['destination_bin_id'] ?? $task->destination_bin_id,
            'assigned_to' => $data['assigned_to'] ?? $task->assigned_to,
            'remarks' => $data['remarks'] ?? $task->remarks,
        ]);
        
        Log::info('Putaway task updated', [
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'updated_by' => $userId,
        ]);
        
        return $task->load(['grnLineItem', 'material', 'sourceBin', 'destinationBin']);
    }

    /**
     * Start putaway (PENDING → IN_PROGRESS)
     */
    public function startPutaway(int $id, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($id);
        
        if (!$task->canStart()) {
            throw new \Exception('Putaway task must be in PENDING status to start');
        }
        
        $task->update([
            'status' => 'IN_PROGRESS',
            'assigned_to' => $userId, // Assign to current user if not already assigned
        ]);
        
        Log::info('Putaway started', [
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'started_by' => $userId,
        ]);
        
        return $task->load(['grnLineItem', 'material', 'sourceBin', 'destinationBin']);
    }

    /**
     * Complete putaway (IN_PROGRESS → COMPLETED)
     */
    public function completePutaway(int $id, array $data, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($id);
        
        if (!$task->canComplete()) {
            throw new \Exception('Putaway task must be in IN_PROGRESS status to complete');
        }
        
        return DB::connection('tenant')->transaction(function () use ($task, $data, $userId) {
            $task->update([
                'status' => 'COMPLETED',
                'bin_scan_confirmed' => $data['bin_scan_confirmed'],
                'item_scan_confirmed' => $data['item_scan_confirmed'],
                'completed_at' => now(),
                'completed_by' => $userId,
                'remarks' => $data['remarks'] ?? $task->remarks,
            ]);
            
            // Update GRN line item with final bin location
            $task->grnLineItem->update([
                'warehouse_bin_id' => $task->destination_bin_id,
            ]);
            
            Log::info('Putaway completed', [
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'destination_bin' => $task->destination_bin_id,
                'completed_by' => $userId,
            ]);
            
            return $task->load(['grnLineItem', 'material', 'sourceBin', 'destinationBin']);
        });
    }

    /**
     * Cancel putaway task
     */
    public function cancelPutaway(int $id, string $reason, int $userId): PutawayTask
    {
        $task = PutawayTask::findOrFail($id);
        
        if (!$task->canCancel()) {
            throw new \Exception('Putaway task cannot be cancelled in current status: ' . $task->status);
        }
        
        $task->update([
            'status' => 'CANCELLED',
            'remarks' => ($task->remarks ?? '') . "\nCancellation Reason: " . $reason,
        ]);
        
        Log::info('Putaway cancelled', [
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'reason' => $reason,
            'cancelled_by' => $userId,
        ]);
        
        return $task->load(['grnLineItem', 'material', 'sourceBin', 'destinationBin']);
    }

    /**
     * Determine destination bin based on strategy
     */
    private function determineDestinationBin(int $materialId, string $strategy): ?int
    {
        switch ($strategy) {
            case 'FIXED_BIN':
                // Find fixed bin for this material
                return BinLocation::where('material_id', $materialId)
                    ->where('is_active', true)
                    ->first()?->id;
                
            case 'EMPTY_BIN':
                // Find nearest empty bin
                return BinLocation::where('is_active', true)
                    ->whereNull('material_id')
                    ->orderBy('bin_code')
                    ->first()?->id;
                
            case 'FIFO':
            case 'FEFO':
                // Find bin with oldest stock for FIFO/FEFO
                return BinLocation::where('material_id', $materialId)
                    ->where('is_active', true)
                    ->orderBy('created_at')
                    ->first()?->id;
                
            case 'MANUAL':
            default:
                // Manual selection - no automatic determination
                return null;
        }
    }
}
