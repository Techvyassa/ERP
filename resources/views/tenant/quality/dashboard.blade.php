@extends('layouts.quality')

@section('title', 'Quality Control Dashboard - ' . $organization->org_name)
@section('page-title', 'Quality Control Dashboard')

@section('content')
<div x-data="qualityDashboard()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Quality Control</h2>
            <p class="text-gray-500 text-sm">Manage inspections, test results, and usage decisions</p>
        </div>
    </div>

    <!-- Stats Cards -->
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

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('tenant.quality.inspections', $organization->org_slug) }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600">assignment</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Inspections</p>
                    <p class="text-sm text-gray-500">View & manage QC lots</p>
                </div>
            </div>
        </a>

        <a href="{{ route('tenant.quality.decisions', $organization->org_slug) }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Decisions</p>
                    <p class="text-sm text-gray-500">Usage decisions & approvals</p>
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
                    <p class="text-sm text-gray-500">Quality metrics & trends</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Inspections -->
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
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" x-show="recentLots.length > 0">
                    <template x-for="lot in recentLots" :key="lot.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + lot.id"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.material?.material_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.grn?.grn_number || '—'"></td>
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
                <tbody x-show="recentLots.length === 0">
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
        stats: { pending: 0, inProgress: 0, completed: 0, decisions: 0 },
        recentLots: [],

        async init() {
            await this.loadStats();
            await this.loadRecentLots();
        },

        async loadStats() {
            try {
                const [pending, inProgress, completed] = await Promise.all([
                    fetch('/api/v1/qc/pending', { headers: headers() }).then(r => r.json()),
                    fetch('/api/v1/qc/in-progress', { headers: headers() }).then(r => r.json()),
                    fetch('/api/v1/qc/completed', { headers: headers() }).then(r => r.json()),
                ]);

                this.stats.pending = pending.data?.length || 0;
                this.stats.inProgress = inProgress.data?.length || 0;
                this.stats.completed = completed.data?.length || 0;
                this.stats.decisions = this.stats.completed; // Placeholder
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        },

        async loadRecentLots() {
            try {
                const res = await fetch('/api/v1/qc?per_page=5', { headers: headers() });
                const data = await res.json();
                this.recentLots = data.data?.data || [];
            } catch (e) {
                console.error('Failed to load recent lots:', e);
            }
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
