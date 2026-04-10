<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

class RequestController extends Controller
{
    private function getOrg($orgSlug) {
        $org = Organization::where('org_slug', $orgSlug)->firstOrFail();
        return ['organization' => $org, 'tenantType' => 'path', 'org' => $org];
    }

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
}