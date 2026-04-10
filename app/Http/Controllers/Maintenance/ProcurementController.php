<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class ProcurementController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $orders = DB::connection('tenant')->table('maint_procurement_orders')->orderByDesc('id')->get()->map(fn($o) => [
            'id' => $o->id,
            'po_no' => $o->po_no,
            'part_code' => $o->part_code,
            'part_name' => $o->part_name,
            'unit' => $o->unit,
            'qty' => $o->qty,
            'vendor' => $o->vendor,
            'expected_date' => $o->expected_date,
            'notes' => $o->notes,
            'status' => $o->status,
            'raised_on' => $o->created_at ? date('Y-m-d', strtotime($o->created_at)) : null,
        ])->all();
        $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
            'code' => $p->code,
            'name' => $p->name,
            'stock' => $p->stock,
            'unit' => $p->unit,
        ])->all();
        return view('tenant.maintenance.procurement.index', compact('orders', 'parts') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function ordersJson($orgSlug) {
        $orders = DB::connection('tenant')->table('maint_procurement_orders')->orderByDesc('id')->get()->map(fn($o) => [
            'id' => $o->id,
            'po_no' => $o->po_no,
            'part_code' => $o->part_code,
            'part_name' => $o->part_name,
            'unit' => $o->unit,
            'qty' => $o->qty,
            'vendor' => $o->vendor,
            'expected_date' => $o->expected_date,
            'status' => $o->status,
            'raised_on' => $o->created_at ? date('Y-m-d', strtotime($o->created_at)) : null,
        ])->all();
        return response()->json($orders);
    }

    public function store($orgSlug, Request $request)
    {
        $seq = (int) (DB::connection('tenant')->table('maint_procurement_orders')->max('id') ?? 0) + 1;
        $code = (string) $request->input('part_code');
        $part = DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->first();
        DB::connection('tenant')->table('maint_procurement_orders')->insert([
            'po_no' => 'MPO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'part_id' => $part?->id,
            'part_code' => $code,
            'part_name' => $part?->name ?? $code,
            'unit' => $part?->unit ?? 'Nos',
            'qty' => (int) $request->input('qty', 1),
            'vendor' => $request->input('vendor', ''),
            'expected_date' => $request->input('expected_date') ?: null,
            'notes' => $request->input('notes', ''),
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $back = $request->input('redirect_back', 'procurement');
        $routeName = match ($back) {
            'spare-parts' => 'tenant.maintenance.spare-parts',
            'material-requests' => 'tenant.maintenance.material-requests',
            default => 'tenant.maintenance.procurement',
        };
        $poNo = 'MPO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true, 'po_no' => $poNo, 'part_code' => $code]);
        }
        return redirect()->route($routeName, $orgSlug)->with('success', 'Procurement order raised successfully.');
    }

    public function markOrdered($orgSlug, $id)
    {
        DB::connection('tenant')->table('maint_procurement_orders')->where('id', $id)->update([
            'status' => 'Ordered',
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.procurement', $orgSlug)->with('success', 'Order marked as Ordered.');
    }

    public function receive($orgSlug, $id, Request $request)
    {
        $order = DB::connection('tenant')->table('maint_procurement_orders')->where('id', $id)->first();
        if (!$order) {
            return redirect()->route('tenant.maintenance.procurement', $orgSlug)->with('success', 'Order not found.');
        }
        $qty = (int) $request->input('qty', $order->qty);
        DB::connection('tenant')->table('maint_spare_parts')->where('code', $order->part_code)->update([
            'stock' => DB::raw('stock + ' . $qty),
            'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('maint_stock_movements')->insert([
            'part_id' => $order->part_id,
            'part_code' => $order->part_code,
            'part_name' => $order->part_name,
            'type' => 'Receive',
            'qty' => $qty,
            'reference' => $order->po_no,
            'note' => $request->input('note', ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('maint_procurement_orders')->where('id', $id)->update([
            'status' => 'Received',
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.procurement', $orgSlug)->with('success', "{$qty} unit(s) received and stock updated.");
    }
}