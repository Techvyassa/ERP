<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class DashboardController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $today = date('Y-m-d');

        $workOrders = DB::connection('tenant')->table('maint_work_orders')->get();
        $assets = DB::connection('tenant')->table('maint_assets')->get();
        $schedules = DB::connection('tenant')->table('maint_pm_schedules')->get();
        $requests = DB::connection('tenant')->table('maint_requests')->get();
        $parts = DB::connection('tenant')->table('maint_spare_parts')->get();
        $matReqs = DB::connection('tenant')->table('maint_material_requests')->get();
        $procOrders = DB::connection('tenant')->table('maint_procurement_orders')->get();

        $woByStatus = $workOrders->groupBy('status')->map->count();

        $recentWOs = DB::connection('tenant')->table('maint_work_orders')
            ->orderByDesc('id')->limit(5)->get()
            ->map(fn($w) => [
                'wo_no' => $w->wo_no,
                'asset' => $w->asset_name,
                'technician' => $w->technician,
                'status' => $w->status,
                'due_date' => $w->due_date,
                'priority' => $w->priority,
            ])->all();

        $overduePM = $schedules->filter(fn($pm) => $pm->next_due && $pm->next_due < $today && $pm->status !== 'Done')->count();

        $lowStockParts = $parts->filter(fn($p) => $p->reorder_level !== null && $p->stock <= $p->reorder_level)
            ->map(fn($p) => ['name' => $p->name, 'code' => $p->code, 'stock' => $p->stock, 'reorder_level' => $p->reorder_level, 'unit' => $p->unit])
            ->values()->all();

        $pendingProcurement = $procOrders->filter(fn($o) => in_array($o->status, ['Pending', 'Ordered']))->count();

        $stats = [
            'openWorkOrders' => $workOrders->filter(fn($w) => in_array($w->status, ['Assigned', 'In Progress']))->count(),
            'overdueOrders' => $workOrders->filter(fn($w) => $w->due_date && $w->due_date < $today && $w->status !== 'Closed')->count(),
            'completedOrders' => $workOrders->where('status', 'Completed')->count(),
            'closedOrders' => $workOrders->where('status', 'Closed')->count(),
            'totalAssets' => $assets->count(),
            'activeAssets' => $assets->where('status', 'Active')->count(),
            'underMaintenance' => $assets->where('status', 'Under Maintenance')->count(),
            'scheduledPM' => $schedules->filter(fn($pm) => $pm->next_due && $pm->next_due >= $today && $pm->next_due <= date('Y-m-d', strtotime('+7 days')) && $pm->status !== 'Done')->count(),
            'overduePM' => $overduePM,
            'donePM' => $schedules->where('status', 'Done')->count(),
            'pendingRequests' => $requests->where('status', 'Pending Approval')->count(),
            'approvedRequests' => $requests->where('status', 'Approved')->count(),
            'procurementNeeded' => $matReqs->where('status', 'Procurement Required')->count(),
            'pendingIssue' => $matReqs->where('status', 'Pending Issue')->count(),
            'pendingProcurement' => $pendingProcurement,
            'lowStockCount' => count($lowStockParts),
            'totalParts' => $parts->count(),
        ];

        return view('tenant.maintenance.dashboard', compact('org', 'tenantType', 'stats', 'recentWOs', 'lowStockParts', 'woByStatus') + ['organization' => $org]);
    }

    public function workOrdersJson($orgSlug) {
        $today = date('Y-m-d');
        $rows = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
            'wo_no' => $w->wo_no,
            'asset' => $w->asset_name,
            'technician' => $w->technician,
            'status' => $w->status,
            'priority' => $w->priority,
            'due_date' => $w->due_date,
            'overdue' => $w->due_date && $w->due_date < $today && $w->status !== 'Closed',
        ])->all();
        return response()->json($rows);
    }

    public function assetsJson($orgSlug) {
        $rows = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'category' => $a->category,
            'location' => $a->location,
            'status' => $a->status,
            'model' => $a->model,
        ])->all();
        return response()->json($rows);
    }

    public function pmJson($orgSlug) {
        $today = date('Y-m-d');
        $rows = DB::connection('tenant')->table('maint_pm_schedules')->orderBy('next_due')->get()->map(fn($p) => [
            'pm_no' => $p->pm_no,
            'asset' => $p->asset_name,
            'task' => $p->task,
            'frequency' => $p->frequency,
            'assigned_to' => $p->assigned_to,
            'next_due' => $p->next_due,
            'status' => $p->status,
            'overdue' => $p->next_due && $p->next_due < $today && $p->status !== 'Done',
        ])->all();
        return response()->json($rows);
    }

    public function lowStockJson($orgSlug) {
        $rows = DB::connection('tenant')->table('maint_spare_parts')
            ->whereNotNull('reorder_level')
            ->whereRaw('stock <= reorder_level')
            ->orderBy('stock')->get()->map(fn($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'stock' => $p->stock,
                'reorder_level' => $p->reorder_level,
                'unit' => $p->unit,
                'asset' => $p->compatible_asset,
            ])->all();
        return response()->json($rows);
    }

    public function materialRequestsJson($orgSlug) {
        $rows = DB::connection('tenant')->table('maint_material_requests')
            ->whereIn('status', ['Procurement Required', 'Pending Issue'])
            ->orderByDesc('id')->get()->map(fn($m) => [
                'id' => $m->mmr_no,
                'wo_no' => $m->wo_no,
                'part_name' => $m->part_name,
                'part_code' => $m->part_code,
                'qty' => $m->qty,
                'unit' => $m->unit,
                'status' => $m->status,
                'raised_on' => $m->raised_on,
            ])->all();
        return response()->json($rows);
    }

    public function requestsJson($orgSlug) {
        $rows = DB::connection('tenant')->table('maint_requests')
            ->whereIn('status', ['Pending Approval', 'Approved'])
            ->orderByDesc('id')->get()->map(fn($r) => [
                'id' => $r->request_no,
                'asset' => $r->asset_name,
                'issue' => $r->issue,
                'priority' => $r->priority,
                'status' => $r->status,
                'raised_by' => $r->raised_by,
                'date' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
            ])->all();
        return response()->json($rows);
    }
}