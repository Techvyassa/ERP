@extends('layouts.quality')

@section('title', 'QC Inspections - ' . $organization->org_name)
@section('page-title', 'QC Inspections')

@section('content')
<div x-data="qcInspections()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lots</h2>
            <p class="text-gray-500 text-sm">Record test results and manage QC inspections</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select x-model="filters.status" @change="loadLots()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="PENDING">Pending</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="DECISION_MADE">Decision Made</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Material</label>
                <input type="text" x-model="filters.material" @change="loadLots()" placeholder="Search material..."
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">GRN Number</label>
                <input type="text" x-model="filters.grn" @change="loadLots()" placeholder="Search GRN..."
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
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN Number</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Created</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && lots.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No inspection lots found</td></tr>
                    </template>
                    <template x-for="lot in lots" :key="lot.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'LOT-' + lot.id"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.material?.material_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.grn?.grn_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700 text-right" x-text="lot.sample_size"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="lot.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(lot.created_at)"></td>
                            <td class="py-3 px-5 text-right">
                                <a :href="'/org/{{ $organization->org_slug }}/quality/inspections/' + lot.id" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-3 flex items-center justify-between text-sm text-gray-600">
            <span>Showing <span x-text="pagination.from"></span>–<span x-text="pagination.to"></span> of <span x-text="pagination.total"></span></span>
            <div class="flex gap-2">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">Prev</button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">Next</button>
            </div>
        </div>
    </div>

</div>

<script>
function qcInspections() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        lots: [],
        loading: false,
        filters: { status: '', material: '', grn: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },

        async init() {
            await this.loadLots();
        },

        async loadLots(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 15 });
                if (this.filters.status) p.append('status', this.filters.status);
                const res = await fetch(`/api/v1/qc?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lots = data.data.data || [];
                    this.pagination = { current_page: data.data.current_page, last_page: data.data.last_page, from: data.data.from || 0, to: data.data.to || 0, total: data.data.total || 0 };
                }
            } finally { this.loading = false; }
        },

        changePage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadLots(p); },
        resetFilters() { this.filters = { status: '', material: '', grn: '' }; this.loadLots(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        statusClass(s) {
            return { 'PENDING': 'bg-amber-100 text-amber-700', 'IN_PROGRESS': 'bg-blue-100 text-blue-700', 'COMPLETED': 'bg-green-100 text-green-700', 'DECISION_MADE': 'bg-purple-100 text-purple-700' }[s] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
