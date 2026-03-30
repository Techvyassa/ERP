@extends('layouts.quality')

@section('title', 'QC Inspection Detail - ' . $organization->org_name)
@section('page-title', 'Inspection Detail')

@section('content')
<div x-data="qcInspectionDetail()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lot <span x-text="'LOT-' + (lot.id || '')"></span></h2>
            <p class="text-sm text-gray-500">Record test results and submit the final QC decision.</p>
        </div>
        <div class="flex gap-2">
            <button x-show="lot.status === 'PENDING'" @click="startInspection()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Start</button>
            <button x-show="lot.status === 'IN_PROGRESS'" @click="completeInspection()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Complete</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Source</p><p class="font-semibold text-gray-900" x-text="lot.source_type || 'GRN'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Item</p><p class="font-semibold text-gray-900" x-text="lot.product?.product_name || lot.material?.material_name || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Reference</p><p class="font-semibold text-gray-900" x-text="lot.production_order?.order_no || lot.grn?.grn_number || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Batch</p><p class="font-semibold text-gray-900" x-text="lot.batch_number || lot.production_order?.fg_batch_number || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Status</p><span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="(lot.status || '').replace(/_/g, ' ')"></span></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.05fr_0.95fr] gap-6">
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Recorded Results</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Parameter</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tolerance</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Observed</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="qcResults.length === 0">
                                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No test results recorded yet.</td></tr>
                            </template>
                            <template x-for="result in qcResults" :key="result.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900" x-text="result.parameter_name"></div>
                                        <div class="text-xs text-gray-400" x-text="result.parameter_code || 'Manual parameter'"></div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" x-text="formatTolerance(result)"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="result.observed_value + ' ' + (result.unit_of_measurement || '')"></td>
                                    <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-bold" :class="result.is_pass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="result.is_pass ? 'PASS' : 'FAIL'"></span></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="result.remarks || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="lot.status === 'IN_PROGRESS'" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Record Test Result</h3>
                    <p class="text-sm text-gray-500">Use configured parameters when available, or enter a manual FG parameter.</p>
                </div>

                <div x-show="qcParameters.length > 0">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Configured Parameter</label>
                    <select x-model="selectedParameterId" @change="onParameterChange()" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">Select parameter</option>
                        <template x-for="param in qcParameters" :key="param.id">
                            <option :value="param.id" x-text="param.parameter_name + ' • ' + param.parameter_code"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" x-model="newResult.parameter_name" placeholder="Parameter name" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <input type="text" x-model="newResult.parameter_code" placeholder="Parameter code" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <select x-model="newResult.tolerance_type" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="RANGE">Range</option>
                        <option value="MIN_ONLY">Min Only</option>
                        <option value="MAX_ONLY">Max Only</option>
                        <option value="EXACT">Exact</option>
                    </select>
                    <input type="text" x-model="newResult.unit_of_measurement" placeholder="Unit" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <input type="text" x-model="newResult.standard_min" placeholder="Standard min" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <input type="text" x-model="newResult.standard_max" placeholder="Standard max" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <input type="text" x-model="newResult.standard_value" placeholder="Standard value" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <input type="number" step="0.0001" x-model="newResult.observed_value" placeholder="Observed value" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>

                <textarea x-model="newResult.remarks" rows="2" placeholder="Remarks" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></textarea>

                <div class="flex justify-end">
                    <button @click="addResult()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Record Result</button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5" x-show="lot.status === 'COMPLETED'">
                <h3 class="font-semibold text-gray-900 mb-4">Usage Decision</h3>
                <div class="space-y-4">
                    <select x-model="decision.decision" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">Select decision</option>
                        <option value="ACCEPTED">Accepted</option>
                        <option value="REJECTED">Rejected</option>
                        <option value="CONDITIONALLY_ACCEPTED">Conditionally Accepted</option>
                        <option value="REWORK_REQUIRED">Rework Required</option>
                    </select>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="number" step="0.001" x-model="decision.accepted_qty" placeholder="Accepted qty" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <input type="number" step="0.001" x-model="decision.rejected_qty" placeholder="Rejected qty" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <textarea x-model="decision.remarks" rows="3" placeholder="Decision remarks" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></textarea>
                    <button @click="makeDecision()" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Submit Decision</button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5" x-show="lot.usage_decision">
                <h3 class="font-semibold text-gray-900 mb-4">Decision Summary</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span class="text-gray-500">Decision</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.decision || '—'"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Accepted Qty</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.accepted_qty || '0'"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Rejected Qty</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.rejected_qty || '0'"></span></div>
                    <div><p class="text-gray-500 mb-1">Remarks</p><p class="text-gray-900" x-text="lot.usage_decision?.remarks || '—'"></p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function qcInspectionDetail() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const lotId = {{ $lotId }};
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug, 'Content-Type': 'application/json' });

    return {
        lot: {},
        qcResults: [],
        qcParameters: [],
        selectedParameterId: '',
        newResult: { parameter_name: '', parameter_code: '', standard_min: '', standard_max: '', standard_value: '', unit_of_measurement: '', tolerance_type: 'RANGE', observed_value: '', remarks: '' },
        decision: { decision: '', accepted_qty: '', rejected_qty: '', remarks: '' },

        async init() { await this.loadLot(); },

        async loadLot() {
            const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
            const data = await res.json();
            this.lot = data.data || {};
            this.qcResults = this.lot.test_results || [];
            if (this.lot.material_id) {
                const paramRes = await fetch(`/api/v1/qc/parameters/${this.lot.material_id}`, { headers: headers() });
                const paramData = await paramRes.json();
                this.qcParameters = paramData.data || [];
            } else {
                this.qcParameters = [];
            }
        },

        onParameterChange() {
            const param = this.qcParameters.find(item => item.id == this.selectedParameterId);
            if (!param) return;
            this.newResult = {
                parameter_name: param.parameter_name || '',
                parameter_code: param.parameter_code || '',
                standard_min: param.standard_min || '',
                standard_max: param.standard_max || '',
                standard_value: param.standard_value || '',
                unit_of_measurement: param.unit_of_measurement || '',
                tolerance_type: param.tolerance_type || 'RANGE',
                observed_value: '',
                remarks: ''
            };
        },

        async startInspection() {
            const res = await fetch(`/api/v1/qc/${lotId}/start`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to start inspection');
            await this.loadLot();
        },

        async completeInspection() {
            const res = await fetch(`/api/v1/qc/${lotId}/complete`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to complete inspection');
            await this.loadLot();
        },

        async addResult() {
            const res = await fetch(`/api/v1/qc/${lotId}/test-results`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ ...this.newResult, observed_value: parseFloat(this.newResult.observed_value) })
            });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to record result');
            this.selectedParameterId = '';
            this.newResult = { parameter_name: '', parameter_code: '', standard_min: '', standard_max: '', standard_value: '', unit_of_measurement: '', tolerance_type: 'RANGE', observed_value: '', remarks: '' };
            await this.loadLot();
        },

        async makeDecision() {
            const payload = {
                ...this.decision,
                accepted_qty: this.decision.accepted_qty === '' ? null : parseFloat(this.decision.accepted_qty),
                rejected_qty: this.decision.rejected_qty === '' ? null : parseFloat(this.decision.rejected_qty),
            };
            const res = await fetch(`/api/v1/qc/${lotId}/decision`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to make decision');
            await this.loadLot();
        },

        formatTolerance(result) {
            if (result.tolerance_type === 'RANGE') return `${result.standard_min || ''} to ${result.standard_max || ''}`;
            if (result.tolerance_type === 'MIN_ONLY') return `>= ${result.standard_min || ''}`;
            if (result.tolerance_type === 'MAX_ONLY') return `<= ${result.standard_max || ''}`;
            if (result.tolerance_type === 'EXACT') return result.standard_value || 'Exact';
            return '—';
        },

        statusClass(value) {
            return {
                'PENDING': 'bg-amber-100 text-amber-700',
                'IN_PROGRESS': 'bg-blue-100 text-blue-700',
                'COMPLETED': 'bg-green-100 text-green-700',
                'DECISION_MADE': 'bg-purple-100 text-purple-700'
            }[value] || 'bg-gray-100 text-gray-600';
        }
    };
}
</script>
@endsection
