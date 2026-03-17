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

    <div class="flex justify-end mb-6">
        <button @click="openCreateModal()"
            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90 transition">
            New Gate Entry
        </button>
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
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
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
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Enter reason for rejection"></textarea>
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
                        <div><p class="text-xs text-gray-500">Delivery Challan</p><p class="font-medium" x-text="selectedEntry?.challan_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Invoice No.</p><p class="font-medium" x-text="selectedEntry?.vendor_invoice_number || '—'"></p></div>
                        <div><p class="text-xs text-gray-500">Arrived At</p><p class="font-medium" x-text="formatDateTime(selectedEntry?.arrived_at)"></p></div>
                    </div>
                    <div x-show="selectedEntry?.remarks"><p class="text-xs text-gray-500">Remarks</p><p class="font-medium" x-text="selectedEntry?.remarks"></p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Gate Entry Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">New Gate Entry</h3>
                    <button @click="showCreateModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitGateEntry()" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Purchase Order *</label>
                            <select required x-model="createForm.po_id" @change="onPOChange()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select PO</option>
                                <template x-for="po in purchaseOrders" :key="po.id">
                                    <option :value="String(po.id)" x-text="po.po_number + (po.vendor ? ' — ' + po.vendor.vendor_name : '')"></option>
                                </template>
                            </select>
                            <p x-show="poLoading" class="text-xs text-gray-500 mt-1">Loading purchase orders...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor *</label>
                            <input type="text" readonly :value="selectedPO?.vendor?.vendor_name || ''"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p x-show="createForm.po_id && !createForm.vendor_id" class="text-xs text-red-600 mt-1">Vendor not found for selected PO</p>
                        </div>
                        <!-- <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">ASN ID</label>
                            <input type="number" min="1" x-model="createForm.asn_id"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div> -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vehicle Number *</label>
                            <input type="text" maxlength="20" required x-model="createForm.vehicle_number"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Material Type *</label>
                            <select required x-model="createForm.material_type"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select</option>
                                <option value="RAW_MATERIAL">Raw Material</option>
                                <option value="PACKAGING">Packaging</option>
                                <option value="CONSUMABLE">Consumable</option>
                                <option value="CAPITAL_GOODS">Capital Goods</option>
                                <option value="SPARE_PARTS">Spare Parts</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Arrived At *</label>
                            <input type="datetime-local" required x-model="createForm.arrived_at"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Driver Name</label>
                            <input type="text" maxlength="100" x-model="createForm.driver_name"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Driver Phone</label>
                            <input type="text" maxlength="15" x-model="createForm.driver_phone"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Transporter Name</label>
                            <input type="text" maxlength="100" x-model="createForm.transporter_name"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Gross Weight (kg)</label>
                            <input type="number" step="0.001" min="0" x-model="createForm.gross_weight_kg"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Challan Number</label>
                            <input type="text" maxlength="50" x-model="createForm.challan_number"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor Invoice Number</label>
                            <input type="text" maxlength="50" x-model="createForm.vendor_invoice_number"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <!-- <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">E-Way Bill Number</label>
                            <input type="text" maxlength="30" x-model="createForm.eway_bill_number"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">E-Way Bill Expiry</label>
                            <input type="date" x-model="createForm.eway_bill_expiry"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div> -->
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks</label>
                        <textarea rows="2" x-model="createForm.remarks"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving">Create</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
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
        showVerifyModal: false, showViewModal: false, showCreateModal: false,
        selectedEntry: null,
        purchaseOrders: [],
        selectedPO: null,
        poLoading: false,
        counts: { pending: 0, verified: 0, docked: 0, rejected: 0 },
        filters: { status: '', from_date: '', to_date: '' },
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
        createForm: {
            po_id: '',
            vendor_id: '',
            asn_id: '',
            vehicle_number: '',
            transporter_name: '',
            driver_name: '',
            driver_phone: '',
            challan_number: '',
            vendor_invoice_number: '',
            eway_bill_number: '',
            eway_bill_expiry: '',
            material_type: '',
            gross_weight_kg: '',
            arrived_at: '',
            remarks: '',
        },

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

        openVerifyModal(entry) {
            this.selectedEntry = entry;
            this.verifyForm = {
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
            };
            this.showVerifyModal = true;
        },
        viewEntry(entry) { this.selectedEntry = entry; this.showViewModal = true; },

        openCreateModal() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const dtLocal = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            this.selectedPO = null;
            this.createForm = {
                po_id: '',
                vendor_id: '',
                asn_id: '',
                vehicle_number: '',
                transporter_name: '',
                driver_name: '',
                driver_phone: '',
                challan_number: '',
                vendor_invoice_number: '',
                eway_bill_number: '',
                eway_bill_expiry: '',
                material_type: '',
                gross_weight_kg: '',
                arrived_at: dtLocal,
                remarks: '',
            };
            this.showCreateModal = true;
            this.loadPOs();
        },

        async loadPOs() {
            this.poLoading = true;
            try {
                // Fetch all gate entries to know which POs already have one
                const geRes = await fetch(`/api/v1/gate-entries?per_page=500`, { headers: headers() });
                const geData = await geRes.json();
                const usedPoIds = new Set(
                    (geData.success ? (geData.data?.data || []) : [])
                        .filter(e => e.status !== 'REJECTED') // allow re-entry if rejected
                        .map(e => e.po_id)
                );

                const p = new URLSearchParams({ per_page: 100, status: 'OPEN' });
                const res = await fetch(`/api/v1/purchase-orders?${p}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.purchaseOrders = (data.data?.data || []).filter(po => !usedPoIds.has(po.id));
                } else {
                    this.purchaseOrders = [];
                }
            } finally {
                this.poLoading = false;
            }
        },

        onPOChange() {
            const poId = this.createForm.po_id ? Number(this.createForm.po_id) : null;
            this.selectedPO = this.purchaseOrders.find(p => p.id === poId) || null;
            this.createForm.vendor_id = this.selectedPO?.vendor_id ? String(this.selectedPO.vendor_id) : '';
        },

        toApiDateTime(dtLocal) {
            if (!dtLocal) return '';
            const parts = dtLocal.split('T');
            if (parts.length !== 2) return '';
            return `${parts[0]} ${parts[1]}:00`;
        },

        async submitGateEntry() {
            this.saving = true;
            try {
                const payload = {
                    po_id: this.createForm.po_id ? Number(this.createForm.po_id) : null,
                    vendor_id: this.createForm.vendor_id ? Number(this.createForm.vendor_id) : null,
                    asn_id: this.createForm.asn_id ? Number(this.createForm.asn_id) : null,
                    vehicle_number: this.createForm.vehicle_number,
                    transporter_name: this.createForm.transporter_name || null,
                    driver_name: this.createForm.driver_name || null,
                    driver_phone: this.createForm.driver_phone || null,
                    challan_number: this.createForm.challan_number || null,
                    vendor_invoice_number: this.createForm.vendor_invoice_number || null,
                    eway_bill_number: this.createForm.eway_bill_number || null,
                    eway_bill_expiry: this.createForm.eway_bill_expiry || null,
                    material_type: this.createForm.material_type,
                    gross_weight_kg: this.createForm.gross_weight_kg !== '' ? Number(this.createForm.gross_weight_kg) : null,
                    arrived_at: this.toApiDateTime(this.createForm.arrived_at),
                    remarks: this.createForm.remarks || null,
                };

                if (!payload.po_id) {
                    alert('Please select a Purchase Order');
                    return;
                }
                if (!payload.vendor_id) {
                    alert('Vendor is missing for selected PO');
                    return;
                }

                const res = await fetch(`/api/v1/gate-entries`, { method: 'POST', headers: headers(), body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    this.showCreateModal = false;
                    await this.loadEntries();
                } else {
                    alert(data.message || 'Gate entry creation failed');
                }
            } finally {
                this.saving = false;
            }
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
