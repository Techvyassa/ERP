@extends('layouts.production')

@section('title', 'Production Dashboard - ' . $organization->org_name)
@section('page-title', 'Production Portal')

@section('content')
<div x-data="productionPortalDashboard()" x-init="init()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-900 to-slate-800 rounded-2xl p-6 mb-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 border-b-4 border-orange-600">
        <div class="flex items-center gap-4">
            <div class="bg-orange-600 p-4 rounded-2xl shadow-lg shadow-orange-900/20">
                <span class="material-symbols-outlined text-white text-4xl">precision_manufacturing</span>
            </div>
            <div>
                <h2 class="text-2xl font-black mb-0.5 uppercase tracking-tight">Production Portal</h2>
                <p class="text-white/50 text-xs font-bold uppercase tracking-widest">{{ $organization->org_name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-8 bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10 w-full md:w-auto">
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-1">Pending MIR</p>
                    <p class="text-2xl font-black text-orange-500 leading-none" x-text="stats.pendingMIR">0</p>
                </div>
                <div class="h-10 w-px bg-white/10"></div>
                <div class="text-left">
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-1">Approved</p>
                    <p class="text-2xl font-black text-emerald-500 leading-none" x-text="stats.approvedMIR">0</p>
                </div>
            </div>
            <div class="hidden lg:block max-w-[200px]">
                <p class="text-[10px] font-bold text-white/60 leading-tight uppercase tracking-tight">Sync with Store: Ensure all MIRs are approved to keep machines running.</p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-orange-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-orange-600 text-2xl">factory</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-orange-600 bg-orange-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.activeOrders">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Orders</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">task_alt</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-1 rounded">Last 30 Days</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.completedLast30Days">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Completed Orders</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl">trending_up</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Total Volume</span>
            </div>
            <div class="flex items-baseline gap-1">
                <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.totalFGConfirmedLast30Days">0</h3>
                <span class="text-xs font-bold text-gray-400">units</span>
            </div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Production Volume</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">monitoring</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Efficiency</span>
            </div>
            <div class="flex items-baseline gap-1">
                <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.avgYieldLast30Days">0</h3>
                <span class="text-xs font-bold text-gray-400">%</span>
            </div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Average Yield (30d)</p>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Stock Details Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Finished Goods Stock</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">All products with stock status</p>
                    </div>
                    <button @click="init()" class="text-orange-600 hover:bg-orange-50 p-2 rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-sm">refresh</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100">Product</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Available</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">QC Hold</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Reserved</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <template x-for="item in stockData" :key="item.item_id + '-' + item.uom_id">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-900" x-text="item.item_name"></p>
                                        <p class="text-[10px] text-gray-400 font-mono" x-text="item.item_code"></p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-semibold text-xs" 
                                            x-text="item.available.toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-medium text-amber-600" x-text="item.qc_hold.toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-medium text-blue-600" x-text="item.reserved.toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900" x-text="item.on_hand.toFixed(2) + ' ' + item.uom"></td>
                                </tr>
                            </template>
                            <template x-if="!stockData || stockData.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">inventory_2</span>
                                        <p class="text-xs font-bold uppercase tracking-widest">No products found</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs mb-6">Execution Gateways</h3>
                <div class="space-y-3">
                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/production/orders'"
                        class="w-full flex items-center justify-between p-4 bg-orange-50/50 rounded-xl hover:bg-orange-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-orange-600">factory</span>
                            <span class="text-xs font-black text-orange-900 uppercase tracking-widest">Production Orders</span>
                        </div>
                        <span class="material-symbols-outlined text-orange-200 group-hover:text-orange-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/production/fg-confirmation'"
                        class="w-full flex items-center justify-between p-4 bg-emerald-50/50 rounded-xl hover:bg-emerald-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-emerald-600">task_alt</span>
                            <span class="text-xs font-black text-emerald-900 uppercase tracking-widest">FG Confirmation</span>
                        </div>
                        <span class="material-symbols-outlined text-emerald-200 group-hover:text-emerald-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/production/packing'"
                        class="w-full flex items-center justify-between p-4 bg-indigo-50/50 rounded-xl hover:bg-indigo-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-indigo-600">inventory_2</span>
                            <span class="text-xs font-black text-indigo-900 uppercase tracking-widest">Packing Workspace</span>
                        </div>
                        <span class="material-symbols-outlined text-indigo-200 group-hover:text-indigo-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/production/mir'"
                        class="w-full flex items-center justify-between p-4 bg-amber-50/50 rounded-xl hover:bg-amber-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-amber-600">assignment</span>
                            <span class="text-xs font-black text-amber-900 uppercase tracking-widest">Material Issues</span>
                        </div>
                        <span class="material-symbols-outlined text-amber-200 group-hover:text-amber-500 transition-colors">arrow_forward</span>
                    </button>
                </div>
            </div>


        </div>
    </div>
</div>
</div>

<script>
    function productionPortalDashboard() {
        return {
            stats: {
                activeOrders: 0,
                pendingMIR: 0,
                approvedMIR: 0,
                products: 0,
                completedLast30Days: 0,
                totalFGConfirmedLast30Days: 0,
                avgYieldLast30Days: 0,
                fgStock: []
            },
            stockData: [],
            loading: false,
            async init() {
                this.loading = true;
                try {
                    // Load stats
                    const statsResponse = await fetch(`/api/v1/production-orders/stats`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const statsData = await statsResponse.json();
                    if (statsData.success) {
                        this.stats = statsData.data;
                    }

                    // Load stock data (all products with stock info)
                    const stockResponse = await fetch(`/api/v1/stock/current?item_type=Product`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const stockResult = await stockResponse.json();
                    if (stockResult.success) {
                        this.stockData = stockResult.data;
                    }
                } catch (e) {
                    console.error('Failed to load data', e);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection