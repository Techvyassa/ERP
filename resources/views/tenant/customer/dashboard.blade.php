@extends('layouts.customer')

@section('title', 'Customer Dashboard - ' . $organization->org_name)
@section('page-title', 'Customer Portal')

@section('content')
<div x-data="customerDashboard()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-white text-4xl">people</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Customer Portal</h2>
                <p class="text-white/90">{{ $organization->org_name }}</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">group</span>
                </div>
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Total</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.totalCustomers">0</h3>
            <p class="text-sm text-gray-600">Total Customers</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl">shopping_bag</span>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.activeOrders">0</h3>
            <p class="text-sm text-gray-600">Active Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-red-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-2xl">report_problem</span>
                </div>
                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">Open</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.openComplaints">0</h3>
            <p class="text-sm text-gray-600">Open Complaints</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-yellow-600 text-2xl">account_balance_wallet</span>
                </div>
                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">Overdue</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.overdueAccounts">0</h3>
            <p class="text-sm text-gray-600">Overdue Accounts</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-indigo-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/customer/list'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-indigo-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-3xl">group</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">All Customers</h4>
                    <p class="text-xs text-gray-600">Customer master list</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">View and manage all customer profiles, contact details, and account settings.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-indigo-600" x-text="stats.totalCustomers + ' Total'">0 Total</span>
                <span class="material-symbols-outlined text-indigo-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-emerald-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/customer/orders'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-emerald-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl">shopping_bag</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Customer Orders</h4>
                    <p class="text-xs text-gray-600">Order history & status</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Track all customer orders, delivery status, and order history across accounts.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-emerald-600" x-text="stats.activeOrders + ' Active'">0 Active</span>
                <span class="material-symbols-outlined text-emerald-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-red-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/customer/complaints'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-red-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-red-600 text-3xl">report_problem</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Complaints</h4>
                    <p class="text-xs text-gray-600">Issue tracking & resolution</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Log, track, and resolve customer complaints with full audit trail and SLA tracking.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-red-600" x-text="stats.openComplaints + ' Open'">0 Open</span>
                <span class="material-symbols-outlined text-red-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/customer/ledger'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-yellow-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-yellow-600 text-3xl">account_balance_wallet</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Ledger</h4>
                    <p class="text-xs text-gray-600">Account balances & dues</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">View customer account ledgers, outstanding balances, and payment history.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-yellow-600" x-text="stats.overdueAccounts + ' Overdue'">0 Overdue</span>
                <span class="material-symbols-outlined text-yellow-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function customerDashboard() {
    return {
        stats: { totalCustomers: 0, activeOrders: 0, openComplaints: 0, overdueAccounts: 0 },
        async init() {
            // TODO: replace with real API calls
            this.stats = { totalCustomers: 52, activeOrders: 11, openComplaints: 3, overdueAccounts: 4 };
        }
    }
}
</script>
@endsection
