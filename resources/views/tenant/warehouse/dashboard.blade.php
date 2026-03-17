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
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-500">Awaiting inspection</span>
            </div>
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
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">inventory</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Today</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.receiptsToday">0</h3>
            <p class="text-sm text-gray-600 mb-2">Material Receipts</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold">Completed</span>
            </div>
        </div>
    </div>

    <!-- Live Receiving Queue -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
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
</div>

<script>
function warehouseDashboard() {
    return {
        stats: {
            expectedToday: 0,
            arrivedToday: 0,
            pendingQC: 0,
            unloadingBays: 0,
            bayStatus: '0 / 0',
            receiptsToday: 0
        },
        receivingQueue: [],
        
        init() {
            this.loadStats();
            this.loadReceivingQueue();
        },
        
        async loadStats() {
            this.stats = {
                expectedToday: 24,
                arrivedToday: 8,
                pendingQC: 12,
                unloadingBays: 3,
                bayStatus: '3 / 5',
                receiptsToday: 15
            };
        },
        
        async loadReceivingQueue() {
            this.receivingQueue = [
                { id: 1, position: '01', vehicle: 'Truck GJ-01-XX-1234', details: 'Vendor: Tata Steel • PO: #45021', status: 'UNLOADING', statusClass: 'bg-green-500', priorityClass: 'bg-green-500' },
                { id: 2, position: '02', vehicle: 'Vehicle MH-12-AB-9876', details: 'Vendor: Reliance Poly • PO: #45025', status: 'DOC VERIFICATION', statusClass: 'bg-amber-500', priorityClass: 'bg-amber-500' }
            ];
        }
    }
}
</script>
@endsection
