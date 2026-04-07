@extends('layouts.quality')

@section('title', 'QC Decisions - ' . $organization->org_name)
@section('page-title', 'Usage Decisions')

@section('content')
<div x-data="qcDecisions()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Usage Decisions</h2>
            <p class="text-sm text-gray-500">Review raw material and finished goods decisions under separate tabs.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-2 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <button @click="setTab('GRN')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'GRN' ? 'bg-sky-50 text-qc ring-1 ring-sky-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Raw Material QC</p>
                    <p class="text-xs text-gray-500">Incoming material decisions</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="tabCounts.GRN"></span>
            </button>
            <button @click="setTab('PRODUCTION')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'PRODUCTION' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Finished Goods QC</p>
                    <p class="text-xs text-gray-500">FG release and rejection decisions</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="tabCounts.PRODUCTION"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase mb-1">Accepted</p><p class="text-3xl font-bold text-green-600" x-text="stats.accepted">0</p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase mb-1">Rejected</p><p class="text-3xl font-bold text-red-600" x-text="stats.rejected">0</p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase mb-1">Conditional / Rework</p><p class="text-3xl font-bold text-amber-600" x-text="stats.conditional">0</p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total</p><p class="text-3xl font-bold text-blue-600" x-text="stats.total">0</p></div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select x-model="filters.decision" @change="applyFilters()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <option value="">All Decisions</option>
                <option value="ACCEPTED">Accepted</option>
                <option value="REJECTED">Rejected</option>
                <option value="CONDITIONALLY_ACCEPTED">Conditionally Accepted</option>
                <option value="REWORK_REQUIRED">Rework Required</option>
            </select>
            <button @click="resetFilters()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Reset</button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Source</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Item</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Reference</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Decision</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Accepted / Rejected</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && filteredDecisions.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No decisions found.</td></tr>
                    </template>
                    <template x-for="decision in filteredDecisions" :key="decision.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + decision.lot_id"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="decision.source_type"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.item_name"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.reference_number"></td>
                            <td class="py-3 px-5"><span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="decisionClass(decision.decision)" x-text="decision.decision"></span></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.accepted_qty + ' / ' + decision.rejected_qty"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(decision.decided_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function qcDecisions() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        decisions: [],
        filteredDecisions: [],
        loading: false,
        activeTab: 'GRN',
        tabCounts: { GRN: 0, PRODUCTION: 0 },
        stats: { accepted: 0, rejected: 0, conditional: 0, total: 0 },
        filters: { decision: '' },

        async init() {
            const initialTab = new URLSearchParams(window.location.search).get('tab');
            if (initialTab === 'GRN' || initialTab === 'PRODUCTION') {
                this.activeTab = initialTab;
            }
            await this.loadDecisions();
        },

        async loadDecisions() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/qc?per_page=200', { headers: headers() });
                const data = await res.json();
                const lots = data.data?.data || [];
                this.decisions = lots.filter(lot => lot.usage_decision).map(lot => ({
                    id: lot.usage_decision.id,
                    lot_id: lot.id,
                    source_type: lot.source_type || 'GRN',
                    decision: lot.usage_decision.decision,
                    accepted_qty: lot.usage_decision.accepted_qty || 0,
                    rejected_qty: lot.usage_decision.rejected_qty || 0,
                    decided_at: lot.usage_decision.decided_at,
                    item_name: lot.product?.product_name || lot.material?.material_name || '—',
                    reference_number: lot.production_order?.order_no || lot.grn?.grn_number || '—'
                }));
                this.computeTabCounts();
                this.applyFilters();
                this.computeStats();
            } finally {
                this.loading = false;
            }
        },

        setTab(source) {
            this.activeTab = source;
            this.applyFilters();
            this.computeStats();
        },

        computeTabCounts() {
            this.tabCounts = {
                GRN: this.decisions.filter(decision => decision.source_type === 'GRN').length,
                PRODUCTION: this.decisions.filter(decision => decision.source_type === 'PRODUCTION').length
            };
        },

        applyFilters() {
            this.filteredDecisions = this.decisions.filter(decision => {
                return decision.source_type === this.activeTab
                    && (!this.filters.decision || decision.decision === this.filters.decision);
            });
        },

        computeStats() {
            this.stats = { accepted: 0, rejected: 0, conditional: 0, total: this.filteredDecisions.length };
            this.filteredDecisions.forEach(decision => {
                if (decision.decision === 'ACCEPTED') this.stats.accepted++;
                else if (decision.decision === 'REJECTED') this.stats.rejected++;
                else this.stats.conditional++;
            });
        },

        resetFilters() { this.filters = { decision: '' }; this.applyFilters(); this.computeStats(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        decisionClass(value) {
            return {
                'ACCEPTED': 'bg-green-100 text-green-700',
                'REJECTED': 'bg-red-100 text-red-700',
                'CONDITIONALLY_ACCEPTED': 'bg-amber-100 text-amber-700',
                'REWORK_REQUIRED': 'bg-purple-100 text-purple-700'
            }[value] || 'bg-gray-100 text-gray-600';
        }
    };
}
</script>
@endsection
