<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class ApprovalController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $approvals = DB::connection('tenant')->table('maint_requests')
            ->where('status', 'Pending Approval')
            ->orderByDesc('id')
            ->get()
            ->map(fn($r) => [
                'id' => $r->request_no,
                'asset' => $r->asset_name,
                'priority' => $r->priority,
                'issue' => $r->issue,
                'status' => $r->status,
                'raised_by' => $r->raised_by,
                'raised_on' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
            ])
            ->all();
        return view('tenant.maintenance.approvals.index', compact('approvals') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function approve($orgSlug, $id, Request $request)
    {
        DB::connection('tenant')->table('maint_requests')->where('request_no', $id)->update([
            'status' => 'Approved',
            'approved_on' => now()->format('Y-m-d'),
            'remarks' => $request->input('remarks', ''),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} approved.");
    }

    public function reject($orgSlug, $id, Request $request)
    {
        DB::connection('tenant')->table('maint_requests')->where('request_no', $id)->update([
            'status' => 'Rejected',
            'rejected_on' => now()->format('Y-m-d'),
            'remarks' => $request->input('remarks', ''),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.approvals', $orgSlug)->with('success', "Request {$id} rejected.");
    }
}