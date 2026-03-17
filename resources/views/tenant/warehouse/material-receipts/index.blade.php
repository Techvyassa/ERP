@extends('layouts.warehouse')

@section('title', 'Material Receipts - ' . $organization->org_name)
@section('page-title', 'Material Receipts')

@section('content')
<div x-data="mrData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Material Receipts</h2>
            <p class="text-gray-500 text-sm">Unloading, quantity verification and staging</p>
        </div>
        <button @click="openCreateModal()"
            class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add</span> New Receipt
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">In Progress</p>
            <p class="text-3xl font-bold text-amber-500" x-text="counts.in_progress">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Completed</p>
            <p class="text-3xl font-bold text-green-600" x-text="counts.completed">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Pending GRN</p>
            <p class="text-3xl font-bold text-blue-600" x-text="counts.pending_grn">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">GRN Posted</p>
            <p class="text-3xl font-bold text-purple-600" x-text="counts.grn_posted">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select x-model="filters.status" @change="loadReceipts()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="PENDING_GRN">Pending GRN</option>
                    <option value="GRN_POSTED">GRN Posted</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                <input type="date" x-model="filters.from_date" @change="loadReceipts()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
                <input type="date" x-model="filters.to_date" @change="loadReceipts()"
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
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">MR Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Gate Entry</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Unloading Start</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Unloading End</th>
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
                    <template x-if="!loading && receipts.length === 0">
                        <tr><td colspan="8" class="py-12 text-center text-gray-400">No material receipts found</td></tr>
                    </template>
                    <template x-for="mr in receipts" :key="mr.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="mr.mr_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="mr.gate_entry?.ge_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="mr.vendor?.vendor_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="mr.purchase_order?.po_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDateTime(mr.unloading_start_time)"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDateTime(mr.unloading_end_time)"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="statusClass(mr.status)" x-text="mr.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-right flex items-center justify-end gap-2">
                                <button @click="viewReceipt(mr)" title="View" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button x-show="mr.status === 'IN_PROGRESS'" @click="startUnloading(mr.id)" title="Start Unloading"
                                    class="text-amber-600 hover:text-amber-800">
                                    <span class="material-symbols-outlined text-lg">play_circle</span>
                                </button>
                                <button x-show="mr.status === 'IN_PROGRESS'" @click="completeUnloading(mr.id)" title="Complete Unloading"
                                    class="text-green-600 hover:text-green-800">
                                    <span class="material-symbols-outlined text-lg">check_circle</span>
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


    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">New Material Receipt</h3>
                    <button @click="showCreateModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="saveReceipt()" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Gate Entry *</label>
                            <select x-model="form.ge_id" @change="onGESelect()" required
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select Gate Entry</option>
                                <template x-for="ge in gateEntries" :key="ge.id">
                                    <option :value="ge.id" x-text="ge.ge_number + ' — ' + (ge.vendor?.vendor_name ?? '')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Purchase Order</label>
                            <input type="text" :value="selectedGE?.purchase_order?.po_number || '—'" readonly
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor</label>
                            <input type="text" :value="selectedGE?.vendor?.vendor_name || '—'" readonly
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Dock / Bay</label>
                            <input type="text" x-model="form.dock_number" placeholder="e.g. Dock-3"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-800">Received Items</h4>
                            <button type="button" @click="addLine()"
                                class="text-xs px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition">+ Add Item</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(line, i) in form.line_items" :key="i">
                                <div class="grid grid-cols-12 gap-2 items-start bg-gray-50 p-3 rounded-lg">
                                    <div class="col-span-4">
                                        <select x-model="line.material_id" required
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                            <option value="">Material</option>
                                            <template x-for="m in materials" :key="m.id">
                                                <option :value="m.id" x-text="m.material_code + ' — ' + m.material_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" x-model="line.received_qty" placeholder="Rcvd Qty" required min="0" step="0.001"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" x-model="line.rejected_qty" placeholder="Rej Qty" min="0" step="0.001"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" x-model="line.batch_number" placeholder="Batch / Lot No."
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs focus:ring-1 focus:ring-primary/20">
                                    </div>
                                    <div class="col-span-1 flex justify-end pt-1">
                                        <button type="button" @click="form.line_items.splice(i,1)" class="text-red-500 hover:text-red-700">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Rejected Qty = items returned immediately due to visible damage</p>
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
                            <span x-show="!saving">Create MR</span><span x-show="saving">Saving...</span>
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
                    <h3 class="text-lg font-bold text-gray-900">MR Detail — <span x-text="selectedMR?.mr_number"></span></h3>
                    <button @click="showViewModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <div class="p-6 space-y-4 text-sm" x-show="selectedMR">
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-gray-500">Status</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="statusClass(selectedMR?.status)" x-text="selectedMR?.status?.replace(/_/g,' ')"></span>
                        </div>
                        <div><p class="text-xs text-gray-500">Gate Entry</p><p class="font-medium" x-text="selectedMR?.gate_entry?.ge_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Vendor</p><p class="font-medium" x-text="selectedMR?.vendor?.vendor_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">PO Number</p><p class="font-medium" x-text="selectedMR?.purchase_order?.po_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Unloading Start</p><p class="font-medium" x-text="formatDateTime(selectedMR?.unloading_start_time)"></p></div>
                        <div><p class="text-xs text-gray-500">Unloading End</p><p class="font-medium" x-text="formatDateTime(selectedMR?.unloading_end_time)"></p></div>
                    </div>
                    <div x-show="selectedMR?.line_items?.length">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Line Items</p>
                        <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Material</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-600">Rcvd Qty</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-600">Rej Qty</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Batch</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-600">Bin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="line in selectedMR?.line_items" :key="line.id">
                                    <tr>
                                        <td class="px-3 py-2" x-text="line.material?.material_name || line.material_id"></td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="line.received_qty"></td>
                                        <td class="px-3 py-2 text-right text-red-600" x-text="line.rejected_qty || 0"></td>
                                        <td class="px-3 py-2" x-text="line.batch_number || '—'"></td>
                                        <td class="px-3 py-2" x-text="line.provisional_bin?.bin_code || '—'"></td>
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
function mrData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        receipts: [], gateEntries: [], materials: [],
        loading: false, saving: false,
        showCreateModal: false, showViewModal: false,
        selectedMR: null, selectedGE: null,
        counts: { in_progress: 0, completed: 0, pending_grn: 0, grn_posted: 0 },
        filters: { status: '', from_date: '', to_date: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        form: { ge_id: '', dock_number: '', remarks: '', line_items: [] },

        async init() {
            await Promise.all([this.loadReceipts(), this.loadGateEntries(), this.loadMaterials()]);
        },

        async loadReceipts(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 20 });
                if (this.filters.status) p.append('status', this.filters.status);
                if (this.filters.from_date) p.append('from_date', this.filters.from_date);
                if (this.filters.to_date) p.append('to_date', this.filters.to_date);
                const res = await fetch(`/api/v1/material-receipts?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.receipts = data.data.data || [];
                    this.pagination = { current_page: data.data.current_page, last_page: data.data.last_page, from: data.data.from || 0, to: data.data.to || 0, total: data.data.total || 0 };
                    this.computeCounts();
                }
            } finally { this.loading = false; }
        },

        computeCounts() {
            this.counts = { in_progress: 0, completed: 0, pending_grn: 0, grn_posted: 0 };
            this.receipts.forEach(r => {
                if (r.status === 'IN_PROGRESS') this.counts.in_progress++;
                else if (r.status === 'COMPLETED') this.counts.completed++;
                else if (r.status === 'PENDING_GRN') this.counts.pending_grn++;
                else if (r.status === 'GRN_POSTED') this.counts.grn_posted++;
            });
        },

        async loadGateEntries() {
            const res = await fetch('/api/v1/gate-entries?status=MOVED_TO_DOCK&per_page=200', { headers: headers() });
            const data = await res.json();
            this.gateEntries = data.success ? (data.data?.data ?? []) : [];
        },

        async loadMaterials() {
            const res = await fetch('/api/v1/materials?per_page=200&is_active=true', { headers: headers() });
            const data = await res.json();
            this.materials = data.success ? (data.data?.materials ?? []) : [];
        },

        openCreateModal() {
            this.form = { ge_id: '', dock_number: '', remarks: '', line_items: [{ material_id: '', received_qty: '', rejected_qty: 0, batch_number: '' }] };
            this.selectedGE = null;
            this.showCreateModal = true;
        },

        onGESelect() {
            this.selectedGE = this.gateEntries.find(g => g.id == this.form.ge_id) || null;
            if (this.selectedGE) this.form.po_id = this.selectedGE.po_id;
        },

        addLine() { this.form.line_items.push({ material_id: '', received_qty: '', rejected_qty: 0, batch_number: '' }); },

        async saveReceipt() {
            this.saving = true;
            try {
                const res = await fetch('/api/v1/material-receipts', { method: 'POST', headers: headers(), body: JSON.stringify(this.form) });
                const data = await res.json();
                if (data.success) { this.showCreateModal = false; await this.loadReceipts(); }
                else alert(data.message || 'Failed to create MR');
            } finally { this.saving = false; }
        },

        async startUnloading(id) {
            const res = await fetch(`/api/v1/material-receipts/${id}/start-unloading`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadReceipts();
            else alert(data.message || 'Failed to start unloading');
        },

        async completeUnloading(id) {
            if (!confirm('Mark unloading as complete?')) return;
            const res = await fetch(`/api/v1/material-receipts/${id}/complete`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadReceipts();
            else alert(data.message || 'Failed to complete unloading');
        },

        async viewReceipt(mr) {
            const res = await fetch(`/api/v1/material-receipts/${mr.id}`, { headers: headers() });
            const data = await res.json();
            this.selectedMR = data.success ? data.data : mr;
            this.showViewModal = true;
        },

        changePage(p) { if (p >= 1 && p <= this.pagination.last_page) this.loadReceipts(p); },
        resetFilters() { this.filters = { status: '', from_date: '', to_date: '' }; this.loadReceipts(); },
        formatDateTime(v) { return v ? new Date(v).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'; },
        statusClass(s) {
            return { 'IN_PROGRESS': 'bg-amber-100 text-amber-700', 'COMPLETED': 'bg-green-100 text-green-700', 'PENDING_GRN': 'bg-blue-100 text-blue-700', 'GRN_POSTED': 'bg-purple-100 text-purple-700' }[s] ?? 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
