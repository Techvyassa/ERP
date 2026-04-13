<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class WorkOrderController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
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
        return view('tenant.maintenance.work-orders.index', compact('workOrders') + ['organization' => $org, 'tenantType' => $tenantType]);
    }
}