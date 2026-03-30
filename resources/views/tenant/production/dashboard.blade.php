@extends('layouts.production')

@section('title', 'Production Dashboard - ' . $organization->org_name)
@section('page-title', 'Production Portal')

@section('content')
<div x-data="productionPortalDashboard()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-white text-4xl">precision_manufacturing</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Production Portal</h2>
                <p class="text-white/90">{{ $organization->org_name }}</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-orange-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-orange-600 text-2xl">factory</span>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.activeOrders">0</h3>
            <p class="text-sm text-gray-600">Production Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-yellow-600 text-2xl">pending_actions</span>
                </div>
                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">Pending</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingMIR">0</h3>
            <p class="text-sm text-gray-600">Pending MIRs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Approved</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.approvedMIR">0</h3>
            <p class="text-sm text-gray-600">Approved MIRs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">category</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Products</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.products">0</h3>
            <p class="text-sm text-gray-600">Products with BOM</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-orange-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/production/orders'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-orange-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-orange-600 text-3xl">factory</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Production Orders</h4>
                    <p class="text-xs text-gray-600">Select product & target quantity</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create production orders — system auto-calculates RM requirements from BOM and generates MIR for Store.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-orange-600" x-text="stats.activeOrders + ' Active'">0 Active</span>
                <span class="material-symbols-outlined text-orange-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/production/mir'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-yellow-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-yellow-600 text-3xl">assignment</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Material Issue Requests</h4>
                    <p class="text-xs text-gray-600">MIRs sent to Store for approval</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Track all Material Issue Requests generated from production orders, monitor approval status from Store.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-yellow-600" x-text="stats.pendingMIR + ' Pending'">0 Pending</span>
                <span class="material-symbols-outlined text-yellow-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-indigo-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/production/packing'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-indigo-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-3xl">inventory_2</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Packing Orders</h4>
                    <p class="text-xs text-gray-600">Cartons, scans, and seal workflow</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Pack finished goods into cartons and complete packing after QC clearance.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-indigo-600">Open Packing Workspace</span>
                <span class="material-symbols-outlined text-indigo-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function productionPortalDashboard() {
    return {
        stats: { activeOrders: 0, pendingMIR: 0, approvedMIR: 0, products: 0 },
        async init() {
            // TODO: replace with real API calls
            this.stats = { activeOrders: 8, pendingMIR: 3, approvedMIR: 12, products: 23 };
        }
    }
}
</script>
@endsection
