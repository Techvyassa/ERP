@extends('layouts.sales')

@section('title', 'Send Picklist To Store - ' . $organization->org_name)
@section('page-title', 'Send Picklist To Store')

@section('content')
<div x-data="picklistApp()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                    <span class="material-symbols-outlined text-3xl">checklist</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Send Picklist To Store</h2>
                    <p class="text-white/80 text-sm">Check stock availability and generate picklists for approved orders</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Ready for Stock Check</span>
                <span class="material-symbols-outlined text-blue-600">inventory</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.ready_for_check">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Stock Available</span>
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.stock_available">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Partial Stock</span>
                <span class="material-symbols-outlined text-amber-600">warning</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.partial_stock">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Picklists Sent</span>
                <span class="material-symbols-outlined text-purple-600">send</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="stats.picklists_sent">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Status</label>
                <select x-model="statusFilter" @change="loadOrders()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">All Statuses</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="STOCK_CHECKED">Stock Checked</option>
                    <option value="PICKING">Picking</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Stock Status</label>
                <select x-model="stockStatusFilter" @change="loadOrders()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">All</option>
                    <option value="AVAILABLE">Available</option>
                    <option value="PARTIAL">Partial</option>
                    <option value="UNAVAILABLE">Unavailable</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Search</label>
                <input type="text" x-model="search" @input="loadOrders()" placeholder="Search SO number, customer..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
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
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Sales Order Creation</h3>
            <span class="text-xs font-bold text-gray-500" x-text="orders.length + ' orders'"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">SO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">SO Date</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Delivery Date</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Grand Total</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-emerald-600 text-sm" x-text="order.so_number"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900 text-sm" x-text="order.customer?.customer_name || 'N/A'"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(order.so_date)"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(order.required_delivery_date)"></td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900" x-text="'₹' + parseFloat(order.grand_total).toLocaleString()"></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="inline-block px-3 py-1 text-xs font-black rounded-full uppercase tracking-widest"
                                        :class="{
                                            'bg-blue-100 text-blue-700': order.status === 'CONFIRMED',
                                            'bg-purple-100 text-purple-700': order.status === 'STOCK_CHECKED',
                                            'bg-indigo-100 text-indigo-700': order.status === 'PICKING'
                                        }"
                                        x-text="order.status"></span>
                                    <span x-show="order.stock_status" class="inline-block px-2 py-0.5 text-xs font-bold rounded uppercase"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': order.stock_status === 'AVAILABLE',
                                            'bg-amber-100 text-amber-700': order.stock_status === 'PARTIAL',
                                            'bg-red-100 text-red-700': order.stock_status === 'UNAVAILABLE'
                                        }"
                                        x-text="order.stock_status"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Check Stock button for CONFIRMED orders -->
                                    <template x-if="order.status === 'CONFIRMED'">
                                        <button @click="checkStock(order.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-bold flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">inventory</span>
                                            Check Stock
                                        </button>
                                    </template>
                                    
                                    <!-- Send Picklist button for STOCK_CHECKED with AVAILABLE stock -->
                                    <template x-if="order.status === 'STOCK_CHECKED' && order.stock_status === 'AVAILABLE'">
                                        <button @click="sendPicklist(order.id)" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-bold flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">send_to_mobile</span>
                                            Send Picklist to Store
                                        </button>
                                    </template>
                                    
                                    <!-- Sent badge for PICKING status (picklist already sent) -->
                                    <template x-if="order.status === 'PICKING'">
                                        <div class="flex items-center gap-2">
                                            <button @click="generatePicklist(order.id)" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 text-xs font-bold flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">visibility</span>
                                                View Picklist
                                            </button>
                                            <span class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-black flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                                Sent
                                            </span>
                                        </div>
                                    </template>
                                    
                                    <!-- Cancel button (only for CONFIRMED and STOCK_CHECKED) -->
                                    <template x-if="order.status !== 'PICKING'">
                                        <button @click="cancelOrder(order.id)" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-xs font-bold">
                                            Cancel
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">checklist</span>
                                <p class="text-sm font-bold">No orders found</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50 animate-spin">progress_activity</span>
                                <p class="text-sm font-bold">Loading...</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Picklist Preview Modal -->
    <div x-show="showPicklistModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-100 p-2 rounded-lg">
                        <span class="material-symbols-outlined text-purple-600">list_alt</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Picklist Preview</h3>
                        <p class="text-xs text-gray-500"
                            x-text="picklistSO ? picklistSO.so_number + ' · ' + (picklistSO.customer?.customer_name ?? '') : ''">
                        </p>
                    </div>
                </div>
                <button @click="showPicklistModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6">
                <!-- Loading -->
                <div x-show="picklistLoading" class="flex items-center justify-center py-10">
                    <span
                        class="material-symbols-outlined animate-spin text-3xl text-purple-500">progress_activity</span>
                </div>

                <!-- Picklist Lines -->
                <div x-show="!picklistLoading">
                    <div class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-800">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">info</span>
                        Review the picklist below. On confirmation, stock will be reserved and the picklist dispatched
                        to the store team's HHT.
                    </div>

                    <table class="w-full text-sm mb-6">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-center">Ordered Qty</th>
                                <th class="px-4 py-3 text-center">Available</th>
                                <th class="px-4 py-3 text-left">Bin Location</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="picklistLines.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">No line items found.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(line, i) in picklistLines" :key="line.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500" x-text="i + 1"></td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900"
                                            x-text="line.product?.product_name ?? '—'"></p>
                                        <p class="text-xs text-gray-500" x-text="line.product?.product_code ?? ''"></p>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-800"
                                        x-text="parseFloat(line.qty).toFixed(3) + ' ' + (line.uom?.uom_code ?? '')">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="parseFloat(line.available_qty) >= parseFloat(line.qty) ? 'text-green-600 font-bold' : 'text-amber-600 font-bold'"
                                            x-text="parseFloat(line.available_qty).toFixed(3)"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <template x-if="line.bin_locations && line.bin_locations.length > 0">
                                            <div class="space-y-1">
                                                <template x-for="bl in line.bin_locations" :key="bl.bin_code">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="material-symbols-outlined text-sm text-gray-400">shelves</span>
                                                        <span class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded"
                                                            x-text="bl.bin_code"></span>
                                                        <span class="text-xs text-gray-500"
                                                            x-text="'(' + parseFloat(bl.qty_available).toFixed(3) + ' avail)'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!line.bin_locations || line.bin_locations.length === 0">
                                            <span class="text-xs text-gray-400">No bin assigned</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="line.availability === 'AVAILABLE' ? 'bg-green-100 text-green-700' : line.availability === 'PARTIAL' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                            class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="line.availability"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="picklistError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2 mb-4"
                        x-text="picklistError"></div>

                    <div class="flex justify-between items-center gap-3">
                        <!-- Status indicator -->
                        <div class="text-xs text-gray-500 flex items-center gap-1">
                            <template x-if="picklistSO">
                                <span>
                                    Status: <span class="font-semibold px-2 py-1 rounded"
                                        :class="{
                                            'bg-blue-100 text-blue-700': picklistSO?.status === 'CONFIRMED',
                                            'bg-purple-100 text-purple-700': picklistSO?.status === 'STOCK_CHECKED',
                                            'bg-indigo-100 text-indigo-700': picklistSO?.status === 'PICKING'
                                        }"
                                        x-text="picklistSO?.status"></span>
                                </span>
                            </template>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showPicklistModal = false"
                                class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
                            <!-- STOCK_CHECKED: confirm generates picklist & sends to HHT -->
                            <template x-if="picklistSO && picklistSO.status === 'STOCK_CHECKED'">
                                <button type="button" @click="confirmGeneratePicklist()" :disabled="picklistConfirming"
                                    class="px-5 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-semibold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base">checklist</span>
                                    <span
                                        x-text="picklistConfirming ? 'Processing...' : 'Send Picklist to Store'"></span>
                                </button>
                            </template>
                            <!-- PICKING: already sent to HHT -->
                            <template x-if="picklistSO && picklistSO.status === 'PICKING'">
                                <button type="button" disabled
                                    class="px-5 py-2 text-sm bg-amber-100 text-amber-700 rounded-lg font-semibold flex items-center gap-2 cursor-default">
                                    <span class="material-symbols-outlined text-base">send_to_mobile</span>
                                    Sent to HHT
                                </button>
                            </template>
                            <!-- PACKED / DISPATCHED / DELIVERED: view only -->
                            <template
                                x-if="picklistSO && ['PACKED','DISPATCHED','DELIVERED'].includes(picklistSO.status)">
                                <button type="button" disabled
                                    class="px-5 py-2 text-sm bg-gray-100 text-gray-500 rounded-lg font-semibold flex items-center gap-2 cursor-default">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    View Only
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function picklistApp() {
    return {
        orders: [],
        loading: false,
        search: '',
        statusFilter: '',
        stockStatusFilter: '',
        stats: {
            ready_for_check: 0,
            stock_available: 0,
            partial_stock: 0,
            picklists_sent: 0
        },
        
        // Picklist modal state
        showPicklistModal: false,
        picklistSO: null,
        picklistLines: [],
        picklistLoading: false,
        picklistConfirming: false,
        picklistError: '',
        
        async init() {
            await this.loadStats();
            await this.loadOrders();
        },
        
        headers() {
            const token = localStorage.getItem('access_token');
            const orgSlug = '{{ $organization->org_slug }}';
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'X-Org-Slug': orgSlug,
                'Content-Type': 'application/json'
            };
        },
        
        async loadStats() {
            try {
                const response = await fetch('/api/v1/sales-orders/dashboard-stats', {
                    headers: this.headers()
                });
                const result = await response.json();
                if (result.success) {
                    this.stats.ready_for_check = result.data.stats.pending_stock_check || 0;
                    this.stats.stock_available = result.data.stats.stock_available || 0;
                    this.stats.partial_stock = result.data.stats.stock_partial || 0;
                    this.stats.picklists_sent = result.data.stats.in_picking || 0;
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
                
                // Add filters
                if (this.statusFilter) {
                    params.append('status', this.statusFilter);
                } else {
                    // Default: show CONFIRMED, STOCK_CHECKED, and PICKING orders
                    // We'll filter on client side since API doesn't support multiple statuses
                }
                
                if (this.search) {
                    params.append('search', this.search);
                }
                
                const response = await fetch(`/api/v1/sales-orders?${params}`, {
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    let allOrders = result.data.data || [];
                    
                    // Filter to show only relevant statuses
                    if (!this.statusFilter) {
                        allOrders = allOrders.filter(o => 
                            ['CONFIRMED', 'STOCK_CHECKED', 'PICKING'].includes(o.status)
                        );
                    }
                    
                    // Apply stock status filter
                    if (this.stockStatusFilter) {
                        allOrders = allOrders.filter(o => o.stock_status === this.stockStatusFilter);
                    }
                    
                    this.orders = allOrders;
                }
            } catch (e) {
                console.error('Failed to load orders', e);
            } finally {
                this.loading = false;
            }
        },
        
        async checkStock(orderId) {
            if (!confirm('Run FG stock availability check for this order?')) return;
            
            try {
                const response = await fetch(`/api/v1/sales-orders/${orderId}/check-stock`, {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message || 'Stock check completed successfully!');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert('Failed: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to check stock', e);
                alert('Error checking stock');
            }
        },
        
        async sendPicklist(orderId) {
            // Open modal to preview picklist before sending
            await this.generatePicklist(orderId);
        },
        
        async generatePicklist(id) {
            // Fetch SO details with line items and bin locations, then show preview modal
            this.picklistSO = null;
            this.picklistLines = [];
            this.picklistError = '';
            this.picklistConfirming = false;
            this.picklistLoading = true;
            this.showPicklistModal = true;

            try {
                const response = await fetch(`/api/v1/sales-orders/${id}`, {
                    headers: this.headers()
                });
                const result = await response.json();
                if (result.success) {
                    this.picklistSO = result.data;
                    const lines = result.data.line_items ?? [];

                    // For each line, fetch bin locations with available stock
                    const enriched = await Promise.all(lines.map(async (line) => {
                        try {
                            const bRes = await fetch(`/api/v1/lookup/stock-bins?product_id=${line.product_id}`, {
                                headers: this.headers()
                            });
                            const bJson = await bRes.json();
                            line.bin_locations = bJson.success ? bJson.data : [];
                        } catch (e) {
                            line.bin_locations = [];
                        }
                        return line;
                    }));

                    this.picklistLines = enriched;
                } else {
                    this.picklistError = result.message || 'Failed to load SO details.';
                }
            } catch (e) {
                this.picklistError = 'Network error loading picklist.';
            }
            this.picklistLoading = false;
        },

        async confirmGeneratePicklist() {
            if (!this.picklistSO) return;
            this.picklistConfirming = true;
            this.picklistError = '';
            try {
                const response = await fetch(`/api/v1/sales-orders/${this.picklistSO.id}/generate-picklist`, {
                    method: 'POST',
                    headers: this.headers()
                });
                const result = await response.json();
                if (result.success) {
                    this.showPicklistModal = false;
                    alert('Picklist generated and sent to store successfully!');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    this.picklistError = result.message || 'Failed to generate picklist.';
                }
            } catch (e) {
                this.picklistError = 'Network error. Please try again.';
            }
            this.picklistConfirming = false;
        },
        
        async cancelOrder(orderId) {
            if (!confirm('Cancel this sales order?')) return;
            
            try {
                const response = await fetch(`/api/v1/sales-orders/${orderId}/cancel`, {
                    method: 'PATCH',
                    headers: this.headers()
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Order cancelled successfully!');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert('Failed: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to cancel order', e);
                alert('Error cancelling order');
            }
        },
        
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }
    }
}
</script>
@endsection
