<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class MaterialRequestController extends Controller
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
}