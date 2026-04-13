<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class AssignmentController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $approved = DB::connection('tenant')->table('maint_requests')->where('status', 'Approved')->orderByDesc('id')->get()->map(fn($r) => [
            'id' => $r->request_no,
            'asset' => $r->asset_name,
            'priority' => $r->priority,
            'issue' => $r->issue,
            'status' => $r->status,
        ])->all();
        $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
            'wo' => $w->wo_no,
            'mr_id' => $w->request_id ? (DB::connection('tenant')->table('maint_requests')->where('id', $w->request_id)->value('request_no')) : null,
            'asset' => $w->asset_name,
            'technician' => $w->technician,
            'team' => $w->team,
            'due' => $w->due_date,
            'priority' => $w->priority,
            'notes' => $w->notes,
            'status' => $w->status,
            'assigned_on' => $w->assigned_on,
        ])->all();
        return view('tenant.maintenance.assignments.index', compact('approved', 'workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        $mrNo = $request->input('request_id');
        $reqRow = DB::connection('tenant')->table('maint_requests')->where('request_no', $mrNo)->first();
        if ($reqRow) {
            DB::connection('tenant')->table('maint_requests')->where('id', $reqRow->id)->update([
                'status' => 'Assigned',
                'updated_at' => now(),
            ]);
        }

        $seq = (int) (DB::connection('tenant')->table('maint_work_orders')->max('id') ?? 0) + 1;
        DB::connection('tenant')->table('maint_work_orders')->insert([
            'wo_no' => 'WO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'request_id' => $reqRow?->id,
            'asset_id' => $reqRow?->asset_id,
            'asset_name' => $reqRow?->asset_name ?? '',
            'technician' => $request->input('technician'),
            'team' => $request->input('team', 'Mechanical'),
            'due_date' => $request->input('due_date'),
            'priority' => $reqRow?->priority ?? $request->input('priority', 'Medium'),
            'notes' => $request->input('notes', ''),
            'status' => 'Assigned',
            'assigned_on' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', 'Work order created and technician assigned.');
    }

    public function updateStatus($orgSlug, $wo, Request $request)
    {
        DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $wo)->update([
            'status' => $request->input('status'),
            'engineer_notes' => $request->input('engineer_notes', ''),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.assignments', $orgSlug)->with('success', "Work order {$wo} status updated.");
    }
}