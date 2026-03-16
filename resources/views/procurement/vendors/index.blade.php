@extends('layouts.procurement')

@section('title', 'Vendors - ' . $organization->org_name)
@section('page-title', 'Vendors')

@section('content')
<div x-data="procurementVendorsData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Vendors</h2>
            <p class="text-gray-600">Vendors and their purchase order activity</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.400ms="loadVendors()"
                       placeholder="Name, Code, GSTIN..."
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                <select x-model="filters.vendor_type" @change="loadVendors()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Types</option>
                    <option value="SUPPLIER">Supplier</option>
                    <option value="SERVICE">Service Provider</option>
                    <option value="TRADER">Trader</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Approval Status</label>
                <select x-model="filters.is_approved" @change="loadVendors()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="1">Approved</option>
                    <option value="0">Pending</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()" class="w-full px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Type</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">GSTIN</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Payment Terms</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Total POs</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">PO Value</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Last PO</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-4 px-6 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="9" class="py-12 text-center">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && vendors.length === 0">
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-500">
                                No vendors found
                            </td>
                        </tr>
                    </template>

                    <template x-for="vendor in vendors" :key="vendor.id">
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="openVendorDetail(vendor)">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-900" x-text="vendor.vendor_name"></div>
                                <div class="text-xs text-gray-500" x-text="vendor.vendor_code"></div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-blue-100 text-blue-700': vendor.vendor_type === 'SUPPLIER',
                                          'bg-green-100 text-green-700': vendor.vendor_type === 'SERVICE',
                                          'bg-yellow-100 text-yellow-700': vendor.vendor_type === 'TRADER',
                                          'bg-gray-100 text-gray-700': !vendor.vendor_type
                                      }"
                                      x-text="vendor.vendor_type || 'N/A'"></span>
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-600" x-text="vendor.gstin || '—'"></td>
                            <td class="py-4 px-6 text-sm text-gray-600" x-text="vendor.payment_terms || '—'"></td>
                            <td class="py-4 px-6">
                                <span class="font-semibold text-gray-900" x-text="vendor.po_summary ? vendor.po_summary.total_pos : '—'"></span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-gray-900"
                                x-text="vendor.po_summary ? formatCurrency(vendor.po_summary.total_value) : '—'"></td>
                            <td class="py-4 px-6 text-sm text-gray-600"
                                x-text="vendor.po_summary && vendor.po_summary.last_po_date ? formatDate(vendor.po_summary.last_po_date) : '—'"></td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="vendor.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                      x-text="vendor.is_approved ? 'Approved' : 'Pending'"></span>
                            </td>
                            <td class="py-4 px-6 text-right" @click.stop>
                                <button @click="openVendorDetail(vendor)"
                                        class="text-primary hover:text-primary/80 mr-3" title="View POs">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <a href="{{ url('/org/' . $organization->org_slug . '/procurement/purchase-orders') }}?vendor_id="
                                   :href="'{{ url('/org/' . $organization->org_slug . '/procurement/purchase-orders') }}?vendor_id=' + vendor.id"
                                   class="text-gray-600 hover:text-gray-800" title="View all POs for this vendor">
                                    <span class="material-symbols-outlined text-lg">open_in_new</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> vendors
            </div>
            <div class="flex gap-2">
                <button @click="previousPage()" :disabled="pagination.current_page === 1"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button @click="nextPage()" :disabled="pagination.current_page === pagination.last_page"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Vendor Detail / PO Drawer -->
    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display:none;">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-40" @click="closeDetail()"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-2xl bg-white shadow-xl flex flex-col">

            <!-- Drawer Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="selectedVendor ? selectedVendor.vendor_name : ''"></h3>
                    <p class="text-sm text-gray-500" x-text="selectedVendor ? selectedVendor.vendor_code : ''"></p>
                </div>
                <button @click="closeDetail()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Vendor Info -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">GSTIN</span>
                        <p class="font-medium text-gray-900" x-text="selectedVendor ? (selectedVendor.gstin || '—') : ''"></p>
                    </div>
                    <div>
                        <span class="text-gray-500">Payment Terms</span>
                        <p class="font-medium text-gray-900" x-text="selectedVendor ? (selectedVendor.payment_terms || '—') : ''"></p>
                    </div>
                    <div>
                        <span class="text-gray-500">Credit Days</span>
                        <p class="font-medium text-gray-900" x-text="selectedVendor ? (selectedVendor.credit_days ?? '—') : ''"></p>
                    </div>
                    <div>
                        <span class="text-gray-500">Rating</span>
                        <p class="font-medium text-gray-900" x-text="selectedVendor && selectedVendor.rating_score ? selectedVendor.rating_score + '/100' : '—'"></p>
                    </div>
                </div>
            </div>

            <!-- PO Summary Cards -->
            <div class="px-6 py-4 border-b border-gray-100" x-show="selectedVendor && selectedVendor.po_summary">
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-700" x-text="selectedVendor && selectedVendor.po_summary ? selectedVendor.po_summary.total_pos : 0"></p>
                        <p class="text-xs text-blue-600 mt-1">Total POs</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-green-700" x-text="selectedVendor && selectedVendor.po_summary ? formatCurrency(selectedVendor.po_summary.total_value) : '₹0'"></p>
                        <p class="text-xs text-green-600 mt-1">Total Value</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-purple-700" x-text="selectedVendor && selectedVendor.po_summary ? (selectedVendor.po_summary.open_pos || 0) : 0"></p>
                        <p class="text-xs text-purple-600 mt-1">Open POs</p>
                    </div>
                </div>
            </div>

            <!-- PO List -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Purchase Orders</h4>

                <template x-if="loadingPOs">
                    <div class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    </div>
                </template>

                <template x-if="!loadingPOs && vendorPOs.length === 0">
                    <div class="text-center py-8 text-gray-500 text-sm">
                        No purchase orders found for this vendor
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="po in vendorPOs" :key="po.id">
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary/40 transition-colors">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="font-semibold text-primary text-sm" x-text="po.po_number"></span>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="'Date: ' + formatDate(po.po_date)"></p>
                                    <p class="text-xs text-gray-500" x-text="po.expected_delivery ? 'Expected: ' + formatDate(po.expected_delivery) : ''"></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900 text-sm" x-text="formatCurrency(po.grand_total || 0)"></p>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold mt-1 inline-block"
                                          :class="getStatusClass(po.status)" x-text="po.status"></span>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500" x-show="po.line_items_count">
                                <span x-text="po.line_items_count + ' line item(s)'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Drawer Footer -->
            <div class="px-6 py-4 border-t border-gray-200">
                <a :href="'{{ url('/org/' . $organization->org_slug . '/procurement/purchase-orders') }}?vendor_id=' + (selectedVendor ? selectedVendor.id : '')"
                   class="w-full block text-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-semibold">
                    View All POs for this Vendor
                </a>
            </div>
        </div>
    </div>

