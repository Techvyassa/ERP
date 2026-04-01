@extends('layouts.warehouse')

@section('title', 'Warehouse Dashboard - ' . $organization->org_name)
@section('page-title', 'Warehouse Portal')

@section('content')
<div x-data="warehouseDashboard()" x-init="init()">
    <!-- Department Header -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-amber-500 p-4 rounded-xl">
                    <span class="material-symbols-outlined text-white text-4xl">warehouse</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Warehouse Portal</h2>
                    <p class="text-white/90">{{ $organization->org_name }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Expected</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.expectedToday">0</h3>
            <p class="text-sm text-gray-600 mb-2">Expected ASNs Today</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold" x-text="stats.arrivedToday">0</span>
                <span class="text-gray-500">already arrived</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">assignment_turned_in</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Pending</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingQC">0</h3>
            <p class="text-sm text-gray-600 mb-2">Pending QC</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">warehouse</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.unloadingBays">0</h3>
            <p class="text-sm text-gray-600 mb-2">Unloading Bays</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-500" x-text="stats.bayStatus">0 / 0</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">shopping_cart</span>
                </div>
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Outward</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="soStats.total_open">0</h3>
            <p class="text-sm text-gray-600 mb-2">Open Sales Orders</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-red-600 font-semibold" x-text="soStats.due_today">0</span>
                <span class="text-gray-500">due today</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="border-b border-gray-200 px-6">
            <nav class="flex gap-6 -mb-px">
                <button @click="activeTab = 'receiving'"
                    :class="activeTab === 'receiving' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap">
                    Live Receiving Queue
                </button>
                <button @click="activeTab = 'sales'; loadSalesOrders()"
                    :class="activeTab === 'sales' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2">
                    Sales Order Creation
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full" x-text="soStats.total_open"></span>
                </button>
            </nav>
        </div>

        <!-- Receiving Queue Tab -->
        <div x-show="activeTab === 'receiving'" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Live Receiving Queue</h3>
                <span class="text-xs font-semibold text-gray-500">Real-time updates</span>
            </div>
            <div class="space-y-4">
                <template x-for="item in receivingQueue" :key="item.id">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                                 :class="item.priorityClass" x-text="item.position"></div>
                            <div>
                                <p class="text-sm font-bold text-gray-900" x-text="item.vehicle"></p>
                                <p class="text-xs text-gray-500" x-text="item.details"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold text-white"
                                  :class="item.statusClass" x-text="item.status"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Sales Orders Tab -->
        <div x-show="activeTab === 'sales'" x-cloak class="p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Sales Order Creation</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Sales team creates orders with customer details, products, quantity and required delivery date. Triggers outward flow and stock availability check.</p>
                </div>
                <button @click="openCreateModal()"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-base">add</span>
                    New Sales Order
                </button>
            </div>

            <!-- SO Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-indigo-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-indigo-700" x-text="soStats.total_open">0</p>
                    <p class="text-xs text-indigo-600 mt-1">Open Orders</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-green-700" x-text="soStats.stock_available">0</p>
                    <p class="text-xs text-green-600 mt-1">Stock Available</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-amber-700" x-text="soStats.stock_partial">0</p>
                    <p class="text-xs text-amber-600 mt-1">Partial Stock</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-red-700" x-text="soStats.due_today">0</p>
                    <p class="text-xs text-red-600 mt-1">Due Today</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="flex items-center gap-3 mb-4">
                <input x-model="soSearch" @input.debounce.400ms="loadSalesOrders()"
                    type="text" placeholder="Search SO number..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <select x-model="soStatusFilter" @change="loadSalesOrders()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">All Statuses</option>
                    <option value="DRAFT">Draft</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="STOCK_CHECKED">Stock Checked</option>
                    <option value="PICKING">Picking</option>
                    <option value="PACKED">Packed</option>
                    <option value="DISPATCHED">Dispatched</option>
                    <option value="DELIVERED">Delivered</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <button @click="loadSalesOrders()" class="text-gray-500 hover:text-gray-700">
                    <span class="material-symbols-outlined text-xl">refresh</span>
                </button>
            </div>

            <!-- Sales Orders Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">SO Number</th>
                            <th class="px-4 py-3 text-left font-semibold">Customer</th>
                            <th class="px-4 py-3 text-left font-semibold">SO Date</th>
                            <th class="px-4 py-3 text-left font-semibold">Delivery Date</th>
                            <th class="px-4 py-3 text-right font-semibold">Grand Total</th>
                            <th class="px-4 py-3 text-center font-semibold">Stock</th>
                            <th class="px-4 py-3 text-center font-semibold">Status</th>
                            <th class="px-4 py-3 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="soLoading">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
                        </template>
                        <template x-if="!soLoading && salesOrders.length === 0">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No sales orders found.</td></tr>
                        </template>
                        <template x-for="so in salesOrders" :key="so.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-semibold text-indigo-700" x-text="so.so_number"></td>
                                <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                <td class="px-4 py-3 text-gray-600" x-text="so.so_date"></td>
                                <td class="px-4 py-3">
                                    <span :class="isOverdue(so.required_delivery_date, so.status) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                          x-text="so.required_delivery_date"></span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                    x-text="'₹' + parseFloat(so.grand_total).toLocaleString('en-IN', {minimumFractionDigits:2})"></td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="stockBadgeClass(so.stock_status)"
                                          class="px-2 py-1 rounded text-xs font-bold" x-text="so.stock_status"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="statusBadgeClass(so.status)"
                                          class="px-2 py-1 rounded text-xs font-bold" x-text="so.status"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <template x-if="so.status === 'DRAFT'">
                                            <button @click="confirmSO(so.id)"
                                                class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-2 py-1 rounded font-semibold transition-colors">
                                                Confirm
                                            </button>
                                        </template>
                                        <template x-if="so.status === 'CONFIRMED' || so.status === 'DRAFT'">
                                            <button @click="checkStock(so.id)"
                                                class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-2 py-1 rounded font-semibold transition-colors">
                                                Check Stock
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Sales Order Modal -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">New Sales Order</h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form @submit.prevent="submitSalesOrder()" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                        <select x-model="form.customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">Select customer...</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.customer_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Required Delivery Date <span class="text-red-500">*</span></label>
                        <input type="date" x-model="form.required_delivery_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                        <select x-model="form.payment_terms"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="NET30">NET30</option>
                            <option value="NET60">NET60</option>
                            <option value="COD">COD</option>
                            <option value="ADVANCE">ADVANCE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <input type="text" x-model="form.remarks" placeholder="Optional notes"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>

                <!-- Line Items -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Products <span class="text-red-500">*</span></label>
                        <button type="button" @click="addLine()"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">add</span> Add Line
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(line, idx) in form.line_items" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-lg p-2">
                                <div class="col-span-5">
                                    <select x-model="line.product_id" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <option value="">Select product...</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.product_name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" x-model="line.qty" placeholder="Qty" min="0.001" step="0.001" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                </div>
                                <div class="col-span-2">
                                    <select x-model="line.uom_id" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <option value="">UOM</option>
                                        <template x-for="u in uoms" :key="u.id">
                                            <option :value="u.id" x-text="u.uom_code"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" x-model="line.unit_price" placeholder="Price" min="0" step="0.01"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                </div>
                                <div class="col-span-1 flex justify-center">
                                    <button type="button" @click="removeLine(idx)" class="text-red-400 hover:text-red-600">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="formError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2" x-text="formError"></div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal = false"
                        class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="formSubmitting"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-semibold">
                        <span x-text="formSubmitting ? 'Creating...' : 'Create Sales Order'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function warehouseDashboard() {
    return {
        activeTab: 'receiving',
        stats: {
            expectedToday: 0, arrivedToday: 0, pendingQC: 0,
            unloadingBays: 0, bayStatus: '0 / 0', receiptsToday: 0
        },
        receivingQueue: [],
        // Sales Orders
        salesOrders: [],
        soStats: { total_open: 0, due_today: 0, stock_available: 0, stock_partial: 0, pending_dispatch: 0 },
        soLoading: false,
        soSearch: '',
        soStatusFilter: '',
        // Modal
        showCreateModal: false,
        customers: [],
        products: [],
        uoms: [],
        form: { customer_id: '', required_delivery_date: '', payment_terms: 'NET30', remarks: '', line_items: [] },
        formError: '',
        formSubmitting: false,

        init() {
            this.loadStats();
            this.loadReceivingQueue();
            this.loadSOStats();
        },

        async loadStats() {
            this.stats = { expectedToday: 24, arrivedToday: 8, pendingQC: 12, unloadingBays: 3, bayStatus: '3 / 5', receiptsToday: 15 };
        },

        async loadReceivingQueue() {
            this.receivingQueue = [
                { id: 1, position: '01', vehicle: 'Truck GJ-01-XX-1234', details: 'Vendor: Tata Steel • PO: #45021', status: 'UNLOADING', statusClass: 'bg-green-500', priorityClass: 'bg-green-500' },
                { id: 2, position: '02', vehicle: 'Vehicle MH-12-AB-9876', details: 'Vendor: Reliance Poly • PO: #45025', status: 'DOC VERIFICATION', statusClass: 'bg-amber-500', priorityClass: 'bg-amber-500' }
            ];
        },

        async loadSOStats() {
            try {
                const token = localStorage.getItem('auth_token');
                const res = await fetch('/api/v1/sales-orders/dashboard-stats', {
                    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success) this.soStats = json.data.stats;
            } catch (e) { console.error('SO stats error', e); }
        },

        async loadSalesOrders() {
            this.soLoading = true;
            try {
                const token = localStorage.getItem('auth_token');
                const params = new URLSearchParams({ per_page: 20 });
                if (this.soSearch) params.append('search', this.soSearch);
                if (this.soStatusFilter) params.append('status', this.soStatusFilter);
                const res = await fetch('/api/v1/sales-orders?' + params.toString(), {
                    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success) this.salesOrders = json.data.data;
            } catch (e) { console.error('SO load error', e); }
            this.soLoading = false;
        },

        async openCreateModal() {
            this.form = { customer_id: '', required_delivery_date: '', payment_terms: 'NET30', remarks: '', line_items: [this.emptyLine()] };
            this.formError = '';
            this.formSubmitting = false;
            await this.loadDropdowns();
            this.showCreateModal = true;
        },

        async loadDropdowns() {
            const token = localStorage.getItem('auth_token');
            const headers = { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' };
            const [cRes, pRes, uRes] = await Promise.all([
                fetch('/api/v1/customers?active_only=1', { headers }),
                fetch('/api/v1/products', { headers }),
                fetch('/api/v1/uoms', { headers }),
            ]);
            const [cJson, pJson, uJson] = await Promise.all([cRes.json(), pRes.json(), uRes.json()]);
            this.customers = cJson.success ? cJson.data : [];
            this.products  = pJson.success ? (pJson.data.data ?? pJson.data) : [];
            this.uoms      = uJson.success ? (uJson.data.data ?? uJson.data) : [];
        },

        emptyLine() {
            return { product_id: '', qty: '', uom_id: '', unit_price: 0, discount_percent: 0 };
        },

        addLine() { this.form.line_items.push(this.emptyLine()); },
        removeLine(idx) { if (this.form.line_items.length > 1) this.form.line_items.splice(idx, 1); },

        async submitSalesOrder() {
            this.formError = '';
            if (!this.form.customer_id || !this.form.required_delivery_date) {
                this.formError = 'Customer and delivery date are required.'; return;
            }
            if (this.form.line_items.some(l => !l.product_id || !l.qty || !l.uom_id)) {
                this.formError = 'All line items must have product, quantity and UOM.'; return;
            }
            this.formSubmitting = true;
            try {
                const token = localStorage.getItem('auth_token');
                const res = await fetch('/api/v1/sales-orders', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.success) {
                    this.showCreateModal = false;
                    await this.loadSalesOrders();
                    await this.loadSOStats();
                } else {
                    this.formError = json.message || Object.values(json.errors ?? {}).flat().join(', ');
                }
            } catch (e) { this.formError = 'Network error. Please try again.'; }
            this.formSubmitting = false;
        },

        async confirmSO(id) {
            if (!confirm('Confirm this Sales Order?')) return;
            const token = localStorage.getItem('auth_token');
            const res = await fetch('/api/v1/sales-orders/' + id + '/confirm', {
                method: 'PATCH',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (json.success) { await this.loadSalesOrders(); await this.loadSOStats(); }
        },

        async checkStock(id) {
            if (!confirm('Run stock availability check for this order?')) return;
            const token = localStorage.getItem('auth_token');
            const res = await fetch('/api/v1/sales-orders/' + id + '/check-stock', {
                method: 'PATCH',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (json.success) {
                alert('Stock check complete. Status: ' + json.stock_status);
                await this.loadSalesOrders(); await this.loadSOStats();
            }
        },

        isOverdue(date, status) {
            if (['DELIVERED', 'CANCELLED'].includes(status)) return false;
            return new Date(date) < new Date(new Date().toDateString());
        },

        stockBadgeClass(s) {
            return { AVAILABLE: 'bg-green-100 text-green-700', PARTIAL: 'bg-amber-100 text-amber-700', UNAVAILABLE: 'bg-red-100 text-red-700', PENDING: 'bg-gray-100 text-gray-600' }[s] ?? 'bg-gray-100 text-gray-600';
        },

        statusBadgeClass(s) {
            const map = { DRAFT: 'bg-gray-100 text-gray-600', CONFIRMED: 'bg-blue-100 text-blue-700', STOCK_CHECKED: 'bg-indigo-100 text-indigo-700', PICKING: 'bg-amber-100 text-amber-700', PACKED: 'bg-purple-100 text-purple-700', DISPATCHED: 'bg-teal-100 text-teal-700', DELIVERED: 'bg-green-100 text-green-700', CANCELLED: 'bg-red-100 text-red-700' };
            return map[s] ?? 'bg-gray-100 text-gray-600';
        },
    }
}
</script>
@endsection
