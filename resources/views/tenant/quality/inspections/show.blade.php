@extends('layouts.quality')

@section('title', 'QC Inspection Detail - ' . $organization->org_name)
@section('page-title', 'Inspection Detail')

@section('content')
<div x-data="qcInspectionDetail()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lot <span x-text="'LOT-' + lot.id"></span></h2>
            <p class="text-gray-500 text-sm">Record test results and manage QC inspection</p>
        </div>
        <div class="flex gap-2">
            <button x-show="lot.status === 'PENDING'" @click="startInspection()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <span class="material-symbols-outlined inline mr-2">play_arrow</span> Start Inspection
            </button>
            <button x-show="lot.status === 'IN_PROGRESS'" @click="completeInspection()" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <span class="material-symbols-outlined inline mr-2">check</span> Complete Inspection
            </button>
        </div>
    </div>

    <!-- Lot Details -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Material</p>
            <p class="font-semibold text-gray-900" x-text="lot.material?.material_name || '—'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">GRN Number</p>
            <p class="font-semibold text-gray-900" x-text="lot.grn?.grn_number || '—'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Sample Size</p>
            <p class="font-semibold text-gray-900" x-text="lot.sample_size"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Status</p>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="lot.status?.replace(/_/g,' ')"></span>
        </div>
    </div>

    <!-- Test Results Section -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Test Results</h3>
            <button x-show="lot.status === 'IN_PROGRESS'" @click="openAddResultModal()" class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm hover:bg-primary/90">
                <span class="material-symbols-outlined inline mr-1 text-sm">add</span> Add Result
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Parameter</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Standard Min</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Standard Max</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Observed Value</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Unit</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="qcResults.length === 0">
                        <tr><td colspan="6" class="py-8 text-center text-gray-400">No test results recorded</td></tr>
                    </template>
                    <template x-for="result in qcResults" :key="result.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-5 font-medium text-gray-900" x-text="result.qc_parameter?.parameter_name"></td>
                            <td class="py-3 px-5 text-right text-gray-700" x-text="result.qc_parameter?.standard_value_min"></td>
                            <td class="py-3 px-5 text-right text-gray-700" x-text="result.qc_parameter?.standard_value_max"></td>
                            <td class="py-3 px-5 text-right font-semibold text-gray-900" x-text="result.observed_value"></td>
                            <td class="py-3 px-5 text-gray-700" x-text="result.qc_parameter?.unit"></td>
                            <td class="py-3 px-5">
                                <span class="px-2 py-1 rounded text-xs font-bold" :class="result.qc_parameter?.isWithinStandard ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="result.qc_parameter?.isWithinStandard ? 'PASS' : 'FAIL'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Usage Decision Section -->
    <div x-show="lot.status === 'COMPLETED'" class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4">Usage Decision</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Decision *</label>
                <select x-model="decision.decision" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Select decision</option>
                    <option value="ACCEPTED">Accepted - Stock Released</option>
                    <option value="REJECTED">Rejected - Return to Vendor</option>
                    <option value="CONDITIONAL">Conditional - Requires Approval</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                <textarea x-model="decision.remarks" rows="3"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Add any remarks or notes..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="makeDecision()" :disabled="!decision.decision || saving"
                    class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50">
                    <span x-show="!saving">Make Decision</span>
                    <span x-show="saving">Processing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Result Modal -->
    <div x-show="showAddResultModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showAddResultModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Add Test Result</h3>
                    <button @click="showAddResultModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="addResult()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">QC Parameter *</label>
                        <select x-model="newResult.qc_parameter_id" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select parameter</option>
                            <template x-for="param in qcParameters" :key="param.id">
                                <option :value="param.id" x-text="param.parameter_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Observed Value *</label>
                        <input type="number" x-model="newResult.observed_value" step="0.0001" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Enter observed value">
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showAddResultModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving">Add Result</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function qcInspectionDetail() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const lotId = {{ $lotId }};
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        lot: {},
        qcResults: [],
        qcParameters: [],
        showAddResultModal: false,
        saving: false,
        newResult: { qc_parameter_id: '', observed_value: '' },
        decision: { decision: '', remarks: '' },

        async init() {
            await this.loadLot();
            await this.loadResults();
            await this.loadParameters();
        },

        async loadLot() {
            try {
                const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
                const data = await res.json();
                this.lot = data.data || {};
            } catch (e) {
                console.error('Failed to load lot:', e);
            }
        },

        async loadResults() {
            try {
                const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
                const data = await res.json();
                this.qcResults = data.data?.qc_results || [];
            } catch (e) {
                console.error('Failed to load results:', e);
            }
        },

        async loadParameters() {
            try {
                const res = await fetch(`/api/v1/qc/parameters/${this.lot.material_id}`, { headers: headers() });
                const data = await res.json();
                this.qcParameters = data.data || [];
            } catch (e) {
                console.error('Failed to load parameters:', e);
            }
        },

        async startInspection() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/start`, { method: 'PATCH', headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Inspection started successfully');
                } else {
                    alert(data.message || 'Failed to start inspection');
                }
            } finally { this.saving = false; }
        },

        async completeInspection() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/complete`, { method: 'PATCH', headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Inspection completed successfully');
                } else {
                    alert(data.message || 'Failed to complete inspection');
                }
            } finally { this.saving = false; }
        },

        async addResult() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/test-results`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(this.newResult)
                });
                const data = await res.json();
                if (data.success) {
                    this.showAddResultModal = false;
                    this.newResult = { qc_parameter_id: '', observed_value: '' };
                    await this.loadResults();
                    alert('Test result recorded successfully');
                } else {
                    alert(data.message || 'Failed to record result');
                }
            } finally { this.saving = false; }
        },

        async makeDecision() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/decision`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(this.decision)
                });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Decision made successfully');
                } else {
                    alert(data.message || 'Failed to make decision');
                }
            } finally { this.saving = false; }
        },

        openAddResultModal() {
            this.showAddResultModal = true;
        },

        statusClass(s) {
            return { 'PENDING': 'bg-amber-100 text-amber-700', 'IN_PROGRESS': 'bg-blue-100 text-blue-700', 'COMPLETED': 'bg-green-100 text-green-700', 'DECISION_MADE': 'bg-purple-100 text-purple-700' }[s] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
