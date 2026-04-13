<?php

$outDir = __DIR__ . '/../app/Http/Controllers/Maintenance';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$base = <<<'EOT'
<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class {{CLASSNAME}} extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

{{BODY}}
}
EOT;

$controllers = [];

$controllers['DashboardController'] = <<<'EOT'
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
EOT;

$controllers['RequestController'] = <<<'EOT'
    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $requests = DB::connection('tenant')->table('maint_requests')->orderByDesc('id')->get()->map(function ($r) {
            return [
                'id' => $r->request_no,
                'asset' => $r->asset_name,
                'asset_code' => '',
                'priority' => $r->priority,
                'issue' => $r->issue,
                'status' => $r->status,
                'raised_by' => $r->raised_by,
                'raised_on' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
            ];
        })->all();
        $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
        ])->all();
        return view('tenant.maintenance.requests.index', compact('requests', 'assets') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        $seq = (int) (DB::connection('tenant')->table('maint_requests')->max('id') ?? 0) + 1;
        $user = session('auth_user_name', 'User');
        $assetName = (string) $request->input('asset');
        $assetCode = (string) $request->input('asset_code', '');
        $assetRow = null;
        if ($assetCode !== '') {
            $assetRow = DB::connection('tenant')->table('maint_assets')->where('code', $assetCode)->first();
        }
        if (!$assetRow && $assetName !== '') {
            $assetRow = DB::connection('tenant')->table('maint_assets')->where('name', $assetName)->first();
        }

        DB::connection('tenant')->table('maint_requests')->insert([
            'request_no' => 'MR-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'asset_id' => $assetRow?->id,
            'asset_name' => $assetRow?->name ?? $assetName,
            'priority' => $request->input('priority'),
            'issue' => $request->input('issue'),
            'status' => 'Pending Approval',
            'raised_by' => $user,
            'raised_by_id' => $request->get('auth_user_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.requests', $orgSlug)
            ->with('success', 'Maintenance request submitted successfully.');
    }
EOT;

$controllers['ApprovalController'] = <<<'EOT'
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
EOT;

$controllers['AssignmentController'] = <<<'EOT'
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
EOT;

$controllers['WorkOrderController'] = <<<'EOT'
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
EOT;

$controllers['MaterialRequestController'] = <<<'EOT'
    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $workOrders = DB::connection('tenant')->table('maint_work_orders')->orderByDesc('id')->get()->map(fn($w) => [
            'wo' => $w->wo_no,
            'asset' => $w->asset_name,
            'status' => $w->status,
        ])->all();
        $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'stock' => $p->stock,
            'reorder_level' => $p->reorder_level,
            'unit' => $p->unit,
        ])->all();
        $matRequests = DB::connection('tenant')->table('maint_material_requests')->orderByDesc('id')->get()->map(fn($m) => [
            'id' => $m->mmr_no,
            'wo_id' => $m->wo_no,
            'part_code' => $m->part_code,
            'part_name' => $m->part_name,
            'qty' => $m->qty,
            'unit' => $m->unit,
            'in_stock' => (bool) $m->in_stock,
            'status' => $m->status,
            'raised_on' => $m->raised_on,
            'issued_on' => $m->issued_on,
            'po_no' => $m->po_no ?? null,
        ])->all();
        return view('tenant.maintenance.material-requests.index', compact('workOrders', 'parts', 'matRequests') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        $woNo = $request->input('wo_id');
        $woRow = DB::connection('tenant')->table('maint_work_orders')->where('wo_no', $woNo)->first();

        $items = $request->input('items');
        if (!is_array($items) || count($items) === 0) {
            $items = [
                [
                    'part_code' => $request->input('part_code'),
                    'part_name' => $request->input('part_name'),
                    'unit' => $request->input('unit', 'Nos'),
                    'qty' => $request->input('qty', 1),
                ]
            ];
        }

        $items = array_values(array_filter($items, fn($i) => is_array($i) && !empty($i['part_code'])));

        if (!$woNo || count($items) === 0) {
            return redirect()->route('tenant.maintenance.material-requests', $orgSlug)
                ->with('success', 'No material items provided.');
        }

        $now = now();
        $today = $now->format('Y-m-d');
        $seq = (int) (DB::connection('tenant')->table('maint_material_requests')->max('id') ?? 0) + 1;

        $createdCount = 0;
        $anyProcurement = false;
        $anyPendingIssue = false;

        DB::connection('tenant')->transaction(function () use ($items, $woNo, $woRow, $today, $now, &$seq, &$createdCount, &$anyProcurement, &$anyPendingIssue) {
            foreach ($items as $item) {
                $partCode = $item['part_code'] ?? null;
                $qty = max(1, (int) ($item['qty'] ?? 1));

                $partRow = $partCode
                    ? DB::connection('tenant')->table('maint_spare_parts')->where('code', $partCode)->first()
                    : null;

                $inStock = $partRow ? ((int) $partRow->stock >= $qty) : false;
                $status = $inStock ? 'Pending Issue' : 'Procurement Required';

                $anyPendingIssue = $anyPendingIssue || $inStock;
                $anyProcurement = $anyProcurement || !$inStock;

                DB::connection('tenant')->table('maint_material_requests')->insert([
                    'mmr_no' => 'MMR-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'wo_id' => $woRow?->id,
                    'wo_no' => $woRow?->wo_no ?? $woNo,
                    'part_id' => $partRow?->id,
                    'part_code' => $partCode,
                    'part_name' => ($item['part_name'] ?? null) ?: ($partRow?->name ?? $partCode),
                    'qty' => $qty,
                    'unit' => ($item['unit'] ?? null) ?: ($partRow?->unit ?? 'Nos'),
                    'in_stock' => $inStock,
                    'status' => $status,
                    'raised_on' => $today,
                    'issued_on' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $seq++;
                $createdCount++;
            }
        });

        $msg = "Material request raised for {$createdCount} item(s).";
        if ($anyPendingIssue && $anyProcurement) {
            $msg .= ' Some items are in stock (Pending Issue) and some require procurement.';
        } elseif ($anyPendingIssue) {
            $msg .= ' Stock available — ready to issue.';
        } else {
            $msg .= ' Material not in stock. Procurement request flagged.';
        }

        return redirect()->route('tenant.maintenance.material-requests', $orgSlug)->with('success', $msg);
    }

    public function issue($orgSlug, $id)
    {
        $row = DB::connection('tenant')->table('maint_material_requests')->where('mmr_no', $id)->first();
        if ($row && $row->status === 'Pending Issue') {
            DB::connection('tenant')->table('maint_material_requests')->where('id', $row->id)->update([
                'status' => 'Issued',
                'issued_on' => now()->format('Y-m-d'),
                'updated_at' => now(),
            ]);

            if ($row->part_id) {
                DB::connection('tenant')->table('maint_spare_parts')->where('id', $row->part_id)->update([
                    'stock' => DB::raw('GREATEST(0, stock - ' . ((int) $row->qty) . ')'),
                    'updated_at' => now(),
                ]);
            }
        }
        return redirect()->route('tenant.maintenance.material-requests', $orgSlug)->with('success', "Material {$id} issued from stock.");
    }

    public function raisePo($orgSlug, $id, Request $request)
    {
        $row = DB::connection('tenant')->table('maint_material_requests')->where('mmr_no', $id)->first();
        if (!$row || !in_array($row->status, ['Procurement Required'])) {
            return redirect()->route('tenant.maintenance.material-requests', $orgSlug)
                ->with('success', 'Cannot raise PO for this request.');
        }
        $seq = (int) (DB::connection('tenant')->table('maint_procurement_orders')->max('id') ?? 0) + 1;
        $poNo = 'MPO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        DB::connection('tenant')->table('maint_procurement_orders')->insert([
            'po_no' => $poNo,
            'part_id' => $row->part_id,
            'part_code' => $row->part_code,
            'part_name' => $row->part_name,
            'unit' => $row->unit,
            'qty' => $row->qty,
            'vendor' => $request->input('vendor', ''),
            'expected_date' => $request->input('expected_date') ?: null,
            'notes' => 'Auto-raised from MMR: ' . $row->mmr_no . ' (WO: ' . $row->wo_no . ')',
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('maint_material_requests')->where('id', $row->id)->update([
            'status' => 'PO Raised',
            'po_no' => $poNo,
            'updated_at' => now(),
        ]);
        return redirect()->route('tenant.maintenance.material-requests', $orgSlug)
            ->with('success', "Procurement order {$poNo} raised for {$row->part_name}.");
    }

    public function raisePoDirect($orgSlug, Request $request)
    {
        $id = $request->input('mmr_no');
        $row = DB::connection('tenant')->table('maint_material_requests')->where('mmr_no', $id)->first();
        if (!$row || $row->status !== 'Procurement Required') {
            return response()->json(['ok' => false, 'message' => 'Cannot raise PO for this request.'], 422);
        }
        $seq = (int) (DB::connection('tenant')->table('maint_procurement_orders')->max('id') ?? 0) + 1;
        $poNo = 'MPO-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        DB::connection('tenant')->table('maint_procurement_orders')->insert([
            'po_no' => $poNo,
            'part_id' => $row->part_id,
            'part_code' => $row->part_code,
            'part_name' => $row->part_name,
            'unit' => $row->unit,
            'qty' => $row->qty,
            'vendor' => $request->input('vendor', ''),
            'expected_date' => $request->input('expected_date') ?: null,
            'notes' => 'Auto-raised from MMR: ' . $row->mmr_no . ' (WO: ' . $row->wo_no . ')',
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('maint_material_requests')->where('id', $row->id)->update([
            'status' => 'PO Raised',
            'po_no' => $poNo,
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'po_no' => $poNo, 'mmr_no' => $id, 'part_name' => $row->part_name]);
    }
EOT;

$controllers['ClosureController'] = <<<'EOT'
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
EOT;

$controllers['AssetController'] = <<<'EOT'
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
EOT;

$controllers['ScheduleController'] = <<<'EOT'
    public function index($orgSlug)
    {
        extract($this->getOrg($orgSlug));
        $assets = DB::connection('tenant')->table('maint_assets')->orderBy('name')->get()->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
        ])->all();
        $parts = DB::connection('tenant')->table('maint_spare_parts')->orderBy('name')->get()->map(fn($p) => [
            'code' => $p->code,
            'name' => $p->name,
            'stock' => $p->stock,
            'unit' => $p->unit,
        ])->all();

        $materialsByPm = DB::connection('tenant')->table('maint_pm_materials')->get()->groupBy('pm_id');
        $schedules = DB::connection('tenant')->table('maint_pm_schedules')->orderByDesc('id')->get()->map(function ($pm) use ($materialsByPm) {
            $mats = ($materialsByPm[$pm->id] ?? collect())->map(fn($m) => [
                'name' => $m->part_name,
                'qty' => $m->qty,
                'unit' => $m->unit,
            ])->values()->all();

            $status = $pm->status;
            if ($status !== 'Done' && $pm->next_due && $pm->next_due < date('Y-m-d')) {
                $status = 'Overdue';
            }

            return [
                'id' => $pm->pm_no,
                'asset' => $pm->asset_name,
                'task' => $pm->task,
                'frequency' => $pm->frequency,
                'assigned_to' => $pm->assigned_to,
                'next_due' => $pm->next_due,
                'duration' => $pm->duration,
                'materials' => $mats,
                'last_done' => $pm->last_done,
                'status' => $status,
                'notes' => $pm->notes,
            ];
        })->all();
        return view('tenant.maintenance.schedule.index', compact('schedules', 'assets', 'parts') + ['organization' => $org, 'tenantType' => $tenantType]);
    }

    public function store($orgSlug, Request $request)
    {
        $matNames = $request->input('mat_name', []);
        $matQtys = $request->input('mat_qty', []);
        $matUnits = $request->input('mat_unit', []);
        $materials = [];
        foreach ($matNames as $i => $mn) {
            if (trim($mn))
                $materials[] = ['name' => $mn, 'qty' => $matQtys[$i] ?? 1, 'unit' => $matUnits[$i] ?? 'Nos'];
        }

        $assetName = (string) $request->input('asset');
        $assetRow = DB::connection('tenant')->table('maint_assets')->where('name', $assetName)->first();

        $seq = (int) (DB::connection('tenant')->table('maint_pm_schedules')->max('id') ?? 0) + 1;
        $pmNo = 'PM-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $pmId = DB::connection('tenant')->table('maint_pm_schedules')->insertGetId([
            'pm_no' => $pmNo,
            'asset_id' => $assetRow?->id,
            'asset_name' => $assetRow?->name ?? $assetName,
            'task' => $request->input('task'),
            'frequency' => $request->input('frequency'),
            'assigned_to' => $request->input('assigned_to', ''),
            'next_due' => $request->input('next_due'),
            'duration' => $request->input('duration', ''),
            'status' => 'Scheduled',
            'last_done' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($materials as $mat) {
            DB::connection('tenant')->table('maint_pm_materials')->insert([
                'pm_id' => $pmId,
                'part_name' => $mat['name'],
                'qty' => (int) ($mat['qty'] ?? 1),
                'unit' => $mat['unit'] ?? 'Nos',
            ]);
        }
        return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', 'PM task scheduled successfully.');
    }

    public function done($orgSlug, $id, Request $request)
    {
        $pmRow = DB::connection('tenant')->table('maint_pm_schedules')->where('pm_no', $id)->first();
        if ($pmRow) {
            DB::connection('tenant')->table('maint_pm_schedules')->where('id', $pmRow->id)->update([
                'status' => 'Done',
                'last_done' => now()->format('Y-m-d'),
                'notes' => $request->input('notes', ''),
                'updated_at' => now(),
            ]);

            $materials = DB::connection('tenant')->table('maint_pm_materials')->where('pm_id', $pmRow->id)->get();
            foreach ($materials as $mat) {
                DB::connection('tenant')->table('maint_spare_parts')
                    ->whereRaw('LOWER(name) = ?', [strtolower($mat->part_name)])
                    ->update([
                        'stock' => DB::raw('GREATEST(0, stock - ' . ((int) $mat->qty) . ')'),
                        'updated_at' => now(),
                    ]);
            }
        }
        return redirect()->route('tenant.maintenance.schedule', $orgSlug)->with('success', "PM task {$id} marked as done. Materials deducted from stock.");
    }
EOT;

$controllers['SparePartController'] = <<<'EOT'
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
EOT;

$controllers['ProcurementController'] = <<<'EOT'
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
EOT;

$controllers['StockManagementController'] = <<<'EOT'
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
EOT;

foreach ($controllers as $name => $body) {
    $content = str_replace(['{{CLASSNAME}}', '{{BODY}}'], [$name, $body], $base);
    file_put_contents($outDir . '/' . $name . '.php', $content);
}
echo "Created all controllers successfully.";
