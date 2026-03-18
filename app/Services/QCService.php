<?php

namespace App\Services;

use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\QCResult;
use App\Models\Tenant\QCDecision;
use App\Models\Tenant\GRN;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QCService
{
    /**
     * Create inspection lot from GRN
     */
    public function createInspectionLot(GRN $grn, int $userId): InspectionLot
    {
        return DB::connection('tenant')->transaction(function () use ($grn, $userId) {
            // Get first line item to determine material and sample size
            $firstLineItem = $grn->lineItems->first();
            
            if (!$firstLineItem) {
                throw new \Exception('GRN has no line items');
            }

            // Calculate sample size (10% of accepted qty, minimum 1)
            $sampleSize = max(1, ceil($firstLineItem->accepted_qty * 0.1));

            $lot = InspectionLot::create([
                'grn_id' => $grn->id,
                'material_id' => $firstLineItem->material_id,
                'sample_size' => $sampleSize,
                'status' => 'PENDING',
                'created_by' => $userId,
            ]);

            Log::info('Inspection lot created', [
                'lot_id' => $lot->id,
                'grn_id' => $grn->id,
                'material_id' => $firstLineItem->material_id,
                'sample_size' => $sampleSize,
            ]);

            return $lot->load(['grn', 'material']);
        });
    }

    /**
     * Start inspection
     */
    public function startInspection(int $lotId, int $userId): InspectionLot
    {
        $lot = InspectionLot::findOrFail($lotId);

        if (!$lot->canStart()) {
            throw new \Exception('Inspection lot cannot be started in current status: ' . $lot->status);
        }

        $lot->update([
            'status' => 'IN_PROGRESS',
        ]);

        Log::info('Inspection started', [
            'lot_id' => $lot->id,
            'started_by' => $userId,
        ]);

        return $lot;
    }

    /**
     * Record test result
     */
    public function recordTestResult(int $lotId, array $data, int $userId): QCResult
    {
        $lot = InspectionLot::findOrFail($lotId);

        if ($lot->status !== 'IN_PROGRESS') {
            throw new \Exception('Inspection lot must be in IN_PROGRESS status');
        }

        return DB::connection('tenant')->transaction(function () use ($lotId, $data, $userId) {
            $result = QCResult::create([
                'inspection_lot_id' => $lotId,
                'qc_parameter_id' => $data['qc_parameter_id'],
                'observed_value' => $data['observed_value'],
                'status' => 'RECORDED',
                'recorded_by' => $userId,
            ]);

            Log::info('QC result recorded', [
                'result_id' => $result->id,
                'lot_id' => $lotId,
                'parameter_id' => $data['qc_parameter_id'],
            ]);

            return $result->load(['qcParameter']);
        });
    }

    /**
     * Complete inspection
     */
    public function completeInspection(int $lotId, int $userId): InspectionLot
    {
        $lot = InspectionLot::findOrFail($lotId);

        if (!$lot->canComplete()) {
            throw new \Exception('Inspection lot cannot be completed in current status: ' . $lot->status);
        }

        $lot->update([
            'status' => 'COMPLETED',
        ]);

        Log::info('Inspection completed', [
            'lot_id' => $lot->id,
            'completed_by' => $userId,
        ]);

        return $lot;
    }

    /**
     * Make usage decision
     */
    public function makeDecision(int $lotId, array $data, int $userId): QCDecision
    {
        $lot = InspectionLot::findOrFail($lotId);

        if ($lot->status !== 'COMPLETED') {
            throw new \Exception('Inspection lot must be COMPLETED before making decision');
        }

        return DB::connection('tenant')->transaction(function () use ($lot, $data, $userId) {
            // Create decision
            $decision = QCDecision::create([
                'inspection_lot_id' => $lot->id,
                'decision' => $data['decision'],
                'remarks' => $data['remarks'] ?? null,
                'decided_by' => $userId,
                'decided_at' => now(),
            ]);

            // Update lot status
            $lot->update(['status' => 'DECISION_MADE']);

            // Update GRN line item stock status based on decision
            if ($decision->isAccepted()) {
                // Release stock to UNRESTRICTED
                $lot->grn->lineItems()->update(['stock_status' => 'UNRESTRICTED']);
                
                // Create putaway tasks
                $this->createPutawayTasks($lot, $userId);
            } elseif ($decision->isRejected()) {
                // Block stock
                $lot->grn->lineItems()->update(['stock_status' => 'BLOCKED']);
                // TODO: Trigger RTV workflow
            } elseif ($decision->isConditional()) {
                // Keep as RESTRICTED until approval override
                // TODO: Trigger approval override workflow
            }

            Log::info('QC decision made', [
                'decision_id' => $decision->id,
                'lot_id' => $lot->id,
                'decision' => $data['decision'],
                'decided_by' => $userId,
            ]);

            return $decision->load(['inspectionLot', 'decisionMaker']);
        });
    }

    /**
     * Create putaway tasks for accepted material
     */
    private function createPutawayTasks(InspectionLot $lot, int $userId): void
    {
        $putawayService = app(PutawayService::class);
        
        foreach ($lot->grn->lineItems as $lineItem) {
            $putawayService->createPutawayTask([
                'grn_id' => $lot->grn->id,
                'material_id' => $lineItem->material_id,
                'source_bin_id' => $lineItem->warehouse_bin_id,
                'quantity' => $lineItem->accepted_qty,
                'strategy' => 'MANUAL', // Default strategy
            ], $userId);
        }
    }
}
