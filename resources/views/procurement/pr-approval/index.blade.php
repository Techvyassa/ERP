@extends('layouts.procurement')

@section('title', 'PR Approval')
@section('page-title', 'Purchase Requisition Approval')

@section('content')
<div x-data="prApprovalData()" x-init="init()">

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
                <p class="text-sm text-gray-500 mt-0.5">Approved</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="bg-red-100 p-3 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-red-600 text-2xl">cancel</span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" x-text="stats.rejected">0</p>
                <p class="text-sm text-gray-500 mt-0.5">Rejected</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadData()"
                       placeholder="PR Number, Department..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                <select x-model="filters.status" @change="loadData()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">All Status</option>
                    <option value="PENDING_APPROVAL">Pending Approval</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Priority</label>
                <select x-model="filters.priority" @change="loadData()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    <option value="">All Priority</option>
                    <option value="EMERGENCY">Emergency</option>
                    <option value="HIGH">High</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="LOW">Low</option>
                </select>
            </div>
            <div>
                <button @click="resetFilters()"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">filter_alt_off</span>Reset Filters
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
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">PR Number</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Requested By</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Department</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Required Date</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Priority</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-200 border-t-blue-500"></div>
                                <span class="text-sm">Loading...</span>
                            </div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <span class="material-symbols-outlined text-5xl text-gray-300">pending_actions</span>
                                <p class="text-sm font-medium text-gray-500">No purchase requisitions found</p>
                                <p class="text-xs text-gray-400">Try adjusting your filters</p>
                            </div>
                        </td></tr>
                    </template>
                    <template x-for="pr in items" :key="pr.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-semibold text-blue-600 cursor-pointer hover:underline"
                                      @click="viewDetails(pr.id)" x-text="pr.pr_number"></span>
                            </td>
                            <td class="py-3.5 px-5 text-gray-700" x-text="pr.requested_by_name"></td>
                            <td class="py-3.5 px-5 text-gray-500" x-text="pr.department_name"></td>
                            <td class="py-3.5 px-5 text-gray-500" x-text="formatDate(pr.required_date)"></td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-red-100 text-red-800': pr.priority === 'EMERGENCY',
                                          'bg-orange-100 text-orange-800': pr.priority === 'HIGH',
                                          'bg-yellow-100 text-yellow-800': pr.priority === 'MEDIUM',
                                          'bg-green-100 text-green-800': pr.priority === 'LOW'
                                      }"
                                      x-text="pr.priority"></span>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': pr.status === 'PENDING_APPROVAL',
                                          'bg-green-100 text-green-800': pr.status === 'APPROVED',
                                          'bg-red-100 text-red-800': pr.status === 'REJECTED',
                                          'bg-gray-100 text-gray-800': pr.status === 'DRAFT'
                                      }"
                                      x-text="pr.status === 'PENDING_APPROVAL' ? 'Pending Approval' : pr.status"></span>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Eye / View -->
                                    <button @click="viewDetails(pr.id)"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </button>
                                    <!-- Approve (PENDING_APPROVAL only) -->
                                    <button @click="approvePR(pr)"
                                            :disabled="pr.status !== 'PENDING_APPROVAL'"
                                            class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed" title="Approve">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    </button>
                                    <!-- Reject (PENDING_APPROVAL only) -->
                                    <button @click="openRejectModal(pr)"
                                            :disabled="pr.status !== 'PENDING_APPROVAL'"
                                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed" title="Reject">
                                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                                    </button>
                                    <!-- Send to Vendor (APPROVED only) -->
                                    <button @click="openSendToVendorModal(pr)"
                                            :disabled="pr.status !== 'APPROVED'"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            :title="pr.status === 'APPROVED' ? 'Send PR to Vendor' : 'Approve PR first to enable sending'">
                                        <span class="material-symbols-outlined text-[18px]">send</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="px-5 py-4 border-t flex items-center justify-between text-sm text-gray-600">
            <span x-text="'Showing ' + pagination.from + ' to ' + pagination.to + ' of ' + pagination.total + ' records'"></span>
            <div class="flex gap-2">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">Previous</button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">Next</button>
            </div>
        </div>
    </div>

    <!-- PR Details Modal -->
    <div x-show="showDetailsModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showDetailsModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <!-- Loading spinner inside modal -->
            <template x-if="!selectedPR">
                <div class="p-16 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-200 border-t-blue-500 mx-auto"></div>
                </div>
            </template>
            <template x-if="selectedPR">
                <div>
                    <div class="flex items-center justify-between p-6 border-b border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Purchase Requisition</p>
                            <h2 class="text-xl font-bold text-blue-600 tracking-widest mt-0.5" x-text="selectedPR.pr_number"></h2>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': selectedPR.status === 'PENDING_APPROVAL',
                                      'bg-green-100 text-green-800': selectedPR.status === 'APPROVED',
                                      'bg-red-100 text-red-800': selectedPR.status === 'REJECTED',
                                      'bg-gray-100 text-gray-800': selectedPR.status === 'DRAFT'
                                  }"
                                  x-text="selectedPR.status === 'PENDING_APPROVAL' ? 'Pending Approval' : selectedPR.status"></span>
                            <button @click="showDetailsModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">PR Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatDate(selectedPR.pr_date)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Required Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatDate(selectedPR.required_date)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Priority</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedPR.priority"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Requested By</p>
                                <p class="text-sm font-semibold text-gray-900"
                                   x-text="selectedPR.requested_by ? selectedPR.requested_by.first_name + ' ' + selectedPR.requested_by.last_name : '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Department</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedPR.department?.dept_name || '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3" x-show="selectedPR.cost_center_code">
                                <p class="text-xs text-gray-500 mb-1">Cost Center</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedPR.cost_center_code"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3" x-show="selectedPR.budget_code">
                                <p class="text-xs text-gray-500 mb-1">Budget Code</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedPR.budget_code"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3" x-show="selectedPR.suggested_vendor">
                                <p class="text-xs text-gray-500 mb-1">Suggested Vendor</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedPR.suggested_vendor?.vendor_name || '—'"></p>
                            </div>
                        </div>
                        <template x-if="selectedPR.justification">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Justification</p>
                                <p class="text-sm text-gray-700" x-text="selectedPR.justification"></p>
                            </div>
                        </template>
                        <template x-if="selectedPR.remarks">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-1">Remarks</p>
                                <p class="text-sm text-gray-700" x-text="selectedPR.remarks"></p>
                            </div>
                        </template>
                        <!-- Line Items -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Line Items</h3>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">#</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Item</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                            <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">UOM</th>
                                            <th class="text-right py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                            <th class="text-right py-2.5 px-4 text-xs font-semibold text-gray-500 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(line, idx) in (selectedPR.line_items || [])" :key="idx">
                                            <tr>
                                                <td class="py-2.5 px-4 text-gray-500" x-text="line.line_number"></td>
                                                <td class="py-2.5 px-4 font-medium text-gray-900" x-text="line.item_name"></td>
                                                <td class="py-2.5 px-4 text-gray-700" x-text="line.quantity"></td>
                                                <td class="py-2.5 px-4 text-gray-500" x-text="line.uom?.uom_name || '—'"></td>
                                                <td class="py-2.5 px-4 text-right text-gray-700"
                                                    x-text="line.estimated_unit_price ? '₹ ' + parseFloat(line.estimated_unit_price).toFixed(2) : '—'"></td>
                                                <td class="py-2.5 px-4 text-right font-semibold text-gray-900"
                                                    x-text="'₹ ' + ((parseFloat(line.quantity)||0)*(parseFloat(line.estimated_unit_price)||0)).toFixed(2)"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!selectedPR.line_items || selectedPR.line_items.length === 0">
                                            <tr><td colspan="6" class="py-6 text-center text-sm text-gray-400">No line items</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                        <button @click="showDetailsModal = false"
                                class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">Close</button>
                        <template x-if="selectedPR.status === 'DRAFT'">
                            <button @click="sendForApproval(selectedPR)"
                                    class="px-4 py-2 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">send</span>Send for Approval
                            </button>
                        </template>
                        <template x-if="selectedPR.status === 'PENDING_APPROVAL'">
                            <div class="flex gap-2">
                                <button @click="openRejectModal(selectedPR)"
                                        class="px-4 py-2 text-sm bg-red-50 border border-red-200 text-red-600 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">cancel</span>Reject
                                </button>
                                <button @click="approvePR(selectedPR)"
                                        class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">check_circle</span>Approve
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showRejectModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Reject Purchase Requisition</h2>
                <p class="text-sm text-gray-500 mt-1" x-text="'PR: ' + (rejectingPR ? rejectingPR.pr_number : '')"></p>
            </div>
            <div class="p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea x-model="rejectReason" rows="4" placeholder="Please provide a reason for rejection..."
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400 resize-none"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showRejectModal = false"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">Cancel</button>
                <button @click="confirmReject()" :disabled="!rejectReason.trim()"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">cancel</span>Confirm Reject
                </button>
            </div>
        </div>
    </div>

    <!-- Send to Vendor Modal -->
    <div x-show="showSendToVendorModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showSendToVendorModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Send PR to Vendor</h2>
                <p class="text-sm text-gray-500 mt-1" x-text="'PR: ' + (sendingPR ? sendingPR.pr_number : '')"></p>
            </div>
            <div class="p-6 space-y-4">
                <!-- Suggested Vendor (if available) -->
                <div x-show="sendingPR && sendingPR.suggested_vendor">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Suggested Vendor</label>
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm font-medium text-blue-900" x-text="sendingPR?.suggested_vendor?.vendor_name || ''"></p>
                        <p class="text-xs text-blue-600 mt-1" x-text="sendingPR?.suggested_vendor?.email || ''"></p>
                    </div>
                </div>
                
                <!-- Vendor Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Vendor(s) <span class="text-red-500">*</span></label>
                    <select x-model="selectedVendors" multiple size="5" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400">
                        <template x-for="vendor in vendors" :key="vendor.id">
                            <option :value="vendor.id" x-text="vendor.vendor_name + ' (' + (vendor.email || 'No email') + ')'"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple vendors</p>
                </div>
                
                <!-- Email Message -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Message (Optional)</label>
                    <textarea x-model="emailMessage" rows="3" placeholder="Additional message to include in the email..."
                              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 resize-none"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showSendToVendorModal = false"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-white transition-colors">Cancel</button>
                <button @click="confirmSendToVendor()" :disabled="selectedVendors.length === 0 || sendingToVendor"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base" x-show="!sendingToVendor">send</span>
                    <span class="material-symbols-outlined text-base animate-spin" x-show="sendingToVendor">hourglass_top</span>
                    <span x-text="sendingToVendor ? 'Sending...' : 'Send to Vendor(s)'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <span class="material-symbols-outlined text-base" x-text="toast.type === 'success' ? 'check_circle' : 'error'"></span>
        <span x-text="toast.message"></span>
    </div>

