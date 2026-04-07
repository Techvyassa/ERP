<?php

namespace App\Http\Controllers;

use App\Models\Control\Organization;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPortalController extends Controller
{
    /**
     * Generate a signed token for vendor PO access.
     * Format: base64(orgSlug:poId:hmac)
     */
    public static function generateToken(string $orgSlug, int $poId): string
    {
        $payload = $orgSlug . ':' . $poId;
        $hmac    = hash_hmac('sha256', $payload, config('app.key'));
        return base64_encode($payload . ':' . $hmac);
    }

    /**
     * Generate a signed token for vendor PR access.
     * Format: base64(orgSlug:PR:prId:hmac)
     */
    public static function generatePRToken(string $orgSlug, int $prId): string
    {
        $payload = $orgSlug . ':PR:' . $prId;
        $hmac    = hash_hmac('sha256', $payload, config('app.key'));
        return base64_encode($payload . ':' . $hmac);
    }

    /**
     * Decode and validate a vendor token.
     * Returns ['org_slug' => ..., 'po_id' => ...] or null if invalid.
     */
    private function decodeToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if (!$decoded) return null;

        $parts = explode(':', $decoded, 3);
        if (count($parts) !== 3) return null;

        [$orgSlug, $poId, $hmac] = $parts;
        $expected = hash_hmac('sha256', $orgSlug . ':' . $poId, config('app.key'));

        if (!hash_equals($expected, $hmac)) return null;

        return ['org_slug' => $orgSlug, 'po_id' => (int) $poId];
    }

    /**
     * Decode and validate a vendor PR token.
     * Returns ['org_slug' => ..., 'pr_id' => ...] or null if invalid.
     */
    private function decodePRToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if (!$decoded) return null;

        $parts = explode(':', $decoded, 4);
        if (count($parts) !== 4) return null;

        [$orgSlug, $type, $prId, $hmac] = $parts;
        if ($type !== 'PR') return null;

        $expected = hash_hmac('sha256', $orgSlug . ':PR:' . $prId, config('app.key'));

        if (!hash_equals($expected, $hmac)) return null;

        return ['org_slug' => $orgSlug, 'pr_id' => (int) $prId];
    }

    /**
     * View PO — vendor opens the link, status is updated to VENDOR_VIEWED.
     */
    public function viewPO(string $token)
    {
        $data = $this->decodeToken($token);
        if (!$data) abort(404, 'Invalid or expired link.');

        $org = Organization::where('org_slug', $data['org_slug'])->firstOrFail();

        // Switch to tenant DB
        config(['database.connections.tenant.database' => $org->tenant_db_name]);
        DB::purge('tenant');

        $po = PurchaseOrder::on('tenant')
            ->with(['lineItems.material', 'lineItems.uom', 'vendor'])
            ->findOrFail($data['po_id']);

        // When vendor clicks View PO, mark status as OPEN (vendor acknowledged)
        // and stamp approved_at if not already set — uses existing columns, no new table/column
        if ($po->approved_at === null) {
            PurchaseOrder::on('tenant')
                ->where('id', $po->id)
                ->update([
                    'status'      => 'OPEN',
                    'approved_at' => now(),
                ]);
            $po->status      = 'OPEN';
            $po->approved_at = now();
        }

        $warehouses   = Warehouse::on('tenant')->where('is_active', true)->get();
        $lineItemsData = $po->lineItems->map(fn($i) => [
            'id'          => $i->id,
            'material_id' => $i->material_id,
            'ordered_qty' => $i->ordered_qty,
            'uom_id'      => $i->uom_id,
        ]);

        return view('vendor.po-view', [
            'po'             => $po,
            'token'          => $token,
            'orgName'        => $org->org_name,
            'warehouses'     => $warehouses,
            'lineItemsData'  => $lineItemsData->toJson(),
            'vendorApproved' => false,
            'vendorRejected' => false,
        ]);
    }

    /**
     * View PR — vendor opens the link to view purchase requisition details.
     */
    public function viewPR(string $token)
    {
        $data = $this->decodePRToken($token);
        if (!$data) abort(404, 'Invalid or expired link.');

        $org = Organization::where('org_slug', $data['org_slug'])->firstOrFail();

        // Switch to tenant DB
        config(['database.connections.tenant.database' => $org->tenant_db_name]);
        DB::purge('tenant');

        $pr = \App\Models\Tenant\PurchaseRequisition::on('tenant')
            ->with(['lineItems.material', 'lineItems.uom', 'requestedBy', 'department', 'suggestedVendor'])
            ->findOrFail($data['pr_id']);

        return view('vendor.pr-view', [
            'pr'      => $pr,
            'token'   => $token,
            'orgName' => $org->org_name,
        ]);
    }

    // -------------------------------------------------------------------------
    // The methods below are commented out / stubbed — not active yet
    // -------------------------------------------------------------------------

    public function acknowledge(string $token, Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Not implemented'], 501);
    }

    public function vendorApprove(string $token, Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Not implemented'], 501);
    }

    public function vendorReject(string $token, Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Not implemented'], 501);
    }

    public function uploadASN(string $token, Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Not implemented'], 501);
    }
}
