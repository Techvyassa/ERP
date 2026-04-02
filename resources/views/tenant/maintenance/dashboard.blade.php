@extends('layouts.maintenance')

@section('title', 'Maintenance Dashboard - ' . $organization->org_name)
@section('page-title', 'Maintenance Portal')

@section('content')
<div x-data="maintenanceDashboard()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-white text-4xl">build</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Maintenance Portal</h2>
                <p class="text-white/90">{{ $organization->org_name }}</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">handyman</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Open</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.openWorkOrders">0</h3>
            <p class="text-sm text-gray-600">Open Work Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-red-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-2xl">warning</span>
                </div>
                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">Overdue</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.overdueOrders">0</h3>
            <p class="text-sm text-gray-600">Overdue Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">precision_manufacturing</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Tracked</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.totalAssets">0</h3>
            <p class="text-sm text-gray-600">Total Assets</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">calendar_month</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">This Week</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.scheduledPM">0</h3>
            <p class="text-sm text-gray-600">Scheduled PM Tasks</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-amber-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/maintenance/work-orders'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-3xl">handyman</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Work Orders</h4>
                    <p class="text-xs text-gray-600">Breakdown & corrective</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create and manage corrective maintenance work orders, assign technicians, and track resolution.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-amber-600" x-text="stats.openWorkOrders + ' Open'">0 Open</span>
                <span class="material-symbols-outlined text-amber-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/maintenance/assets'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">precision_manufacturing</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Assets</h4>
                    <p class="text-xs text-gray-600">Equipment & machinery</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Maintain asset register with specifications, location, and full maintenance history.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.totalAssets + ' Assets'">0 Assets</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/maintenance/schedule'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-green-600 text-3xl">calendar_month</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">PM Schedule</h4>
                    <p class="text-xs text-gray-600">Preventive maintenance</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Plan and execute preventive maintenance schedules to reduce unplanned downtime.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-green-600" x-text="stats.scheduledPM + ' This Week'">0 This Week</span>
                <span class="material-symbols-outlined text-green-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="window.location.href = '/org/{{ $organization->org_slug }}/maintenance/spare-parts'">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-purple-600 text-3xl">settings</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Spare Parts</h4>
                    <p class="text-xs text-gray-600">Parts inventory</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Track spare parts inventory, reorder levels, and consumption against work orders.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-purple-600">View Inventory</span>
                <span class="material-symbols-outlined text-purple-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function maintenanceDashboard() {
    return {
        stats: { openWorkOrders: 0, overdueOrders: 0, totalAssets: 0, scheduledPM: 0 },
        async init() {
            // TODO: replace with real API calls
            this.stats = { openWorkOrders: 6, overdueOrders: 2, totalAssets: 45, scheduledPM: 4 };
        }
    }
}
</script>
@endsection
