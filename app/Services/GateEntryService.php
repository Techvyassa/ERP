<?php

namespace App\Services;

use App\Models\Tenant\GateEntry;
use App\Models\Tenant\GateVerification;
use App\Models\Tenant\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GateEntryService
{
    /**
     * Create gate entry
     */
    public function createGateEntry(array $data, int $userId): GateEntry
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId) {
            // Validate PO exists and is in valid status
            $this->validatePO($data['po_id']);
            
            // Generate GE number
            $geNumber = GateEntry::generateGENumber();
            
            // Create gate entry
            $gateEntry = GateEntry::create([
                'ge_number' => $geNumber,
                'po_id' => $data['po_id'],
                'asn_id' => $data['asn_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'vehicle_number' => $data['vehicle_number'],
                'transporter_name' => $data['transporter_name'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_phone' => $data['driver_phone'] ?? null,
                'challan_number' => $data['challan_number'] ?? null,
                'vendor_invoice_number' => $data['vendor_invoice_number'] ?? null,
                'eway_bill_number' => $data['eway_bill_number'] ?? null,
                'eway_bill_expiry' => $data['eway_bill_expiry'] ?? null,
                'material_type' => $data['material_type'],
                'gross_weight_kg' => $data['gross_weight_kg'] ?? null,
                'arrived_at' => $data['arrived_at'],
                'status' => 'PENDING_VERIFICATION',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);
            
            Log::info('Gate entry created', [
                'ge_id' => $gateEntry->id,
                'ge_number' => $gateEntry->ge_number,
                'po_id' => $gateEntry->po_id,
                'created_by' => $userId,
            ]);
            
            return $gateEntry->load(['purchaseOrder', 'vendor', 'asn', 'creator']);
        });
    }

    /**
     * Create gate verification
     */
    public function createVerification(int $geId, array $data, int $userId): GateVerification
    {
        $gateEntry = GateEntry::findOrFail($geId);
        
        if (!$gateEntry->canVerify()) {
            throw new \Exception('Gate entry cannot be verified in current status: ' . $gateEntry->status);
        }
        
        return DB::connection('tenant')->transaction(function () use ($gateEntry, $geId, $data, $userId) {
            // Calculate net weight if both gross and tare are provided
            $netWeight = null;
            if (isset($data['tare_weight_kg']) && $gateEntry->gross_weight_kg) {
                $netWeight = $gateEntry->gross_weight_kg - $data['tare_weight_kg'];
            }
            
            // Create verification record
            $verification = GateVerification::create([
                'ge_id' => $geId,
                'challan_verified' => $data['challan_verified'],
                'invoice_verified' => $data['invoice_verified'],
                'eway_bill_valid' => $data['eway_bill_valid'],
                'po_status_valid' => $data['po_status_valid'],
                'seal_number' => $data['seal_number'] ?? null,
                'seal_intact' => $data['seal_intact'] ?? null,
                'external_damage' => $data['external_damage'],
                'tare_weight_kg' => $data['tare_weight_kg'] ?? null,
                'net_weight_kg' => $netWeight,
                'weight_variance_flag' => $data['weight_variance_flag'],
                'dock_assigned' => $data['dock_assigned'] ?? null,
                'approval_status' => $data['approval_status'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'security_remarks' => $data['security_remarks'] ?? null,
                'verified_by' => $userId,
                'verified_at' => now(),
            ]);
            
            // Update gate entry status based on approval
            if ($data['approval_status'] === 'APPROVED') {
                $gateEntry->update(['status' => 'VERIFIED']);
            } elseif ($data['approval_status'] === 'REJECTED') {
                $gateEntry->update(['status' => 'REJECTED']);
            }
            
            Log::info('Gate verification created', [
                'ge_id' => $geId,
                'approval_status' => $data['approval_status'],
                'verified_by' => $userId,
            ]);
            
            return $verification->load(['gateEntry', 'verifier']);
        });
    }

    /**
     * Move gate entry to dock
     */
    public function moveToDock(int $geId, int $userId): GateEntry
    {
        $gateEntry = GateEntry::findOrFail($geId);
        
        if (!$gateEntry->canMoveToDock()) {
            throw new \Exception('Gate entry cannot be moved to dock in current status: ' . $gateEntry->status);
        }
        
        $gateEntry->update(['status' => 'MOVED_TO_DOCK']);
        
        Log::info('Gate entry moved to dock', [
            'ge_id' => $geId,
            'moved_by' => $userId,
        ]);
        
        return $gateEntry->load(['purchaseOrder', 'vendor', 'verification']);
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
