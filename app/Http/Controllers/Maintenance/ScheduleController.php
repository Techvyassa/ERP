<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class ScheduleController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

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
}