</div>

<script>
function prApprovalData() {
    return {
        items: [],
        loading: false,
        stats: { pending: 0, approved: 0, rejected: 0 },
        filters: { search: '', status: '', priority: '', page: 1 },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        showDetailsModal: false,
        selectedPR: null,
        showRejectModal: false,
        rejectingPR: null,
        rejectReason: '',
        showSendToVendorModal: false,
        sendingPR: null,
        selectedVendors: [],
        emailMessage: '',
        sendingToVendor: false,
        vendors: [],
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await Promise.all([this.loadData(), this.loadStats(), this.loadVendors()]);
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        async apiFetch(url, options = {}) {
            const headers = { 'Accept': 'application/json', ...(options.headers || {}) };
            const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
            return res.json();
        },

        async loadStats() {
            try {
                const [p, a, r] = await Promise.all([
                    this.apiFetch('/api/v1/purchase-requisitions?status=PENDING_APPROVAL&per_page=1'),
                    this.apiFetch('/api/v1/purchase-requisitions?status=APPROVED&per_page=1'),
                    this.apiFetch('/api/v1/purchase-requisitions?status=REJECTED&per_page=1'),
                ]);
                this.stats.pending  = p.data?.total || 0;
                this.stats.approved = a.data?.total || 0;
                this.stats.rejected = r.data?.total || 0;
            } catch (e) { console.error(e); }
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search)   params.append('search', this.filters.search);
                // If status is empty (All Status), we need to exclude DRAFT
                // We'll handle this by fetching all and filtering client-side, or by using multiple status params
                if (this.filters.status)   params.append('status', this.filters.status);
                if (this.filters.priority) params.append('priority', this.filters.priority);
                params.append('page', this.filters.page);
                params.append('per_page', 15);

                const json = await this.apiFetch('/api/v1/purchase-requisitions?' + params.toString());
                if (json.success) {
                    const paged = json.data;
                    // Filter out DRAFT status when "All Status" is selected
                    let filteredData = paged.data || [];
                    if (!this.filters.status) {
                        filteredData = filteredData.filter(pr => pr.status !== 'DRAFT');
                    }
                    
                    this.items = filteredData.map(pr => ({
                        id:                pr.id,
                        pr_number:         pr.pr_number,
                        requested_by_name: pr.requested_by
                            ? (pr.requested_by.first_name + ' ' + pr.requested_by.last_name)
                            : '—',
                        department_name:   pr.department?.dept_name || '—',
                        required_date:     pr.required_date,
                        priority:          pr.priority,
                        status:            pr.status,
                    }));
                    this.pagination = {
                        current_page: paged.current_page,
                        last_page:    paged.last_page,
                        from:         paged.from || 0,
                        to:           paged.to   || 0,
                        total:        paged.total || 0,
                    };
                }
            } catch (e) {
                console.error(e);
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        async viewDetails(id) {
            this.selectedPR = null;
            this.showDetailsModal = true;
            try {
                const json = await this.apiFetch('/api/v1/purchase-requisitions/' + id);
                if (json.success) this.selectedPR = json.data.purchase_requisition;
            } catch (e) { console.error(e); }
        },

        async sendForApproval(pr) {
            if (!confirm('Send "' + pr.pr_number + '" for approval?')) return;
            try {
                const json = await this.apiFetch('/api/v1/purchase-requisitions/' + pr.id + '/submit', {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken() },
                });
                if (json.success) {
                    this.showToast('PR submitted for approval.', 'success');
                    this.showDetailsModal = false;
                    await Promise.all([this.loadData(), this.loadStats()]);
                } else {
                    this.showToast(json.message || 'Failed to submit.', 'error');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
        },

        async approvePR(pr) {
            if (!confirm('Approve PR "' + pr.pr_number + '"?')) return;
            try {
                const json = await this.apiFetch('/api/v1/purchase-requisitions/' + pr.id + '/approve', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                if (json.success) {
                    this.showToast('PR approved successfully.', 'success');
                    this.showDetailsModal = false;
                    await Promise.all([this.loadData(), this.loadStats()]);
                } else {
                    this.showToast(json.message || 'Failed to approve.', 'error');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
        },

        openRejectModal(pr) {
            this.rejectingPR = pr;
            this.rejectReason = '';
            this.showRejectModal = true;
        },

        async confirmReject() {
            if (!this.rejectReason.trim()) return;
            try {
                const json = await this.apiFetch('/api/v1/purchase-requisitions/' + this.rejectingPR.id + '/reject', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                    body: JSON.stringify({ rejection_reason: this.rejectReason }),
                });
                if (json.success) {
                    this.showToast('PR rejected.', 'success');
                    this.showRejectModal = false;
                    this.showDetailsModal = false;
                    await Promise.all([this.loadData(), this.loadStats()]);
                } else {
                    this.showToast(json.message || 'Failed to reject.', 'error');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
        },

        resetFilters() {
            this.filters = { search: '', status: '', priority: '', page: 1 };
            this.loadData();
        },

        changePage(page) {
            this.filters.page = page;
            this.loadData();
        },

        formatDate(val) {
            if (!val) return '—';
            const d = new Date(val);
            return isNaN(d) ? val : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        async loadVendors() {
            try {
                const json = await this.apiFetch('/api/v1/vendors?per_page=1000&is_active=1');
                if (json.success && json.data) {
                    this.vendors = json.data.vendors?.data || json.data.vendors || [];
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
            }
        },

        async openSendToVendorModal(pr) {
            this.sendingPR = null;
            this.selectedVendors = [];
            this.emailMessage = '';
            this.showSendToVendorModal = true;
            
            // Load full PR details to get suggested vendor
            try {
                const json = await this.apiFetch('/api/v1/purchase-requisitions/' + pr.id);
                if (json.success) {
                    this.sendingPR = json.data.purchase_requisition;
                    
                    // Pre-select suggested vendor if available
                    if (this.sendingPR.suggested_vendor && this.sendingPR.suggested_vendor.id) {
                        this.selectedVendors = [this.sendingPR.suggested_vendor.id];
                    }
                }
            } catch (e) {
                console.error('Error loading PR details:', e);
                this.sendingPR = pr; // Fallback to basic PR data
            }
        },

        async confirmSendToVendor() {
            if (this.selectedVendors.length === 0) {
                this.showToast('Please select at least one vendor', 'error');
                return;
            }

            this.sendingToVendor = true;
            try {
                const json = await this.apiFetch(`/api/v1/purchase-requisitions/${this.sendingPR.id}/send-to-vendor`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({
                        vendor_ids: this.selectedVendors,
                        message: this.emailMessage
                    })
                });
                
                if (json.success) {
                    this.showToast('PR sent to vendor(s) successfully via email', 'success');
                    this.showSendToVendorModal = false;
                    await this.loadData();
                } else {
                    this.showToast(json.message || 'Failed to send PR to vendor', 'error');
                }
            } catch (e) {
                console.error('Error sending to vendor:', e);
                this.showToast('Network error. Please try again.', 'error');
            } finally {
                this.sendingToVendor = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
    }
}
</script>
@endsection
