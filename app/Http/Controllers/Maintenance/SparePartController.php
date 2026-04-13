<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class SparePartController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $activePOs = DB::connection('tenant')->table('maint_procurement_orders')
            ->whereIn('status', ['Pending', 'Ordered'])
            ->get()
            ->keyBy('part_code');

        $parts = DB::connection('tenant')->table('maint_spare_parts')->orderByDesc('id')->get()->map(fn($p) => [
            'code' => $p->code,
            'name' => $p->name,
            'asset' => $p->compatible_asset,
            'stock' => $p->stock,
            'reorder_level' => $p->reorder_level,
            'unit' => $p->unit,
            'po_no' => $activePOs[$p->code]->po_no ?? null,
            'po_status' => $activePOs[$p->code]->status ?? null,
        ])->all();
        $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'category' => $a->category,
            'status' => $a->status,
        ])->all();
        $matReqs = DB::connection('tenant')->table('maint_material_requests')->orderByDesc('id')->get()->map(fn($m) => [
            'id' => $m->mmr_no,
            'wo_id' => $m->wo_no,
            'part_code' => $m->part_code,
            'part_name' => $m->part_name,
            'qty' => $m->qty,
            'unit' => $m->unit,
            'status' => $m->status,
        ])->all();
        $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
            'wo' => $w->wo_no,
            'asset' => $w->asset_name,
            'status' => $w->status,
        ])->all();
        return view('tenant.maintenance.spare-parts.index', compact('parts', 'assets', 'matReqs', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        DB::connection('tenant')->table('maint_spare_parts')->insert([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'compatible_asset' => $request->input('asset', ''),
            'stock' => (int) $request->input('stock', 0),
            'reorder_level' => $request->input('reorder_level') !== null && $request->input('reorder_level') !== '' ? (int) $request->input('reorder_level') : null,
            'unit' => $request->input('unit', 'Nos'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', 'Spare part added successfully.');
    }

    public function issue($orgSlug, $code, Request $request)
    {
        $qty = (int) $request->input('qty', 1);
        $wo = $request->input('work_order', '');
        $part = DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->first();
        DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->update([
            'stock' => DB::raw('GREATEST(0, stock - ' . $qty . ')'),
            'updated_at' => now(),
        ]);
        if ($part) {
            DB::connection('tenant')->table('maint_stock_movements')->insert([
                'part_id' => $part->id,
                'part_code' => $part->code,
                'part_name' => $part->name,
                'type' => 'Issue',
                'qty' => $qty,
                'reference' => $wo ?: null,
                'note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', "{$qty} unit(s) of {$code} issued.");
    }

    public function receive($orgSlug, $code, Request $request)
    {
        $qty = (int) $request->input('qty', 1);
        $part = DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->first();
        DB::connection('tenant')->table('maint_spare_parts')->where('code', $code)->update([
            'stock' => DB::raw('stock + ' . $qty),
            'updated_at' => now(),
        ]);
        if ($part) {
            DB::connection('tenant')->table('maint_stock_movements')->insert([
                'part_id' => $part->id,
                'part_code' => $part->code,
                'part_name' => $part->name,
                'type' => 'Receive',
                'qty' => $qty,
                'reference' => null,
                'note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return redirect()->route('tenant.maintenance.spare-parts', $orgSlug)->with('success', "{$qty} unit(s) of {$code} received into stock.");
    }
}