@extends('layouts.security')

@section('title', 'Security Dashboard - ' . $organization->org_name)
@section('page-title', 'Security Portal')

@section('content')
<div x-data="securityDashboard()" x-init="init()">
    <!-- Department Header -->
    <div class="bg-gradient-to-r from-indigo-800 to-indigo-900 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-indigo-500 p-4 rounded-xl">
                <span class="material-symbols-outlined text-white text-4xl">security</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Security Portal</h2>
                <p class="text-white/90">{{ $organization->org_name }}</p>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">pending</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingGateEntries">0</h3>
            <p class="text-sm text-gray-600">Pending Gate Entries</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">fact_check</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Today</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.completedToday">0</h3>
            <p class="text-sm text-gray-600">GRN Created Today</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.vehiclesOnPremise">0</h3>
            <p class="text-sm text-gray-600">Vehicles on Premise</p>
        </div>
    </div>

    <!-- Recent Gate Entries -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Gate Entries</h3>
            <a href="{{ url("/org/{$organization->org_slug}/security/gate-entry") }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All →</a>
        </div>
        <div class="space-y-3">
            <template x-for="entry in recentEntries" :key="entry.id">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-100 p-2 rounded-lg">
                            <span class="material-symbols-outlined text-indigo-600 text-xl">directions_car</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900" x-text="entry.vehicle_number"></p>
                            <p class="text-xs text-gray-500" x-text="entry.vendor_name + ' • ' + entry.ge_number + (entry.grn_number ? ' • GRN: ' + entry.grn_number : '')"></p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold"
                          :class="statusClass(entry.status)" x-text="entry.status?.replace(/_/g,' ')"></span>
                </div>
            </template>
            <template x-if="recentEntries.length === 0">
                <p class="text-center text-gray-400 py-6 text-sm">No recent gate entries</p>
            </template>
        </div>
    </div>
</div>

<script>
function securityDashboard() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        stats: { pendingGateEntries: 0, completedToday: 0, vehiclesOnPremise: 0 },
        recentEntries: [],

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const res = await fetch('/api/v1/gate-entries?per_page=5', { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    const entries = data.data.data || [];
                    this.recentEntries = entries.map(e => ({
                        id: e.id,
                        ge_number: e.ge_number,
                        vehicle_number: e.vehicle_number,
                        vendor_name: e.vendor?.vendor_name || '—',
                        grn_number: e.grn?.grn_number || null,
                        status: e.status
                    }));
                    const today = new Date().toDateString();
                    this.stats.pendingGateEntries = entries.filter(e => e.status === 'PENDING').length;
                    this.stats.completedToday = entries.filter(e => e.status === 'COMPLETED' && new Date(e.updated_at).toDateString() === today).length;
                    this.stats.vehiclesOnPremise = entries.filter(e => e.status === 'PENDING').length;
                }
            } catch (e) { console.error(e); }
        },

        statusClass(s) {
            return {
                'PENDING': 'bg-amber-100 text-amber-700',
                'COMPLETED': 'bg-green-100 text-green-700',
            }[s] ?? 'bg-gray-100 text-gray-600';
        }
    };
}
</script>
@endsection
