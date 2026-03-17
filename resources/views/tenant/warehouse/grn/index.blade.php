@extends('layouts.warehouse')

@section('title', 'GRN - ' . $organization->org_name)
@section('page-title', 'Goods Receipt Note')

@section('content')
<div x-data="grnData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Goods Receipt Note (GRN)</h2>
            <p class="text-gray-500 text-sm">Official inventory acceptance — triggers accounting entry and QC inspection</p>
        </div>
        <button @click="openCreateModal()"
            class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add</span> Create GRN
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Provisional</p>
            <p class="text-3xl font-bold text-amber-500" x-text="counts.provisional">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">QC Pending</p>
            <p class="text-3xl font-bold text-blue-600" x-text="counts.qc_pending">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Accepted</p>
            <p class="text-3xl font-bold text-green-600" x-text="counts.accepted">0</p>
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
                <select x-model="filters.status" @change="loadGRNs()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="PROVISIONAL">Provisional</option>
                    <option value="QC_PENDING">QC Pending</option>
                    <option value="ACCEPTED">Accepted</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="PARTIALLY_ACCEPTED">Partially Accepted</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">GRN Date From</label>
                <input type="date" x-model="filters.grn_date_from" @change="loadGRNs()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">GRN Date To</label>
                <input type="date" x-model="filters.grn_date_to" @change="loadGRNs()"
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
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN Date</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">MR Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Posting Date</th>
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
                    <template x-if="!loading && grns.length === 0">
                        <tr><td colspan="8" class="py-12 text-center text-gray-400">No GRNs found</td></tr>
                    </template>
                    <template x-for="grn in grns" :key="grn.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="grn.grn_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="formatDate(grn.grn_date)"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="grn.vendor?.vendor_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="grn.purchase_order?.po_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="grn.material_receipt?.mr_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDate(grn.posting_date)"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="statusClass(grn.status)" x-text="grn.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-right flex items-center justify-end gap-2">
                                <button @click="viewGRN(grn.id)" title="View" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button x-show="grn.status === 'PROVISIONAL'" @click="approveGRN(grn.id)" title="Approve → QC Pending"
                                    class="text-green-600 hover:text-green-800">
                                    <span class="material-symbols-outlined text-lg">check_circle</span>
                                </button>
                                <button x-show="['PROVISIONAL','QC_PENDING'].includes(grn.status)" @click="openCancelModal(grn)" title="Cancel"
                                    class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined text-lg">cancel</span>
                                </button>
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


    <!-- Create GRN Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Create GRN</h3>
                    <button @click="showCreateModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="saveGRN()" class="p-6 space-y-4">
                    <!-- 3-way match info banner -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                        <strong>3-Way Match:</strong> PO (what we ordered) → MR (what we received) → GRN (what we accept into books)
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Material Receipt *</label>
                            <select x-model="form.mr_id" @change="onMRSelect()" required
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select MR</option>
                                <template x-for="mr in pendingMRs" :key="mr.id">
                                    <option :value="mr.id" x-text="mr.mr_number + ' — ' + (mr.vendor?.vendor_name ?? '')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">GRN Date *</label>
                            <input type="date" x-model="form.grn_date" required
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Posting Date *</label>
                            <input type="date" x-model="form.posting_date" required
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor</label>
                            <input type="text" :value="selectedMR?.vendor?.vendor_name || '—'" readonly
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">PO Number</label>
                            <input type="text" :value="selectedMR?.purchase_order?.po_number || '—'" readonly
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50">
                        </div>
                    </div>

                    <!-- Line Items from MR -->
                    <div x-show="form.line_items.length > 0">
                        <p class="text-sm font-bold text-gray-800 mb-2">Accepted Quantities</p>
                        <p class="text-xs text-gray-400 mb-3">ERP auto-checks over/under delivery tolerance. Adjust accepted qty if needed.</p>
                        <div class="space-y-2">
                            <template x-for="(line, i) in form.line_items" :key="i">
                                <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 p-3 rounded-lg text-xs">
                                    <div class="col-span-4 font-medium text-gray-800" x-text="line.material_name"></div>
                                    <div class="col-span-2 text-gray-500">Rcvd: <span class="font-semibold text-gray-800" x-text="line.received_qty"></span></div>
                                    <div class="col-span-2">
                                        <input type="number" x-model="line.accepted_qty" placeholder="Accepted" required min="0" step="0.001"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" x-model="line.batch_number" placeholder="Batch"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" x-model="line.warehouse_bin" placeholder="Bin"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks</label>
                        <textarea x-model="form.remarks" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving">Create GRN</span><span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCancelModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Cancel GRN</h3>
                    <button @click="showCancelModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitCancel()" class="p-6 space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                        Cancelling GRN: <strong x-text="selectedGRN?.grn_number"></strong>. This action cannot be undone.
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Reason *</label>
                        <textarea x-model="cancelReason" required rows="3"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Provide a reason for cancellation"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showCancelModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Back</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 disabled:opacity-50">
                            <span x-show="!saving">Confirm Cancel</span><span x-show="saving">Processing...</span>
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
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">GRN — <span x-text="selectedGRN?.grn_number"></span></h3>
                    <button @click="showViewModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <div class="p-6 space-y-4 text-sm" x-show="selectedGRN">
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-gray-500">Status</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="statusClass(selectedGRN?.status)" x-text="selectedGRN?.status?.replace(/_/g,' ')"></span>
                        </div>
                        <div><p class="text-xs text-gray-500">GRN Date</p><p class="font-medium" x-text="formatDate(selectedGRN?.grn_date)"></p></div>
                        <div><p class="text-xs text-gray-500">Posting Date</p><p class="font-medium" x-text="formatDate(selectedGRN?.posting_date)"></p></div>
                        <div><p class="text-xs text-gray-500">Vendor</p><p class="font-medium" x-text="selectedGRN?.vendor?.vendor_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">PO Number</p><p class="font-medium" x-text="selectedGRN?.purchase_order?.po_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">MR Number</p><p class="font-medium" x-text="selectedGRN?.material_receipt?.mr_number || '—'"></p></div>
                    </div>
                    <div x-show="selectedGRN?.line_items?.length">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Line Items</p>
                        <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Material</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-600">Accepted Qty</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">UOM</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Batch</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Bin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="line in selectedGRN?.line_items" :key="line.id">
                                    <tr>
                                        <td class="px-3 py-2" x-text="line.material?.material_name || '—'"></td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="line.accepted_qty"></td>
                                        <td class="px-3 py-2" x-text="line.uom?.uom_code || '—'"></td>
                                        <td class="px-3 py-2" x-text="line.batch_number || '—'"></td>
                                        <td class="px-3 py-2" x-text="line.warehouse_bin?.bin_code || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="selectedGRN?.remarks"><p class="text-xs text-gray-500">Remarks</p><p x-text="selectedGRN?.remarks"></p></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function grnData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        grns: [], pendingMRs: [],
        loading: false, saving: false,
        showCreateModal: false, showViewModal: false, showCancelModal: false,
        selectedGRN: null, selectedMR: null,
        cancelReason: '',
        counts: { provisional: 0, qc_pending: 0, accepted: 0, rejected: 0 },
        filters: { status: '', grn_date_from: '', grn_date_to: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        form: { mr_id: '', grn_date: '', posting_date: '', remarks: '', line_items: [] },

        async init() {
            await Promise.all([this.loadGRNs(), this.loadPendingMRs()]);
        },

        async loadGRNs(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 15 });
                if (this.filters.status) p.append('status', this.filters.status);
                if (this.filters.grn_date_from) p.append('grn_date_from', this.filters.grn_date_from);
                if (this.filters.grn_date_to) p.append('grn_date_to', this.filters.grn_date_to);
                const res = await fetch(`/api/v1/grn?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.grns = data.data.data || [];
                    this.pagination = { current_page: data.data.current_page, last_page: data.data.last_page, from: data.data.from || 0, to: data.data.to || 0, total: data.data.total || 0 };
                    this.computeCounts();
                }
            } finally { this.loading = false; }
        },

        computeCounts() {
            this.counts = { provisional: 0, qc_pending: 0, accepted: 0, rejected: 0 };
            this.grns.forEach(g => {
                if (g.status === 'PROVISIONAL') this.counts.provisional++;
                else if (g.status === 'QC_PENDING') this.counts.qc_pending++;
                else if (g.status === 'ACCEPTED') this.counts.accepted++;
                else if (g.status === 'REJECTED') this.counts.rejected++;
            });
        },

        async loadPendingMRs() {
            const res = await fetch('/api/v1/material-receipts/pending-grn', { headers: headers() });
            const data = await res.json();
            this.pendingMRs = data.success ? (data.data?.data ?? data.data ?? []) : [];
        },

        openCreateModal() {
            const today = new Date().toISOString().split('T')[0];
            this.form = { mr_id: '', grn_date: today, posting_date: today, remarks: '', line_items: [] };
            this.selectedMR = null;
            this.showCreateModal = true;
        },

        async onMRSelect() {
            const mr = this.pendingMRs.find(m => m.id == this.form.mr_id);
            if (!mr) return;
            // Fetch full MR with line items
            const res = await fetch(`/api/v1/material-receipts/${mr.id}`, { headers: headers() });
            const data = await res.json();
            this.selectedMR = data.success ? data.data : mr;
            this.form.line_items = (this.selectedMR.line_items || []).map(l => ({
                mr_line_item_id: l.id,
                material_id: l.material_id,
                material_name: l.material?.material_name || l.material_id,
                received_qty: l.received_qty,
                accepted_qty: l.received_qty,
                batch_number: l.batch_number || '',
                warehouse_bin: '',
            }));
        },

        async saveGRN() {
            this.saving = true;
            try {
                const res = await fetch('/api/v1/grn', { method: 'POST', headers: headers(), body: JSON.stringify(this.form) });
                const data = await res.json();
                if (data.success) { this.showCreateModal = false; await this.loadGRNs(); await this.loadPendingMRs(); }
                else alert(data.message || 'Failed to create GRN');
            } finally { this.saving = false; }
        },

        async viewGRN(id) {
            const res = await fetch(`/api/v1/grn/${id}`, { headers: headers() });
            const data = await res.json();
            this.selectedGRN = data.success ? data.data : null;
            this.showViewModal = true;
        },

        async approveGRN(id) {
            if (!confirm('Approve this GRN? Status will move to QC Pending and a QC inspection lot will be triggered.')) return;
            const res = await fetch(`/api/v1/grn/${id}/approve`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadGRNs();
            else alert(data.message || 'Approval failed');
        },

        openCancelModal(grn) { this.selectedGRN = grn; this.cancelReason = ''; this.showCancelModal = true; },

        async submitCancel() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/grn/${this.selectedGRN.id}/cancel`, { method: 'PATCH', headers: headers(), body: JSON.stringify({ reason: this.cancelReason }) });
                const data = await res.json();
                if (data.success) { this.showCancelModal = false; await this.loadGRNs(); }
                else alert(data.message || 'Cancellation failed');
            } finally { this.saving = false; }
        },

        changePage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadGRNs(p); },
        resetFilters() { this.filters = { status: '', grn_date_from: '', grn_date_to: '' }; this.loadGRNs(); },
        formatDate(v) { return v ? new Date(v).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'; },
        statusClass(s) {
            return { 'PROVISIONAL': 'bg-amber-100 text-amber-700', 'QC_PENDING': 'bg-blue-100 text-blue-700', 'ACCEPTED': 'bg-green-100 text-green-700', 'REJECTED': 'bg-red-100 text-red-700', 'PARTIALLY_ACCEPTED': 'bg-orange-100 text-orange-700', 'CANCELLED': 'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
