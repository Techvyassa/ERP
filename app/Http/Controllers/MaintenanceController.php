<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class MaintenanceController extends Controller
{
    public function dashboard($orgSlug)
    {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
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

        return view('tenant.maintenance.dashboard', compact('org', 'stats', 'recentWOs', 'lowStockParts', 'woByStatus'));
    }
}
