@extends('layouts.procurement')

@section('title', 'ASN Tracking - ' . $organization->org_name)
@section('page-title', 'ASN Tracking')

@section('content')
<div x-data="asnData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Advance Shipping Notices</h2>
            <p class="text-gray-600">Track all incoming shipments</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <template x-for="s in statCards" :key="s.label">
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 cursor-pointer hover:border-primary/40 transition-colors"
                 @click="filterByStatus(s.status)"
                 :class="filters.status === s.status ? 'border-primary ring-1 ring-primary/20' : ''">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" :class="s.bg">
                    <span class="material-symbols-outlined text-lg" :class="s.color" x-text="s.icon"></span>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900" x-text="s.count"></p>
                    <p class="text-xs text-gray-500" x-text="s.label"></p>
                </div>
            </div>
        </template>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadASNs()"
                       placeholder="ASN #, Tracking #, Vehicle #..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Status</label>
                <select x-model="filters.status" @change="loadASNs()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="SENT">Sent</option>
                    <option value="IN_TRANSIT">In Transit</option>
                    <option value="ARRIVED">Arrived</option>
                    <option value="RECEIVED">Received</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">PO Number</label>
                <input type="text" x-model="filters.po_search" @input.debounce.300ms="loadASNs()"
                       placeholder="Filter by PO..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">filter_alt_off</span>
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">ASN #</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">PO #</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Ship Date</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">ETA</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Carrier / Tracking</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Items</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="9" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-200 border-t-primary"></div>
                                    <span class="text-sm">Loading ASNs...</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && asns.length === 0">
                        <tr>
                            <td colspan="9" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <span class="material-symbols-outlined text-5xl text-gray-300">local_shipping</span>
                                    <p class="text-sm font-medium text-gray-500">No ASNs found</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="asn in asns" :key="asn.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-semibold text-primary cursor-pointer hover:underline"
                                      @click="viewASN(asn)" x-text="asn.asn_number"></span>
                            </td>
                            <td class="py-3.5 px-5 text-gray-700"
                                x-text="asn.purchase_order ? asn.purchase_order.po_number : '—'"></td>
                            <td class="py-3.5 px-5 text-gray-700"
                                x-text="asn.vendor ? asn.vendor.vendor_name : '—'"></td>
                            <td class="py-3.5 px-5 text-gray-500" x-text="formatDate(asn.ship_date)"></td>
                            <td class="py-3.5 px-5">
                                <span x-text="formatDate(asn.eta)"
                                      :class="isOverdue(asn) ? 'text-red-600 font-semibold' : 'text-gray-500'"></span>
                                <span x-show="isOverdue(asn)" class="ml-1 text-xs text-red-500">(Overdue)</span>
                            </td>
                            <td class="py-3.5 px-5 text-gray-600">
                                <span x-text="asn.carrier_name || '—'"></span>
                                <template x-if="asn.tracking_number">
                                    <span class="block text-xs text-gray-400" x-text="asn.tracking_number"></span>
                                </template>
                            </td>
                            <td class="py-3.5 px-5 text-gray-700"
                                x-text="asn.line_items ? asn.line_items.length : '—'"></td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="getStatusClass(asn.status)" x-text="asn.status"></span>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <button @click="viewASN(asn)"
                                        class="p-1.5 text-gray-500 hover:text-primary hover:bg-blue-50 rounded-lg transition-colors"
                                        title="View">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> ASNs
            </div>
            <div class="flex gap-2">
                <button @click="prevPage()" :disabled="pagination.current_page === 1"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                    Previous
                </button>
                <button @click="nextPage()" :disabled="pagination.current_page === pagination.last_page"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- ASN Detail Drawer -->
    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display:none;">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-40" @click="showDetail = false"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-2xl bg-white shadow-xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="selectedASN ? selectedASN.asn_number : ''"></h3>
                    <p class="text-sm text-gray-500" x-text="selectedASN && selectedASN.purchase_order ? 'PO: ' + selectedASN.purchase_order.po_number : ''"></p>
                </div>
                <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5" x-show="selectedASN">
                <!-- Status + Info -->
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Status</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold mt-1"
                              :class="getStatusClass(selectedASN ? selectedASN.status : '')"
                              x-text="selectedASN ? selectedASN.status : ''"></span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Vendor</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN && selectedASN.vendor ? selectedASN.vendor.vendor_name : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Ship Date</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN ? formatDate(selectedASN.ship_date) : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">ETA</p>
                        <p class="font-semibold" :class="selectedASN && isOverdue(selectedASN) ? 'text-red-600' : 'text-gray-900'"
                           x-text="selectedASN ? formatDate(selectedASN.eta) : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Carrier</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN ? (selectedASN.carrier_name || '—') : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Tracking #</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN ? (selectedASN.tracking_number || '—') : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Vehicle #</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN ? (selectedASN.vehicle_number || '—') : '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Warehouse</p>
                        <p class="font-semibold text-gray-900" x-text="selectedASN && selectedASN.warehouse ? selectedASN.warehouse.warehouse_name : '—'"></p>
                    </div>
                </div>

                <template x-if="selectedASN && selectedASN.remarks">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        <strong>Remarks:</strong> <span x-text="selectedASN.remarks"></span>
                    </div>
                </template>

                <!-- Line Items -->
                <div>
                    <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Line Items</h4>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Material</th>
                                    <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Shipped Qty</th>
                                    <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Batch</th>
                                    <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, i) in (selectedASN ? selectedASN.line_items || [] : [])" :key="i">
                                    <tr>
                                        <td class="py-2.5 px-4 text-gray-900"
                                            x-text="item.material ? item.material.material_name : '—'"></td>
                                        <td class="py-2.5 px-4 text-gray-700"
                                            x-text="item.shipped_qty + ' ' + (item.uom ? item.uom.uom_code : '')"></td>
                                        <td class="py-2.5 px-4 text-gray-500" x-text="item.batch_number || '—'"></td>
                                        <td class="py-2.5 px-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600"
                                                  x-text="item.line_status"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function asnData() {
    return {
        asns: [],
        loading: false,
        filters: { search: '', status: '', po_search: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        showDetail: false,
        selectedASN: null,
        statCards: [
            { label: 'Draft',      status: 'DRAFT',      count: 0, icon: 'draft',          bg: 'bg-gray-100',   color: 'text-gray-600' },
            { label: 'Sent',       status: 'SENT',       count: 0, icon: 'send',            bg: 'bg-blue-100',   color: 'text-blue-600' },
            { label: 'In Transit', status: 'IN_TRANSIT', count: 0, icon: 'local_shipping',  bg: 'bg-orange-100', color: 'text-orange-600' },
            { label: 'Arrived',    status: 'ARRIVED',    count: 0, icon: 'where_to_vote',   bg: 'bg-green-100',  color: 'text-green-600' },
            { label: 'Received',   status: 'RECEIVED',   count: 0, icon: 'inventory_2',     bg: 'bg-purple-100', color: 'text-purple-600' },
        ],

        async init() {
            await this.loadASNs();
            await this.loadStats();
        },

        getToken() { return localStorage.getItem('auth_token') || localStorage.getItem('access_token'); },

        async loadASNs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page, per_page: 15 });
                if (this.filters.search)    params.append('search', this.filters.search);
                if (this.filters.status)    params.append('status', this.filters.status);

                const res = await fetch(`/api/v1/asn?${params}`, {
                    headers: { 'Authorization': `Bearer ${this.getToken()}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.data) {
                    let items = data.data.asns || [];
                    // Client-side PO filter if needed
                    if (this.filters.po_search) {
                        const q = this.filters.po_search.toLowerCase();
                        items = items.filter(a => a.purchase_order && a.purchase_order.po_number.toLowerCase().includes(q));
                    }
                    this.asns = items;
                    const p = data.data.pagination;
                    this.pagination = {
                        current_page: p.current_page,
                        last_page: p.last_page,
                        from: p.total > 0 ? ((p.current_page - 1) * p.per_page) + 1 : 0,
                        to: Math.min(p.current_page * p.per_page, p.total),
                        total: p.total
                    };
                } else {
                    this.asns = [];
                }
            } catch (e) {
                console.error(e);
                this.asns = [];
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            const statuses = ['DRAFT', 'SENT', 'IN_TRANSIT', 'ARRIVED', 'RECEIVED'];
            await Promise.all(statuses.map(async (s) => {
                try {
                    const res = await fetch(`/api/v1/asn?status=${s}&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${this.getToken()}`, 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    const card = this.statCards.find(c => c.status === s);
                    if (card && data.success) card.count = data.data.pagination?.total || 0;
                } catch (e) {}
            }));
        },

        filterByStatus(status) {
            this.filters.status = this.filters.status === status ? '' : status;
            this.loadASNs();
        },

        async viewASN(asn) {
            this.selectedASN = asn;
            this.showDetail = true;
            try {
                const res = await fetch(`/api/v1/asn/${asn.id}`, {
                    headers: { 'Authorization': `Bearer ${this.getToken()}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) this.selectedASN = data.data.asn;
            } catch (e) {}
        },

        resetFilters() {
            this.filters = { search: '', status: '', po_search: '' };
            this.loadASNs();
        },

        prevPage() { if (this.pagination.current_page > 1) this.loadASNs(this.pagination.current_page - 1); },
        nextPage() { if (this.pagination.current_page < this.pagination.last_page) this.loadASNs(this.pagination.current_page + 1); },

        isOverdue(asn) {
            return asn.eta && new Date(asn.eta) < new Date() && !asn.actual_arrival && ['SENT', 'IN_TRANSIT'].includes(asn.status);
        },

        formatDate(d) {
            if (!d) return '—';
            return new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        getStatusClass(status) {
            const map = {
                'DRAFT':      'bg-gray-100 text-gray-600',
                'SENT':       'bg-blue-100 text-blue-700',
                'IN_TRANSIT': 'bg-orange-100 text-orange-700',
                'ARRIVED':    'bg-green-100 text-green-700',
                'RECEIVED':   'bg-purple-100 text-purple-700',
                'CANCELLED':  'bg-red-100 text-red-600',
            };
            return map[status] || 'bg-gray-100 text-gray-600';
        }
    };
}
</script>
@endsection
