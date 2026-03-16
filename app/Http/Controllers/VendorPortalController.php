<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Warehouse;
use App\Services\ASNService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class VendorPortalController extends Controller
{
    public function __construct(private ASNService $asnService) {}

    /**
     * Decode token → [orgSlug, poId]
     */
    private function decodeToken(string $token): array
    {
        try {
            $payload = Crypt::decryptString($token);
            [$orgSlug, $poId] = explode('|', $payload);
            return [$orgSlug, (int) $poId];
        } catch (\Exception $e) {
            abort(404, 'Invalid or expired link');
        }
    }

    /**
     * Switch to tenant DB and return org
     */
    private function bootTenant(string $orgSlug): Organization
    {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        config(['database.connections.tenant.database' => $org->tenant_db_name]);
        \DB::purge('tenant');
        return $org;
    }

    /**
     * Show PO to vendor (public, token-based)
     * GET /vendor/po/{token}
     */
    public function viewPO(string $token)
    {
        [$orgSlug, $poId] = $this->decodeToken($token);
        $org = $this->bootTenant($orgSlug);

        $po = PurchaseOrder::with([
            'vendor',
            'lineItems.material',
            'lineItems.uom',
        ])->findOrFail($poId);

        $warehouses = Warehouse::where('is_active', true)->get(['id', 'warehouse_name', 'warehouse_code']);

        // Pre-compute line items data for JS (avoids @json with arrow functions)
        $lineItemsData = $po->lineItems->map(function ($i) {
            return [
                'id'          => $i->id,
                'material_id' => $i->material_id,
                'uom_id'      => $i->uom_id,
                'ordered_qty' => (float) $i->ordered_qty,
            ];
        })->values()->toArray();

        // Determine vendor decision from remarks
        $remarks = $po->remarks ?? '';
        $vendorApproved = str_contains($remarks, '[VENDOR_APPROVED]');
        $vendorRejected = str_contains($remarks, '[VENDOR_REJECTED]') || $po->status === 'CANCELLED';

        return view('vendor.po-view', [
            'po'             => $po,
            'orgName'        => $org->org_name,
            'token'          => $token,
            'warehouses'     => $warehouses,
            'lineItemsData'  => json_encode($lineItemsData),
            'vendorApproved' => $vendorApproved,
            'vendorRejected' => $vendorRejected,
        ]);
    }

    /**
     * Vendor acknowledges / adds remark
     * POST /vendor/po/{token}/acknowledge
     */
    public function acknowledge(Request $request, string $token)
    {
        [$orgSlug, $poId] = $this->decodeToken($token);
        $request->validate(['remark' => 'required|string|max:1000']);

        $org = $this->bootTenant($orgSlug);
        $po  = PurchaseOrder::findOrFail($poId);

        $existing = $po->remarks ? $po->remarks . "\n" : '';
        $po->remarks = $existing . '[Vendor] ' . now()->format('d M Y H:i') . ': ' . $request->input('remark');
        $po->save();

        return response()->json(['success' => true, 'message' => 'Acknowledgement recorded']);
    }

    /**
     * Vendor approves the PO
     * POST /vendor/po/{token}/vendor-approve
     */
    public function vendorApprove(Request $request, string $token)
    {
        [$orgSlug, $poId] = $this->decodeToken($token);
        $org = $this->bootTenant($orgSlug);
        $po  = PurchaseOrder::findOrFail($poId);

        if (!in_array($po->status, ['OPEN', 'PARTIAL'])) {
            return response()->json(['success' => false, 'message' => 'PO is not in a state that can be approved by vendor.'], 422);
        }

        // Mark vendor approval in remarks
        $existing = $po->remarks ? $po->remarks . "\n" : '';
        $po->remarks = $existing . '[VENDOR_APPROVED] ' . now()->format('d M Y H:i');
        $po->save();

        return response()->json(['success' => true, 'message' => 'PO approved. You can now upload your ASN.']);
    }

    /**
     * Vendor rejects the PO
     * POST /vendor/po/{token}/vendor-reject
     */
    public function vendorReject(Request $request, string $token)
    {
        [$orgSlug, $poId] = $this->decodeToken($token);
        $request->validate(['reason' => 'nullable|string|max:500']);

        $org = $this->bootTenant($orgSlug);
        $po  = PurchaseOrder::findOrFail($poId);

        if (!in_array($po->status, ['OPEN', 'PARTIAL'])) {
            return response()->json(['success' => false, 'message' => 'PO cannot be rejected in its current state.'], 422);
        }

        $reason = $request->input('reason', '');
        $existing = $po->remarks ? $po->remarks . "\n" : '';
        $po->remarks = $existing . '[VENDOR_REJECTED] ' . now()->format('d M Y H:i') . ($reason ? ': ' . $reason : '');
        $po->status = 'CANCELLED';
        $po->save();

        return response()->json(['success' => true, 'message' => 'PO has been rejected and cancelled.']);
    }

    /**
     * Vendor uploads ASN via CSV — only allowed when PO is OPEN
     * POST /vendor/po/{token}/upload-asn
     */
    public function uploadASN(Request $request, string $token)
    {
        [$orgSlug, $poId] = $this->decodeToken($token);
        $org = $this->bootTenant($orgSlug);

        $po = PurchaseOrder::with(['vendor', 'lineItems'])->findOrFail($poId);

        // Only allow when PO is approved (OPEN or PARTIAL)
        if (!in_array($po->status, ['OPEN', 'PARTIAL'])) {
            return response()->json([
                'success' => false,
                'message' => 'ASN can only be uploaded for approved (OPEN) purchase orders. Current status: ' . $po->status,
            ], 422);
        }

        $request->validate([
            'file'         => 'required|file|mimes:csv,txt|max:2048',
            'warehouse_id' => 'required|integer',
            'ship_date'    => 'required|date',
            'eta'          => 'required|date',
            'carrier_name'     => 'nullable|string|max:100',
            'tracking_number'  => 'nullable|string|max:100',
            'vehicle_number'   => 'nullable|string|max:50',
            'remarks'          => 'nullable|string|max:500',
        ]);

        // Build a lookup map of PO line items keyed by id
        $poLineMap = $po->lineItems->keyBy('id');

        // Parse CSV
        $file    = $request->file('file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        if (!in_array('po_line_id', $headers) || !in_array('shipped_qty', $headers)) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'CSV must have at least: po_line_id, shipped_qty',
            ], 422);
        }

        $lineItems = [];
        $rowNum    = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < 2) continue;
            $data = array_combine($headers, array_map('trim', array_slice($row, 0, count($headers))));

            if (empty($data['po_line_id']) || empty($data['shipped_qty'])) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => "Row {$rowNum}: po_line_id and shipped_qty are required",
                ], 422);
            }

            $poLineId = (int) $data['po_line_id'];

            // Look up the PO line item — use its material_id and uom_id as source of truth
            $poLine = $poLineMap->get($poLineId);
            if (!$poLine) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => "Row {$rowNum}: po_line_id {$poLineId} not found in this PO",
                ], 422);
            }

            $lineItems[] = [
                'po_line_id'         => $poLineId,
                'material_id'        => (int) $poLine->material_id,   // always from PO, not CSV
                'shipped_qty'        => (float) $data['shipped_qty'],
                'uom_id'             => (int) ($poLine->uom_id ?? (!empty($data['uom_id']) ? $data['uom_id'] : 1)),
                'batch_number'       => $data['batch_number'] ?? null,
                'lot_number'         => $data['lot_number'] ?? null,
                'manufacturing_date' => !empty($data['manufacturing_date']) ? $data['manufacturing_date'] : null,
                'expiry_date'        => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                'pallet_id'          => $data['pallet_id'] ?? null,
                'sscc'               => $data['sscc'] ?? null,
                'gross_weight'       => !empty($data['gross_weight']) ? (float) $data['gross_weight'] : null,
                'net_weight'         => !empty($data['net_weight']) ? (float) $data['net_weight'] : null,
            ];
        }
        fclose($handle);

        if (empty($lineItems)) {
            return response()->json(['success' => false, 'message' => 'CSV file has no data rows'], 422);
        }

        try {
            $asn = $this->asnService->createASN([
                'po_id'          => $po->id,
                'vendor_id'      => $po->vendor_id,
                'warehouse_id'   => (int) $request->input('warehouse_id'),
                'ship_date'      => $request->input('ship_date'),
                'eta'            => $request->input('eta'),
                'carrier_name'   => $request->input('carrier_name'),
                'tracking_number'=> $request->input('tracking_number'),
                'vehicle_number' => $request->input('vehicle_number'),
                'remarks'        => $request->input('remarks'),
                'line_items'     => $lineItems,
            ], null); // vendor upload — no internal user id

            return response()->json([
                'success' => true,
                'message' => 'ASN ' . $asn->asn_number . ' created successfully with ' . count($lineItems) . ' line item(s).',
                'data'    => ['asn_number' => $asn->asn_number],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate a signed token for a PO link
     */
    public static function generateToken(string $orgSlug, int $poId): string
    {
        return Crypt::encryptString($orgSlug . '|' . $poId);
    }
}
