@extends('layouts.procurement')

@section('title', 'Procurement Dashboard - ' . $organization->org_name)
@section('page-title', 'Procurement Portal')

@section('content')
<div x-data="procurementDashboard()" x-init="init()">
    <!-- Department Header -->
    <div class="bg-gradient-to-r from-primary to-blue-700 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-4 rounded-xl">
                    <span class="material-symbols-outlined text-white text-4xl">shopping_cart</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Procurement Portal</h2>
                    <p class="text-white/90">{{ $organization->org_name }}</p>
                </div>
            </div>
            <button class="px-6 py-3 bg-white text-primary font-bold rounded-lg hover:shadow-lg transition-all">
                Create Purchase Order
            </button>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">shopping_bag</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.activePOs">0</h3>
            <p class="text-sm text-gray-600 mb-2">Active Purchase Orders</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold">+5%</span>
                <span class="text-gray-500">this month</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">pending_actions</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Urgent</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingApproval">0</h3>
            <p class="text-sm text-gray-600 mb-2">Pending Approval</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-amber-600 font-semibold">Requires Action</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">store</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Total</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.vendors">0</h3>
            <p class="text-sm text-gray-600 mb-2">Active Vendors</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold">+12</span>
                <span class="text-gray-500">this month</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Today</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.inwardToday">0</h3>
            <p class="text-sm text-gray-600 mb-2">Expected Deliveries</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold">On Schedule</span>
            </div>
        </div>
    </div>

    <!-- Recent Purchase Orders -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Recent Purchase Orders</h3>
            <button class="text-sm font-semibold text-primary hover:underline">View All</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Amount</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="po in recentPOs" :key="po.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4"><span class="font-semibold text-primary" x-text="po.number"></span></td>
                            <td class="py-3 px-4 text-gray-900" x-text="po.vendor"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="po.date"></td>
                            <td class="py-3 px-4 font-bold text-gray-900" x-text="po.amount"></td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold" 
                                      :class="po.statusClass" x-text="po.status"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function procurementDashboard() {
    return {
        stats: {
            activePOs: 0,
            pendingApproval: 0,
            vendors: 0,
            inwardToday: 0
        },
        recentPOs: [],
        
        init() {
            this.loadStats();
            this.loadRecentPOs();
        },
        
        async loadStats() {
            this.stats = {
                activePOs: 142,
                pendingApproval: 18,
                vendors: 2450,
                inwardToday: 12
            };
        },
        
        async loadRecentPOs() {
            this.recentPOs = [
                { id: 1, number: '#PO-2024-001', vendor: 'Global Steel Dynamics', date: 'Oct 24, 2024', amount: '$12,450.00', status: 'Approved', statusClass: 'bg-green-100 text-green-700' },
                { id: 2, number: '#PO-2024-002', vendor: 'Precision Machining', date: 'Oct 25, 2024', amount: '$8,200.00', status: 'Pending', statusClass: 'bg-amber-100 text-amber-700' }
            ];
        }
    }
}
</script>
@endsection
