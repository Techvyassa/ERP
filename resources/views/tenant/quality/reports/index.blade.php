@extends('layouts.quality')

@section('title', 'Quality Reports - ' . $organization->org_name)
@section('page-title', 'Quality Reports')

@section('content')
<div x-data="qualityReports()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Quality Reports</h2>
            <p class="text-gray-500 text-sm">QC metrics, trends, and performance analysis</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total Inspections</p>
            <p class="text-3xl font-bold text-blue-600" x-text="summary.totalInspections">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Acceptance Rate</p>
            <p class="text-3xl font-bold text-green-600" x-text="summary.acceptanceRate + '%'">0%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Rejection Rate</p>
            <p class="text-3xl font-bold text-red-600" x-text="summary.rejectionRate + '%'">0%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Avg. Test Time</p>
            <p class="text-3xl font-bold text-purple-600" x-text="summary.avgTestTime + ' hrs'">0 hrs</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date From</label>
                <input type="date" x-model="filters.dateFrom" @change="loadReports()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date To</label>
                <input type="date" x-model="filters.dateTo" @change="loadReports()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Material</label>
                <input type="text" x-model="filters.material" @change="loadReports()" placeholder="Search material..."
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">Reset</button>
            </div>
        </div>
    </div>

    <!-- Decision Distribution -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-bold text-gray-900 mb-4">Decision Distribution</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-700">Accepted</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="summary.accepted"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" :style="'width: ' + (summary.acceptanceRate || 0) + '%'"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-700">Rejected</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="summary.rejected"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" :style="'width: ' + (summary.rejectionRate || 0) + '%'"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-700">Conditional</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="summary.conditional"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-amber-600 h-2 rounded-full" :style="'width: ' + (summary.conditionalRate || 0) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-bold text-gray-900 mb-4">Top Materials by Inspections</h3>
            <div class="space-y-2">
                <template x-for="material in topMaterials" :key="material.id">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-700" x-text="material.name"></span>
                        <span class="text-sm font-semibold text-gray-900" x-text="material.count + ' lots'"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Recent Inspections Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900">Recent Inspections</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Decision</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="recentInspections.length === 0">
                        <tr><td colspan="5" class="py-8 text-center text-gray-400">No inspections found</td></tr>
                    </template>
                    <template x-for="inspection in recentInspections" :key="inspection.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + inspection.id"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="inspection.material?.material_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="inspection.sample_size"></td>
                            <td class="py-3 px-5">
                                <span class="px-2 py-1 rounded text-xs font-bold" :class="decisionClass(inspection.qc_decision?.decision)" x-text="inspection.qc_decision?.decision || 'PENDING'"></span>
                            </td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(inspection.created_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function qualityReports() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        summary: { totalInspections: 0, acceptanceRate: 0, rejectionRate: 0, conditionalRate: 0, avgTestTime: 0, accepted: 0, rejected: 0, conditional: 0 },
        recentInspections: [],
        topMaterials: [],
        filters: { dateFrom: '', dateTo: '', material: '' },

        async init() {
            await this.loadReports();
        },

        async loadReports() {
            try {
                const res = await fetch('/api/v1/qc?per_page=100', { headers: headers() });
                const data = await res.json();
                const lots = data.data?.data || [];

                this.recentInspections = lots.slice(0, 10);
                this.computeSummary(lots);
                this.computeTopMaterials(lots);
            } catch (e) {
                console.error('Failed to load reports:', e);
            }
        },

        computeSummary(lots) {
            let accepted = 0, rejected = 0, conditional = 0;
            lots.forEach(lot => {
                if (lot.qc_decision) {
                    if (lot.qc_decision.decision === 'ACCEPTED') accepted++;
                    else if (lot.qc_decision.decision === 'REJECTED') rejected++;
                    else if (lot.qc_decision.decision === 'CONDITIONAL') conditional++;
                }
            });

            const total = accepted + rejected + conditional;
            this.summary = {
                totalInspections: lots.length,
                accepted: accepted,
                rejected: rejected,
                conditional: conditional,
                acceptanceRate: total > 0 ? Math.round((accepted / total) * 100) : 0,
                rejectionRate: total > 0 ? Math.round((rejected / total) * 100) : 0,
                conditionalRate: total > 0 ? Math.round((conditional / total) * 100) : 0,
                avgTestTime: 2, // Placeholder
            };
        },

        computeTopMaterials(lots) {
            const materials = {};
            lots.forEach(lot => {
                const name = lot.material?.material_name || 'Unknown';
                materials[name] = (materials[name] || 0) + 1;
            });

            this.topMaterials = Object.entries(materials)
                .map(([name, count]) => ({ name, count }))
                .sort((a, b) => b.count - a.count)
                .slice(0, 5);
        },

        resetFilters() { this.filters = { dateFrom: '', dateTo: '', material: '' }; this.loadReports(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        decisionClass(d) {
            return { 'ACCEPTED': 'bg-green-100 text-green-700', 'REJECTED': 'bg-red-100 text-red-700', 'CONDITIONAL': 'bg-amber-100 text-amber-700' }[d] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
