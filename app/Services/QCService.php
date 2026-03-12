<?php

namespace App\Services;

use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\InspectionResult;
use App\Models\Tenant\UsageDecision;
use App\Models\Tenant\GRNLineItem;
use App\Models\Tenant\QCParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QCService
{
    /**
     * Create inspection lot from GRN line item
     */
    public function createInspectionLot(array $data, int $userId): InspectionLot
    {
        // Get GRN line item
        $grnLineItem = GRNLineItem::findOrFail($data['grn_line_id']);
        
        // Check if lot already exists for this GRN line
        $existingLot = InspectionLot::where('grn_line_id', $data['grn_line_id'])->first();
        if ($existingLot) {
            throw new \Exception('Inspection lot already exists for this GRN line item');
        }
        
        return DB::connection('tenant')->transaction(function () use ($data, $grnLineItem, $userId) {
            // Get sampling method from material master (default to AQL)
            $samplingMethod = $data['sampling_method'] ?? 'AQL';
            
            // Calculate sample size (10% of lot qty or minimum 1, max 100)
            $lotQty = $data['lot_qty'] ?? $grnLineItem->accepted_qty;
            $sampleSize = $data['sample_size'] ?? max(1, min(100, ceil($lotQty * 0.10)));
            
            // Create inspection lot
            $lot = InspectionLot::create([
                'lot_number' => InspectionLot::generateLotNumber(),
                'grn_id' => $data['grn_id'] ?? $grnLineItem->grn_id,
                'grn_line_id' => $data['grn_line_id'],
                'material_id' => $data['material_id'] ?? $grnLineItem->material_id,
                'lot_qty' => $lotQty,
                'sample_size' => $sampleSize,
                'sampling_method' => $samplingMethod,
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_by' => $data['due_by'] ?? null,
                'status' => 'PENDING',
                'remarks' => $data['remarks'] ?? null,
            ]);
            
            // Auto-populate test parameters from QC parameters master
            $this->autoPopulateTestParameters($lot);
            
            Log::info('Inspection lot created', [
                'lot_id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'grn_line_id' => $grnLineItem->id,
                'created_by' => $userId,
            ]);
            
            return $lot->load(['testResults', 'usageDecision']);
        });
    }

    /**
     * Update inspection lot
     */
    public function updateInspectionLot(int $id, array $data, int $userId): InspectionLot
    {
        $lot = InspectionLot::findOrFail($id);
        
        if (!$lot->canEdit()) {
            throw new \Exception('Inspection lot cannot be edited in current status: ' . $lot->status);
        }
        
        $lot->update([
            'sample_size' => $data['sample_size'] ?? $lot->sample_size,
            'assigned_to' => $data['assigned_to'] ?? $lot->assigned_to,
            'due_by' => $data['due_by'] ?? $lot->due_by,
            'remarks' => $data['remarks'] ?? $lot->remarks,
        ]);
        
        Log::info('Inspection lot updated', [
            'lot_id' => $lot->id,
            'lot_number' => $lot->lot_number,
            'updated_by' => $userId,
        ]);
        
        return $lot->load(['testResults', 'usageDecision']);
    }

    /**
     * Start inspection (PENDING → IN_PROGRESS)
     */
    public function startInspection(int $id, int $userId): InspectionLot
    {
        $lot = InspectionLot::findOrFail($id);
        
        if ($lot->status !== 'PENDING') {
            throw new \Exception('Inspection lot must be in PENDING status to start');
        }
        
        $lot->update([
            'status' => 'IN_PROGRESS',
        ]);
        
        Log::info('Inspection started', [
            'lot_id' => $lot->id,
            'lot_number' => $lot->lot_number,
            'started_by' => $userId,
        ]);
        
        return $lot->load(['testResults', 'usageDecision']);
    }

    /**
     * Complete inspection (IN_PROGRESS → COMPLETED)
     */
    public function completeInspection(int $id, int $userId): InspectionLot
    {
        $lot = InspectionLot::findOrFail($id);
        
        if (!$lot->canComplete()) {
            throw new \Exception('Inspection lot must be in IN_PROGRESS status to complete');
        }
        
        // Check if all test parameters have results
        $pendingTests = $lot->testResults()->whereNull('is_pass')->count();
        if ($pendingTests > 0) {
            throw new \Exception("Cannot complete inspection. {$pendingTests} test(s) pending results.");
        }
        
        $lot->update([
            'status' => 'COMPLETED',
        ]);
        
        Log::info('Inspection completed', [
            'lot_id' => $lot->id,
            'lot_number' => $lot->lot_number,
            'completed_by' => $userId,
        ]);
        
        return $lot->load(['testResults', 'usageDecision']);
    }

    /**
     * Record test result
     */
    public function recordTestResult(int $lotId, array $data, int $userId): InspectionResult
    {
        $lot = InspectionLot::findOrFail($lotId);
        
        if (!$lot->canEdit()) {
            throw new \Exception('Cannot record test result in current status: ' . $lot->status);
        }
        
        // Check if parameter exists in QC parameters master
        $parameter = QCParameter::where('material_id', $lot->material_id)
            ->where('parameter_name', $data['parameter_name'])
            ->where('is_active', true)
            ->first();
        
        // Get standard values from parameter or use provided values
        $standardMin = $data['standard_min'] ?? ($parameter->standard_min ?? null);
        $standardMax = $data['standard_max'] ?? ($parameter->standard_max ?? null);
        $standardValue = $data['standard_value'] ?? ($parameter->standard_value ?? null);
        
        // Determine pass/fail
        $isPass = $this->evaluateTestResult($data['observed_value'], $standardMin, $standardMax, $standardValue);
        
        $result = InspectionResult::create([
            'lot_id' => $lotId,
            'parameter_name' => $data['parameter_name'],
            'standard_min' => $standardMin,
            'standard_max' => $standardMax,
            'standard_value' => $standardValue,
            'observed_value' => $data['observed_value'],
            'unit_of_measurement' => $data['unit_of_measurement'] ?? null,
            'is_pass' => $isPass,
            'remarks' => $data['remarks'] ?? null,
        ]);
        
        Log::info('Test result recorded', [
            'lot_id' => $lotId,
            'parameter' => $data['parameter_name'],
            'observed_value' => $data['observed_value'],
            'is_pass' => $isPass,
            'recorded_by' => $userId,
        ]);
        
        return $result;
    }

    /**
     * Make usage decision (COMPLETED → DECISION_MADE)
     */
    public function makeUsageDecision(int $id, array $data, int $userId): UsageDecision
    {
        $lot = InspectionLot::findOrFail($id);
        
        if (!$lot->canMakeDecision()) {
            throw new \Exception('Inspection lot must be in COMPLETED status to make decision');
        }
        
        return DB::connection('tenant')->transaction(function () use ($lot, $data, $userId) {
            $decision = $data['decision'];
            $acceptedQty = $data['accepted_qty'] ?? 0;
            $rejectedQty = $data['rejected_qty'] ?? 0;
            
            // Validate quantities
            if ($decision === 'ACCEPTED' && $acceptedQty <= 0) {
                throw new \Exception('Accepted quantity must be greater than 0');
            }
            if ($decision === 'REJECTED' && $rejectedQty <= 0) {
                throw new \Exception('Rejected quantity must be greater than 0');
            }
            
            // Create usage decision
            $usageDecision = UsageDecision::create([
                'lot_id' => $lot->id,
                'decision' => $decision,
                'accepted_qty' => $acceptedQty,
                'rejected_qty' => $rejectedQty,
                'override_approved_by' => $data['override_approved_by'] ?? null,
                'override_reason' => $data['override_reason'] ?? null,
                'coa_file_path' => $data['coa_file_path'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'decided_by' => $userId,
                'decided_at' => now(),
            ]);
            
            // Update lot status
            $lot->update([
                'status' => 'DECISION_MADE',
            ]);
            
            // Update GRN line item stock status based on decision
            $this->updateGRNLineStockStatus($lot->grn_line_id, $decision);
            
            Log::info('Usage decision made', [
                'lot_id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'decision' => $decision,
                'decided_by' => $userId,
            ]);
            
            return $usageDecision->load(['inspectionLot']);
        });
    }

    /**
     * Auto-populate test parameters from QC parameters master
     */
    private function autoPopulateTestParameters(InspectionLot $lot): void
    {
        $parameters = QCParameter::where('material_id', $lot->material_id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        foreach ($parameters as $parameter) {
            InspectionResult::create([
                'lot_id' => $lot->id,
                'parameter_name' => $parameter->parameter_name,
                'standard_min' => $parameter->standard_min,
                'standard_max' => $parameter->standard_max,
                'standard_value' => $parameter->standard_value,
                'unit_of_measurement' => $parameter->unit_of_measurement,
                'is_pass' => null, // Not yet tested
                'remarks' => null,
            ]);
        }
    }

    /**
     * Evaluate test result against standard values
     */
    private function evaluateTestResult(string $observed, ?string $min, ?string $max, ?string $value): ?bool
    {
        $observedNum = floatval($observed);
        
        // If exact value specified
        if ($value !== null) {
            $valueNum = floatval($value);
            return abs($observedNum - $valueNum) < 0.01; // Allow small tolerance
        }
        
        // If range specified
        if ($min !== null && $max !== null) {
            $minNum = floatval($min);
            $maxNum = floatval($max);
            return $observedNum >= $minNum && $observedNum <= $maxNum;
        }
        
        // If no standard specified, mark as not tested
        return null;
    }

    /**
     * Update GRN line item stock status based on usage decision
     */
    private function updateGRNLineStockStatus(int $grnLineId, string $decision): void
    {
        $grnLine = GRNLineItem::findOrFail($grnLineId);
        
        switch ($decision) {
            case 'ACCEPTED':
                $grnLine->update(['stock_status' => 'UNRESTRICTED']);
                break;
            case 'REJECTED':
                $grnLine->update(['stock_status' => 'BLOCKED']);
                break;
            case 'CONDITIONALLY_ACCEPTED':
                $grnLine->update(['stock_status' => 'RESTRICTED']);
                break;
            case 'REWORK_REQUIRED':
                $grnLine->update(['stock_status' => 'RETURNED']);
                break;
        }
    }
}
