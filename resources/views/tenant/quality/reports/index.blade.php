@extends('layouts.quality')

@section('title', 'Quality Reports - ' . $organization->org_name)
@section('page-title', 'Quality Reports')

@section('content')
<div x-data="qualityReports()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Quality Reports</h2>
            <p class="text-gray-500 text-sm">Comprehensive QC analytics and performance metrics</p>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                <input type="date" x-model="filters.dateFrom" @change="loadReports()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
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

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total Inspections</p>
            <p class="text-3xl font-bold text-blue-600" x-text="metrics.totalInspections">0</p>
            <p class="text-xs text-gray-500 mt-2" x-text="'Completed: ' + metrics.completedInspections"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Acceptance Rate</p>
            <p class="text-3xl font-bold text-green-600" x-text="metrics.acceptanceRate + '%'">0%</p>
            <p class="text-xs text-gray-500 mt-2" x-text="metrics.acceptedCount + ' accepted'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Rejection Rate</p>
            <p class="text-3xl font-bold text-red-600" x-text="metrics.rejectionRate + '%'">0%</p>
            <p class="text-xs text-gray-500 mt-2" x-text="metrics.rejectedCount + ' rejected'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Conditional</p>
            <p class="text-3xl font-bold text-amber-600" x-text="metrics.conditionalCount">0</p>
            <p class="text-xs text-gray-500 mt-2">Pending approval</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Avg Inspection Time</p>
            <p class="text-3xl font-bold text-purple-600" x-text="metrics.avgInspectionTime + ' hrs'">0 hrs</p>
            <p class="text-xs text-gray-500 mt-2">Per lot</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="flex gap-2 border-b border-gray-200">
            <button @click="activeTab = 'summary'" :class="activeTab === 'summary' ? 'border-b-2 border-primary text-primary' : 'text-gray-600'"
                class="px-4 py-3 font-semibold text-sm transition">Summary</button>
            <button @click="activeTab = 'byMaterial'" :class="activeTab === 'byMaterial' ? 'border-b-2 border-primary text-primary' : 'text-gray-600'"
                class="px-4 py-3 font-semibold text-sm transition">By Material</button>
            <button @click="activeTab = 'byTechnician'" :class="activeTab === 'byTechnician' ? 'border-b-2 border-primary text-primary' : 'text-gray-600'"
                class="px-4 py-3 font-semibold text-sm transition">By Technician</button>
            <button @click="activeTab = 'failureAnalysis'" :class="activeTab === 'failureAnalysis' ? 'border-b-2 border-primary text-primary' : 'text-gray-600'"
                class="px-4 py-3 font-semibold text-sm transition">Failure Analysis</button>
        </div>
    </div>

    <!-- Summary Tab -->
    <template x-if="activeTab === 'summary'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Decision</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Technician</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="8" class="py-12 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && summaryData.length === 0">
                            <tr><td colspan="8" class="py-12 text-center text-gray-400">No data found</td></tr>
                        </template>
                        <template x-for="item in summaryData" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + item.id"></td>
                                <td class="py-3 px-5 text-sm text-gray-700" x-text="item.material?.material_name || '—'"></td>
                                <td class="py-3 px-5 text-sm text-gray-700" x-text="item.grn?.grn_number || '—'"></td>
                                <td class="py-3 px-5 text-sm text-gray-700 text-right" x-text="item.sample_size"></td>
                                <td class="py-3 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(item.status)" x-text="item.status?.replace(/_/g,' ')"></span>
                                </td>
                                <td class="py-3 px-5">
                                    <span x-show="item.usage_decision" class="px-2.5 py-1 rounded-full text-xs font-bold" :class="decisionClass(item.usage_decision?.decision)" x-text="item.usage_decision?.decision || '—'"></span>
                                    <span x-show="!item.usage_decision" class="text-gray-400 text-sm">—</span>
                                </td>
                                <td class="py-3 px-5 text-sm text-gray-700" x-text="(item.assigned_technician?.first_name || '') + ' ' + (item.assigned_technician?.last_name || '') || '—'"></td>
                                <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(item.created_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <!-- By Material Tab -->
    <template x-if="activeTab === 'byMaterial'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Total Lots</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Accepted</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Rejected</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Acceptance %</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Avg Sample Size</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="6" class="py-12 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && materialData.length === 0">
                            <tr><td colspan="6" class="py-12 text-center text-gray-400">No data found</td></tr>
                        </template>
                        <template x-for="item in materialData" :key="item.material_id">
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-5 font-semibold text-gray-900" x-text="item.material_name"></td>
                                <td class="py-3 px-5 text-sm text-gray-700 text-right font-semibold" x-text="item.total_lots"></td>
                                <td class="py-3 px-5 text-sm text-green-700 text-right font-semibold" x-text="item.accepted_count"></td>
                                <td class="py-3 px-5 text-sm text-red-700 text-right font-semibold" x-text="item.rejected_count"></td>
                                <td class="py-3 px-5 text-sm text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="item.acceptance_rate >= 90 ? 'bg-green-100 text-green-700' : item.acceptance_rate >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'" x-text="item.acceptance_rate + '%'"></span>
                                </td>
                                <td class="py-3 px-5 text-sm text-gray-700 text-right" x-text="item.avg_sample_size"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <!-- By Technician Tab -->
    <template x-if="activeTab === 'byTechnician'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Technician</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Inspections</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Completed</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Pending</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Completion %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="5" class="py-12 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && technicianData.length === 0">
                            <tr><td colspan="5" class="py-12 text-center text-gray-400">No data found</td></tr>
                        </template>
                        <template x-for="item in technicianData" :key="item.technician_id">
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-5 font-semibold text-gray-900" x-text="item.technician_name"></td>
                                <td class="py-3 px-5 text-sm text-gray-700 text-right font-semibold" x-text="item.total_inspections"></td>
                                <td class="py-3 px-5 text-sm text-green-700 text-right font-semibold" x-text="item.completed_count"></td>
                                <td class="py-3 px-5 text-sm text-amber-700 text-right font-semibold" x-text="item.pending_count"></td>
                                <td class="py-3 px-5 text-sm text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700" x-text="item.completion_rate + '%'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <!-- Failure Analysis Tab -->
    <template x-if="activeTab === 'failureAnalysis'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Parameter</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Total Tests</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Failed</th>
                            <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Failure %</th>
                            <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Category</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="6" class="py-12 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && failureData.length === 0">
                            <tr><td colspan="6" class="py-12 text-center text-gray-400">No failures found</td></tr>
                        </template>
                        <template x-for="item in failureData" :key="item.parameter_id">
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-5 font-semibold text-gray-900" x-text="item.parameter_name"></td>
                                <td class="py-3 px-5 text-sm text-gray-700" x-text="item.material_name"></td>
                                <td class="py-3 px-5 text-sm text-gray-700 text-right" x-text="item.total_tests"></td>
                                <td class="py-3 px-5 text-sm text-red-700 text-right font-semibold" x-text="item.failed_count"></td>
                                <td class="py-3 px-5 text-sm text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700" x-text="item.failure_rate + '%'"></span>
                                </td>
                                <td class="py-3 px-5 text-sm text-gray-700" x-text="item.parameter_category"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

</div>

<script>
function qualityReports() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        activeTab: 'summary',
        loading: false,
        filters: { dateFrom: '', dateTo: '', material: '' },
        metrics: { totalInspections: 0, completedInspections: 0, acceptanceRate: 0, rejectionRate: 0, acceptedCount: 0, rejectedCount: 0, conditionalCount: 0, avgInspectionTime: 0 },
        summaryData: [],
        materialData: [],
        technicianData: [],
        failureData: [],

        async init() {
            await this.loadReports();
        },

        async loadReports() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/qc', { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    const lots = data.data.data || [];
                    this.processSummaryData(lots);
                    this.processMaterialData(lots);
                    this.processTechnicianData(lots);
                    this.processFailureData(lots);
                    this.computeMetrics(lots);
                }
            } finally { this.loading = false; }
        },

        processSummaryData(lots) {
            this.summaryData = lots.map(l => ({
                id: l.id,
                material: l.material,
                grn: l.grn,
                sample_size: l.sample_size,
                status: l.status,
                usage_decision: l.usage_decision,
                assigned_technician: l.assigned_technician,
                created_at: l.created_at
            }));
        },

        processMaterialData(lots) {
            const grouped = {};
            lots.forEach(lot => {
                const key = lot.material_id;
                if (!grouped[key]) {
                    grouped[key] = {
                        material_id: lot.material_id,
                        material_name: lot.material?.material_name || '—',
                        total_lots: 0,
                        accepted_count: 0,
                        rejected_count: 0,
                        total_sample_size: 0
                    };
                }
                grouped[key].total_lots++;
                if (lot.usage_decision?.decision === 'ACCEPTED') grouped[key].accepted_count++;
                if (lot.usage_decision?.decision === 'REJECTED') grouped[key].rejected_count++;
                grouped[key].total_sample_size += lot.sample_size || 0;
            });

            this.materialData = Object.values(grouped).map(item => ({
                ...item,
                acceptance_rate: item.total_lots > 0 ? Math.round((item.accepted_count / item.total_lots) * 100) : 0,
                avg_sample_size: item.total_lots > 0 ? Math.round(item.total_sample_size / item.total_lots) : 0
            }));
        },

        processTechnicianData(lots) {
            const grouped = {};
            lots.forEach(lot => {
                const key = lot.assigned_to;
                if (!grouped[key]) {
                    grouped[key] = {
                        technician_id: lot.assigned_to,
                        technician_name: (lot.assigned_technician?.first_name || '') + ' ' + (lot.assigned_technician?.last_name || '') || '—',
                        total_inspections: 0,
                        completed_count: 0,
                        pending_count: 0
                    };
                }
                grouped[key].total_inspections++;
                if (lot.status === 'COMPLETED' || lot.status === 'DECISION_MADE') grouped[key].completed_count++;
                else grouped[key].pending_count++;
            });

            this.technicianData = Object.values(grouped).map(item => ({
                ...item,
                completion_rate: item.total_inspections > 0 ? Math.round((item.completed_count / item.total_inspections) * 100) : 0
            }));
        },

        processFailureData(lots) {
            const failures = [];
            lots.forEach(lot => {
                if (lot.test_results) {
                    lot.test_results.forEach(result => {
                        if (result.is_pass === false) {
                            failures.push({
                                parameter_id: result.id,
                                parameter_name: result.parameter_name,
                                material_name: lot.material?.material_name || '—',
                                parameter_category: 'QC'
                            });
                        }
                    });
                }
            });

            const grouped = {};
            failures.forEach(f => {
                const key = f.parameter_id;
                if (!grouped[key]) {
                    grouped[key] = {
                        parameter_id: f.parameter_id,
                        parameter_name: f.parameter_name,
                        material_name: f.material_name,
                        parameter_category: f.parameter_category,
                        total_tests: 0,
                        failed_count: 0
                    };
                }
                grouped[key].failed_count++;
            });

            this.failureData = Object.values(grouped).map(item => ({
                ...item,
                total_tests: item.failed_count,
                failure_rate: 100
            }));
        },

        computeMetrics(lots) {
            const completed = lots.filter(l => l.status === 'COMPLETED' || l.status === 'DECISION_MADE').length;
            const accepted = lots.filter(l => l.usage_decision?.decision === 'ACCEPTED').length;
            const rejected = lots.filter(l => l.usage_decision?.decision === 'REJECTED').length;
            const conditional = lots.filter(l => l.usage_decision?.decision === 'CONDITIONALLY_ACCEPTED').length;
            const decided = accepted + rejected + conditional;

            this.metrics = {
                totalInspections: lots.length,
                completedInspections: completed,
                acceptanceRate: decided > 0 ? Math.round((accepted / decided) * 100) : 0,
                rejectionRate: decided > 0 ? Math.round((rejected / decided) * 100) : 0,
                acceptedCount: accepted,
                rejectedCount: rejected,
                conditionalCount: conditional,
                avgInspectionTime: 2
            };
        },

        resetFilters() { this.filters = { dateFrom: '', dateTo: '', material: '' }; this.loadReports(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        statusClass(s) {
            return { 'PENDING': 'bg-amber-100 text-amber-700', 'IN_PROGRESS': 'bg-blue-100 text-blue-700', 'COMPLETED': 'bg-green-100 text-green-700', 'DECISION_MADE': 'bg-purple-100 text-purple-700' }[s] || 'bg-gray-100 text-gray-600';
        },
        decisionClass(d) {
            return { 'ACCEPTED': 'bg-green-100 text-green-700', 'REJECTED': 'bg-red-100 text-red-700', 'CONDITIONALLY_ACCEPTED': 'bg-amber-100 text-amber-700' }[d] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
