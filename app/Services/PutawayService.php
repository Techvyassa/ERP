<?php

namespace App\Services;

use App\Models\Tenant\PutawayTask;
use App\Models\Tenant\PutawayLine;
use App\Models\Tenant\GRN;
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
            $grn = GRN::findOrFail($data['grn_id']);

            // Validate GRN has accepted stock
            if (!$grn->lineItems()->where('stock_status', 'UNRESTRICTED')->exists()) {
                throw new \Exception('GRN has no unrestricted stock for putaway');
            }

            $task = PutawayTask::create([
                'grn_id' => $data['grn_id'],
                'material_id' => $data['material_id'],
                'source_bin_id' => $data['source_bin_id'] ?? null,
                'destination_bin_id' => $data['destination_bin_id'] ?? null,
                'quantity' => $data['quantity'],
                'status' => 'PENDING',
                'strategy' => $data['strategy'] ?? 'MANUAL',
                'created_by' => $userId,
            ]);

            Log::info('Putaway task created', [
                'task_id' => $task->id,
                'grn_id' => $data['grn_id'],
                'material_id' => $data['material_id'],
                'quantity' => $data['quantity'],
            ]);

            return $task->load(['grn', 'material', 'sourceBin', 'destinationBin']);
        });
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
