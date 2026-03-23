<?php

namespace App\Services;

use App\Models\Tenant\GateEntry;
use App\Models\Tenant\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GateEntryService
{
    /**
     * Create gate entry and auto-create GRN from PO lines.
     * New flow: Gate Entry → GRN (auto) → QC → Putaway → Stock
     */
    public function createGateEntry(array $data, int $userId): GateEntry
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId) {
            // Validate PO exists and is in valid status
            $this->validatePO($data['po_id']);

            // Generate GE number
            $geNumber = GateEntry::generateGENumber();

            // Create gate entry with PENDING status
            $gateEntry = GateEntry::create([
                'ge_number'            => $geNumber,
                'po_id'                => $data['po_id'],
                'asn_id'               => $data['asn_id'] ?? null,
                'vendor_id'            => $data['vendor_id'],
                'vehicle_number'       => $data['vehicle_number'],
                'transporter_name'     => $data['transporter_name'] ?? null,
                'driver_name'          => $data['driver_name'] ?? null,
                'driver_phone'         => $data['driver_phone'] ?? null,
                'challan_number'       => $data['challan_number'] ?? null,
                'vendor_invoice_number'=> $data['vendor_invoice_number'] ?? null,
                'eway_bill_number'     => $data['eway_bill_number'] ?? null,
                'eway_bill_expiry'     => $data['eway_bill_expiry'] ?? null,
                'material_type'        => $data['material_type'],
                'gross_weight_kg'      => $data['gross_weight_kg'] ?? null,
                'arrived_at'           => $data['arrived_at'],
                'status'               => 'PENDING',
                'remarks'              => $data['remarks'] ?? null,
                'created_by'           => $userId,
            ]);

            Log::info('Gate entry created', [
                'ge_id'      => $gateEntry->id,
                'ge_number'  => $gateEntry->ge_number,
                'po_id'      => $gateEntry->po_id,
                'created_by' => $userId,
            ]);

            // Auto-create GRN from PO lines
            $grnService = app(GRNService::class);
            $grn = $grnService->createGRNFromGateEntry($gateEntry, $userId);

            Log::info('GRN auto-created from gate entry', [
                'ge_id'     => $gateEntry->id,
                'grn_id'    => $grn->id,
                'grn_number'=> $grn->grn_number,
            ]);

            return $gateEntry->load(['purchaseOrder', 'vendor', 'grn']);
        });
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

        if (!in_array($po->status, ['APPROVED', 'OPEN', 'PARTIAL', 'PARTIALLY_RECEIVED'])) {
            throw new \Exception('Purchase Order must be in APPROVED, OPEN, or PARTIAL status');
        }
    }
}
