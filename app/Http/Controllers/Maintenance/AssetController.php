<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class AssetController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $workOrders = DB::connection('tenant')->table('maint_work_orders')->get();
        $schedules = DB::connection('tenant')->table('maint_pm_schedules')->get();
        $assets = DB::connection('tenant')->table('maint_assets')->orderByDesc('id')->get()->map(function ($a) use ($workOrders, $schedules) {
            $woCount = $workOrders->filter(fn($w) => $w->asset_id === $a->id)->count();
            $pmCount = $schedules->filter(fn($pm) => $pm->asset_id === $a->id)->count();
            return [
                'code' => $a->code,
                'name' => $a->name,
                'category' => $a->category,
                'location' => $a->location,
                'model' => $a->model,
                'installed_on' => $a->installed_on,
                'last_maintained' => $a->last_maintained,
                'status' => $a->status,
                'wo_count' => $woCount,
                'pm_count' => $pmCount,
            ];
        })->all();
        return view('tenant.maintenance.assets.index', compact('assets') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        $seq = (int) (DB::connection('tenant')->table('maint_assets')->max('id') ?? 0) + 1;
        DB::connection('tenant')->table('maint_assets')->insert([
            'code' => $request->input('code') ?: 'AST-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'location' => $request->input('location', ''),
            'model' => $request->input('model', ''),
            'installed_on' => $request->input('installed_on') ?: null,
            'last_maintained' => null,
            'status' => 'Active',
            'created_by' => $request->get('auth_user_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.assets', $orgSlug)->with('success', 'Asset registered successfully.');
    }
}