@extends('layouts.quality')

@section('title', 'QC Inspections - ' . $organization->org_name)
@section('page-title', 'QC Inspections')

@section('content')
<div x-data="qcInspections()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lots</h2>
            <p class="text-gray-500 text-sm">Switch between raw material QC and finished goods QC from separate tabs.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-2 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <button @click="setTab('GRN')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'GRN' ? 'bg-sky-50 text-qc ring-1 ring-sky-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Raw Material QC</p>
                    <p class="text-xs text-gray-500">GRN-linked incoming material checks</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="tabCounts.GRN"></span>
            </button>
            <button @click="setTab('PRODUCTION')"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-left transition"
                    :class="activeTab === 'PRODUCTION' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'hover:bg-gray-50 text-gray-700'">
                <div>
                    <p class="font-semibold">Finished Goods QC</p>
                    <p class="text-xs text-gray-500">Production output inspection and release</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold" x-text="tabCounts.PRODUCTION"></span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select x-model="filters.status" @change="applyFilters()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <option value="">All Status</option>
                <option value="PENDING">Pending</option>
                <option value="IN_PROGRESS">In Progress</option>
                <option value="COMPLETED">Completed</option>
                <option value="DECISION_MADE">Decision Made</option>
            </select>
            <input type="text" x-model="filters.item" @input.debounce.300ms="applyFilters()" placeholder="Search item"
                   class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <input type="text" x-model="filters.reference" @input.debounce.300ms="applyFilters()" placeholder="Search GRN / order"
                   class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <button @click="resetFilters()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">
                Reset Filters
            </button>
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
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Sample Size</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && filteredLots.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No inspection batches found.</td></tr>
                    </template>
                    <template x-for="lot in filteredLots" :key="lot.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="lot.lot_number || lot.batch_number || ('IL-' + lot.id)"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="sourceLabel(lot.source_type || 'GRN')"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.product?.product_name || lot.material?.material_name || '-'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="lot.production_order?.order_no || lot.grn?.grn_number || '-'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700 text-right" x-text="lot.sample_size"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="(lot.status || '').replace(/_/g, ' ')"></span>
                            </td>
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
    </div>
</div>

<script>
function qcInspections() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        lots: [],
        filteredLots: [],
        loading: false,
        activeTab: 'GRN',
        tabCounts: { GRN: 0, PRODUCTION: 0 },
        filters: { status: '', item: '', reference: '' },

        async init() {
            const initialTab = new URLSearchParams(window.location.search).get('tab');
            if (initialTab === 'GRN' || initialTab === 'PRODUCTION') {
                this.activeTab = initialTab;
            }
            await this.loadLots();
        },

        async loadLots() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/qc?per_page=200', { headers: headers() });
                const data = await res.json();
                this.lots = data.data?.data || [];
                this.computeTabCounts();
                this.applyFilters();
            } finally {
                this.loading = false;
            }
        },

        setTab(source) {
            this.activeTab = source;
            this.applyFilters();
        },

        computeTabCounts() {
            this.tabCounts = {
                GRN: this.lots.filter(lot => (lot.source_type || 'GRN') === 'GRN').length,
                PRODUCTION: this.lots.filter(lot => (lot.source_type || 'GRN') === 'PRODUCTION').length
            };
        },

        applyFilters() {
            const itemNeedle = this.filters.item.toLowerCase();
            const referenceNeedle = this.filters.reference.toLowerCase();
            this.filteredLots = this.lots.filter(lot => {
                const item = (lot.product?.product_name || lot.material?.material_name || '').toLowerCase();
                const reference = (lot.production_order?.order_no || lot.grn?.grn_number || '').toLowerCase();
                const source = lot.source_type || 'GRN';
                return source === this.activeTab
                    && (!this.filters.status || lot.status === this.filters.status)
                    && (!itemNeedle || item.includes(itemNeedle))
                    && (!referenceNeedle || reference.includes(referenceNeedle));
            });
        },

        resetFilters() {
            this.filters = { status: '', item: '', reference: '' };
            this.applyFilters();
        },

        sourceLabel(source) {
            return source === 'PRODUCTION' ? 'Finished Goods' : 'Raw Material';
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
