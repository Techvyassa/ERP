<?php

namespace App\Services;

use App\Models\Tenant\ASN;
use App\Models\Tenant\ASNLineItem;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PoLineItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ASNService
{
    /**
     * Generate ASN number
     * Format: ASN-YYMM-NNNN
     */
    public function generateASNNumber(): string
    {
        $prefix = 'ASN-' . now()->format('ym') . '-';
        
        $lastASN = ASN::where('asn_number', 'like', $prefix . '%')
            ->orderBy('asn_number', 'desc')
            ->first();
        
        if ($lastASN) {
            $lastNumber = (int) substr($lastASN->asn_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create ASN with line items
     */
    public function createASN(array $data, int $userId): ASN
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId) {
            // Validate PO
            $this->validatePO($data['po_id'], $data['vendor_id']);
            
            // Validate line items against PO
            $this->validateLineItems($data['po_id'], $data['line_items']);
            
            // Generate ASN number
            $asnNumber = $this->generateASNNumber();
            
            // Create ASN header
            $asn = ASN::create([
                'asn_number' => $asnNumber,
                'po_id' => $data['po_id'],
                'vendor_id' => $data['vendor_id'],
                'warehouse_id' => $data['warehouse_id'],
                'ship_date' => $data['ship_date'],
                'eta' => $data['eta'],
                'actual_arrival' => $data['actual_arrival'] ?? null,
                'carrier_name' => $data['carrier_name'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'vehicle_number' => $data['vehicle_number'] ?? null,
                'container_id' => $data['container_id'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_phone' => $data['driver_phone'] ?? null,
                'ship_from_address' => $data['ship_from_address'] ?? null,
                'ship_to_address' => $data['ship_to_address'] ?? null,
                'customer_reference' => $data['customer_reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $userId,
            ]);
            
            // Create line items
            foreach ($data['line_items'] as $item) {
                ASNLineItem::create([
                    'asn_id' => $asn->id,
                    'po_line_id' => $item['po_line_id'],
                    'material_id' => $item['material_id'],
                    'material_description' => $item['material_description'] ?? null,
                    'shipped_qty' => $item['shipped_qty'],
                    'uom_id' => $item['uom_id'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'lot_number' => $item['lot_number'] ?? null,
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'pallet_id' => $item['pallet_id'] ?? null,
                    'sscc' => $item['sscc'] ?? null,
                    'gross_weight' => $item['gross_weight'] ?? null,
                    'net_weight' => $item['net_weight'] ?? null,
                    'weight_uom' => $item['weight_uom'] ?? 'KG',
                    'length' => $item['length'] ?? null,
                    'width' => $item['width'] ?? null,
                    'height' => $item['height'] ?? null,
                    'dimension_uom' => $item['dimension_uom'] ?? 'CM',
                    'line_status' => 'PENDING',
                    'received_qty' => 0,
                ]);
            }
            
            Log::info('ASN created', [
                'asn_id' => $asn->id,
                'asn_number' => $asn->asn_number,
                'po_id' => $asn->po_id,
                'created_by' => $userId,
            ]);
            
            return $asn->load(['lineItems', 'purchaseOrder', 'vendor', 'warehouse']);
        });
    }

    /**
     * Update ASN
     */
    public function updateASN(int $id, array $data, int $userId): ASN
    {
        $asn = ASN::findOrFail($id);
        
        if (!$asn->canEdit()) {
            throw new \Exception('ASN cannot be edited in current status: ' . $asn->status);
        }
        
        $asn->update(array_merge($data, ['updated_by' => $userId]));
        
        Log::info('ASN updated', [
            'asn_id' => $asn->id,
            'asn_number' => $asn->asn_number,
            'updated_by' => $userId,
        ]);
        
        return $asn->load(['lineItems', 'purchaseOrder', 'vendor', 'warehouse']);
    }

    /**
     * Change ASN status
     */
    public function changeStatus(int $id, string $status, int $userId, ?array $data = []): ASN
    {
        $asn = ASN::findOrFail($id);
        
        // Validate status transition
        $this->validateStatusTransition($asn->status, $status);
        
        $updateData = ['status' => $status, 'updated_by' => $userId];
        
        // If marking as arrived, set actual_arrival
        if ($status === 'ARRIVED' && isset($data['actual_arrival'])) {
            $updateData['actual_arrival'] = $data['actual_arrival'];
        } elseif ($status === 'ARRIVED' && !$asn->actual_arrival) {
            $updateData['actual_arrival'] = now();
        }
        
        $asn->update($updateData);
        
        Log::info('ASN status changed', [
            'asn_id' => $asn->id,
            'asn_number' => $asn->asn_number,
            'old_status' => $asn->getOriginal('status'),
            'new_status' => $status,
            'updated_by' => $userId,
        ]);
        
        return $asn->load(['lineItems', 'purchaseOrder', 'vendor', 'warehouse']);
    }

    /**
     * Cancel ASN
     */
    public function cancelASN(int $id, int $userId): bool
    {
        $asn = ASN::findOrFail($id);
        
        if (!$asn->canCancel()) {
            throw new \Exception('ASN cannot be cancelled in current status: ' . $asn->status);
        }
        
        $asn->update([
            'status' => 'CANCELLED',
            'updated_by' => $userId,
        ]);
        
        // Update line items status
        $asn->lineItems()->update(['line_status' => 'CANCELLED']);
        
        Log::info('ASN cancelled', [
            'asn_id' => $asn->id,
            'asn_number' => $asn->asn_number,
            'cancelled_by' => $userId,
        ]);
        
        return true;
    }

    /**
     * Validate PO
     */
    private function validatePO(int $poId, int $vendorId): void
    {
        $po = PurchaseOrder::find($poId);
        
        if (!$po) {
            throw new \Exception('Purchase Order not found');
        }
        
        if ($po->vendor_id !== $vendorId) {
            throw new \Exception('Vendor does not match Purchase Order vendor');
        }
        
        if (!in_array($po->status, ['APPROVED', 'OPEN', 'PARTIAL'])) {
            throw new \Exception('Purchase Order must be in APPROVED, OPEN, or PARTIAL status');
        }
    }

    /**
     * Validate line items against PO
     */
    private function validateLineItems(int $poId, array $lineItems): void
    {
        foreach ($lineItems as $item) {
            $poLineItem = PoLineItem::where('id', $item['po_line_id'])
                ->where('po_id', $poId)
                ->first();
            
            if (!$poLineItem) {
                throw new \Exception('PO line item not found or does not belong to this PO');
            }
            
            if ($poLineItem->material_id !== $item['material_id']) {
                throw new \Exception('Material ID does not match PO line item');
            }
            
            // Check if shipped quantity exceeds remaining PO quantity
            $remainingQty = $poLineItem->ordered_qty - ($poLineItem->received_qty ?? 0);
            if ($item['shipped_qty'] > $remainingQty) {
                throw new \Exception("Shipped quantity exceeds remaining PO quantity for material {$poLineItem->material_id}");
            }
        }
    }

    /**
     * Validate status transition
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        $validTransitions = [
            'DRAFT' => ['SENT', 'CANCELLED'],
            'SENT' => ['IN_TRANSIT', 'CANCELLED'],
            'IN_TRANSIT' => ['ARRIVED', 'CANCELLED'],
            'ARRIVED' => ['RECEIVED', 'CANCELLED'],
            'RECEIVED' => [],
            'CANCELLED' => [],
        ];
        
        if (!isset($validTransitions[$currentStatus])) {
            throw new \Exception('Invalid current status');
        }
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \Exception("Cannot transition from {$currentStatus} to {$newStatus}");
        }
    }
}
