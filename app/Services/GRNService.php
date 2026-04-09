<?php

namespace App\Services;

use App\Models\Tenant\GRN;
use App\Models\Tenant\GRNLineItem;
use App\Models\Tenant\GateEntry;
use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\MaterialReceipt;
use App\Models\Tenant\MRLineItem;
use App\Models\Tenant\PoLineItem;
use App\Models\Tenant\StockBalance;
use App\Services\QCService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GRNService
{
    /**
     * Auto-create GRN from Gate Entry (new inward flow).
     * Reads PO line items directly; one GRN line per PO line.
     * Auto-generates batch number and triggers QC inspection lot per line.
     */
    public function createGRNFromGateEntry(GateEntry $ge, int $userId): GRN
    {
        if (!$ge->canCreateGRN()) {
            throw new \Exception('Gate entry cannot create a GRN in current status: ' . $ge->status);
        }

        return DB::connection('tenant')->transaction(function () use ($ge, $userId) {
            $po = $ge->purchaseOrder()->with('lineItems.material', 'lineItems.uom')->firstOrFail();

            // Create GRN header
            $today = now()->toDateString();
            $grn = GRN::create([
                'grn_number'   => GRN::generateGRNNumber(),
                'ge_id'        => $ge->id,
                'mr_id'        => null,
                'po_id'        => $po->id,
                'vendor_id'    => $ge->vendor_id,
                'grn_date'     => $today,
                'posting_date' => $today,
                'status'       => 'PROVISIONAL',
                'created_by'   => $userId,
            ]);

            $totalValue = 0;
            $totalTax   = 0;

            foreach ($po->lineItems as $poLine) {
                // Auto-generate batch: GE-number + material code + sequence
                $batchNumber = strtoupper($ge->ge_number . '-' . ($poLine->material->material_code ?? $poLine->material_id));

                $unitPrice = $poLine->unit_price ?? 0;
                $taxRate   = $poLine->tax_rate   ?? 0;
                $acceptedQty = $poLine->ordered_qty - ($poLine->received_qty ?? 0);
                $acceptedQty = max(0, $acceptedQty);

                $lineValue = round($acceptedQty * $unitPrice, 2);
                $taxAmount = round($lineValue * ($taxRate / 100), 2);

                $grnLine = GRNLineItem::create([
                    'grn_id'       => $grn->id,
                    'mr_line_id'   => null,
                    'po_line_id'   => $poLine->id,
                    'material_id'  => $poLine->material_id,
                    'accepted_qty' => $acceptedQty,
                    'uom_id'       => $poLine->uom_id,
                    'batch_number' => $batchNumber,
                    'unit_price'   => $unitPrice,
                    'tax_rate'     => $taxRate,
                    'line_value'   => $lineValue,
                    'tax_amount'   => $taxAmount,
                    'stock_status' => 'QC_HOLD', // Awaiting inspection — not yet available
                ]);

                $totalValue += $lineValue;
                $totalTax   += $taxAmount;

                // --- LEDGER: Post GRN_RECEIPT → QC_HOLD ---
                // Stock physically sits at the receiving dock; NOT available until QC passes + putaway.
                if ($acceptedQty > 0 && $poLine->material) {
                    $warehouse = $ge->purchaseOrder?->warehouse_id
                                 ?? $ge->vendor?->default_warehouse_id
                                 ?? $poLine->material->default_warehouse_id
                                 ?? null;
                    
                    if (!$warehouse) {
                        Log::error('[GRNService] Warehouse could not be resolved for GRN line', [
                            'grn_id' => $grn->id,
                            'material_id' => $poLine->material_id
                        ]);
                        throw new \Exception("Cannot auto-create GRN: warehouse could not be determined for material {$poLine->material_id}. Set a default warehouse in Material Master or Vendor Master.");
                    }

                    app(StockService::class)->post(
                        [
                            'material_id'  => $poLine->material_id,
                            'uom_id'       => $poLine->uom_id,
                            'warehouse_id' => $warehouse,
                            'batch_number' => $batchNumber,
                        ],
                        'QC_HOLD',
                        +$acceptedQty,
                        'GRN_RECEIPT',
                        'GRN',
                        $grn->id,
                        $grn->grn_number,
                        $userId,
                        null, // bin unknown — goods just arrived at dock
                        $unitPrice,
                        'Stock arrived at receiving dock — QC pending'
                    );
                }

                // Auto-trigger QC inspection lot per line (one lot per GRN line enforced by DB unique)
                try {
                    $qcService = app(QCService::class);
                    $qcService->createInspectionLotForLine($grn, $grnLine, $userId);
                } catch (\Exception $e) {
                    Log::warning('[GRNService] QC lot creation failed for line, continuing', [
                        'grn_line_id' => $grnLine->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }

            // Update GRN totals
            $grn->update([
                'total_received_value' => $totalValue,
                'total_tax_amount'     => $totalTax,
                'grand_total'          => $totalValue + $totalTax,
            ]);

            // Mark gate entry as COMPLETED
            $ge->update(['status' => 'COMPLETED']);

            Log::info('[GRNService] GRN auto-created from gate entry', [
                'grn_id'     => $grn->id,
                'grn_number' => $grn->grn_number,
                'ge_id'      => $ge->id,
                'lines'      => $grn->lineItems->count(),
            ]);

            return $grn->load(['lineItems', 'gateEntry', 'purchaseOrder', 'vendor']);
        });
    }

    /**
     * Create GRN from Material Receipt (legacy flow — kept for backward compat)
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
            'status'      => 'QC_PENDING',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        Log::debug('[GRNService] GRN status updated to QC_PENDING — stock remains in QC_HOLD bucket until QC decision.', ['grn_id' => $grn->id]);
        // NOTE: We do NOT flip stock_status to UNRESTRICTED here.
        // Stock stays in QC_HOLD and only moves to PUTAWAY_PENDING after QC passes via StockService::transfer().

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
     * Apply QC decision to GRN line items.
     * Writes back accepted_qty / rejected_qty from QC decision, transitions GRN status.
     *
     * @param GRN $grn The GRN header
     * @param array $qcDecisions Array of [grn_line_id => ['accepted_qty' => x, 'rejected_qty' => y]]
     * @param int $userId User performing the action
     * @return GRN Updated GRN
     */
    public function applyQCDecision(GRN $grn, array $qcDecisions, int $userId): GRN
    {
        return DB::connection('tenant')->transaction(function () use ($grn, $qcDecisions, $userId) {
            $totalAccepted = 0;
            $totalRejected = 0;
            $putawayService = null;

            foreach ($qcDecisions as $lineId => $decision) {
                $lineItem = GRNLineItem::findOrFail($lineId);

                $acceptedQty = (float) ($decision['accepted_qty'] ?? 0);
                $rejectedQty = (float) ($decision['rejected_qty'] ?? 0);
                $returnQty = (float) ($decision['return_qty'] ?? 0);
                $returnRemarks = $decision['return_remarks'] ?? null;
                $sourceBinId = $decision['source_bin_id'] ?? $lineItem->warehouse_bin_id;

                // Determine stock status per line (human-readable column, mirrors bucket state)
                if ($acceptedQty > 0 && $rejectedQty === 0) {
                    $stockStatus = 'PUTAWAY_PENDING'; // Will move to AVAILABLE after putaway
                } elseif ($rejectedQty > 0 && $acceptedQty === 0) {
                    $stockStatus = 'BLOCKED';
                } else {
                    $stockStatus = 'QC_HOLD'; // Mixed or pending
                }

                $updates = [
                    'accepted_qty' => $acceptedQty,
                    'rejected_qty' => $rejectedQty,
                    'stock_status' => $stockStatus,
                ];

                $warehouseId = $this->resolveWarehouseIdForGrnLine($lineItem);
                if (($acceptedQty > 0 || $rejectedQty > 0) && !$warehouseId) {
                    throw new \Exception(
                        "Cannot post QC stock for GRN line {$lineItem->id}: warehouse could not be resolved. " .
                        'Set the purchase order warehouse or line bin warehouse before making the QC decision.'
                    );
                }

                // Validate QC_HOLD stock only for initial QC decision, not for post-QC edits
                // Check if this is a post-QC edit by seeing if quantities were previously set
                $isPostQCEdit = ($lineItem->accepted_qty > 0 || $lineItem->rejected_qty > 0);
                
                if (!$isPostQCEdit && ($acceptedQty > 0 || $rejectedQty > 0)) {
                    $requiredQcHoldQty = round($acceptedQty + $rejectedQty, 3);
                    $qcHoldQty = $this->getMaterialBucketQty($lineItem, $warehouseId, 'QC_HOLD');

                    if ($qcHoldQty < $requiredQcHoldQty) {
                        throw new \Exception(
                            "Cannot post QC decision for GRN line {$lineItem->id}: QC_HOLD stock is {$qcHoldQty} but {$requiredQcHoldQty} is required. " .
                            'This GRN likely predates the stock ledger feature and needs stock backfill before QC can proceed.'
                        );
                    }
                }

                // For post-QC edits, reverse previous stock movements first
                if ($isPostQCEdit) {
                    $oldAcceptedQty = (float) $lineItem->accepted_qty;
                    $oldRejectedQty = (float) $lineItem->rejected_qty;
                    
                    // Reverse previous accepted qty movement (PUTAWAY_PENDING → QC_HOLD)
                    if ($oldAcceptedQty > 0 && $lineItem->material_id) {
                        try {
                            app(StockService::class)->transfer(
                                [
                                    'material_id'  => $lineItem->material_id,
                                    'uom_id'       => $lineItem->uom_id,
                                    'warehouse_id' => $warehouseId,
                                    'batch_number' => $lineItem->batch_number,
                                ],
                                'PUTAWAY_PENDING', // from
                                'QC_HOLD',          // to
                                $oldAcceptedQty,
                                'STOCK_ADJUSTMENT', // Use existing ENUM value
                                'GRN',
                                $lineItem->grn_id,
                                $lineItem->grn?->grn_number ?? "GRN-{$lineItem->grn_id}",
                                $userId,
                                null,
                                null,
                                $lineItem->unit_price,
                                "Reversing previous QC pass for post-QC edit"
                            );
                        } catch (\Exception $e) {
                            Log::error('[GRNService] Failed to reverse previous QC pass', [
                                'grn_line_id' => $lineItem->id,
                                'material_id' => $lineItem->material_id,
                                'qty' => $oldAcceptedQty,
                                'error' => $e->getMessage(),
                            ]);
                            throw new \Exception(
                                "Failed to reverse previous QC acceptance for line {$lineItem->id}: " . $e->getMessage()
                            );
                        }
                    }
                    
                    // Reverse previous rejected qty movement (BLOCKED → QC_HOLD)
                    if ($oldRejectedQty > 0 && $lineItem->material_id) {
                        try {
                            app(StockService::class)->transfer(
                                [
                                    'material_id'  => $lineItem->material_id,
                                    'uom_id'       => $lineItem->uom_id,
                                    'warehouse_id' => $warehouseId,
                                    'batch_number' => $lineItem->batch_number,
                                ],
                                'BLOCKED',         // from
                                'QC_HOLD',         // to
                                $oldRejectedQty,
                                'STOCK_ADJUSTMENT', // Use existing ENUM value
                                'GRN',
                                $lineItem->grn_id,
                                $lineItem->grn?->grn_number ?? "GRN-{$lineItem->grn_id}",
                                $userId,
                                null,
                                null,
                                null,
                                "Reversing previous QC reject for post-QC edit"
                            );
                        } catch (\Exception $e) {
                            Log::error('[GRNService] Failed to reverse previous QC reject', [
                                'grn_line_id' => $lineItem->id,
                                'material_id' => $lineItem->material_id,
                                'qty' => $oldRejectedQty,
                                'error' => $e->getMessage(),
                            ]);
                            throw new \Exception(
                                "Failed to reverse previous QC rejection for line {$lineItem->id}: " . $e->getMessage()
                            );
                        }
                    }
                }

                // --- LEDGER: Transfer accepted qty QC_HOLD → PUTAWAY_PENDING ---
                // Stock has passed QC but is still physically on the dock/forklift.
                // It is NOT yet available — that only happens when putaway is confirmed.
                if ($acceptedQty > 0 && $lineItem->material_id) {
                    // Use already-resolved $warehouseId from line 388 (already validated)
                    app(StockService::class)->transfer(
                        [
                            'material_id'  => $lineItem->material_id,
                            'uom_id'       => $lineItem->uom_id,
                            'warehouse_id' => $warehouseId,
                            'batch_number' => $lineItem->batch_number,
                        ],
                        'QC_HOLD',       // from
                        'PUTAWAY_PENDING',// to
                        $acceptedQty,
                        'QC_PASS',
                        'GRN',
                        $lineItem->grn_id,
                        $lineItem->grn?->grn_number ?? "GRN-{$lineItem->grn_id}",
                        $userId,
                        null,            // from bin: receiving dock (virtual, no specific bin)
                        null,            // to bin: staging (will be set when putaway starts)
                        $lineItem->unit_price,
                        "QC passed — moved to putaway queue"
                    );
                }

                // --- LEDGER: Transfer rejected qty QC_HOLD → BLOCKED ---
                if ($rejectedQty > 0 && $lineItem->material_id) {
                    // Use already-resolved $warehouseId from line 388 (already validated)
                    app(StockService::class)->transfer(
                        [
                            'material_id'  => $lineItem->material_id,
                            'uom_id'       => $lineItem->uom_id,
                            'warehouse_id' => $warehouseId,
                            'batch_number' => $lineItem->batch_number,
                        ],
                        'QC_HOLD',
                        'BLOCKED',
                        $rejectedQty,
                        'QC_REJECT',
                        'GRN',
                        $lineItem->grn_id,
                        $lineItem->grn?->grn_number ?? "GRN-{$lineItem->grn_id}",
                        $userId,
                        null,
                        null,
                        null,
                        "QC rejected — stock quarantined"
                    );
                }

                // Create putaway task for any accepted quantity, avoiding duplicates
                if ($acceptedQty > 0) {
                    $putawayService = app(PutawayService::class);

                    $existingTask = \App\Models\Tenant\PutawayTask::where('grn_line_id', $lineItem->id)
                        ->whereIn('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED'])
                        ->first();

                    if (!$existingTask) {
                        $putawayService->createPutawayTask([
                            'grn_line_id'   => $lineItem->id,
                            'material_id'   => $lineItem->material_id,
                            'source_bin_id' => $sourceBinId,
                            'quantity'      => $acceptedQty,
                            'uom_id'        => $lineItem->uom_id,
                            'batch_number'  => $lineItem->batch_number,
                            'strategy'      => 'MANUAL',
                        ], $userId);
                    } elseif ($existingTask->status === 'PENDING') {
                        $existingTask->update(['quantity' => $acceptedQty]);
                    }
                    // COMPLETED tasks are left untouched — putaway already done
                }

                // Add return fields if provided
                if ($returnQty > 0) {
                    $updates['return_qty'] = $returnQty;
                    if ($returnRemarks) {
                        $updates['return_remarks'] = $returnRemarks;
                    }
                }

                $lineItem->update($updates);

                $totalAccepted += $acceptedQty;
                $totalRejected += $rejectedQty;
            }

            // Determine GRN header status based on operational completion
            $grandTotal = $totalAccepted + $totalRejected;
            if ($grandTotal > 0) {
                if ($totalAccepted > 0 && $totalRejected === 0) {
                    $grnStatus = 'PUTAWAY_IN_PROGRESS';  // Awaiting putaway completion
                } elseif ($totalRejected > 0 && $totalAccepted === 0) {
                    $grnStatus = 'REJECTED';  // Fully rejected - no putaway needed
                } else {
                    $grnStatus = 'PARTIALLY_ACCEPTED';  // Mixed - some lines need putaway
                }

                $grn->update(['status' => $grnStatus]);
            }

            Log::info('[GRNService] QC decision applied to GRN', [
                'grn_id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'status' => $grnStatus,
                'total_accepted' => $totalAccepted,
                'total_rejected' => $totalRejected,
            ]);

            return $grn->load(['lineItems', 'purchaseOrder', 'vendor']);
        });
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
            'stock_status' => 'QC_HOLD', // Default: awaiting QC inspection
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

    private function resolveWarehouseIdForGrnLine(GRNLineItem $lineItem): ?int
    {
        return $lineItem->warehouseBin?->warehouse_id
            ?? $lineItem->material?->default_warehouse_id
            ?? InventoryTransaction::query()
                ->where('material_id', $lineItem->material_id)
                ->where('reference_type', 'GRN')
                ->where('reference_id', $lineItem->grn_id)
                ->where('batch_number', $lineItem->batch_number)
                ->value('warehouse_id')
            ?? StockBalance::query()
                ->where('material_id', $lineItem->material_id)
                ->where('batch_number', $lineItem->batch_number)
                ->value('warehouse_id');
    }

    private function getMaterialBucketQty(GRNLineItem $lineItem, int $warehouseId, string $bucket): float
    {
        return (float) StockBalance::query()
            ->where('material_id', $lineItem->material_id)
            ->where('batch_number', $lineItem->batch_number)
            ->where('warehouse_id', $warehouseId)
            ->where('bucket', $bucket)
            ->sum('qty_on_hand');
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
