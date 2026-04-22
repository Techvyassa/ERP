@extends('layouts.sales')

@section('title', 'Sales Dashboard - ' . $organization->org_name)
@section('page-title', 'Sales Portal')

@section('content')
<div x-data="salesDashboard()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-white text-4xl">storefront</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Sales Portal</h2>
                <p class="text-white/90">{{ $organization->org_name }}</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl">receipt_long</span>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Open</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.openOrders">0</h3>
            <p class="text-sm text-gray-600">Open Sales Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Pending</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingDispatch">0</h3>
            <p class="text-sm text-gray-600">Pending Dispatch</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">group</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.activeCustomers">0</h3>
            <p class="text-sm text-gray-600">Active Customers</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-yellow-600 text-2xl">description</span>
                </div>
                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">Unpaid</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.unpaidInvoices">0</h3>
            <p class="text-sm text-gray-600">Unpaid Invoices</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-emerald-500 hover:shadow-xl transition-all cursor-pointer group p-6"
            @click="window.location.href = '/org/{{ $organization->org_slug }}/sales/orders'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-emerald-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl">receipt_long</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Sales Orders</h4>
                    <p class="text-xs text-gray-600">Create & manage orders</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create and track sales orders from customer request through dispatch and delivery.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-emerald-600" x-text="stats.openOrders + ' Open'">0 Open</span>
                <span class="material-symbols-outlined text-emerald-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6"
            @click="window.location.href = '/org/{{ $organization->org_slug }}/sales/customers'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">group</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Customers</h4>
                    <p class="text-xs text-gray-600">Customer master data</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage customer profiles, contacts, credit limits, and order history.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.activeCustomers + ' Active'">0 Active</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 hover:shadow-xl transition-all cursor-pointer group p-6"
            @click="window.location.href = '/org/{{ $organization->org_slug }}/sales/invoices'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-yellow-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-yellow-600 text-3xl">description</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Invoices</h4>
                    <p class="text-xs text-gray-600">Billing & payments</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Generate invoices, track payment status, and manage outstanding receivables.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-yellow-600" x-text="stats.unpaidInvoices + ' Unpaid'">0 Unpaid</span>
                <span class="material-symbols-outlined text-yellow-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl transition-all cursor-pointer group p-6"
            @click="window.location.href = '/org/{{ $organization->org_slug }}/sales/dispatch'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-purple-600 text-3xl">local_shipping</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Dispatch</h4>
                    <p class="text-xs text-gray-600">Outward logistics</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage dispatch notes, delivery challans, and outward shipment tracking.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-purple-600" x-text="stats.pendingDispatch + ' Pending'">0 Pending</span>
                <span class="material-symbols-outlined text-purple-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
    function salesDashboard() {
        return {
            stats: {
                openOrders: 0,
                pendingDispatch: 0,
                activeCustomers: 0,
                unpaidInvoices: 0
            },
            async init() {
                // TODO: replace with real API calls
                this.stats = {
                    openOrders: 14,
                    pendingDispatch: 5,
                    activeCustomers: 38,
                    unpaidInvoices: 7
                };
            }
        }
    }
</script>
@endsection