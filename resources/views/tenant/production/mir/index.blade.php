@extends('layouts.production')

@section('title', 'Material Issue Requests')
@section('page-title', 'Material Issue Requests')

@section('content')
<div x-data="mirList('{{ $organization->org_slug }}')" x-init="init()">

    <!-- Reject Modal -->
    <div x-show="rejectModal.show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none;">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="rejectModal.show=false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md z-10" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Reject MIR</h3>
                <button @click="rejectModal.show=false" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-600">Provide a reason for rejection. Production team will be notified.</p>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea x-model="rejectModal.reason" rows="3"
                              placeholder="e.g. Insufficient stock in bin A-01-03, material not available..."
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-400 focus:border-transparent resize-none text-sm"></textarea>
                </div>
                <div x-show="rejectModal.error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="rejectModal.error"></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button @click="rejectModal.show=false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">Cancel</button>
                <button @click="submitReject()"
                        :disabled="rejectModal.submitting"
                        class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm" :class="rejectModal.submitting?'animate-spin':''" x-text="rejectModal.submitting?'progress_activity':'cancel'"></span>
                    <span x-text="rejectModal.submitting?'Rejecting...':'Reject MIR'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Scan Modal -->
    <div x-show="scanModal.show" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4" style="display:none;">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="closeScan()"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg z-10" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Scan & Issue Material</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="scanModal.line?.material_name + ' — Required: ' + scanModal.line?.required_qty + ' ' + (scanModal.line?.uom||'')"></p>
                </div>
                <button @click="closeScan()" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700 flex items-start gap-2">
                    <span class="material-symbols-outlined text-base mt-0.5">info</span>
                    <span>Scan or manually enter the <strong>bin barcode</strong> and <strong>material barcode</strong>. Stock will be deducted only after both are validated.</span>
                </div>

                <!-- Bin Barcode -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle text-orange-500">shelves</span>
                        Bin Barcode <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="scanModal.binBarcode"
                           x-ref="binInput"
                           @keydown.enter="$refs.materialInput.focus()"
                           placeholder="Scan or type bin code (e.g. A-01-03)"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent font-mono text-sm">
                </div>

                <!-- Material Barcode -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle text-orange-500">qr_code_scanner</span>
                        Material Barcode <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="scanModal.materialBarcode"
                           x-ref="materialInput"
                           @keydown.enter="submitScan()"
                           placeholder="Scan or type material code / barcode"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent font-mono text-sm">
                </div>

                <div x-show="scanModal.error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 flex items-start gap-2">
                    <span class="material-symbols-outlined text-base mt-0.5">error</span>
                    <span x-text="scanModal.error"></span>
                </div>
                <div x-show="scanModal.success" class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span x-text="scanModal.success"></span>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button @click="closeScan()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">Close</button>
                <button @click="submitScan()"
                        :disabled="scanModal.submitting || !scanModal.binBarcode || !scanModal.materialBarcode"
                        :class="(!scanModal.submitting && scanModal.binBarcode && scanModal.materialBarcode) ? 'hover:bg-orange-600' : 'opacity-50 cursor-not-allowed'"
                        class="px-5 py-2 bg-orange-500 text-white font-semibold rounded-lg transition-colors text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm" :class="scanModal.submitting?'animate-spin':''" x-text="scanModal.submitting?'progress_activity':'check_circle'"></span>
                    <span x-text="scanModal.submitting?'Validating...':'Validate & Issue'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MIR Detail / Review Modal -->
    <div x-show="viewModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="viewModal.show=false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl z-10" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="'MIR — ' + (viewModal.data?.mir_no||'')"></h3>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="(viewModal.data?.product_name||'') + ' · Order: ' + (viewModal.data?.order_no||'')"></p>
                    </div>
                    <button @click="viewModal.show=false" class="text-gray-400 hover:text-gray-600"><span class="material-symbols-outlined">close</span></button>
                </div>

                <div class="px-6 py-5 max-h-[70vh] overflow-y-auto space-y-5">
                    <div x-show="viewModal.loading" class="text-center py-8 text-gray-400">
                        <span class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>Loading...
                    </div>

                    <template x-if="!viewModal.loading && viewModal.data">
                        <div class="space-y-5">
                            <!-- Status + Info -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Status</p>
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                          :class="{'bg-yellow-100 text-yellow-800':viewModal.data.status==='PENDING','bg-green-100 text-green-800':viewModal.data.status==='APPROVED','bg-red-100 text-red-800':viewModal.data.status==='REJECTED'}"
                                          x-text="viewModal.data.status"></span>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Target Qty</p>
                                    <p class="font-bold text-gray-900" x-text="(viewModal.data.target_qty||'') + ' ' + (viewModal.data.uom||'')"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 col-span-2" x-show="viewModal.data.rejection_reason">
                                    <p class="text-xs text-gray-500 mb-1">Rejection Reason</p>
                                    <p class="text-sm text-red-700 font-medium" x-text="viewModal.data.rejection_reason"></p>
                                </div>
                            </div>

                            <!-- RM Lines with scan status -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-orange-500 text-base">inventory_2</span>
                                    Raw Materials
                                </h4>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Req. Qty</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">UOM</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Bin</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Scan</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase" x-show="viewModal.data.status==='APPROVED'">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="line in (viewModal.data.lines||[])" :key="line.id">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2">
                                                        <p class="font-medium text-gray-900" x-text="line.material_name"></p>
                                                        <p class="text-xs text-gray-400" x-text="line.material_code"></p>
                                                    </td>
                                                    <td class="px-4 py-2 text-right font-bold text-orange-600" x-text="line.required_qty"></td>
                                                    <td class="px-4 py-2 text-gray-500" x-text="line.uom"></td>
                                                    <td class="px-4 py-2 text-gray-500 font-mono text-xs" x-text="line.bin_barcode || '—'"></td>
                                                    <td class="px-4 py-2">
                                                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                                              :class="{'bg-gray-100 text-gray-600':line.scan_status==='PENDING','bg-blue-100 text-blue-700':line.scan_status==='SCANNED','bg-green-100 text-green-800':line.scan_status==='ISSUED'}"
                                                              x-text="line.scan_status"></span>
                                                    </td>
                                                    <td class="px-4 py-2 text-right" x-show="viewModal.data.status==='APPROVED'">
                                                        <button x-show="line.scan_status !== 'ISSUED'"
                                                                @click="openScan(viewModal.data, line)"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-50 text-orange-700 hover:bg-orange-100 rounded text-xs transition-colors font-semibold">
                                                            <span class="material-symbols-outlined text-sm">qr_code_scanner</span> Scan
                                                        </button>
                                                        <span x-show="line.scan_status === 'ISSUED'" class="text-xs text-green-600 font-semibold flex items-center gap-1 justify-end">
                                                            <span class="material-symbols-outlined text-sm">check_circle</span> Issued
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer actions -->
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-2" x-show="viewModal.data?.status === 'PENDING'">
                        <button @click="openReject(viewModal.data)"
                                class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors text-sm">
                            <span class="material-symbols-outlined text-sm">cancel</span> Reject
                        </button>
                        <button @click="submitApprove(viewModal.data)"
                                :disabled="approving"
                                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors text-sm">
                            <span class="material-symbols-outlined text-sm" :class="approving?'animate-spin':''" x-text="approving?'progress_activity':'check_circle'"></span>
                            <span x-text="approving?'Approving...':'Approve'"></span>
                        </button>
                    </div>
                    <div x-show="viewModal.data?.status !== 'PENDING'" class="text-xs text-gray-400"></div>
                    <button @click="viewModal.show=false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Material Issue Requests</h2>
            <p class="text-sm text-gray-500 mt-1">Store team reviews, approves/rejects, and issues RM via bin + material scan.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.400ms="loadMIRs()"
                   placeholder="Search MIR no, product, order..."
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
            <select x-model="filters.status" @change="loadMIRs()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
                <option value="">All Status</option>
                <option value="PENDING">Pending</option>
                <option value="APPROVED">Approved</option>
                <option value="REJECTED">Rejected</option>
            </select>
            <button @click="filters.search=''; filters.status=''; loadMIRs()"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm transition-colors">Reset</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MIR No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lines</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-2">progress_activity</span>Loading...
                        </td></tr>
                    </template>
                    <template x-if="!loading && mirs.length === 0">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-5xl block mx-auto mb-2">assignment</span>
                            No Material Issue Requests found.
                        </td></tr>
                    </template>
                    <template x-for="mir in mirs" :key="mir.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-sm font-semibold text-gray-900" x-text="mir.mir_no"></td>
                            <td class="px-5 py-3 text-sm text-gray-600" x-text="mir.order_no"></td>
                            <td class="px-5 py-3 text-sm text-gray-700">
                                <div x-text="mir.product_name"></div>
                                <div class="text-xs text-gray-400" x-text="mir.product_code"></div>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">
                                <span x-text="(mir.lines||[]).filter(l=>l.scan_status==='ISSUED').length + '/' + (mir.lines||[]).length + ' issued'"></span>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500" x-text="mir.created_at"></td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                      :class="{'bg-yellow-100 text-yellow-800':mir.status==='PENDING','bg-green-100 text-green-800':mir.status==='APPROVED','bg-red-100 text-red-800':mir.status==='REJECTED'}"
                                      x-text="mir.status"></span>
                            </td>
                            <td class="px-5 py-3 text-right flex items-center justify-end gap-2">
                                <!-- Quick approve/reject for PENDING -->
                                <template x-if="mir.status === 'PENDING'">
                                    <div class="flex gap-1">
                                        <button @click="openReject(mir)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded text-xs transition-colors font-semibold">
                                            <span class="material-symbols-outlined text-sm">cancel</span> Reject
                                        </button>
                                        <button @click="submitApprove(mir)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-green-50 text-green-700 hover:bg-green-100 rounded text-xs transition-colors font-semibold">
                                            <span class="material-symbols-outlined text-sm">check_circle</span> Approve
                                        </button>
                                    </div>
                                </template>
                                <button @click="openView(mir)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded text-xs transition-colors">
                                    <span class="material-symbols-outlined text-sm">visibility</span> View
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function mirList(orgSlug) {
    return {
        orgSlug,
        loading: false,
        approving: false,
        mirs: [],
        filters: { search: '', status: '' },

        viewModal:   { show: false, loading: false, data: null },
        rejectModal: { show: false, mir: null, reason: '', error: '', submitting: false },
        scanModal:   { show: false, mir: null, line: null, binBarcode: '', materialBarcode: '', error: '', success: '', submitting: false },

        async init() { await this.loadMIRs(); },

        async loadMIRs() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                const data = await this._fetch(`/api/v1/material-issue-requests?${params}`).then(r => r.json());
                this.mirs = data?.data?.mirs || [];
            } catch(e) { this.mirs = []; }
            finally { this.loading = false; }
        },

        async openView(mir) {
            this.viewModal = { show: true, loading: true, data: null };
            try {
                const data = await this._fetch(`/api/v1/material-issue-requests/${mir.id}`).then(r => r.json());
                this.viewModal.data = data?.data?.mir || mir;
            } catch(e) { this.viewModal.data = mir; }
            finally { this.viewModal.loading = false; }
        },

        // ── Approve ─────────────────────────────────────────────────────
        async submitApprove(mir) {
            this.approving = true;
            try {
                const res = await this._fetch(`/api/v1/material-issue-requests/${mir.id}/approve`, { method: 'POST' });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed');
                await this.loadMIRs();
                // Refresh view modal if open
                if (this.viewModal.show && this.viewModal.data?.id === mir.id) {
                    await this.openView(mir);
                }
            } catch(e) { alert(e.message); }
            finally { this.approving = false; }
        },

        // ── Reject ──────────────────────────────────────────────────────
        openReject(mir) {
            this.rejectModal = { show: true, mir, reason: '', error: '', submitting: false };
        },

        async submitReject() {
            if (!this.rejectModal.reason.trim()) {
                this.rejectModal.error = 'Rejection reason is required.';
                return;
            }
            this.rejectModal.submitting = true;
            this.rejectModal.error = '';
            try {
                const res = await this._fetch(`/api/v1/material-issue-requests/${this.rejectModal.mir.id}/reject`, {
                    method: 'POST',
                    body: JSON.stringify({ reason: this.rejectModal.reason })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed');
                this.rejectModal.show = false;
                await this.loadMIRs();
                if (this.viewModal.show && this.viewModal.data?.id === this.rejectModal.mir.id) {
                    await this.openView(this.rejectModal.mir);
                }
            } catch(e) {
                this.rejectModal.error = e.message;
            } finally { this.rejectModal.submitting = false; }
        },

        // ── Scan ────────────────────────────────────────────────────────
        openScan(mir, line) {
            this.viewModal.show = false;
            this.scanModal = { show: true, mir, line, binBarcode: '', materialBarcode: '', error: '', success: '', submitting: false };
            this.$nextTick(() => this.$refs.binInput?.focus());
        },

        closeScan() {
            const mir = this.scanModal.mir;
            this.scanModal.show = false;
            if (mir) this.openView(mir);
        },

        async submitScan() {
            this.scanModal.error = '';
            this.scanModal.success = '';
            if (!this.scanModal.binBarcode || !this.scanModal.materialBarcode) {
                this.scanModal.error = 'Both bin barcode and material barcode are required.';
                return;
            }
            this.scanModal.submitting = true;
            try {
                const res = await this._fetch(
                    `/api/v1/material-issue-requests/${this.scanModal.mir.id}/lines/${this.scanModal.line.id}/scan`,
                    { method: 'POST', body: JSON.stringify({ bin_barcode: this.scanModal.binBarcode, material_barcode: this.scanModal.materialBarcode }) }
                );
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Scan failed');

                this.scanModal.success = 'Stock deducted successfully. Line marked as ISSUED.';
                this.scanModal.binBarcode = '';
                this.scanModal.materialBarcode = '';

                await this.loadMIRs();

                if (data.data?.all_issued) {
                    this.scanModal.success += ' All lines issued — Production Order updated to IN PROGRESS.';
                }

                // Auto-close scan modal and reopen view after short delay
                setTimeout(() => this.closeScan(), 1500);
            } catch(e) {
                this.scanModal.error = e.message;
            } finally { this.scanModal.submitting = false; }
        },

        _fetch(url, options = {}) {
            return fetch(url, {
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Authorization': 'Bearer ' + (localStorage.getItem('access_token') || '') },
                ...options
            });
        }
    }
}
</script>
@endsection
