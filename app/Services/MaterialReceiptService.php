<?php

namespace App\Services;

use App\Models\Tenant\MaterialReceipt;
use App\Models\Tenant\MRLineItem;
use App\Models\Tenant\GateEntry;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PoLineItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialReceiptService
{
    /**
     * Create material receipt
     */
    public function createMaterialReceipt(array $data, int $userId): MaterialReceipt
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId) {
            // Validate gate entry and PO
            $this->validateGateEntry($data['ge_id']);
            $this->validatePO($data['po_id']);
            
            // Generate MR number
            $mrNumber = MaterialReceipt::generateMRNumber();
            
            // Create MR header
            $mr = MaterialReceipt::create([
                'mr_number' => $mrNumber,
                'ge_id' => $data['ge_id'],
                'po_id' => $data['po_id'],
                'vendor_id' => $data['vendor_id'],
                'unloading_started_at' => $data['unloading_started_at'] ?? now(),
                'status' => 'IN_PROGRESS',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);
            
            // Create line items with variance calculation
            foreach ($data['line_items'] as $item) {
                $this->createLineItem($mr->id, $item);
            }
            
            Log::info('Material Receipt created', [
                'mr_id' => $mr->id,
                'mr_number' => $mr->mr_number,
                'ge_id' => $mr->ge_id,
                'created_by' => $userId,
            ]);
            
            return $mr->load(['lineItems', 'gateEntry', 'purchaseOrder', 'vendor']);
        });
    }

    /**
     * Update material receipt
     */
    public function updateMaterialReceipt(int $id, array $data, int $userId): MaterialReceipt
    {
        $mr = MaterialReceipt::findOrFail($id);
        
        if (!$mr->canEdit()) {
            throw new \Exception('Material Receipt cannot be edited in current status: ' . $mr->status);
        }
        
        return DB::connection('tenant')->transaction(function () use ($mr, $data, $userId) {
            // Update header
            $mr->update([
                'unloading_completed_at' => $data['unloading_completed_at'] ?? $mr->unloading_completed_at,
                'remarks' => $data['remarks'] ?? $mr->remarks,
                'updated_by' => $userId,
            ]);
            
            // Update line items if provided
            if (isset($data['line_items'])) {
                foreach ($data['line_items'] as $item) {
                    if (isset($item['id'])) {
                        // Update existing line item
                        $lineItem = MRLineItem::findOrFail($item['id']);
                        $this->updateLineItem($lineItem, $item);
                    } else {
                        // Create new line item
                        $this->createLineItem($mr->id, $item);
                    }
                }
            }
            
            Log::info('Material Receipt updated', [
                'mr_id' => $mr->id,
                'mr_number' => $mr->mr_number,
                'updated_by' => $userId,
            ]);
            
            return $mr->load(['lineItems', 'gateEntry', 'purchaseOrder', 'vendor']);
        });
    }

    /**
     * Start unloading
     */
    public function startUnloading(int $id, int $userId): MaterialReceipt
    {
        $mr = MaterialReceipt::findOrFail($id);
        
        $mr->update([
            'unloading_started_at' => now(),
            'updated_by' => $userId,
        ]);
        
        Log::info('Unloading started', [
            'mr_id' => $mr->id,
            'started_by' => $userId,
        ]);
        
        return $mr->load(['lineItems', 'gateEntry']);
    }

    /**
     * Complete unloading
     */
    public function completeUnloading(int $id, int $userId): MaterialReceipt
    {
        $mr = MaterialReceipt::findOrFail($id);
        
        if (!$mr->canComplete()) {
            throw new \Exception('Material Receipt cannot be completed in current status: ' . $mr->status);
        }
        
        $mr->update([
            'unloading_completed_at' => now(),
            'status' => 'PENDING_GRN',
            'updated_by' => $userId,
        ]);
        
        Log::info('Unloading completed', [
            'mr_id' => $mr->id,
            'completed_by' => $userId,
        ]);
        
        return $mr->load(['lineItems', 'gateEntry', 'purchaseOrder']);
    }

    /**
     * Create line item with variance calculation
     */
    private function createLineItem(int $mrId, array $item): MRLineItem
    {
        $poLineItem = PoLineItem::findOrFail($item['po_line_id']);
        
        // Calculate variances
        $variances = $this->calculateVariances($item, $poLineItem);
        
        // Check tolerances
        $tolerances = $this->checkTolerances($poLineItem, $variances['shortage'], $variances['excess']);
        
        // Generate internal barcode
        $internalBarcode = 'MR-' . $mrId . '-' . uniqid();
        
        return MRLineItem::create([
            'mr_id' => $mrId,
            'po_line_id' => $item['po_line_id'],
            'material_id' => $item['material_id'],
            'received_qty' => $item['received_qty'],
            'shortage_qty' => $variances['shortage'],
            'excess_qty' => $variances['excess'],
            'rejected_on_arrival' => $item['rejected_on_arrival'] ?? 0,
            'uom_id' => $item['uom_id'],
            'shortage_flag' => $tolerances['shortage_flag'],
            'excess_flag' => $tolerances['excess_flag'],
            'batch_number' => $item['batch_number'] ?? null,
            'manufacturing_date' => $item['manufacturing_date'] ?? null,
            'expiry_date' => $item['expiry_date'] ?? null,
            'provisional_bin_id' => $item['provisional_bin_id'] ?? null,
            'damage_found' => $item['damage_found'] ?? false,
            'damage_remarks' => $item['damage_remarks'] ?? null,
            'internal_barcode' => $internalBarcode,
        ]);
    }

    /**
     * Update line item with variance recalculation
     */
    private function updateLineItem(MRLineItem $lineItem, array $item): MRLineItem
    {
        // Get po_line_id from input or existing line item
        $poLineId = $item['po_line_id'] ?? $lineItem->po_line_id;
        $poLineItem = PoLineItem::findOrFail($poLineId);
        
        // Get received_qty from input or existing line item
        $receivedQty = $item['received_qty'] ?? $lineItem->received_qty;
        
        // Only recalculate variances if received_qty changed
        if (isset($item['received_qty'])) {
            $itemForCalculation = ['received_qty' => $receivedQty];
            $variances = $this->calculateVariances($itemForCalculation, $poLineItem);
            $tolerances = $this->checkTolerances($poLineItem, $variances['shortage'], $variances['excess']);
            
            $lineItem->update([
                'received_qty' => $receivedQty,
                'shortage_qty' => $variances['shortage'],
                'excess_qty' => $variances['excess'],
                'shortage_flag' => $tolerances['shortage_flag'],
                'excess_flag' => $tolerances['excess_flag'],
            ]);
        }
        
        // Update other fields
        $lineItem->update([
            'rejected_on_arrival' => $item['rejected_on_arrival'] ?? $lineItem->rejected_on_arrival,
            'batch_number' => $item['batch_number'] ?? $lineItem->batch_number,
            'manufacturing_date' => $item['manufacturing_date'] ?? $lineItem->manufacturing_date,
            'expiry_date' => $item['expiry_date'] ?? $lineItem->expiry_date,
            'provisional_bin_id' => $item['provisional_bin_id'] ?? $lineItem->provisional_bin_id,
            'damage_found' => $item['damage_found'] ?? $lineItem->damage_found,
            'damage_remarks' => $item['damage_remarks'] ?? $lineItem->damage_remarks,
        ]);
        
        return $lineItem;
    }

    /**
     * Calculate variances (shortage/excess)
     */
    private function calculateVariances(array $lineItem, PoLineItem $poLineItem): array
    {
        $receivedQty = $lineItem['received_qty'];
        $orderedQty = $poLineItem->ordered_qty;
        
        $variance = $receivedQty - $orderedQty;
        
        return [
            'variance' => $variance,
            'shortage' => $variance < 0 ? abs($variance) : 0,
            'excess' => $variance > 0 ? $variance : 0,
        ];
    }

    /**
     * Check tolerances and set flags
     */
    private function checkTolerances(PoLineItem $poLineItem, float $shortage, float $excess): array
    {
        $orderedQty = $poLineItem->ordered_qty;
        $underTolerance = $poLineItem->under_delivery_tolerance ?? 0;
        $overTolerance = $poLineItem->over_delivery_tolerance ?? 0;
        
        // Calculate tolerance amounts
        $underToleranceQty = ($orderedQty * $underTolerance) / 100;
        $overToleranceQty = ($orderedQty * $overTolerance) / 100;
        
        return [
            'shortage_flag' => $shortage > $underToleranceQty,
            'excess_flag' => $excess > $overToleranceQty,
        ];
    }

    /**
     * Validate gate entry
     */
    private function validateGateEntry(int $geId): void
    {
        $ge = GateEntry::find($geId);
        
        if (!$ge) {
            throw new \Exception('Gate Entry not found');
        }
        
        if ($ge->status !== 'MOVED_TO_DOCK') {
            throw new \Exception('Gate Entry must be in MOVED_TO_DOCK status');
        }
    }

    /**
     * Validate PO
     */
    private function validatePO(int $poId): void
    {
        $po = PurchaseOrder::find($poId);
        
        if (!$po) {
            throw new \Exception('Purchase Order not found');
        }
        
        if (!in_array($po->status, ['APPROVED', 'OPEN', 'PARTIAL'])) {
            throw new \Exception('Purchase Order must be in APPROVED, OPEN, or PARTIAL status');
        }
    }
}
