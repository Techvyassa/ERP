@extends('layouts.security')

@section('title', 'Gate Entry - ' . $organization->org_name)
@section('page-title', 'Gate Entry')

@section('content')
<div x-data="gateEntryData()" x-init="init()">

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gate Entry</h2>
        <p class="text-gray-500 text-sm">Track vehicle arrivals and document verification status</p>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Pending Verification</p>
            <p class="text-3xl font-bold text-amber-500" x-text="counts.pending">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Verified</p>
            <p class="text-3xl font-bold text-green-600" x-text="counts.verified">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Moved to Dock</p>
            <p class="text-3xl font-bold text-blue-600" x-text="counts.docked">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Rejected</p>
            <p class="text-3xl font-bold text-red-500" x-text="counts.rejected">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select x-model="filters.status" @change="loadEntries()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="PENDING_VERIFICATION">Pending Verification</option>
                    <option value="VERIFIED">Verified</option>
                    <option value="MOVED_TO_DOCK">Moved to Dock</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                <input type="date" x-model="filters.from_date" @change="loadEntries()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
                <input type="date" x-model="filters.to_date" @change="loadEntries()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GE Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Vehicle No.</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Arrived At</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Driver</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="8" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && entries.length === 0">
                        <tr><td colspan="8" class="py-12 text-center text-gray-400">No gate entries found</td></tr>
                    </template>
                    <template x-for="entry in entries" :key="entry.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="entry.ge_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-900" x-text="entry.vehicle_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="entry.vendor ? entry.vendor.vendor_name : '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="entry.purchase_order ? entry.purchase_order.po_number : '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDateTime(entry.arrived_at)"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="entry.driver_name || '—'"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="statusClass(entry.status)" x-text="entry.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <button @click="viewEntry(entry)" title="View"
                                    class="text-primary hover:text-primary/70 mr-2">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button x-show="entry.status === 'PENDING_VERIFICATION'"
                                    @click="openVerifyModal(entry)" title="Verify"
                                    class="text-green-600 hover:text-green-800 mr-2">
                                    <span class="material-symbols-outlined text-lg">fact_check</span>
                                </button>
                                <button x-show="entry.status === 'VERIFIED'"
                                    @click="moveToDock(entry.id)" title="Move to Dock"
                                    class="text-blue-600 hover:text-blue-800">
                                    <span class="material-symbols-outlined text-lg">forklift</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
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

    <!-- Verify Modal -->
    <div x-show="showVerifyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showVerifyModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Verify Gate Entry</h3>
                    <button @click="showVerifyModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitVerification()" class="p-6 space-y-4">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        Verifying: <strong x-text="selectedEntry?.ge_number"></strong> —
                        <span x-text="selectedEntry?.vendor?.vendor_name"></span>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Decision *</label>
                        <select x-model="verifyForm.decision" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select Decision</option>
                            <option value="VERIFIED">Verified — Allow Entry</option>
                            <option value="REJECTED">Rejected — Turn Away</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Seal Number</label>
                        <input type="text" x-model="verifyForm.seal_number"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks</label>
                        <textarea x-model="verifyForm.remarks" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Required if rejecting"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showVerifyModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
                            <span x-show="!saving">Submit Verification</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Detail Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showViewModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Gate Entry Detail</h3>
                    <button @click="showViewModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <div class="p-6 space-y-3 text-sm" x-show="selectedEntry">
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-gray-500">GE Number</p><p class="font-semibold text-primary" x-text="selectedEntry?.ge_number"></p></div>
                        <div><p class="text-xs text-gray-500">Status</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="statusClass(selectedEntry?.status)" x-text="selectedEntry?.status?.replace(/_/g,' ')"></span>
                        </div>
                        <div><p class="text-xs text-gray-500">Vehicle</p><p class="font-medium" x-text="selectedEntry?.vehicle_number"></p></div>
                        <div><p class="text-xs text-gray-500">Driver</p><p class="font-medium" x-text="selectedEntry?.driver_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Vendor</p><p class="font-medium" x-text="selectedEntry?.vendor?.vendor_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">PO Number</p><p class="font-medium" x-text="selectedEntry?.purchase_order?.po_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Delivery Challan</p><p class="font-medium" x-text="selectedEntry?.delivery_challan_no || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Invoice No.</p><p class="font-medium" x-text="selectedEntry?.invoice_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Arrived At</p><p class="font-medium" x-text="formatDateTime(selectedEntry?.arrived_at)"></p></div>
                    </div>
                    <div x-show="selectedEntry?.remarks"><p class="text-xs text-gray-500">Remarks</p><p class="font-medium" x-text="selectedEntry?.remarks"></p></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function gateEntryData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        entries: [],
        loading: false, saving: false,
        showVerifyModal: false, showViewModal: false,
        selectedEntry: null,
        counts: { pending: 0, verified: 0, docked: 0, rejected: 0 },
        filters: { status: '', from_date: '', to_date: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        verifyForm: { decision: '', seal_number: '', remarks: '' },

        async init() {
            await this.loadEntries();
        },

        async loadEntries(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 20 });
                if (this.filters.status) p.append('status', this.filters.status);
                if (this.filters.from_date) p.append('from_date', this.filters.from_date);
                if (this.filters.to_date) p.append('to_date', this.filters.to_date);
                const res = await fetch(`/api/v1/gate-entries?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.entries = data.data.data || [];
                    this.pagination = { current_page: data.data.current_page, last_page: data.data.last_page, from: data.data.from || 0, to: data.data.to || 0, total: data.data.total || 0 };
                    this.computeCounts();
                }
            } finally { this.loading = false; }
        },

        computeCounts() {
            this.counts = { pending: 0, verified: 0, docked: 0, rejected: 0 };
            this.entries.forEach(e => {
                if (e.status === 'PENDING_VERIFICATION') this.counts.pending++;
                else if (e.status === 'VERIFIED') this.counts.verified++;
                else if (e.status === 'MOVED_TO_DOCK') this.counts.docked++;
                else if (e.status === 'REJECTED') this.counts.rejected++;
            });
        },

        openVerifyModal(entry) { this.selectedEntry = entry; this.verifyForm = { decision: '', seal_number: '', remarks: '' }; this.showVerifyModal = true; },
        viewEntry(entry) { this.selectedEntry = entry; this.showViewModal = true; },

        async submitVerification() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/gate-entries/${this.selectedEntry.id}/verify`, { method: 'POST', headers: headers(), body: JSON.stringify(this.verifyForm) });
                const data = await res.json();
                if (data.success) { this.showVerifyModal = false; await this.loadEntries(); }
                else alert(data.message || 'Verification failed');
            } finally { this.saving = false; }
        },

        async moveToDock(id) {
            if (!confirm('Move this entry to dock?')) return;
            const res = await fetch(`/api/v1/gate-entries/${id}/move-to-dock`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadEntries();
            else alert(data.message || 'Failed to move to dock');
        },

        changePage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadEntries(p); },
        resetFilters() { this.filters = { status: '', from_date: '', to_date: '' }; this.loadEntries(); },
        formatDateTime(v) { return v ? new Date(v).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'; },
        statusClass(s) {
            return { 'PENDING_VERIFICATION': 'bg-amber-100 text-amber-700', 'VERIFIED': 'bg-green-100 text-green-700', 'MOVED_TO_DOCK': 'bg-blue-100 text-blue-700', 'REJECTED': 'bg-red-100 text-red-700' }[s] ?? 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
