<?php

namespace App\Services;

use App\Models\Tenant\PutawayTask;
use App\Models\Tenant\PutawayLine;
use App\Models\Tenant\GRN;
use App\Models\Tenant\GRNLineItem;
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

        return DB::connection('tenant')->transaction(function () use ($task, $data, $userId) {
            // Update destination bin if provided
            if (isset($data['destination_bin_id'])) {
                $task->update(['destination_bin_id' => $data['destination_bin_id']]);
            }

            // Update task status
            $task->update([
                'status' => 'COMPLETED',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            // Create putaway line records
            if (isset($data['putaway_lines'])) {
                foreach ($data['putaway_lines'] as $line) {
                    PutawayLine::create([
                        'putaway_task_id' => $task->id,
                        'line_number' => $line['line_number'] ?? 1,
                        'batch_number' => $line['batch_number'] ?? null,
                        'quantity' => $line['quantity'] ?? $task->quantity,
                        'status' => 'COMPLETED',
                    ]);
                }
            }

            Log::info('Putaway completed', [
                'task_id' => $task->id,
                'completed_by' => $userId,
                'destination_bin_id' => $task->destination_bin_id,
            ]);

            return $task->load(['putawayLines']);
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
}
