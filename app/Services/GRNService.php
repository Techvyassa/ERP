<?php

namespace App\Services;

use App\Models\Tenant\GRN;
use App\Models\Tenant\GRNLineItem;
use App\Models\Tenant\MaterialReceipt;
use App\Models\Tenant\MRLineItem;
use App\Models\Tenant\PoLineItem;
use App\Services\QCService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GRNService
{
    /**
     * Create GRN from Material Receipt
     */
    public function createGRN(array $data, int $userId): GRN
    {
        // Validate Material Receipt
        $mr = MaterialReceipt::findOrFail($data['mr_id']);
        $this->validateMaterialReceipt($mr);
        
        return DB::connection('tenant')->transaction(function () use ($data, $mr, $userId) {
            // Create GRN header
            $grn = GRN::create([
                'grn_number' => GRN::generateGRNNumber(),
                'mr_id' => $data['mr_id'],
                'po_id' => $mr->po_id,
                'vendor_id' => $mr->vendor_id,
                'grn_date' => $data['grn_date'],
                'posting_date' => $data['posting_date'],
                'status' => 'PROVISIONAL',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);
            
            // Create line items
            $totalValue = 0;
            $totalTax = 0;
            
            foreach ($data['line_items'] as $item) {
                $lineItem = $this->createLineItem($grn->id, $item);
                $totalValue += $lineItem->line_value;
                $totalTax += $lineItem->tax_amount;
            }
            
            // Update GRN totals
            $grn->update([
                'total_received_value' => $totalValue,
                'total_tax_amount' => $totalTax,
                'grand_total' => $totalValue + $totalTax,
            ]);
            
            // Update Material Receipt status
            $mr->update(['status' => 'GRN_POSTED']);

            // Update PO line received/pending quantities
            $this->updatePOLineQuantities($grn);

            Log::info('GRN created', [
                'grn_id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'mr_id' => $mr->id,
                'created_by' => $userId,
                'mr_data' => $mr->toArray(),
                'po_data' => $mr->purchaseOrder->toArray(),
                'line_items' => $data['line_items'],
                'total_value' => $totalValue,
                'total_tax' => $totalTax,
                'grand_total' => $totalValue + $totalTax,
            ]);
            
            return $grn->load(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor']);
        });
    }

    /**
     * Update GRN
     */
    public function updateGRN(int $id, array $data, int $userId): GRN
    {
        $grn = GRN::findOrFail($id);
        
        if (!$grn->canEdit()) {
            throw new \Exception('GRN cannot be edited in current status: ' . $grn->status);
        }
        
        return DB::connection('tenant')->transaction(function () use ($grn, $data, $userId) {
            // Update header
            $grn->update([
                'grn_date' => $data['grn_date'] ?? $grn->grn_date,
                'posting_date' => $data['posting_date'] ?? $grn->posting_date,
                'remarks' => $data['remarks'] ?? $grn->remarks,
            ]);
            
            // Update line items if provided
            if (isset($data['line_items'])) {
                $totalValue = 0;
                $totalTax = 0;
                
                foreach ($data['line_items'] as $item) {
                    if (isset($item['id'])) {
                        $lineItem = GRNLineItem::findOrFail($item['id']);
                        $this->updateLineItem($lineItem, $item);
                    } else {
                        $lineItem = $this->createLineItem($grn->id, $item);
                    }
                    
                    $totalValue += $lineItem->line_value;
                    $totalTax += $lineItem->tax_amount;
                }
                
                // Update totals
                $grn->update([
                    'total_received_value' => $totalValue,
                    'total_tax_amount' => $totalTax,
                    'grand_total' => $totalValue + $totalTax,
                ]);
            }
            
            Log::info('GRN updated', [
                'grn_id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'updated_by' => $userId,
            ]);
            
            return $grn->load(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor']);
        });
    }

    /**
     * Approve GRN (PROVISIONAL → QC_PENDING)
     */
    public function approveGRN(int $id, int $userId): GRN
    {
        Log::debug('[GRNService] Starting approveGRN', ['grn_id' => $id, 'user_id' => $userId]);
        
        $grn = GRN::findOrFail($id);
        Log::debug('[GRNService] GRN found', ['grn_id' => $grn->id, 'grn_number' => $grn->grn_number, 'status' => $grn->status]);
        
        if (!$grn->canApprove()) {
            Log::warning('[GRNService] GRN cannot be approved', ['grn_id' => $grn->id, 'status' => $grn->status]);
            throw new \Exception('GRN cannot be approved in current status: ' . $grn->status);
        }
        
        // Update GRN status
        $grn->update([
            'status' => 'QC_PENDING',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        Log::debug('[GRNService] GRN status updated to QC_PENDING', ['grn_id' => $grn->id]);

        // Release stock from RESTRICTED to UNRESTRICTED
        $grn->lineItems()->update(['stock_status' => 'UNRESTRICTED']);
        Log::debug('[GRNService] Stock status updated', ['grn_id' => $grn->id]);

        // Auto-create inspection lot for Quality department
        try {
            Log::debug('[GRNService] Attempting to create inspection lot', ['grn_id' => $grn->id]);
            
            $qcService = app(QCService::class);
            Log::debug('[GRNService] QCService instantiated', ['grn_id' => $grn->id]);
            
            $inspectionLot = $qcService->createInspectionLot($grn, $userId);
            
            Log::info('[GRNService] GRN approved and inspection lot created', [
                'grn_id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'inspection_lot_id' => $inspectionLot->id,
                'approved_by' => $userId,
            ]);
        } catch (\Exception $e) {
            Log::error('[GRNService] Failed to create inspection lot for GRN', [
                'grn_id' => $grn->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail the approval if inspection lot creation fails
            // Quality team can manually create it if needed
        }
        
        return $grn->load(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor']);
    }

    /**
     * Cancel GRN
     */
    public function cancelGRN(int $id, string $reason, int $userId): GRN
    {
        $grn = GRN::findOrFail($id);
        
        if (!$grn->canCancel()) {
            throw new \Exception('GRN cannot be cancelled in current status: ' . $grn->status);
        }
        
        $grn->update([
            'status' => 'CANCELLED',
            'remarks' => ($grn->remarks ?? '') . "\nCancellation Reason: " . $reason,
        ]);
        
        // Revert Material Receipt status
        $grn->materialReceipt->update(['status' => 'COMPLETED']);
        
        Log::info('GRN cancelled', [
            'grn_id' => $grn->id,
            'grn_number' => $grn->grn_number,
            'reason' => $reason,
            'cancelled_by' => $userId,
        ]);
        
        return $grn->load(['lineItems', 'materialReceipt', 'purchaseOrder', 'vendor']);
    }

    /**
     * Update PO line received/pending quantities after GRN creation
     */
    private function updatePOLineQuantities(GRN $grn): void
    {
        // Load GRN line items with their MR line items
        $grn->load('lineItems.mrLineItem');

        Log::info('Updating PO line quantities for GRN', [
            'grn_id' => $grn->id,
            'grn_number' => $grn->grn_number,
            'line_items_count' => $grn->lineItems->count(),
        ]);

        foreach ($grn->lineItems as $grnLine) {
            $mrLine = $grnLine->mrLineItem;
            if (!$mrLine || !$mrLine->po_line_id) {
                Log::warning('MR line or PO line ID missing', [
                    'grn_line_id' => $grnLine->id,
                    'mr_line_id' => $mrLine?->id,
                    'po_line_id' => $mrLine?->po_line_id,
                ]);
                continue;
            }

            $poLine = PoLineItem::find($mrLine->po_line_id);
            if (!$poLine) {
                Log::warning('PO line not found', [
                    'po_line_id' => $mrLine->po_line_id,
                ]);
                continue;
            }

            // Add accepted qty to received_qty
            $newReceivedQty = $poLine->received_qty + $grnLine->accepted_qty;
            $newPendingQty = max(0, $poLine->ordered_qty - $newReceivedQty);

            // Determine receipt status
            $receiptStatus = 'OPEN';
            if ($newPendingQty <= 0) {
                $receiptStatus = 'COMPLETE';
            } elseif ($newReceivedQty > 0) {
                $receiptStatus = 'PARTIAL';
            }

            Log::info('Updating PO line', [
                'po_line_id' => $poLine->id,
                'old_received_qty' => $poLine->received_qty,
                'grn_accepted_qty' => $grnLine->accepted_qty,
                'new_received_qty' => $newReceivedQty,
                'new_pending_qty' => $newPendingQty,
                'receipt_status' => $receiptStatus,
            ]);

            $poLine->update([
                'received_qty' => $newReceivedQty,
                'receipt_status' => $receiptStatus,
            ]);
        }

        // Update PO header status
        $po = $grn->purchaseOrder;
        if ($po) {
            $allLines = $po->lineItems;
            $allFullyReceived = $allLines->every(fn($l) => $l->fresh()->receipt_status === 'COMPLETE');
            $anyReceived = $allLines->some(fn($l) => $l->fresh()->received_qty > 0);

            Log::info('Updating PO status', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'all_fully_received' => $allFullyReceived,
                'any_received' => $anyReceived,
            ]);

            if ($allFullyReceived) {
                $po->update(['status' => 'FULLY_RECEIVED']);
            } elseif ($anyReceived) {
                $po->update(['status' => 'PARTIALLY_RECEIVED']);
            }
        }
    }

    /**
     * Create GRN line item
     */
    private function createLineItem(int $grnId, array $item): GRNLineItem
    {
        // Get MR line item for reference
        $mrLineItem = MRLineItem::findOrFail($item['mr_line_id']);
        
        // Get PO line item for pricing
        $poLineItem = PoLineItem::findOrFail($mrLineItem->po_line_id);
        
        // Calculate line value and tax
        $acceptedQty = $item['accepted_qty'];
        $unitPrice = $item['unit_price'];
        $taxRate = $item['tax_rate'] ?? 0;
        
        $lineValue = round($acceptedQty * $unitPrice, 2);
        $taxAmount = round($lineValue * ($taxRate / 100), 2);
        
        Log::info('GRN Line Item created', [
            'grn_id' => $grnId,
            'mr_line_id' => $mrLineItem->id,
            'po_line_id' => $poLineItem->id,
            'material_id' => $item['material_id'],
            'accepted_qty' => $acceptedQty,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_value' => $lineValue,
            'tax_amount' => $taxAmount,
            'batch_number' => $item['batch_number'] ?? null,
            'warehouse_bin_id' => $item['warehouse_bin_id'] ?? null,
        ]);
        
        return GRNLineItem::create([
            'grn_id' => $grnId,
            'mr_line_id' => $item['mr_line_id'],
            'material_id' => $item['material_id'],
            'accepted_qty' => $acceptedQty,
            'uom_id' => $item['uom_id'],
            'batch_number' => $item['batch_number'] ?? $mrLineItem->batch_number,
            'manufacturing_date' => $item['manufacturing_date'] ?? $mrLineItem->manufacturing_date,
            'expiry_date' => $item['expiry_date'] ?? $mrLineItem->expiry_date,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_value' => $lineValue,
            'tax_amount' => $taxAmount,
            'warehouse_bin_id' => $item['warehouse_bin_id'] ?? null,
            'stock_status' => 'RESTRICTED', // Default: awaiting QC
        ]);
    }

    /**
     * Update GRN line item
     */
    private function updateLineItem(GRNLineItem $lineItem, array $item): GRNLineItem
    {
        $acceptedQty = $item['accepted_qty'] ?? $lineItem->accepted_qty;
        $unitPrice = $lineItem->unit_price;
        $taxRate = $lineItem->tax_rate;
        
        // Recalculate if quantity changed
        if (isset($item['accepted_qty'])) {
            $lineValue = round($acceptedQty * $unitPrice, 2);
            $taxAmount = round($lineValue * ($taxRate / 100), 2);
            
            $lineItem->update([
                'accepted_qty' => $acceptedQty,
                'line_value' => $lineValue,
                'tax_amount' => $taxAmount,
            ]);
        }
        
        // Update other fields
        $lineItem->update([
            'batch_number' => $item['batch_number'] ?? $lineItem->batch_number,
            'manufacturing_date' => $item['manufacturing_date'] ?? $lineItem->manufacturing_date,
            'expiry_date' => $item['expiry_date'] ?? $lineItem->expiry_date,
            'warehouse_bin_id' => $item['warehouse_bin_id'] ?? $lineItem->warehouse_bin_id,
        ]);
        
        return $lineItem;
    }

    /**
     * Validate Material Receipt
     */
    private function validateMaterialReceipt(MaterialReceipt $mr): void
    {
        if (!in_array($mr->status, ['PENDING_GRN', 'COMPLETED'])) {
            throw new \Exception('Material Receipt must be in PENDING_GRN or COMPLETED status. Current status: ' . $mr->status);
        }
    }
}
