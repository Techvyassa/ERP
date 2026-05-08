@extends('layouts.sales')

@section('title', 'Sales Approval - ' . $organization->org_name)
@section('page-title', 'Sales Approval')

@section('content')
<div x-data="salesApprovalApp()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                    <span class="material-symbols-outlined text-3xl">task_alt</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Sales Approval</h2>
                    <p class="text-white/80 text-sm">Review and approve pending sales orders</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-white/80 text-sm">Pending Approvals</p>
                <p class="text-4xl font-black" x-text="pendingCount">0</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Pending</span>
                <span class="material-symbols-outlined text-amber-600">pending</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.pending">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Approved Today</span>
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.approved_today">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Value</span>
                <span class="material-symbols-outlined text-blue-600">payments</span>
            </div>
            <p class="text-2xl font-black text-gray-900" x-text="'₹' + stats.total_value.toLocaleString()">₹0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Avg. Order Value</span>
                <span class="material-symbols-outlined text-purple-600">trending_up</span>
            </div>
            <p class="text-2xl font-black text-gray-900" x-text="'₹' + stats.avg_value.toLocaleString()">₹0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Status</label>
                <select x-model="statusFilter" @change="loadOrders()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                    <option value="DRAFT">Pending</option>
                    <option value="CONFIRMED">Approved</option>
                    <option value="">All</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Customer</label>
                <select x-model="customerFilter" @change="loadOrders()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Customers</option>
                    <template x-for="customer in customers" :key="customer.id">
                        <option :value="customer.id" x-text="customer.customer_name"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Search</label>
                <input type="text" x-model="search" @input="loadOrders()" placeholder="Search by SO number..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex items-end">
                <button @click="loadOrders()" class="w-full px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">refresh</span>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs" x-text="statusFilter === 'DRAFT' ? 'Pending Approvals' : statusFilter === 'CONFIRMED' ? 'Approved Orders' : 'All Orders'"></h3>
            <span class="text-xs font-bold text-gray-500" x-text="orders.length + ' orders'"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">SO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Delivery Date</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Created By</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 text-sm" x-text="order.so_number"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900 text-sm" x-text="order.customer?.customer_name || 'N/A'"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(order.so_date)"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(order.required_delivery_date)"></td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900" x-text="'₹' + parseFloat(order.grand_total).toLocaleString()"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-black rounded-full uppercase tracking-widest"
                                    :class="{
                                        'bg-amber-100 text-amber-700': order.status === 'DRAFT',
                                        'bg-emerald-100 text-emerald-700': order.status === 'CONFIRMED',
                                        'bg-blue-100 text-blue-700': order.status === 'STOCK_CHECKED',
                                        'bg-purple-100 text-purple-700': order.status === 'PICKING',
                                        'bg-gray-100 text-gray-700': order.status === 'CANCELLED'
                                    }"
                                    x-text="order.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="order.created_by_user?.first_name + ' ' + order.created_by_user?.last_name || 'N/A'"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="viewOrder(order)" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-xs font-bold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        View
                                    </button>
                                    <template x-if="order.status === 'DRAFT'">
                                        <button @click="approveOrder(order.id)" class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 text-xs font-bold flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                            Approve
                                        </button>
                                    </template>
                                    <template x-if="order.status === 'DRAFT'">
                                        <button @click="rejectOrder(order.id)" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-xs font-bold flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">cancel</span>
                                            Reject
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">task_alt</span>
                                <p class="text-sm font-bold" x-text="statusFilter === 'DRAFT' ? 'No pending approvals' : statusFilter === 'CONFIRMED' ? 'No approved orders' : 'No orders found'"></p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="loading">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50 animate-spin">progress_activity</span>
                                <p class="text-sm font-bold">Loading...</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Order Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.away="showViewModal = false">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-bold text-gray-900">Sales Order Details</h3>
                    <span x-show="selectedOrder" class="inline-block px-3 py-1 text-xs font-black rounded-full uppercase tracking-widest"
                        :class="{
                            'bg-amber-100 text-amber-700': selectedOrder?.status === 'DRAFT',
                            'bg-emerald-100 text-emerald-700': selectedOrder?.status === 'CONFIRMED',
                            'bg-blue-100 text-blue-700': selectedOrder?.status === 'STOCK_CHECKED',
                            'bg-purple-100 text-purple-700': selectedOrder?.status === 'PICKING',
                            'bg-gray-100 text-gray-700': selectedOrder?.status === 'CANCELLED'
                        }"
                        x-text="selectedOrder?.status"></span>
                </div>
                <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div x-show="selectedOrder" class="p-6">
                <!-- Order Header -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">SO Number</p>
                        <p class="text-lg font-bold text-gray-900" x-text="selectedOrder?.so_number"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Customer</p>
                        <p class="text-lg font-bold text-gray-900" x-text="selectedOrder?.customer?.customer_name"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Order Date</p>
                        <p class="font-semibold text-gray-900" x-text="formatDate(selectedOrder?.so_date)"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Delivery Date</p>
                        <p class="font-semibold text-gray-900" x-text="formatDate(selectedOrder?.required_delivery_date)"></p>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="mb-6">
                    <h4 class="font-bold text-gray-900 mb-3">Order Items</h4>
                    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Product</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">Unit Price</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="item in selectedOrder?.line_items" :key="item.id">
                                <tr>
                                    <td class="px-4 py-2 text-sm" x-text="item.product?.product_name"></td>
                                    <td class="px-4 py-2 text-sm text-right" x-text="item.qty"></td>
                                    <td class="px-4 py-2 text-sm text-right" x-text="'₹' + parseFloat(item.unit_price).toFixed(2)"></td>
                                    <td class="px-4 py-2 text-sm text-right font-bold" x-text="'₹' + parseFloat(item.line_total).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="font-semibold" x-text="'₹' + parseFloat(selectedOrder?.subtotal || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Tax:</span>
                        <span class="font-semibold" x-text="'₹' + parseFloat(selectedOrder?.tax_amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-300">
                        <span class="font-bold text-gray-900">Grand Total:</span>
                        <span class="font-bold text-lg text-emerald-600" x-text="'₹' + parseFloat(selectedOrder?.grand_total || 0).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Actions -->
                <div x-show="selectedOrder?.status === 'DRAFT'" class="flex gap-3 mt-6">
                    <button @click="approveOrder(selectedOrder.id)" class="flex-1 px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-bold flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">check_circle</span>
                        Approve Order
                    </button>
                    <button @click="rejectOrder(selectedOrder.id)" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">cancel</span>
                        Reject Order
                    </button>
                </div>
                
                <!-- Status Info for Non-Draft Orders -->
                <div x-show="selectedOrder?.status !== 'DRAFT'" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-blue-600">info</span>
                        <div>
                            <p class="font-bold text-blue-900">Order Status: <span x-text="selectedOrder?.status"></span></p>
                            <p class="text-sm text-blue-700" x-show="selectedOrder?.status === 'CONFIRMED'">This order has been approved and is ready for stock check.</p>
                            <p class="text-sm text-blue-700" x-show="selectedOrder?.status === 'CANCELLED'">This order has been cancelled.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function salesApprovalApp() {
    return {
        orders: [],
        customers: [],
        loading: false,
        search: '',
        statusFilter: 'DRAFT',
        customerFilter: '',
        stats: {
            pending: 0,
            approved_today: 0,
            total_value: 0,
            avg_value: 0
        },
        pendingCount: 0,
        showViewModal: false,
        selectedOrder: null,
        
        async init() {
            await this.loadCustomers();
            await this.loadStats();
            await this.loadOrders();
        },
        
        headers() {
            const token = localStorage.getItem('access_token');
            const orgSlug = '{{ $organization->org_slug }}';
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'X-Org-Slug': orgSlug
            };
        },
        
        async loadCustomers() {
            try {
                const response = await fetch('/api/v1/customers?per_page=1000', {
                    headers: this.headers()
                });
                const result = await response.json();
                if (result.success) {
                    this.customers = result.data?.data || result.data || [];
                    console.log(`Loaded ${this.customers.length} customers`);
                }
            } catch (e) {
                console.error('Failed to load customers', e);
            }
        },
        
        async loadStats() {
            try {
                const response = await fetch('/api/v1/sales-orders/dashboard-stats', {
                    headers: this.headers()
                });
                const result = await response.json();
                if (result.success) {
                    // Calculate stats from pending orders
                    this.stats.pending = result.data.stats.pending_stock_check || 0;
                    this.stats.approved_today = result.data.stats.dispatched_today || 0;
                    this.pendingCount = this.stats.pending;
                }
            } catch (e) {
                console.error('Failed to load stats', e);
            }
        },
        
        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    per_page: 100
                });
                
                // Add status filter
                if (this.statusFilter) {
                    params.append('status', this.statusFilter);
                }
                
                // Add customer filter
                if (this.customerFilter) {
                    params.append('customer_id', this.customerFilter);
                }
                
                // Add search filter
                if (this.search) {
                    params.append('search', this.search);
                }
                
                const response = await fetch(`/api/v1/sales-orders?${params}`, {
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    this.orders = result.data.data || [];
                    
                    // Calculate stats
                    this.stats.total_value = this.orders.reduce((sum, o) => sum + parseFloat(o.grand_total || 0), 0);
                    this.stats.avg_value = this.orders.length > 0 ? this.stats.total_value / this.orders.length : 0;
                    
                    // Update pending count only for DRAFT orders
                    if (this.statusFilter === 'DRAFT') {
                        this.pendingCount = this.orders.length;
                    }
                }
            } catch (e) {
                console.error('Failed to load orders', e);
            } finally {
                this.loading = false;
            }
        },
        
        async viewOrder(order) {
            try {
                const response = await fetch(`/api/v1/sales-orders/${order.id}`, {
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    this.selectedOrder = result.data;
                    this.showViewModal = true;
                }
            } catch (e) {
                console.error('Failed to load order details', e);
                alert('Error loading order details');
            }
        },
        
        async approveOrder(orderId) {
            if (!confirm('Approve this sales order?')) return;
            
            try {
                const response = await fetch(`/api/v1/sales-orders/${orderId}/confirm`, {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Sales order approved successfully!');
                    this.showViewModal = false;
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert('Failed to approve: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to approve order', e);
                alert('Error approving order');
            }
        },
        
        async rejectOrder(orderId) {
            if (!confirm('Reject this sales order? This will cancel the order.')) return;
            
            try {
                const response = await fetch(`/api/v1/sales-orders/${orderId}/cancel`, {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Sales order rejected successfully!');
                    this.showViewModal = false;
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert('Failed to reject: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to reject order', e);
                alert('Error rejecting order');
            }
        },
        
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endsection