</div>

<script>
function procurementVendorsData() {
    return {
        vendors: [],
        loading: false,
        filters: { search: '', vendor_type: '', is_approved: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },

        showDetail: false,
        selectedVendor: null,
        vendorPOs: [],
        loadingPOs: false,

        async init() {
            await this.loadVendors();
        },

        async loadVendors(page = 1) {
            this.loading = true;
            try {
                const token = localStorage.getItem('access_token');
                const params = new URLSearchParams({ page, per_page: 15, blacklisted: '0' });
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.vendor_type) params.append('vendor_type', this.filters.vendor_type);
                if (this.filters.is_approved !== '') params.append('is_approved', this.filters.is_approved);

                const response = await fetch(`/api/v1/vendors?${params}`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (data.success && data.data && data.data.vendors) {
                    // Enrich vendors with PO summary
                    this.vendors = await this.enrichWithPOSummary(data.data.vendors, token);
                    const p = data.data.pagination;
                    this.pagination = {
                        current_page: p.current_page,
                        last_page: p.last_page,
                        from: p.total > 0 ? ((p.current_page - 1) * p.per_page) + 1 : 0,
                        to: Math.min(p.current_page * p.per_page, p.total),
                        total: p.total
                    };
                } else {
                    this.vendors = [];
                }
            } catch (e) {
                console.error('Error loading vendors:', e);
                this.vendors = [];
            } finally {
                this.loading = false;
            }
        },

        async enrichWithPOSummary(vendors, token) {
            // Fetch PO summary for each vendor in parallel
            const enriched = await Promise.all(vendors.map(async (vendor) => {
                try {
                    const res = await fetch(`/api/v1/purchase-orders?vendor_id=${vendor.id}&per_page=100`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    const pos = data.success && data.data && data.data.data ? data.data.data : [];

                    const totalValue = pos.reduce((sum, po) => sum + parseFloat(po.grand_total || 0), 0);
                    const openPOs = pos.filter(po => ['OPEN', 'PARTIAL', 'PENDING_APPROVAL', 'APPROVED'].includes(po.status)).length;
                    const dates = pos.map(po => po.po_date).filter(Boolean).sort().reverse();

                    vendor.po_summary = {
                        total_pos: pos.length,
                        total_value: totalValue,
                        open_pos: openPOs,
                        last_po_date: dates[0] || null
                    };
                } catch (e) {
                    vendor.po_summary = { total_pos: 0, total_value: 0, open_pos: 0, last_po_date: null };
                }
                return vendor;
            }));
            return enriched;
        },

        async openVendorDetail(vendor) {
            this.selectedVendor = vendor;
            this.showDetail = true;
            this.vendorPOs = [];
            this.loadingPOs = true;

            try {
                const token = localStorage.getItem('access_token');
                const res = await fetch(`/api/v1/purchase-orders?vendor_id=${vendor.id}&per_page=50`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.vendorPOs = data.success && data.data && data.data.data ? data.data.data : [];
            } catch (e) {
                console.error('Error loading vendor POs:', e);
                this.vendorPOs = [];
            } finally {
                this.loadingPOs = false;
            }
        },

        closeDetail() {
            this.showDetail = false;
            this.selectedVendor = null;
            this.vendorPOs = [];
        },

        resetFilters() {
            this.filters = { search: '', vendor_type: '', is_approved: '' };
            this.loadVendors();
        },

        previousPage() {
            if (this.pagination.current_page > 1) this.loadVendors(this.pagination.current_page - 1);
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) this.loadVendors(this.pagination.current_page + 1);
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(amount || 0);
        },

        getStatusClass(status) {
            const map = {
                'DRAFT': 'bg-gray-100 text-gray-700',
                'PENDING_APPROVAL': 'bg-yellow-100 text-yellow-700',
                'APPROVED': 'bg-blue-100 text-blue-700',
                'OPEN': 'bg-green-100 text-green-700',
                'PARTIAL': 'bg-orange-100 text-orange-700',
                'CLOSED': 'bg-gray-200 text-gray-600',
                'CANCELLED': 'bg-red-100 text-red-700',
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        }
    };
}
</script>
@endsection
