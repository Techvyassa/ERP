@extends('layouts.quality')

@section('title', 'Quality Control Dashboard - ' . $organization->org_name)
@section('page-title', 'Quality Control Dashboard')

@section('content')
<div x-data="qualityDashboard()" x-init="init()">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Quality Control</h2>
            <p class="text-gray-500 text-sm">Manage raw material QC and finished goods QC from separate tabs.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-2 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <button @click="setTab('GRN')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'GRN' ? 'bg-sky-50 text-qc ring-1 ring-sky-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Raw Material QC</p>
                    <p class="text-xs text-gray-500">Incoming material inspection and usage decisions</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="sourceStats.GRN.total"></span>
            </button>
            <button @click="setTab('PRODUCTION')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'PRODUCTION' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Finished Goods QC</p>
                    <p class="text-xs text-gray-500">Production output inspection and release decisions</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="sourceStats.PRODUCTION.total"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Pending Inspections</p>
            <p class="text-3xl font-bold text-amber-500" x-text="stats.pending">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">In Progress</p>
            <p class="text-3xl font-bold text-blue-600" x-text="stats.inProgress">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Completed</p>
            <p class="text-3xl font-bold text-green-600" x-text="stats.completed">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Decisions Made</p>
            <p class="text-3xl font-bold text-purple-600" x-text="stats.decisions">0</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a :href="inspectionUrl()" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600">assignment</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900" x-text="activeTab === 'PRODUCTION' ? 'FG Inspections' : 'RM Inspections'"></p>
                    <p class="text-sm text-gray-500">Open the selected quality-check tab</p>
                </div>
            </div>
        </a>

        <a :href="decisionUrl()" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900" x-text="activeTab === 'PRODUCTION' ? 'FG Decisions' : 'RM Decisions'"></p>
                    <p class="text-sm text-gray-500">Review decisions for the selected tab</p>
                </div>
            </div>
        </a>

        <a href="{{ route('tenant.quality.reports', $organization->org_slug) }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-purple-600">bar_chart</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Reports</p>
                    <p class="text-sm text-gray-500">Quality metrics and trends</p>
                </div>
            </div>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900" x-text="activeTab === 'PRODUCTION' ? 'Recent Finished Goods Inspections' : 'Recent Raw Material Inspections'"></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Item</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Reference</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" x-show="filteredRecentLots.length > 0">
                    <template x-for="lot in filteredRecentLots" :key="lot.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + lot.id"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.product?.product_name || lot.material?.material_name || '-'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.production_order?.order_no || lot.grn?.grn_number || '-'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.sample_size"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="lot.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <a :href="'/org/{{ $organization->org_slug }}/quality/inspections/' + lot.id" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tbody x-show="filteredRecentLots.length === 0">
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">No recent inspections</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function qualityDashboard() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        activeTab: 'GRN',
        stats: { pending: 0, inProgress: 0, completed: 0, decisions: 0 },
        recentLots: [],
        sourceStats: {
            GRN: { pending: 0, inProgress: 0, completed: 0, decisions: 0, total: 0 },
            PRODUCTION: { pending: 0, inProgress: 0, completed: 0, decisions: 0, total: 0 }
        },

        get filteredRecentLots() {
            return this.recentLots.filter(lot => (lot.source_type || 'GRN') === this.activeTab);
        },

        async init() {
            const initialTab = new URLSearchParams(window.location.search).get('tab');
            if (initialTab === 'GRN' || initialTab === 'PRODUCTION') {
                this.activeTab = initialTab;
            }
            await this.loadStats();
            await this.loadRecentLots();
        },

        setTab(source) {
            this.activeTab = source;
            this.applyActiveStats();
        },

        async loadStats() {
            try {
                const [pending, inProgress, completed, allLots] = await Promise.all([
                    fetch('/api/v1/qc/pending?per_page=200', { headers: headers() }).then(r => r.json()),
                    fetch('/api/v1/qc/in-progress?per_page=200', { headers: headers() }).then(r => r.json()),
                    fetch('/api/v1/qc/completed?per_page=200', { headers: headers() }).then(r => r.json()),
                    fetch('/api/v1/qc?per_page=200', { headers: headers() }).then(r => r.json()),
                ]);

                const pendingLots = pending.data || [];
                const inProgressLots = inProgress.data || [];
                const completedLots = completed.data || [];
                const lots = allLots.data?.data || [];

                this.sourceStats = {
                    GRN: this.buildSourceStats('GRN', pendingLots, inProgressLots, completedLots, lots),
                    PRODUCTION: this.buildSourceStats('PRODUCTION', pendingLots, inProgressLots, completedLots, lots)
                };

                this.applyActiveStats();
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        },

        async loadRecentLots() {
            try {
                const res = await fetch('/api/v1/qc?per_page=25', { headers: headers() });
                const data = await res.json();
                this.recentLots = data.data?.data || [];
            } catch (e) {
                console.error('Failed to load recent lots:', e);
            }
        },

        buildSourceStats(source, pendingLots, inProgressLots, completedLots, lots) {
            const matchesSource = lot => (lot.source_type || 'GRN') === source;
            return {
                pending: pendingLots.filter(matchesSource).length,
                inProgress: inProgressLots.filter(matchesSource).length,
                completed: completedLots.filter(matchesSource).length,
                decisions: lots.filter(lot => matchesSource(lot) && lot.usage_decision).length,
                total: lots.filter(matchesSource).length
            };
        },

        applyActiveStats() {
            const activeStats = this.sourceStats[this.activeTab];
            this.stats = {
                pending: activeStats.pending,
                inProgress: activeStats.inProgress,
                completed: activeStats.completed,
                decisions: activeStats.decisions
            };
        },

        inspectionUrl() {
            return `{{ route('tenant.quality.inspections', $organization->org_slug) }}?tab=${this.activeTab}`;
        },

        decisionUrl() {
            return `{{ route('tenant.quality.decisions', $organization->org_slug) }}?tab=${this.activeTab}`;
        },

        statusClass(status) {
            return {
                'PENDING': 'bg-amber-100 text-amber-700',
                'IN_PROGRESS': 'bg-blue-100 text-blue-700',
                'COMPLETED': 'bg-green-100 text-green-700',
                'DECISION_MADE': 'bg-purple-100 text-purple-700',
            }[status] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
