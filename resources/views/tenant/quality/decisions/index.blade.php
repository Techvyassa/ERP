@extends('layouts.quality')

@section('title', 'QC Decisions - ' . $organization->org_name)
@section('page-title', 'Usage Decisions')

@section('content')
<div x-data="qcDecisions()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Usage Decisions</h2>
            <p class="text-gray-500 text-sm">View and manage QC usage decisions</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Accepted</p>
            <p class="text-3xl font-bold text-green-600" x-text="stats.accepted">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Rejected</p>
            <p class="text-3xl font-bold text-red-600" x-text="stats.rejected">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Conditional</p>
            <p class="text-3xl font-bold text-amber-600" x-text="stats.conditional">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total Decisions</p>
            <p class="text-3xl font-bold text-blue-600" x-text="stats.total">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Decision</label>
                <select x-model="filters.decision" @change="loadDecisions()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="ACCEPTED">Accepted</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="CONDITIONAL">Conditional</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date From</label>
                <input type="date" x-model="filters.dateFrom" @change="loadDecisions()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">Reset</button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Decision</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Decided By</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && decisions.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No decisions found</td></tr>
                    </template>
                    <template x-for="decision in decisions" :key="decision.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + decision.lot_id"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.inspection_lot?.material?.material_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.inspection_lot?.grn?.grn_number || '—'"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="decisionClass(decision.decision)" x-text="decision.decision"></span>
                            </td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.decision_maker?.first_name + ' ' + decision.decision_maker?.last_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(decision.decided_at)"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="decision.remarks || '—'"></td>
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
        loading: false,
        stats: { accepted: 0, rejected: 0, conditional: 0, total: 0 },
        filters: { decision: '', dateFrom: '' },

        async init() {
            await this.loadDecisions();
        },

        async loadDecisions() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/qc', { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    const lots = data.data.data || [];
                    this.decisions = lots.filter(l => l.usage_decision).map(l => l.usage_decision);
                    this.computeStats();
                }
            } finally { this.loading = false; }
        },

        computeStats() {
            this.stats = { accepted: 0, rejected: 0, conditional: 0, total: 0 };
            this.decisions.forEach(d => {
                if (d.decision === 'ACCEPTED') this.stats.accepted++;
                else if (d.decision === 'REJECTED') this.stats.rejected++;
                else if (d.decision === 'CONDITIONAL') this.stats.conditional++;
                this.stats.total++;
            });
        },

        resetFilters() { this.filters = { decision: '', dateFrom: '' }; this.loadDecisions(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        decisionClass(d) {
            return { 'ACCEPTED': 'bg-green-100 text-green-700', 'REJECTED': 'bg-red-100 text-red-700', 'CONDITIONAL': 'bg-amber-100 text-amber-700' }[d] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
