<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class ClosureController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $closures = DB::connection('tenant')->table('maint_work_orders')
            ->whereIn('status', ['Completed', 'Closed'])
            ->orderByDesc('id')
            ->get()
            ->map(fn($w) => [
                'wo' => $w->wo_no,
                'mr_id' => $w->request_id ? (DB::connection('tenant')->table('maint_requests')->where('id', $w->request_id)->value('request_no')) : null,
                'asset' => $w->asset_name,
                'technician' => $w->technician,
                'due' => $w->due_date,
                'priority' => $w->priority,
                'notes' => $w->notes,
                'status' => $w->status,
                'assigned_on' => $w->assigned_on,
                'closed_on' => $w->closed_on,
                'verified_by' => $w->verified_by,
            ])->all();
        return view('tenant.maintenance.closure.index', compact('closures') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function close($orgSlug, $wo, Request $request)
    {
        $woRow = DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $wo)->first();
        if ($woRow) {
            DB::connection('tenant')->table('maint_work_orders')->where('id', $woRow->id)->update([
                'status' => 'Closed',
                'closed_on' => now()->format('Y-m-d'),
                'verified_by' => $request->input('verified_by', 'Maintenance Lead'),
                'closure_notes' => $request->input('closure_notes', ''),
                'updated_at' => now(),
            ]);
            if ($woRow->asset_id) {
                DB::connection('tenant')->table('maint_assets')->where('id', $woRow->asset_id)->update([
                    'last_maintained' => now()->format('Y-m-d'),
                    'updated_at' => now(),
                ]);
            }
        }
        return redirect()->route('tenant.maintenance.closure', $orgSlug)->with('success', "Work order {$wo} closed successfully.");
    }
}