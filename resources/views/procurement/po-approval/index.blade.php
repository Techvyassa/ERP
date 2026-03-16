@extends('layouts.procurement')

@section('title', 'PO Approval - ' . $organization->org_name)
@section('page-title', 'Purchase Order Approval')

@section('content')
<div x-data="poApprovalData()" x-init="init()">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="bg-amber-100 p-3 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-amber-600 text-2xl">pending_actions</span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" x-text="stats.pending">0</p>
                <p class="text-sm text-gray-500 mt-0.5">Pending Approval</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="bg-green-100 p-3 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" x-text="stats.approved">0</p>
                <p class="text-sm text-gray-500 mt-0.5">Approved Today</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="bg-red-100 p-3 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-red-600 text-2xl">cancel</span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" x-text="stats.rejected">0</p>
                <p class="text-sm text-gray-500 mt-0.5">Rejected Today</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadPendingPOs()"
                       placeholder="PO Number, Vendor..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                <select x-model="filters.status" @change="loadPendingPOs()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">All Status</option>
                    <option value="PENDING_APPROVAL">Pending Approval</option>
                    <option value="DRAFT">Draft</option>
                    <option value="OPEN">Approved</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Amount Range</label>
                <select x-model="filters.amountRange" @change="loadPendingPOs()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">All Amounts</option>
                    <option value="0-50000">₹0 – ₹50,000</option>
                    <option value="50000-200000">₹50,000 – ₹2,00,000</option>
                    <option value="200000-500000">₹2,00,000 – ₹5,00,000</option>
                    <option value="500000+">₹5,00,000+</option>
                </select>
            </div>
            <div>
                <button @click="resetFilters()"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">filter_alt_off</span>
                    Reset Filters
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
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">PO Number</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">PO Date</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Amount</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Created By</th>
                        <th class="text-right py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    <!-- Loading -->
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-200 border-t-blue-500"></div>
                                    <span class="text-sm">Loading purchase orders...</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <template x-if="!loading && pendingPOs.length === 0">
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <span class="material-symbols-outlined text-5xl text-gray-300">check_circle</span>
                                    <p class="text-sm font-medium text-gray-500">No purchase orders found</p>
                                    <p class="text-xs text-gray-400">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Rows -->
                    <template x-for="po in pendingPOs" :key="po.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-semibold text-blue-600 cursor-pointer hover:underline"
                                      @click="viewDetails(po)"
                                      x-text="po.po_number"></span>
                            </td>
                            <td class="py-3.5 px-5 text-gray-700" x-text="po.vendor ? po.vendor.vendor_name : '—'"></td>
                            <td class="py-3.5 px-5 text-gray-500" x-text="formatDate(po.po_date)"></td>
                            <td class="py-3.5 px-5 font-semibold text-gray-900" x-text="formatCurrency(po.grand_total)"></td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="getStatusClass(po.status)"
                                      x-text="getStatusLabel(po.status)"></span>
                            </td>
                            <td class="py-3.5 px-5 text-gray-500"
                                x-text="po.created_by ? po.created_by.first_name + ' ' + po.created_by.last_name : '—'"></td>
                            <td class="py-3.5 px-5">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="viewDetails(po)"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View Details">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </button>
                                    <button @click="approvePO(po)"
                                            :disabled="po.status !== 'PENDING_APPROVAL'"
                                            class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            title="Approve">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    </button>
                                    <button @click="rejectPO(po)"
                                            :disabled="po.status !== 'PENDING_APPROVAL'"
                                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            title="Reject">
                                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                                    </button>
                                    <!-- Send to Vendor — enabled only when OPEN (approved) -->
                                    <button @click="sendToVendor(po)"
                                            :disabled="po.status !== 'OPEN' || sending === po.id"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            :title="po.status === 'OPEN' ? 'Send PO to Vendor' : 'Approve PO first to enable sending'">
                                        <span class="material-symbols-outlined text-[18px]"
                                              x-text="sending === po.id ? 'hourglass_top' : 'send'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                </tbody>
            </table>
        </div>
    </div>

    <!-- PO Details Modal -->
    <div x-show="showDetailsModal"
         x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showDetailsModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <template x-if="selectedPO">
                <div>
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900" x-text="'PO: ' + selectedPO.po_number"></h2>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="selectedPO.vendor ? selectedPO.vendor.vendor_name : ''"></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="getStatusClass(selectedPO.status)"
                                  x-text="getStatusLabel(selectedPO.status)"></span>
                            <button @click="showDetailsModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-6">
                        <!-- PO Info Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">PO Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatDate(selectedPO.po_date)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Delivery Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatDate(selectedPO.delivery_date)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Created By</p>
                                <p class="text-sm font-semibold text-gray-900"
                                   x-text="selectedPO.created_by ? selectedPO.created_by.first_name + ' ' + selectedPO.created_by.last_name : '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Grand Total</p>
                                <p class="text-sm font-bold text-gray-900" x-text="formatCurrency(selectedPO.grand_total)"></p>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Line Items</h3>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">#</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Material</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                            <th class="text-right py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, idx) in (selectedPO.line_items || [])" :key="idx">
                                            <tr>
                                                <td class="py-2.5 px-4 text-gray-500" x-text="idx + 1"></td>
                                                <td class="py-2.5 px-4 text-gray-900" x-text="item.material ? item.material.material_name : '—'"></td>
                                                <td class="py-2.5 px-4 text-gray-700" x-text="item.quantity + ' ' + (item.uom ? item.uom.uom_code : '')"></td>
                                                <td class="py-2.5 px-4 text-gray-700" x-text="formatCurrency(item.unit_price)"></td>
                                                <td class="py-2.5 px-4 text-right font-semibold text-gray-900" x-text="formatCurrency(item.total_price)"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!selectedPO.line_items || selectedPO.line_items.length === 0">
                                            <tr>
                                                <td colspan="5" class="py-6 text-center text-sm text-gray-400">No line items</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <template x-if="selectedPO.remarks">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Remarks</h3>
                                <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3" x-text="selectedPO.remarks"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                        <button @click="showDetailsModal = false"
                                class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">
                            Close
                        </button>
                        <template x-if="selectedPO.status === 'PENDING_APPROVAL'">
                            <div class="flex gap-2">
                                <button @click="rejectPO(selectedPO)"
                                        class="px-4 py-2 text-sm bg-red-50 border border-red-200 text-red-600 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">cancel</span>
                                    Reject
                                </button>
                                <button @click="approvePO(selectedPO)"
                                        class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                    Approve
                                </button>
                            </div>
                        </template>
                        <template x-if="selectedPO.status === 'OPEN' || selectedPO.status === 'PARTIAL'">
                            <button @click="sendToVendor(selectedPO)"
                                    :disabled="sending === selectedPO.id"
                                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base"
                                      x-text="sending === selectedPO.id ? 'hourglass_top' : 'send'"></span>
                                <span x-text="sending === selectedPO.id ? 'Sending...' : 'Send to Vendor'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showRejectModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Reject Purchase Order</h2>
                <p class="text-sm text-gray-500 mt-1" x-text="'PO: ' + (rejectingPO ? rejectingPO.po_number : '')"></p>
            </div>
            <div class="p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea x-model="rejectReason" rows="4"
                          placeholder="Please provide a reason for rejection..."
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400 resize-none"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showRejectModal = false"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">
                    Cancel
                </button>
                <button @click="confirmReject()"
                        :disabled="!rejectReason.trim()"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">cancel</span>
                    Confirm Reject
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <span class="material-symbols-outlined text-base"
              x-text="toast.type === 'success' ? 'check_circle' : 'error'"></span>
        <span x-text="toast.message"></span>
    </div>

    <!-- ASN CSV Upload Modal -->
    <div x-show="showASNModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showASNModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Upload ASN</h2>
                    <p class="text-sm text-gray-500 mt-0.5" x-text="asnPO ? 'PO: ' + asnPO.po_number : ''"></p>
                </div>
                <button @click="showASNModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <!-- CSV Template hint -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                    <strong>CSV required columns:</strong> po_line_id, material_id, shipped_qty, uom_id<br>
                    <strong>Optional:</strong> batch_number, lot_number, manufacturing_date (YYYY-MM-DD), expiry_date, pallet_id, sscc, gross_weight, net_weight
                    <br><button @click="downloadTemplate()" class="mt-1 underline text-blue-700 hover:text-blue-900">Download template CSV</button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Ship Date *</label>
                        <input type="date" x-model="asnForm.ship_date"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">ETA *</label>
                        <input type="date" x-model="asnForm.eta"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Warehouse *</label>
                        <select x-model="asnForm.warehouse_id"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                            <option value="">Select warehouse</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Carrier</label>
                        <input type="text" x-model="asnForm.carrier_name" placeholder="e.g. FedEx"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tracking #</label>
                        <input type="text" x-model="asnForm.tracking_number"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Vehicle #</label>
                        <input type="text" x-model="asnForm.vehicle_number"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks</label>
                    <input type="text" x-model="asnForm.remarks"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-400">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">CSV File *</label>
                    <input type="file" accept=".csv,.txt" @change="onCSVSelect($event)"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <p class="text-xs text-gray-400 mt-1" x-show="asnForm.fileName" x-text="'Selected: ' + asnForm.fileName"></p>
                </div>

                <template x-if="asnError">
                    <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="asnError"></p>
                </template>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showASNModal = false"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">
                    Cancel
                </button>
                <button @click="submitASN()"
                        :disabled="asnUploading || !asnForm.csvFile || !asnForm.ship_date || !asnForm.eta || !asnForm.warehouse_id"
                        class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base" x-text="asnUploading ? 'hourglass_top' : 'upload_file'"></span>
                    <span x-text="asnUploading ? 'Uploading...' : 'Upload ASN'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function poApprovalData() {
    return {
        loading: false,
        pendingPOs: [],
        stats: { pending: 0, approved: 0, rejected: 0 },
        filters: { search: '', status: '', amountRange: '' },
        showDetailsModal: false,
        selectedPO: null,
        showRejectModal: false,
        rejectingPO: null,
        rejectReason: '',
        sending: null,
        showASNModal: false,
        asnPO: null,
        warehouses: [],
        asnForm: { ship_date: '', eta: '', warehouse_id: '', carrier_name: '', tracking_number: '', vehicle_number: '', remarks: '', csvFile: null, fileName: '' },
        asnUploading: false,
        asnError: null,
        toast: { show: false, type: 'success', message: '' },

        async init() {
            await this.loadPendingPOs();
            await this.loadStats();
            await this.loadWarehouses();
        },

        getToken() {
            return localStorage.getItem('auth_token');
        },

        setToken(token) {
            localStorage.setItem('auth_token', token);
        },

        async tryRefresh() {
            const refreshToken = localStorage.getItem('refresh_token');
            if (!refreshToken) return false;
            try {
                const res = await fetch('/api/v1/auth/refresh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ refresh_token: refreshToken })
                });
                const data = await res.json();
                if (data.success) {
                    this.setToken(data.data.access_token);
                    if (data.data.refresh_token) {
                        localStorage.setItem('refresh_token', data.data.refresh_token);
                    }
                    return true;
                }
            } catch (e) {}
            return false;
        },

        async apiFetch(url, options = {}) {
            const makeRequest = (token) => {
                const headers = { 'Accept': 'application/json', ...(options.headers || {}) };
                if (token) headers['Authorization'] = `Bearer ${token}`;
                return fetch(url, { ...options, headers });
            };

            let res = await makeRequest(this.getToken());
            let data = await res.json();

            // Auto-refresh on token expiry and retry once
            if (!data.success && data.error?.code === 'TOKEN_EXPIRED') {
                const refreshed = await this.tryRefresh();
                if (refreshed) {
                    res = await makeRequest(this.getToken());
                    data = await res.json();
                } else {
                    // Refresh failed — redirect to login
                    window.location.href = '{{ url("/org/{$organization->org_slug}/procurement/login") }}';
                    return data;
                }
            }

            if (!data.success) {
                console.error(`API error [${res.status}] ${url}:`, data.message || data);
            }
            return data;
        },

        async loadPendingPOs() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search)      params.append('search', this.filters.search);
                if (this.filters.status)      params.append('status', this.filters.status);
                if (this.filters.amountRange) params.append('amount_range', this.filters.amountRange);

                const data = await this.apiFetch(`/api/v1/purchase-orders?${params}`);
                if (data.success) {
                    const d = data.data;
                    // Paginated: { data: [...], total: n }  |  plain array
                    this.pendingPOs = Array.isArray(d) ? d : (d.data || []);
                } else {
                    this.pendingPOs = [];
                    this.showToast('error', data.message || 'Failed to load purchase orders');
                }
            } catch (e) {
                console.error('loadPendingPOs error:', e);
                this.pendingPOs = [];
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const data = await this.apiFetch('/api/v1/purchase-orders?status=PENDING_APPROVAL&per_page=1');
                if (data.success) {
                    const d = data.data;
                    this.stats.pending = d.total ?? (Array.isArray(d) ? d.length : (d.data?.length ?? 0));
                }
            } catch (e) {}
        },

        async viewDetails(po) {
            this.selectedPO = po;
            this.showDetailsModal = true;
            try {
                const data = await this.apiFetch(`/api/v1/purchase-orders/${po.id}`);
                if (data.success) this.selectedPO = data.data.purchase_order;
            } catch (e) {}
        },

        async approvePO(po) {
            if (!confirm(`Approve PO ${po.po_number}?`)) return;
            try {
                const data = await this.apiFetch(`/api/v1/purchase-orders/${po.id}/approve`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' }
                });
                if (data.success) {
                    this.showToast('success', `PO ${po.po_number} approved successfully`);
                    this.showDetailsModal = false;
                    await this.loadPendingPOs();
                    await this.loadStats();
                } else {
                    this.showToast('error', data.message || 'Failed to approve PO');
                }
            } catch (e) {
                this.showToast('error', 'An error occurred');
            }
        },

        rejectPO(po) {
            this.rejectingPO = po;
            this.rejectReason = '';
            this.showRejectModal = true;
            this.showDetailsModal = false;
        },

        async confirmReject() {
            if (!this.rejectReason.trim()) return;
            try {
                const data = await this.apiFetch(`/api/v1/purchase-orders/${this.rejectingPO.id}/reject`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ remarks: this.rejectReason })
                });
                if (data.success) {
                    this.showToast('success', `PO ${this.rejectingPO.po_number} rejected`);
                    this.showRejectModal = false;
                    await this.loadPendingPOs();
                    await this.loadStats();
                } else {
                    this.showToast('error', data.message || 'Failed to reject PO');
                }
            } catch (e) {
                this.showToast('error', 'An error occurred');
            }
        },

        resetFilters() {
            this.filters = { search: '', status: '', amountRange: '' };
            this.loadPendingPOs();
        },

        getStatusClass(status) {
            const map = {
                'PENDING_APPROVAL': 'bg-amber-100 text-amber-700',
                'DRAFT':            'bg-gray-100 text-gray-600',
                'APPROVED':         'bg-blue-100 text-blue-700',
                'OPEN':             'bg-green-100 text-green-700',
                'PARTIAL':          'bg-purple-100 text-purple-700',
                'CLOSED':           'bg-gray-100 text-gray-500',
                'CANCELLED':        'bg-red-100 text-red-600',
            };
            return map[status] || 'bg-gray-100 text-gray-600';
        },

        getStatusLabel(status) {
            const map = {
                'PENDING_APPROVAL': 'Pending Approval',
                'DRAFT':            'Draft',
                'APPROVED':         'Approved',
                'OPEN':             'Open',
                'PARTIAL':          'Partial',
                'CLOSED':           'Closed',
                'CANCELLED':        'Cancelled',
            };
            return map[status] || status;
        },

        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatCurrency(amount) {
            if (amount == null) return '—';
            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(amount);
        },

        async loadWarehouses() {
            try {
                const data = await this.apiFetch('/api/v1/warehouses?per_page=100');
                if (data.success && data.data) {
                    this.warehouses = data.data.warehouses || data.data.data || [];
                }
            } catch (e) {}
        },

        openASNModal(po) {
            this.asnPO = po;
            this.asnError = null;
            this.asnForm = {
                ship_date: new Date().toISOString().split('T')[0],
                eta: po.expected_delivery ? po.expected_delivery.split('T')[0] : '',
                warehouse_id: '',
                carrier_name: '',
                tracking_number: '',
                vehicle_number: '',
                remarks: '',
                csvFile: null,
                fileName: ''
            };
            this.showASNModal = true;
        },

        onCSVSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.asnForm.csvFile = file;
                this.asnForm.fileName = file.name;
            }
        },

        downloadTemplate() {
            const headers = 'po_line_id,material_id,shipped_qty,uom_id,batch_number,lot_number,manufacturing_date,expiry_date,pallet_id,sscc,gross_weight,net_weight';
            const example = '1,1,10.000,1,BATCH001,LOT001,2026-01-01,2027-01-01,,,25.5,24.0';
            const blob = new Blob([headers + '\n' + example], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a'); a.href = url; a.download = 'asn_template.csv'; a.click();
            URL.revokeObjectURL(url);
        },

        async submitASN() {
            if (!this.asnForm.csvFile || !this.asnForm.ship_date || !this.asnForm.eta || !this.asnForm.warehouse_id) return;
            this.asnUploading = true;
            this.asnError = null;
            try {
                const token = this.getToken();
                const fd = new FormData();
                fd.append('file', this.asnForm.csvFile);
                fd.append('po_id', this.asnPO.id);
                fd.append('vendor_id', this.asnPO.vendor_id);
                fd.append('warehouse_id', this.asnForm.warehouse_id);
                fd.append('ship_date', this.asnForm.ship_date);
                fd.append('eta', this.asnForm.eta);
                if (this.asnForm.carrier_name)    fd.append('carrier_name', this.asnForm.carrier_name);
                if (this.asnForm.tracking_number) fd.append('tracking_number', this.asnForm.tracking_number);
                if (this.asnForm.vehicle_number)  fd.append('vehicle_number', this.asnForm.vehicle_number);
                if (this.asnForm.remarks)         fd.append('remarks', this.asnForm.remarks);

                const res = await fetch('/api/v1/asn/upload-csv', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    this.showASNModal = false;
                    this.showToast('success', data.message + ' — ' + (data.data.asn.asn_number || ''));
                } else {
                    this.asnError = data.message || 'Failed to upload ASN';
                }
            } catch (e) {
                this.asnError = 'Network error. Please try again.';
            } finally {
                this.asnUploading = false;
            }
        },

        async sendToVendor(po) {
            if (!confirm(`Send PO ${po.po_number} to vendor via email?`)) return;
            this.sending = po.id;
            try {
                const data = await this.apiFetch(`/api/v1/purchase-orders/${po.id}/send-to-vendor`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                if (data.success) {
                    this.showToast('success', `PO sent to ${data.data.contact_name} (${data.data.sent_to})`);
                } else {
                    this.showToast('error', data.message || 'Failed to send PO');
                }
            } catch (e) {
                this.showToast('error', 'An error occurred while sending');
            } finally {
                this.sending = null;
            }
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        }
    };
}
</script>
@endsection
