@extends('layouts.procurement')

@section('title', 'Purchase Requisitions')
@section('page-title', 'Purchase Requisitions')

@section('content')
<div x-data="purchaseRequisitionData()" x-init="loadData()">

    <!-- Toast Notification -->
    <div x-show="showToast" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm"
         style="display:none;">
        <div class="rounded-lg shadow-lg p-4 flex items-center gap-3"
             :class="toastType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
            <span class="material-symbols-outlined text-2xl"
                  :class="toastType === 'success' ? 'text-green-600' : 'text-red-600'"
                  x-text="toastType === 'success' ? 'check_circle' : 'error'"></span>
            <p class="text-sm font-medium"
               :class="toastType === 'success' ? 'text-green-800' : 'text-red-800'"
               x-text="toastMessage"></p>
        </div>
    </div>

    <!-- View Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="showViewModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto z-10">
            <div class="flex items-center justify-between p-6 border-b">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Purchase Requisition</p>
                    <p class="text-2xl font-bold text-blue-600 tracking-widest mt-1" x-text="viewData.pr_number"></p>
                </div>
                <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
            <div class="p-6 space-y-4" x-show="viewData.id">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="text-gray-500">PR Date</p><p class="font-medium" x-text="formatDate(viewData.pr_date)"></p></div>
                    <div><p class="text-gray-500">Required Date</p><p class="font-medium" x-text="formatDate(viewData.required_date)"></p></div>
                    <div><p class="text-gray-500">Status</p>
                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                              :class="{
                                  'bg-gray-100 text-gray-800': viewData.status === 'DRAFT',
                                  'bg-yellow-100 text-yellow-800': viewData.status === 'PENDING',
                                  'bg-green-100 text-green-800': viewData.status === 'APPROVED',
                                  'bg-red-100 text-red-800': viewData.status === 'REJECTED',
                                  'bg-blue-100 text-blue-800': viewData.status === 'CONVERTED'
                              }"
                              x-text="viewData.status"></span>
                    </div>
                    <div><p class="text-gray-500">Requested By</p><p class="font-medium" x-text="viewData.requested_by_name"></p></div>
                    <div><p class="text-gray-500">Department</p><p class="font-medium" x-text="viewData.department_name"></p></div>
                    <div><p class="text-gray-500">Priority</p>
                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                              :class="{
                                  'bg-red-100 text-red-800': viewData.priority === 'EMERGENCY',
                                  'bg-orange-100 text-orange-800': viewData.priority === 'HIGH',
                                  'bg-yellow-100 text-yellow-800': viewData.priority === 'MEDIUM',
                                  'bg-green-100 text-green-800': viewData.priority === 'LOW'
                              }"
                              x-text="viewData.priority"></span>
                    </div>
                    <div x-show="viewData.cost_center_code"><p class="text-gray-500">Cost Center</p><p class="font-medium" x-text="viewData.cost_center_code"></p></div>
                    <div x-show="viewData.budget_code"><p class="text-gray-500">Budget Code</p><p class="font-medium" x-text="viewData.budget_code"></p></div>
                    <div x-show="viewData.suggested_vendor_name"><p class="text-gray-500">Suggested Vendor</p><p class="font-medium" x-text="viewData.suggested_vendor_name"></p></div>
                </div>
                <div x-show="viewData.justification" class="border-t pt-4">
                    <p class="text-gray-500 text-sm">Justification</p>
                    <p class="text-sm mt-1" x-text="viewData.justification"></p>
                </div>
                <div x-show="viewData.remarks" class="border-t pt-4">
                    <p class="text-gray-500 text-sm">Remarks</p>
                    <p class="text-sm mt-1" x-text="viewData.remarks"></p>
                </div>
                <!-- Line Items -->
                <div class="border-t pt-4">
                    <p class="font-semibold text-gray-700 mb-3">Line Items</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs text-gray-500">#</th>
                                    <th class="px-3 py-2 text-left text-xs text-gray-500">Item</th>
                                    <th class="px-3 py-2 text-left text-xs text-gray-500">Qty</th>
                                    <th class="px-3 py-2 text-left text-xs text-gray-500">UOM</th>
                                    <th class="px-3 py-2 text-right text-xs text-gray-500">Unit Price</th>
                                    <th class="px-3 py-2 text-right text-xs text-gray-500">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="line in viewData.line_items" :key="line.id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-500" x-text="line.line_number"></td>
                                        <td class="px-3 py-2 font-medium" x-text="line.item_name"></td>
                                        <td class="px-3 py-2" x-text="line.quantity"></td>
                                        <td class="px-3 py-2" x-text="line.uom?.uom_name || ''"></td>
                                        <td class="px-3 py-2 text-right" x-text="line.estimated_unit_price ? '₹ ' + parseFloat(line.estimated_unit_price).toFixed(2) : '—'"></td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="'₹ ' + ((parseFloat(line.quantity)||0) * (parseFloat(line.estimated_unit_price)||0)).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t flex justify-end gap-3">
                <button @click="showViewModal = false" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Close</button>
                <template x-if="viewData.status === 'DRAFT' || viewData.status === 'REJECTED'">
                    <button @click="sendForApproval(viewData.id)" 
                            class="px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">send</span>Send for Approval
                    </button>
                </template>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Purchase Requisitions</h2>
                <p class="text-gray-600 mt-1">Manage purchase requisition requests</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ url("/org/{$organization->org_slug}/procurement/purchase-requisition/create") }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <span class="material-symbols-outlined mr-2">add</span>Create Requisition
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" x-model="filters.search" @input.debounce.400ms="loadData()"
                       placeholder="Search by PR number..."
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                <select x-model="filters.status" @change="loadData()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="PENDING_APPROVAL">Pending Approval</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="CONVERTED">Converted to PO</option>
                </select>

                <select x-model="filters.priority" @change="loadData()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Priority</option>
                    <option value="LOW">Low</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="HIGH">High</option>
                    <option value="EMERGENCY">Emergency</option>
                </select>

                <button @click="resetFilters()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">refresh</span>Reset
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-3"></div>
                                    <p class="text-gray-500">Loading requisitions...</p>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && items.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">shopping_bag</span>
                                    <p class="text-gray-600">No purchase requisitions found.</p>
                                    <a href="{{ url("/org/{$organization->org_slug}/procurement/purchase-requisition/create") }}"
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mt-4">
                                        <span class="material-symbols-outlined mr-2">add</span>Create First Requisition
                                    </a>
                                </td>
                            </tr>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600" x-text="item.pr_number"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.requested_by_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.department_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="formatDate(item.required_date)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                          :class="{
                                              'bg-red-100 text-red-800': item.priority === 'EMERGENCY',
                                              'bg-orange-100 text-orange-800': item.priority === 'HIGH',
                                              'bg-yellow-100 text-yellow-800': item.priority === 'MEDIUM',
                                              'bg-green-100 text-green-800': item.priority === 'LOW'
                                          }"
                                          x-text="item.priority"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                          :class="{
                                              'bg-gray-100 text-gray-800': item.status === 'DRAFT',
                                              'bg-yellow-100 text-yellow-800': item.status === 'PENDING' || item.status === 'PENDING_APPROVAL',
                                              'bg-green-100 text-green-800': item.status === 'APPROVED',
                                              'bg-red-100 text-red-800': item.status === 'REJECTED',
                                              'bg-blue-100 text-blue-800': item.status === 'CONVERTED'
                                          }"
                                          x-text="item.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1">
                                    <button @click="viewItem(item.id)"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded transition-colors" title="View">
                                        <span class="material-symbols-outlined text-sm mr-1">visibility</span>View
                                    </button>
                                    <template x-if="item.status === 'DRAFT' || item.status === 'REJECTED'">
                                        <a :href="'{{ url("/org/{$organization->org_slug}/procurement/purchase-requisition") }}/' + item.id + '/edit'"
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-sm mr-1">edit</span>Edit
                                        </a>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t flex items-center justify-between text-sm text-gray-600">
                <span x-text="'Showing ' + pagination.from + ' to ' + pagination.to + ' of ' + pagination.total + ' records'"></span>
                <div class="flex gap-2">
                    <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">Previous</button>
                    <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function purchaseRequisitionData() {
    return {
        items: [],
        loading: false,
        showViewModal: false,
        viewData: {},
        filters: { search: '', status: '', priority: '', page: 1 },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
        showToast: false,
        toastMessage: '',
        toastType: 'success',

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search)   params.append('search', this.filters.search);
                if (this.filters.status)   params.append('status', this.filters.status);
                if (this.filters.priority) params.append('priority', this.filters.priority);
                params.append('page', this.filters.page);
                params.append('per_page', 15);

                const res = await fetch('/api/v1/purchase-requisitions?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();

                if (json.success) {
                    const paged = json.data;
                    this.items = (paged.data || []).map(pr => ({
                        id:               pr.id,
                        pr_number:        pr.pr_number,
                        requested_by_name: pr.requested_by
                            ? (pr.requested_by.first_name + ' ' + pr.requested_by.last_name)
                            : '—',
                        department_name:  pr.department?.dept_name || '—',
                        required_date:    pr.required_date,
                        priority:         pr.priority,
                        status:           pr.status,
                    }));
                    this.pagination = {
                        current_page: paged.current_page,
                        last_page:    paged.last_page,
                        from:         paged.from || 0,
                        to:           paged.to || 0,
                        total:        paged.total || 0,
                    };
                }
            } catch (error) {
                console.error('Failed to load requisitions:', error);
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        async viewItem(id) {
            this.viewData = {};
            this.showViewModal = true;
            try {
                const res = await fetch('/api/v1/purchase-requisitions/' + id, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success) {
                    const pr = json.data.purchase_requisition;
                    this.viewData = {
                        id:                   pr.id,
                        pr_number:            pr.pr_number,
                        pr_date:              pr.pr_date,
                        required_date:        pr.required_date,
                        status:               pr.status,
                        priority:             pr.priority,
                        cost_center_code:     pr.cost_center_code,
                        budget_code:          pr.budget_code,
                        justification:        pr.justification,
                        remarks:              pr.remarks,
                        requested_by_name:    pr.requested_by
                            ? (pr.requested_by.first_name + ' ' + pr.requested_by.last_name)
                            : '—',
                        department_name:      pr.department?.dept_name || '—',
                        suggested_vendor_name: pr.suggested_vendor?.vendor_name || '',
                        line_items:           pr.line_items || [],
                    };
                }
            } catch (e) {
                console.error('Failed to load PR details:', e);
            }
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

        async sendForApproval(id) {
            if (!confirm('Send this purchase requisition for approval?')) return;
            
            try {
                const res = await fetch('/api/v1/purchase-requisitions/' + id + '/submit', {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });
                
                const json = await res.json();
                
                if (json.success) {
                    this.toast('Purchase requisition sent for approval successfully', 'success');
                    this.showViewModal = false;
                    this.loadData();
                } else {
                    this.toast(json.message || 'Failed to send for approval', 'error');
                }
            } catch (e) {
                console.error('Failed to send for approval:', e);
                this.toast('Network error. Please try again.', 'error');
            }
        },

        toast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => { this.showToast = false; }, 3000);
        },
    }
}
</script>
@endsection
