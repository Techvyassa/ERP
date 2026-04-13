<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class StockManagementController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'asset' => $p->compatible_asset,
            'stock' => $p->stock,
            'reorder_level' => $p->reorder_level,
            'unit' => $p->unit,
        ])->all();
        $movements = DB::connection('tenant')->table('maint_stock_movements')->orderByDesc('id')->limit(50)->get()->map(fn($m) => [
            'date' => $m->created_at ? date('Y-m-d', strtotime($m->created_at)) : null,
            'part_code' => $m->part_code,
            'part_name' => $m->part_name,
            'type' => $m->type,
            'qty' => $m->qty,
            'reference' => $m->reference,
            'note' => $m->note,
        ])->all();
        return view('tenant.maintenance.stock-management.index', compact('parts', 'movements') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function adjust($orgSlug, $id, Request $request)
    {
        $part = DB::connection('tenant')->table('maint_spare_parts')->where('id', $id)->first();
        if (!$part) {
            return redirect()->route('tenant.maintenance.stock-management', $orgSlug)->with('success', 'Part not found.');
        }
        $qty = (int) $request->input('qty', 0);
        $type = $request->input('type', 'add');
        $note = $request->input('note', '');
        if ($type === 'add') {
            DB::connection('tenant')->table('maint_spare_parts')->where('id', $id)->update([
                'stock' => DB::raw('stock + ' . $qty),
                'updated_at' => now(),
            ]);
            $mvType = 'Adjust+';
        } else {
            DB::connection('tenant')->table('maint_spare_parts')->where('id', $id)->update([
                'stock' => DB::raw('GREATEST(0, stock - ' . $qty . ')'),
                'updated_at' => now(),
            ]);
            $mvType = 'Adjust-';
        }
        DB::connection('tenant')->table('maint_stock_movements')->insert([
            'part_id' => $part->id,
            'part_code' => $part->code,
            'part_name' => $part->name,
            'type' => $mvType,
            'qty' => $qty,
            'reference' => null,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.stock-management', $orgSlug)->with('success', "Stock adjusted for {$part->name}.");
    }
}