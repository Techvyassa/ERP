@extends('layouts.security')

@section('title', 'Gate Verification - ' . $organization->org_name)
@section('page-title', 'Gate Verification')

@section('content')
<div x-data="gateVerificationData()" x-init="init()">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gate Verification</h2>
        <p class="text-gray-500 text-sm">Verify pending gate entries and approve/reject them</p>
    </div>

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
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Docs</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && entries.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No pending verifications</td></tr>
                    </template>
                    <template x-for="entry in entries" :key="entry.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="entry.ge_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-900" x-text="entry.vehicle_number"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="entry.vendor ? entry.vendor.vendor_name : '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="entry.purchase_order ? entry.purchase_order.po_number : '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="formatDateTime(entry.arrived_at)"></td>
                            <td class="py-3 px-5 text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span x-text="entry.challan_number ? ('CH: ' + entry.challan_number) : 'CH: —'"></span>
                                    <span x-text="entry.vendor_invoice_number ? ('INV: ' + entry.vendor_invoice_number) : 'INV: —'"></span>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <button @click="openVerifyModal(entry)" title="Verify"
                                    class="text-green-600 hover:text-green-800 mr-2">
                                    <span class="material-symbols-outlined text-lg">fact_check</span>
                                </button>
                                <button @click="viewEntry(entry)" title="View"
                                    class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
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

    <div x-show="showVerifyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showVerifyModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Verify Gate Entry</h3>
                    <button @click="showVerifyModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitVerification()" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        Verifying: <strong x-text="selectedEntry?.ge_number"></strong> —
                        <span x-text="selectedEntry?.vendor?.vendor_name"></span>
                        <template x-if="selectedEntry?.vendor_invoice_number || selectedEntry?.challan_number">
                            <span class="ml-1 text-amber-700">
                                <span x-show="selectedEntry?.vendor_invoice_number"> · Updated</span>
                            </span>
                        </template>
                    </div>

                    <!-- Doc info from gate entry (read-only reference) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 grid grid-cols-2 gap-2 text-xs text-gray-600">
                        <div>
                            <span class="font-semibold text-gray-500">Challan No:</span>
                            <span x-text="selectedEntry?.challan_number || '—'" class="ml-1 font-medium text-gray-800"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500">Invoice No:</span>
                            <span x-text="selectedEntry?.vendor_invoice_number || '—'" class="ml-1 font-medium text-gray-800"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500">E-Way Bill:</span>
                            <span x-text="selectedEntry?.eway_bill_number || '—'" class="ml-1 font-medium text-gray-800"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500">Gross Wt:</span>
                            <span x-text="selectedEntry?.gross_weight_kg ? selectedEntry.gross_weight_kg + ' kg' : '—'" class="ml-1 font-medium text-gray-800"></span>
                        </div>
                    </div>

                    <!-- Document verification checkboxes -->
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.challan_verified">
                            Challan Verified
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.invoice_verified">
                            Invoice Verified
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.eway_bill_valid">
                            E-Way Bill Valid
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.po_status_valid">
                            PO Status Valid
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Approval Status *</label>
                        <select x-model="verifyForm.approval_status" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select Status</option>
                            <option value="APPROVED">Approved — Allow Entry</option>
                            <option value="REJECTED">Rejected — Turn Away</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.seal_intact">
                            Seal Intact
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.external_damage">
                            External Damage
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tare Weight (kg)</label>
                            <input type="number" step="0.001" min="0" x-model="verifyForm.tare_weight_kg"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                :placeholder="selectedEntry?.gross_weight_kg ? 'Gross: ' + selectedEntry.gross_weight_kg : ''">
                        </div>
                        <label class="flex items-center gap-2 text-sm mt-7">
                            <input type="checkbox" class="rounded border-gray-300" x-model="verifyForm.weight_variance_flag">
                            Weight Variance Flag
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Seal Number</label>
                        <input type="text" x-model="verifyForm.seal_number"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Dock Assigned</label>
                        <input type="text" x-model="verifyForm.dock_assigned"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div x-show="verifyForm.approval_status === 'REJECTED'">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Rejection Reason *</label>
                        <textarea x-model="verifyForm.rejection_reason" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Security Remarks</label>
                        <textarea x-model="verifyForm.security_remarks" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showVerifyModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
                            <span x-show="!saving">Submit</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                        <div><p class="text-xs text-gray-500">Status</p><p class="font-medium" x-text="selectedEntry?.status"></p></div>
                        <div><p class="text-xs text-gray-500">Vehicle</p><p class="font-medium" x-text="selectedEntry?.vehicle_number"></p></div>
                        <div><p class="text-xs text-gray-500">Driver</p><p class="font-medium" x-text="selectedEntry?.driver_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Vendor</p><p class="font-medium" x-text="selectedEntry?.vendor?.vendor_name || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">PO Number</p><p class="font-medium" x-text="selectedEntry?.purchase_order?.po_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Challan</p><p class="font-medium" x-text="selectedEntry?.challan_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Invoice</p><p class="font-medium" x-text="selectedEntry?.vendor_invoice_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Arrived At</p><p class="font-medium" x-text="formatDateTime(selectedEntry?.arrived_at)"></p></div>
                    </div>
                    <div x-show="selectedEntry?.remarks"><p class="text-xs text-gray-500">Remarks</p><p class="font-medium" x-text="selectedEntry?.remarks"></p></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function gateVerificationData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        entries: [],
        loading: false,
        saving: false,
        showVerifyModal: false,
        showViewModal: false,
        selectedEntry: null,
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        verifyForm: {
            challan_verified: true,
            invoice_verified: true,
            eway_bill_valid: true,
            po_status_valid: true,
            seal_number: '',
            seal_intact: true,
            external_damage: false,
            tare_weight_kg: '',
            weight_variance_flag: false,
            dock_assigned: '',
            approval_status: '',
            rejection_reason: '',
            security_remarks: '',
        },

        async init() {
            await this.loadPending();
        },

        async loadPending(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 20 });
                const res = await fetch(`/api/v1/gate-entries/pending-verifications?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.entries = data.data.data || [];
                    this.pagination = { current_page: data.data.current_page, last_page: data.data.last_page, from: data.data.from || 0, to: data.data.to || 0, total: data.data.total || 0 };
                } else {
                    this.entries = [];
                }
            } finally {
                this.loading = false;
            }
        },

        openVerifyModal(entry) {
            this.selectedEntry = entry;
            // Pre-fill verification flags based on what was submitted at gate entry
            this.verifyForm = {
                challan_verified: !!entry.challan_number,
                invoice_verified: !!entry.vendor_invoice_number,
                eway_bill_valid: !!entry.eway_bill_number,
                po_status_valid: true,
                seal_number: '',
                seal_intact: true,
                external_damage: false,
                tare_weight_kg: entry.gross_weight_kg || '',
                weight_variance_flag: false,
                dock_assigned: '',
                approval_status: '',
                rejection_reason: '',
                security_remarks: '',
            };
            this.showVerifyModal = true;
        },

        viewEntry(entry) {
            this.selectedEntry = entry;
            this.showViewModal = true;
        },

        async submitVerification() {
            this.saving = true;
            try {
                if (this.verifyForm.approval_status === 'REJECTED' && !this.verifyForm.rejection_reason) {
                    alert('Rejection reason is required');
                    return;
                }

                const payload = {
                    challan_verified: !!this.verifyForm.challan_verified,
                    invoice_verified: !!this.verifyForm.invoice_verified,
                    eway_bill_valid: !!this.verifyForm.eway_bill_valid,
                    po_status_valid: !!this.verifyForm.po_status_valid,
                    seal_number: this.verifyForm.seal_number || null,
                    seal_intact: this.verifyForm.seal_intact === '' ? null : !!this.verifyForm.seal_intact,
                    external_damage: !!this.verifyForm.external_damage,
                    tare_weight_kg: this.verifyForm.tare_weight_kg !== '' ? Number(this.verifyForm.tare_weight_kg) : null,
                    weight_variance_flag: !!this.verifyForm.weight_variance_flag,
                    dock_assigned: this.verifyForm.dock_assigned || null,
                    approval_status: this.verifyForm.approval_status,
                    rejection_reason: this.verifyForm.approval_status === 'REJECTED' ? this.verifyForm.rejection_reason : null,
                    security_remarks: this.verifyForm.security_remarks || null,
                };

                const res = await fetch(`/api/v1/gate-entries/${this.selectedEntry.id}/verify`, { method: 'POST', headers: headers(), body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    this.showVerifyModal = false;
                    await this.loadPending(this.pagination.current_page);
                } else {
                    alert(data.message || 'Verification failed');
                }
            } finally {
                this.saving = false;
            }
        },

        changePage(p) {
            if (p >= 1 && p <= this.pagination.last_page) this.loadPending(p);
        },

        formatDateTime(v) {
            return v ? new Date(v).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';
        },
    };
}
</script>
@endsection
