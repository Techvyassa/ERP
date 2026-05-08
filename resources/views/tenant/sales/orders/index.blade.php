@extends('layouts.sales')

@section('title', 'Sales Orders - ' . $organization->org_name)
@section('page-title', 'Sales Orders')

@section('content')
    <div x-data="salesOrdersApp()" x-init="init()">

        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-xl p-6 mb-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-4 rounded-xl">
                        <span class="material-symbols-outlined text-white text-4xl">receipt_long</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-1">Sales Orders</h2>
                        <p class="text-white/80 text-sm">Create orders, check FG stock, generate picklists, validate picks
                            and confirm dispatch.</p>
                    </div>
                </div>
                <button @click="openCreateModal()"
                    class="flex items-center gap-2 bg-white text-emerald-700 hover:bg-emerald-50 font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm">
                    <span class="material-symbols-outlined text-base">add</span>
                    New Sales Order
                </button>
            </div>
        </div>

        <!-- Workflow Steps -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
            <template x-for="(step, i) in workflowSteps" :key="i">
                <div class="bg-white rounded-xl border-2 p-3 text-center cursor-pointer transition-all"
                    :class="activeTab === i ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300'"
                    @click="activeTab = i">
                    <span class="material-symbols-outlined text-2xl mb-1 block"
                        :class="activeTab === i ? 'text-emerald-600' : 'text-gray-400'" x-text="step.icon"></span>
                    <p class="text-xs font-semibold leading-tight"
                        :class="activeTab === i ? 'text-emerald-700' : 'text-gray-600'" x-text="step.label"></p>
                    <p class="text-lg font-bold mt-1" :class="activeTab === i ? 'text-emerald-700' : 'text-gray-800'"
                        x-text="step.count"></p>
                </div>
            </template>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600" x-text="stats.total_open">0</p>
                <p class="text-xs text-gray-500 mt-1">Open Orders</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-green-600" x-text="stats.stock_available">0</p>
                <p class="text-xs text-gray-500 mt-1">Stock Available</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-amber-600" x-text="stats.stock_partial">0</p>
                <p class="text-xs text-gray-500 mt-1">Partial Stock</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-red-600" x-text="stats.due_today">0</p>
                <p class="text-xs text-gray-500 mt-1">Due Today</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-purple-600" x-text="stats.pending_dispatch">0</p>
                <p class="text-xs text-gray-500 mt-1">Pending Dispatch</p>
            </div>
        </div>

        <!-- Tab: All Orders (Tab 0) -->
        <div x-show="activeTab === 0">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Sales Order Creation</h3>
                    <div class="flex items-center gap-3">
                        <input x-model="search" @input.debounce.400ms="loadOrders()" type="text"
                            placeholder="Search SO number, customer..."
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <select x-model="statusFilter" @change="loadOrders()"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
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
                        <button @click="loadOrders()" class="text-gray-500 hover:text-gray-700">
                            <span class="material-symbols-outlined text-xl">refresh</span>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">SO Number</th>
                                <th class="px-5 py-3 text-left font-semibold">Customer</th>
                                <th class="px-5 py-3 text-left font-semibold">SO Date</th>
                                <th class="px-5 py-3 text-left font-semibold">Delivery Date</th>
                                <th class="px-5 py-3 text-right font-semibold">Grand Total</th>
                                <th class="px-5 py-3 text-center font-semibold">Status</th>
                                <th class="px-5 py-3 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="loading">
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">
                                        <span
                                            class="material-symbols-outlined animate-spin text-3xl">progress_activity</span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!loading && orders.length === 0">
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">No sales orders found.</td>
                                </tr>
                            </template>
                            <template x-for="so in orders" :key="so.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-5 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-5 py-3 text-gray-600"
                                        x-text="so.so_date ? new Date(so.so_date).toLocaleString('en-IN', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true}) : '—'">
                                    </td>
                                    <td class="px-5 py-3">
                                        <span
                                            :class="isOverdue(so.required_delivery_date, so.status) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                            x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-gray-800"
                                        x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN', {minimumFractionDigits:2})">
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span :class="statusBadge(so.status)"
                                                class="px-2 py-1 rounded text-xs font-bold" x-text="so.status"></span>
                                            <template x-if="so.stock_status && so.stock_status !== 'PENDING'">
                                                <span :class="stockBadge(so.stock_status)"
                                                    class="px-2 py-0.5 rounded text-xs font-semibold"
                                                    x-text="so.stock_status"></span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col items-start gap-1">
                                            <!-- View button for all statuses except CANCELLED -->
                                            <template x-if="so.status !== 'CANCELLED'">
                                                <button @click="generatePicklist(so.id)"
                                                    class="text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 px-3 py-1.5 rounded font-semibold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                                    View
                                                </button>
                                            </template>
                                            <!-- CANCELLED -->
                                            <template x-if="so.status === 'CANCELLED'">
                                                <span class="text-xs text-gray-400 italic">—</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100 text-sm text-gray-500">
                    <span x-text="'Showing ' + orders.length + ' of ' + pagination.total + ' orders'"></span>
                    <div class="flex gap-2">
                        <button @click="prevPage()" :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40">Prev</button>
                        <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: FG Stock Check (Tab 1) -->
        <div x-show="activeTab === 1">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="bg-blue-100 p-3 rounded-xl"><span
                            class="material-symbols-outlined text-blue-600 text-2xl">inventory</span></div>
                    <div>
                        <h3 class="font-bold text-gray-900">FG Stock Availability Check</h3>
                        <p class="text-sm text-gray-500">System checks FG stock at bin level against SO quantities.
                            Auto-generates picklist if available, raises hold alert if insufficient.</p>
                    </div>
                </div>

                <div class="mb-6 overflow-hidden rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-700">Finished Goods Stock</h4>
                            <p class="text-[11px] text-gray-500">Inventory snapshot across all buckets for sales planning
                            </p>
                        </div>
                        <button @click="loadFgStock()" class="text-emerald-600 hover:text-emerald-700">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Product</th>
                                    <th class="px-4 py-3 text-right">Available</th>
                                    <th class="px-4 py-3 text-right">QC Hold</th>
                                    <th class="px-4 py-3 text-right">Reserved</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="fgStockLoading">
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                            <span
                                                class="material-symbols-outlined animate-spin text-2xl">progress_activity</span>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="!fgStockLoading && fgStock.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">No finished goods stock
                                            found.</td>
                                    </tr>
                                </template>
                                <template x-for="item in fgStock" :key="item.product_id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900" x-text="item.product_name"></div>
                                            <div class="text-[11px] font-semibold uppercase tracking-widest text-gray-400"
                                                x-text="item.product_code"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700"
                                            x-text="formatQty(item.buckets?.AVAILABLE ?? 0)"></td>
                                        <td class="px-4 py-3 text-right font-semibold text-amber-600"
                                            x-text="formatQty(item.buckets?.QC_HOLD ?? 0)"></td>
                                        <td class="px-4 py-3 text-right font-semibold text-blue-600"
                                            x-text="formatQty(item.buckets?.RESERVED ?? 0)"></td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900"
                                            x-text="formatQty(item.total_on_hand) + ' ' + (item.uom_code || '')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">SO Number</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-left">Delivery Date</th>
                                <th class="px-4 py-3 text-center">Stock Status</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="stockCheckOrders.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No orders pending stock
                                        check.</td>
                                </tr>
                            </template>
                            <template x-for="so in stockCheckOrders" :key="so.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-4 py-3 text-gray-600"
                                        x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}) : '—'">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="stockBadge(so.stock_status)"
                                            class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="so.stock_status ?? 'PENDING'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="checkStock(so.id)"
                                            class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1 rounded font-semibold">Run
                                            Check</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Picklist (Tab 2) -->
        <div x-show="activeTab === 2">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="bg-purple-100 p-3 rounded-xl"><span
                            class="material-symbols-outlined text-purple-600 text-2xl">list_alt</span></div>
                    <div>
                        <h3 class="font-bold text-gray-900">Picklist Auto-Generation & HHT Dispatch</h3>
                        <p class="text-sm text-gray-500">On stock confirmation, system builds picklist with product, qty,
                            bin location and Carton IDs. Dispatched to store team's HHT.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">SO Number</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-center">Lines</th>
                                <th class="px-4 py-3 text-center">Picklist Status</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="picklistOrders.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No orders ready for picklist
                                        generation.</td>
                                </tr>
                            </template>
                            <template x-for="so in picklistOrders" :key="so.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-4 py-3 text-center text-gray-600" x-text="so.line_items_count ?? '—'">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="statusBadge(so.status)" class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="so.status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="generatePicklist(so.id)"
                                            class="text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 px-3 py-1 rounded font-semibold">Generate
                                            & Dispatch</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Pick Validation (Tab 3) -->
        <div x-show="activeTab === 3">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="bg-amber-100 p-3 rounded-xl"><span
                            class="material-symbols-outlined text-amber-600 text-2xl">qr_code_scanner</span></div>
                    <div>
                        <h3 class="font-bold text-gray-900">Pick Validation</h3>
                        <p class="text-sm text-gray-500">After all picklist items are scanned, system confirms total picked
                            qty matches SO. Short picks or missing cartons are flagged before progressing.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">SO Number</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-center">Picked / Ordered</th>
                                <th class="px-4 py-3 text-center">Discrepancies</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="pickingOrders.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No orders currently in
                                        picking stage.</td>
                                </tr>
                            </template>
                            <template x-for="so in pickingOrders" :key="so.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-4 py-3 text-center text-gray-600">
                                        <span x-text="(so.picked_qty ?? 0) + ' / ' + (so.ordered_qty ?? 0)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="(so.discrepancy_count ?? 0) > 0 ? 'text-red-600 font-bold' : 'text-green-600 font-semibold'"
                                            x-text="(so.discrepancy_count ?? 0) > 0 ? so.discrepancy_count + ' Issues' : 'Clear'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="statusBadge(so.status)" class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="so.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Dispatch (Tab 4) -->
        <div x-show="activeTab === 4">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="bg-teal-100 p-3 rounded-xl"><span
                            class="material-symbols-outlined text-teal-600 text-2xl">local_shipping</span></div>
                    <div>
                        <h3 class="font-bold text-gray-900">Dispatch Details & Confirmation</h3>
                        <p class="text-sm text-gray-500">Log vehicle, driver, courier partner and delivery date. Confirm
                            dispatch — auto-generates Delivery Challan & E-Way Bill. FG stock reduced in real time.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">SO Number</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-left">Delivery Date</th>
                                <th class="px-4 py-3 text-left">Vehicle / Driver</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="dispatchOrders.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No orders pending dispatch.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="so in dispatchOrders" :key="so.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-4 py-3 text-gray-600"
                                        x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}) : '—'">
                                    </td>
                                    <td class="px-4 py-3 text-gray-600"
                                        x-text="so.vehicle_number ? so.vehicle_number + ' / ' + so.driver_name : '—'"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="statusBadge(so.status)" class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="so.status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="openDispatchModal(so)"
                                            class="text-xs bg-teal-100 text-teal-700 hover:bg-teal-200 px-3 py-1 rounded font-semibold">Confirm
                                            Dispatch</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Dispatched (Tab 5) -->
        <div x-show="activeTab === 5">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="bg-green-100 p-3 rounded-xl"><span
                            class="material-symbols-outlined text-green-600 text-2xl">check_circle</span></div>
                    <div>
                        <h3 class="font-bold text-gray-900">Dispatched Orders</h3>
                        <p class="text-sm text-gray-500">Completed dispatches with Delivery Challan and E-Way Bill. Customer
                            notified via email/SMS.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">SO Number</th>
                                <th class="px-4 py-3 text-left">Customer</th>
                                <th class="px-4 py-3 text-left">Dispatched On</th>
                                <th class="px-4 py-3 text-left">Vehicle / Courier</th>
                                <th class="px-4 py-3 text-right">Grand Total</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="dispatchedOrders.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No dispatched orders yet.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="so in dispatchedOrders" :key="so.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-emerald-700" x-text="so.so_number"></td>
                                    <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                    <td class="px-4 py-3 text-gray-600"
                                        x-text="(so.dispatched_at ?? so.updated_at) ? new Date(so.dispatched_at ?? so.updated_at).toLocaleString('en-IN', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true}) : '—'">
                                    </td>
                                    <td class="px-4 py-3 text-gray-600"
                                        x-text="(so.vehicle_number ?? '—') + ' / ' + (so.logistics_partner ?? '—')"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                        x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN', {minimumFractionDigits:2})">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="statusBadge(so.status)" class="px-2 py-1 rounded text-xs font-bold"
                                            x-text="so.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Sales Order Modal -->
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">New Sales Order</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form @submit.prevent="submitSO()" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">Customer <span
                                        class="text-red-500">*</span></label>
                                <button type="button"
                                    @click="manualCustomer = !manualCustomer; form.customer_id = ''; form.customer_name_manual = ''"
                                    class="hidden text-xs text-emerald-600 hover:text-emerald-800 underline"
                                    x-text="manualCustomer ? '← Select from list' : 'Not in list? Add manually'"></button>
                            </div>
                            <!-- Dropdown from masters -->
                            <template x-if="!manualCustomer">
                                <div>
                                    <select x-model="form.customer_id" required @change="onCustomerSelect()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                        <option value="">Select customer...</option>
                                        <template x-if="customers.length > 0">
                                            <optgroup label="── Customers ──">
                                                <template x-for="c in customers" :key="c.id">
                                                    <option :value="c.id" x-text="c.label + (c.sub ? ' · ' + c.sub : '')">
                                                    </option>
                                                </template>
                                            </optgroup>
                                        </template>
                                        <template x-if="customers.filter(c => c.source === 'user').length > 0">
                                            <optgroup label="── Users ──">
                                                <template x-for="c in customers.filter(c => c.source === 'user')"
                                                    :key="c.id">
                                                    <option :value="c.id" x-text="c.label + (c.sub ? ' · ' + c.sub : '')">
                                                    </option>
                                                </template>
                                            </optgroup>
                                        </template>
                                    </select>
                                    <p x-show="customers.length === 0" class="text-xs text-amber-600 mt-1">No customers
                                        found in master list.</p>
                                </div>
                            </template>
                            <!-- Manual entry -->
                            <template x-if="manualCustomer">
                                <div>
                                    <input type="text" x-model="form.customer_name_manual" required
                                        placeholder="Enter customer name"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                    <p class="text-xs text-gray-500 mt-1">Customer will be saved to masters automatically.
                                    </p>
                                </div>
                            </template>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Required Delivery Date <span
                                    class="text-red-500">*</span></label>
                            <input type="date" x-model="form.required_delivery_date" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                            <select x-model="form.payment_terms"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                <option value="NET30">NET30</option>
                                <option value="NET60">NET60</option>
                                <option value="COD">COD</option>
                                <option value="ADVANCE">ADVANCE</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <input type="text" x-model="form.remarks" placeholder="Optional notes"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        </div>
                    </div>
                    <!-- Line Items -->
                    <div class="border-t pt-4 border-gray-200 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-medium text-gray-700">Products (<span
                                    x-text="form.line_items.length"></span>) <span class="text-red-500">*</span></p>
                            <button type="button" @click="addLine()"
                                class="text-xs text-emerald-600 hover:text-emerald-800 font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">add_circle</span> Add Line
                            </button>
                        </div>
                        <div class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 text-xs font-medium text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Product</th>
                                        <th class="px-3 py-2 text-left w-32">Qty</th>
                                        <th class="px-3 py-2 text-left w-32">UOM</th>
                                        <th class="px-3 py-2 text-left w-32">Unit Price</th>
                                        <th class="px-3 py-2 text-center w-12"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <template x-for="(line, idx) in form.line_items" :key="idx">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-3 py-3 align-top">
                                                <select x-model="line.product_id" required @change="onProductSelect(idx)"
                                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-300">
                                                    <option value="">Select product...</option>
                                                    <template x-if="products.length === 0">
                                                        <option disabled>No products in masters yet</option>
                                                    </template>
                                                    <template
                                                        x-for="p in products.filter(p => !form.line_items.some((l, i) => i !== idx && l.product_id == p.id))"
                                                        :key="p.id">
                                                        <option :value="p.id" x-text="productOptionLabel(p)"></option>
                                                    </template>
                                                </select>
                                                <template x-if="selectedProduct(line.product_id)">
                                                    <div class="mt-1.5 flex items-center justify-between text-[11px]">
                                                        <span class="text-gray-500"
                                                            x-text="'Code: ' + (selectedProduct(line.product_id)?.product_code || 'NA')"></span>
                                                        <span :class="lineStockClass(line)" class="font-semibold"
                                                            x-text="lineStockText(line)"></span>
                                                    </div>
                                                </template>
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <input type="number" x-model="line.qty" placeholder="Qty" min="0.001"
                                                    step="0.001" required
                                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-300">
                                                <template x-if="line.product_id && line.qty">
                                                    <p class="mt-1 whitespace-normal leading-tight text-[10px]"
                                                        :class="lineStockClass(line)" x-text="lineQtyHint(line)"></p>
                                                </template>
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <select x-model="line.uom_id" required
                                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-300">
                                                    <option value="">UOM</option>
                                                    <template x-for="u in uoms" :key="u.id">
                                                        <option :value="u.id" x-text="u.uom_code"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <div class="relative">
                                                    <span
                                                        class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-xs">₹</span>
                                                    <input type="number" x-model="line.unit_price" placeholder="Price"
                                                        min="0" step="0.01"
                                                        class="w-full border border-gray-300 rounded pl-5 pr-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-300">
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 align-top text-center">
                                                <button type="button" @click="removeLine(idx)"
                                                    class="text-red-400 hover:text-red-600 mt-1 transition-colors"
                                                    title="Remove line">
                                                    <span class="material-symbols-outlined text-base">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div x-show="formError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2" x-text="formError">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="formSubmitting"
                            class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 font-semibold">
                            <span x-text="formSubmitting ? 'Creating...' : 'Create Sales Order'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dispatch Modal -->
        <div x-show="showDispatchModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Confirm Dispatch</h3>
                    <button @click="showDispatchModal = false" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form @submit.prevent="submitDispatch()" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Number <span
                                    class="text-red-500">*</span></label>
                            <input type="text" x-model="dispatchForm.vehicle_number" required placeholder="MH12AB1234"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Driver Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" x-model="dispatchForm.driver_name" required placeholder="Driver name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Logistics Partner</label>
                            <input type="text" x-model="dispatchForm.logistics_partner" placeholder="Courier / transporter"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date <span
                                    class="text-red-500">*</span></label>
                            <input type="date" x-model="dispatchForm.expected_delivery_date" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                    </div>
                    <div class="bg-teal-50 border border-teal-200 rounded-lg p-3 text-sm text-teal-800">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">info</span>
                        Confirming dispatch will auto-generate Delivery Challan & E-Way Bill, reduce FG stock in real time,
                        and notify the customer.
                    </div>
                    <div x-show="dispatchError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2"
                        x-text="dispatchError"></div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showDispatchModal = false"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="dispatchSubmitting"
                            class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-semibold">
                            <span x-text="dispatchSubmitting ? 'Confirming...' : 'Confirm Dispatch'"></span>
                        </button>
                    </div>
                </form>
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
                                        Status: <span class="font-semibold" :class="statusBadge(picklistSO?.status)"
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
        function salesOrdersApp() {
            return {
                activeTab: 0,
                loading: false,
                orders: [],
                search: '',
                statusFilter: '',
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    total: 0
                },
                stats: {
                    total_open: 0,
                    stock_available: 0,
                    stock_partial: 0,
                    due_today: 0,
                    pending_dispatch: 0
                },
                workflowSteps: [{
                    label: 'All Orders',
                    icon: 'receipt_long',
                    count: 0
                },
                {
                    label: 'Stock Check',
                    icon: 'inventory',
                    count: 0
                },
                {
                    label: 'Picklist',
                    icon: 'list_alt',
                    count: 0
                },
                {
                    label: 'Pick Validation',
                    icon: 'qr_code_scanner',
                    count: 0
                },
                {
                    label: 'Dispatch',
                    icon: 'local_shipping',
                    count: 0
                },
                {
                    label: 'Dispatched',
                    icon: 'check_circle',
                    count: 0
                },
                ],
                stockCheckOrders: [],
                picklistOrders: [],
                pickingOrders: [],
                dispatchOrders: [],
                dispatchedOrders: [],
                fgStock: [],
                fgStockLoading: false,

                showCreateModal: false,
                soPrMap: {}, // { [so_id]: pr_number } — persisted in localStorage
                customers: [],
                products: [],
                uoms: [],
                manualCustomer: false,
                form: {
                    customer_id: '',
                    customer_name_manual: '',
                    required_delivery_date: '',
                    payment_terms: 'NET30',
                    remarks: '',
                    line_items: []
                },
                formError: '',
                formSubmitting: false,

                showDispatchModal: false,
                dispatchingSO: null,
                dispatchForm: {
                    vehicle_number: '',
                    driver_name: '',
                    logistics_partner: '',
                    expected_delivery_date: ''
                },
                dispatchError: '',
                dispatchSubmitting: false,

                showPicklistModal: false,
                picklistSO: null,
                picklistLines: [],
                picklistLoading: false,
                picklistConfirming: false,
                picklistError: '',

                token() {
                    return localStorage.getItem('access_token') || localStorage.getItem('auth_token') || '';
                },
                headers() {
                    return {
                        'Authorization': 'Bearer ' + this.token(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    };
                },

                async init() {
                    try {
                        this.soPrMap = JSON.parse(localStorage.getItem('so_pr_map') || '{}');
                    } catch (e) {
                        this.soPrMap = {};
                    }
                    await Promise.all([this.loadStats(), this.loadOrders(), this.loadFgStock()]);
                },

                formatQty(value) {
                    return Number(value ?? 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                async loadStats() {
                    try {
                        const res = await fetch('/api/v1/sales-orders/dashboard-stats', {
                            headers: this.headers()
                        });
                        const json = await res.json();
                        if (json.success && json.data) {
                            const s = json.data.stats ?? json.data;
                            this.stats = {
                                total_open: s.total_open ?? 0,
                                stock_available: s.stock_available ?? 0,
                                stock_partial: s.stock_partial ?? 0,
                                due_today: s.due_today ?? 0,
                                pending_dispatch: s.pending_dispatch ?? 0
                            };
                            this.workflowSteps[0].count = s.total_open ?? 0;
                            this.workflowSteps[1].count = s.pending_stock_check ?? 0;
                            this.workflowSteps[2].count = s.pending_picklist ?? 0;
                            this.workflowSteps[3].count = s.in_picking ?? 0;
                            this.workflowSteps[4].count = s.pending_dispatch ?? 0;
                            this.workflowSteps[5].count = s.dispatched_today ?? 0;
                        }
                    } catch (e) {
                        console.warn('Stats load failed', e);
                    }
                },

                async loadFgStock() {
                    this.fgStockLoading = true;
                    try {
                        const res = await fetch('/api/v1/sales-orders/fg-stock', {
                            headers: this.headers()
                        });
                        const json = await res.json();
                        this.fgStock = json.success ? (Array.isArray(json.data) ? json.data : []) : [];
                    } catch (e) {
                        console.warn('FG stock load failed', e);
                        this.fgStock = [];
                    }
                    this.fgStockLoading = false;
                },

                async loadOrders() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            per_page: 20,
                            page: this.pagination.current_page
                        });
                        if (this.search) params.append('search', this.search);
                        if (this.statusFilter) params.append('status', this.statusFilter);
                        const res = await fetch('/api/v1/sales-orders?' + params, {
                            headers: this.headers()
                        });
                        const json = await res.json();
                        if (json.success) {
                            const d = json.data;
                            this.orders = d.data ?? d;
                            this.pagination = {
                                current_page: d.current_page ?? 1,
                                last_page: d.last_page ?? 1,
                                total: d.total ?? this.orders.length
                            };
                            this.partitionOrders(this.orders);
                        }
                    } catch (e) {
                        console.warn('Orders load failed', e);
                    }
                    this.loading = false;
                },

                partitionOrders(all) {
                    this.stockCheckOrders = all.filter(o => ['DRAFT', 'CONFIRMED'].includes(o.status));
                    this.picklistOrders = all.filter(o => o.status === 'STOCK_CHECKED' && o.stock_status === 'AVAILABLE');
                    this.pickingOrders = all.filter(o => o.status === 'PICKING');
                    this.dispatchOrders = all.filter(o => o.status === 'PACKED');
                    this.dispatchedOrders = all.filter(o => ['DISPATCHED', 'DELIVERED'].includes(o.status));
                },

                prevPage() {
                    if (this.pagination.current_page > 1) {
                        this.pagination.current_page--;
                        this.loadOrders();
                    }
                },
                nextPage() {
                    if (this.pagination.current_page < this.pagination.last_page) {
                        this.pagination.current_page++;
                        this.loadOrders();
                    }
                },

                async openCreateModal() {
                    this.form = {
                        customer_id: '',
                        required_delivery_date: '',
                        payment_terms: 'NET30',
                        remarks: '',
                        line_items: [this.emptyLine()]
                    };
                    this.formError = '';
                    this.formSubmitting = false;
                    const h = this.headers();
                    try {
                        const [cRes, pRes, uRes] = await Promise.all([
                            fetch('/api/v1/lookup/customers?active_only=1', {
                                headers: h
                            }),
                            fetch('/api/v1/lookup/products', {
                                headers: h
                            }),
                            fetch('/api/v1/lookup/uoms', {
                                headers: h
                            })
                        ]);
                        const [cJson, pJson, uJson] = await Promise.all([cRes.json(), pRes.json(), uRes.json()]);
                        this.customers = cJson.success ? (Array.isArray(cJson.data) ? cJson.data.filter(c => c.source === 'customer') : (cJson.data?.data ?? []).filter(c => c.source === 'customer')) : [];
                        this.products = pJson.success ? (Array.isArray(pJson.data) ? pJson.data : (pJson.data?.products ?? pJson.data?.data ?? [])) : [];
                        this.uoms = uJson.success ? (Array.isArray(uJson.data) ? uJson.data : (uJson.data?.data ?? [])) : [];
                    } catch (e) {
                        this.customers = [];
                        this.products = [];
                        this.uoms = [];
                    }
                    this.showCreateModal = true;
                },

                emptyLine() {
                    return {
                        product_id: '',
                        qty: '',
                        uom_id: '',
                        unit_price: 0,
                        discount_percent: 0
                    };
                },
                addLine() {
                    this.form.line_items.push(this.emptyLine());
                },
                removeLine(idx) {
                    if (this.form.line_items.length > 1) this.form.line_items.splice(idx, 1);
                },
                onCustomerSelect() { },
                selectedProduct(productId) {
                    return this.products.find(p => p.id == productId) || null;
                },
                productOptionLabel(product) {
                    const base = product.product_name + (product.product_code ? ' (' + product.product_code + ')' : '');
                    const stock = Number(product.current_stock ?? 0).toLocaleString('en-IN', {
                        maximumFractionDigits: 3
                    });
                    return base + ' - Stock: ' + stock;
                },
                onProductSelect(idx) {
                    const line = this.form.line_items[idx];
                    const product = this.selectedProduct(line.product_id);
                    if (product) {
                        if (product.standard_cost) line.unit_price = parseFloat(product.standard_cost);
                        if (product.pack_uom_id && !line.uom_id) line.uom_id = product.pack_uom_id;
                    }
                },
                lineAvailableStock(line) {
                    return Number(this.selectedProduct(line.product_id)?.current_stock ?? 0);
                },
                lineStockText(line) {
                    const product = this.selectedProduct(line.product_id);
                    if (!product) return '';
                    const available = this.lineAvailableStock(line).toLocaleString('en-IN', {
                        maximumFractionDigits: 3
                    });
                    const total = Number(product.qty_on_hand ?? 0).toLocaleString('en-IN', {
                        maximumFractionDigits: 3
                    });
                    const reserved = Number(product.qty_reserved ?? 0).toLocaleString('en-IN', {
                        maximumFractionDigits: 3
                    });
                    return 'Available: ' + available + ' | Reserved: ' + reserved + ' | Total: ' + total;
                },
                lineQtyHint(line) {
                    const available = this.lineAvailableStock(line);
                    const requested = Number(line.qty || 0);
                    if (requested <= 0) return '';
                    if (available >= requested) return 'Enough FG stock for this line.';
                    if (available > 0) return 'Only ' + available.toLocaleString('en-IN', {
                        maximumFractionDigits: 3
                    }) + ' available right now.';
                    return 'No FG stock available right now.';
                },
                lineStockClass(line) {
                    const available = this.lineAvailableStock(line);
                    const requested = Number(line.qty || 0);
                    if (!requested) return available > 0 ? 'text-green-600' : 'text-red-600';
                    if (available >= requested) return 'text-green-600';
                    if (available > 0) return 'text-amber-600';
                    return 'text-red-600';
                },

                async submitSO() {
                    this.formError = '';
                    if (!this.form.customer_id || !this.form.required_delivery_date) {
                        this.formError = 'Customer and delivery date are required.';
                        return;
                    }
                    if (this.form.line_items.some(l => !l.product_id || !l.qty || !l.uom_id)) {
                        this.formError = 'All lines need product, qty and UOM.';
                        return;
                    }
                    this.formSubmitting = true;
                    try {
                        let customerId = null;

                        if (this.manualCustomer) {
                            // Create new customer from manual input
                            const cRes = await fetch('/api/v1/lookup/customers', {
                                method: 'POST',
                                headers: this.headers(),
                                body: JSON.stringify({
                                    customer_name: this.form.customer_name_manual.trim()
                                })
                            });
                            const cJson = await cRes.json();
                            if (!cJson.success) {
                                this.formError = 'Failed to save customer: ' + (cJson.message || 'Unknown error');
                                this.formSubmitting = false;
                                return;
                            }
                            customerId = cJson.data?.id;
                        } else if (this.form.customer_id.startsWith('u_')) {
                            // Selected a user — auto-create customer record from user data
                            const selected = this.customers.find(c => c.id === this.form.customer_id);
                            const cRes = await fetch('/api/v1/lookup/customers', {
                                method: 'POST',
                                headers: this.headers(),
                                body: JSON.stringify({
                                    customer_name: selected?.label ?? 'Unknown'
                                })
                            });
                            const cJson = await cRes.json();
                            if (!cJson.success) {
                                this.formError = 'Failed to create customer from user: ' + (cJson.message || 'Unknown error');
                                this.formSubmitting = false;
                                return;
                            }
                            customerId = cJson.data?.id;
                        } else {
                            // Selected an existing customer — strip the 'c_' prefix
                            customerId = parseInt(this.form.customer_id.replace('c_', ''));
                        }

                        const payload = {
                            ...this.form,
                            customer_id: customerId
                        };
                        delete payload.customer_name_manual;
                        const res = await fetch('/api/v1/sales-orders', {
                            method: 'POST',
                            headers: this.headers(),
                            body: JSON.stringify(payload)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showCreateModal = false;
                            await this.loadOrders();
                            await this.loadStats();
                            await this.loadFgStock();
                        } else {
                            this.formError = json.message || 'Failed to create sales order.';
                        }
                    } catch (e) {
                        this.formError = 'Network error. Please try again.';
                    }
                    this.formSubmitting = false;
                },

                async confirmSO(id) {
                    if (!confirm('Confirm this Sales Order?')) return;
                    await fetch('/api/v1/sales-orders/' + id + '/confirm', {
                        method: 'PATCH',
                        headers: this.headers()
                    });
                    await this.loadOrders();
                    await this.loadStats();
                },

                async checkStock(id) {
                    if (!confirm('Run FG stock availability check?')) return;
                    const res = await fetch('/api/v1/sales-orders/' + id + '/check-stock', {
                        method: 'PATCH',
                        headers: this.headers()
                    });
                    const json = await res.json();
                    if (json.success) alert(json.message);
                    else alert('Failed: ' + (json.message || 'Unknown error'));
                    await this.loadOrders();
                    await this.loadStats();
                    await this.loadFgStock();
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
                        const res = await fetch('/api/v1/sales-orders/' + id, {
                            headers: this.headers()
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.picklistSO = json.data;
                            const lines = json.data.line_items ?? [];

                            // For each line, fetch bin locations with available stock
                            const enriched = await Promise.all(lines.map(async (line) => {
                                try {
                                    const bRes = await fetch('/api/v1/lookup/stock-bins?product_id=' + line.product_id, {
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
                            this.picklistError = json.message || 'Failed to load SO details.';
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
                        const res = await fetch('/api/v1/sales-orders/' + this.picklistSO.id + '/generate-picklist', {
                            method: 'POST',
                            headers: this.headers()
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showPicklistModal = false;
                            await this.loadOrders();
                            await this.loadStats();
                            await this.loadFgStock();
                        } else {
                            this.picklistError = json.message || 'Failed to generate picklist.';
                        }
                    } catch (e) {
                        this.picklistError = 'Network error. Please try again.';
                    }
                    this.picklistConfirming = false;
                },

                openDispatchModal(so) {
                    this.dispatchingSO = so;
                    this.dispatchForm = {
                        vehicle_number: '',
                        driver_name: '',
                        logistics_partner: '',
                        expected_delivery_date: so.required_delivery_date ?? ''
                    };
                    this.dispatchError = '';
                    this.dispatchSubmitting = false;
                    this.showDispatchModal = true;
                },

                async submitDispatch() {
                    this.dispatchError = '';
                    if (!this.dispatchForm.vehicle_number || !this.dispatchForm.driver_name || !this.dispatchForm.expected_delivery_date) {
                        this.dispatchError = 'Vehicle number, driver name and delivery date are required.';
                        return;
                    }
                    this.dispatchSubmitting = true;
                    try {
                        const res = await fetch('/api/v1/sales-orders/' + this.dispatchingSO.id + '/dispatch', {
                            method: 'PATCH',
                            headers: this.headers(),
                            body: JSON.stringify(this.dispatchForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showDispatchModal = false;
                            alert('Dispatch confirmed. Delivery Challan & E-Way Bill generated.');
                            await this.loadOrders();
                            await this.loadStats();
                            await this.loadFgStock();
                        } else {
                            this.dispatchError = json.message || 'Dispatch failed.';
                        }
                    } catch (e) {
                        this.dispatchError = 'Network error. Please try again.';
                    }
                    this.dispatchSubmitting = false;
                },

                async cancelSO(id) {
                    if (!confirm('Cancel this Sales Order?')) return;
                    await fetch('/api/v1/sales-orders/' + id + '/cancel', {
                        method: 'PATCH',
                        headers: this.headers()
                    });
                    await this.loadOrders();
                    await this.loadStats();
                    await this.loadFgStock();
                },

                async createPRFromSO(so) {
                    if (!confirm('Create a Purchase Requisition (Draft) for the unavailable items in ' + so.so_number + '?')) return;
                    try {
                        // Fetch SO details to get line items with product names
                        const res = await fetch('/api/v1/sales-orders/' + so.id, {
                            headers: this.headers()
                        });
                        const json = await res.json();
                        if (!json.success) {
                            alert('Failed to load SO details.');
                            return;
                        }

                        const soDetail = json.data;
                        const lines = soDetail.line_items ?? [];

                        // Decode auth_user_id from JWT
                        let authUserId = null;
                        try {
                            const payload = JSON.parse(atob(this.token().split('.')[1]));
                            authUserId = payload.sub ?? payload.user_id ?? payload.id ?? null;
                        } catch (e) { }

                        // Build PR line items from SO lines
                        const prLines = lines.map(line => ({
                            item_name: line.product?.product_name ?? ('Product #' + line.product_id),
                            description: 'Auto-generated from SO ' + so.so_number,
                            quantity: parseFloat(line.qty),
                            uom_id: line.uom_id,
                            material_id: null,
                            estimated_unit_price: line.unit_price ? parseFloat(line.unit_price) : null,
                            purpose: 'Stock replenishment for Sales Order ' + so.so_number,
                        }));

                        const payload = {
                            auth_user_id: authUserId,
                            required_date: so.required_delivery_date ?
                                so.required_delivery_date.split('T')[0] : new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
                            priority: 'HIGH',
                            status: 'DRAFT',
                            justification: 'FG stock unavailable for Sales Order ' + so.so_number,
                            remarks: 'Auto-created from SO ' + so.so_number + ' due to UNAVAILABLE stock.',
                            line_items: prLines,
                        };

                        const prRes = await fetch('/api/v1/purchase-requisitions', {
                            method: 'POST',
                            headers: this.headers(),
                            body: JSON.stringify(payload)
                        });
                        const prJson = await prRes.json();

                        if (prJson.success) {
                            const prNumber = prJson.data?.purchase_requisition?.pr_number ?? 'PR';
                            this.soPrMap = {
                                ...this.soPrMap,
                                [so.id]: prNumber
                            };
                            localStorage.setItem('so_pr_map', JSON.stringify(this.soPrMap));
                        } else {
                            alert('Failed to create PR: ' + (prJson.message || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Error creating PR. Please try again.');
                        console.error(e);
                    }
                },

                isOverdue(date, status) {
                    return !['DELIVERED', 'CANCELLED'].includes(status) && date && new Date(date) < new Date(new Date().toDateString());
                },
                stockBadge(s) {
                    return {
                        AVAILABLE: 'bg-green-100 text-green-700',
                        PARTIAL: 'bg-amber-100 text-amber-700',
                        UNAVAILABLE: 'bg-red-100 text-red-700',
                        PENDING: 'bg-gray-100 text-gray-600'
                    }[s] ?? 'bg-gray-100 text-gray-600';
                },
                statusBadge(s) {
                    return {
                        DRAFT: 'bg-gray-100 text-gray-600',
                        CONFIRMED: 'bg-blue-100 text-blue-700',
                        STOCK_CHECKED: 'bg-indigo-100 text-indigo-700',
                        PICKING: 'bg-amber-100 text-amber-700',
                        PACKED: 'bg-purple-100 text-purple-700',
                        DISPATCHED: 'bg-teal-100 text-teal-700',
                        DELIVERED: 'bg-green-100 text-green-700',
                        CANCELLED: 'bg-red-100 text-red-700'
                    }[s] ?? 'bg-gray-100 text-gray-600';
                },
            }
        }
    </script>
@endsection