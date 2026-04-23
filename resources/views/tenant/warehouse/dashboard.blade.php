@extends('layouts.warehouse')

@section('title', 'Warehouse Dashboard - ' . $organization->org_name)
@section('page-title', 'Store Portal')

@section('content')
<div x-data="warehouseDashboard()" x-init="init()">
    <!-- Header/Banner Section -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 mb-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 border-b-4 border-amber-500">
        <div class="flex items-center gap-4">
            <div class="bg-amber-500 p-4 rounded-2xl shadow-lg shadow-amber-900/20">
                <span class="material-symbols-outlined text-white text-4xl">Store</span>
            </div>
            <div>
                <h2 class="text-2xl font-black mb-0.5 uppercase tracking-tight">Store Central</h2>
                <p class="text-white/50 text-xs font-bold uppercase tracking-widest">{{ $organization->org_name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-8 bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10 w-full md:w-auto">
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-1">Pending MIR</p>
                    <p class="text-2xl font-black text-amber-500 leading-none" x-text="stats.pendingMIR">0</p>
                </div>
                <div class="h-10 w-px bg-white/10"></div>
                <div class="text-left">
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-1">Approved</p>
                    <p class="text-2xl font-black text-emerald-500 leading-none" x-text="stats.approvedMIR">0</p>
                </div>
            </div>
            <div class="hidden lg:block max-w-[200px]">
                <p class="text-[10px] font-bold text-white/60 leading-tight uppercase tracking-tight">Material Movement: Track all pending issue requests and approved transfers.</p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-slate-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-slate-600 text-2xl">category</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 bg-slate-50 px-2 py-1 rounded">Catalog</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.materialsCount">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Materials</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">domain</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-600 bg-amber-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.warehousesCount">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Store</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">pending_actions</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-1 rounded">Requests</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.pendingMIR">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Open Issue Requests</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-50 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl">task_alt</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Ready</span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-1" x-text="stats.approvedMIR">0</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Approved for Picking</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Stock Details Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Raw Material Stock</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Availability snapshot for production planning</p>
                    </div>
                    <button @click="init()" class="text-amber-600">
                        <span class="material-symbols-outlined text-sm">refresh</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Material</th>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Available</th>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">QC Hold</th>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Pending Putaway</th>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <template x-for="item in stats.rmStock" :key="item.item_id">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900" x-text="item.item_name"></p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest" x-text="item.item_code"></p>
                                            <span class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded font-black uppercase" x-text="item.item_type"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-md font-black text-[10px]"
                                            x-text="parseFloat(item.available).toFixed(2)"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-amber-600" x-text="parseFloat(item.qc_hold).toFixed(2)"></td>
                                    <td class="px-6 py-4 text-right font-medium text-blue-600" x-text="parseFloat(item.putaway_pending).toFixed(2)"></td>
                                    <td class="px-6 py-4 text-right font-black text-gray-900" x-text="parseFloat(item.on_hand).toFixed(2) + ' ' + (item.uom || '')"></td>
                                </tr>
                            </template>
                            <template x-if="!stats.rmStock || stats.rmStock.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">inventory_2</span>
                                        <p class="text-[10px] font-black uppercase tracking-widest">No material stock data available</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Tools -->
        <div class="space-y-6">
            <!-- <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs mb-6">Inventory Workflows</h3>
                <div class="space-y-3">
                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/warehouse/mir-approvals'"
                        class="w-full flex items-center justify-between p-4 bg-amber-50/50 rounded-xl hover:bg-amber-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-amber-600">assignment_turned_in</span>
                            <span class="text-xs font-black text-amber-900 uppercase tracking-widest">MIR Approvals</span>
                        </div>
                        <span class="material-symbols-outlined text-amber-200 group-hover:text-amber-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/warehouse/stock-management'"
                        class="w-full flex items-center justify-between p-4 bg-slate-50/50 rounded-xl hover:bg-slate-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-slate-600">inventory</span>
                            <span class="text-xs font-black text-slate-900 uppercase tracking-widest">Stock Management</span>
                        </div>
                        <span class="material-symbols-outlined text-slate-200 group-hover:text-slate-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/warehouse/barcode-center'"
                        class="w-full flex items-center justify-between p-4 bg-indigo-50/50 rounded-xl hover:bg-indigo-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-indigo-600">barcode_scanner</span>
                            <span class="text-xs font-black text-indigo-900 uppercase tracking-widest">Barcode Center</span>
                        </div>
                        <span class="material-symbols-outlined text-indigo-200 group-hover:text-indigo-500 transition-colors">arrow_forward</span>
                    </button>

                    <button @click="window.location.href = '/org/{{ $organization->org_slug }}/warehouse/bin-management'"
                        class="w-full flex items-center justify-between p-4 bg-emerald-50/50 rounded-xl hover:bg-emerald-50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-emerald-600">grid_view</span>
                            <span class="text-xs font-black text-emerald-900 uppercase tracking-widest">Bin Visibility</span>
                        </div>
                        <span class="material-symbols-outlined text-emerald-200 group-hover:text-emerald-500 transition-colors">arrow_forward</span>
                    </button>
                </div>
            </div> -->

            <!-- Storage Efficiency Widget -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Storage Efficiency</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Bin Occupancy Rate</p>
                    </div>
                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md font-black text-[9px] uppercase tracking-tighter">Optimal</span>
                </div>
                
                <div class="flex items-end justify-between mb-2">
                    <h4 class="text-3xl font-black text-gray-900 leading-none">84<span class="text-xl text-gray-400">%</span></h4>
                    <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">+2.4% vs last week</span>
                </div>

                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden mb-6">
                    <div class="h-full bg-blue-600 rounded-full shadow-[0_0_8px_rgba(37,99,235,0.3)]" style="width: 84%"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 text-center">Empty Bins</p>
                        <p class="text-lg font-black text-slate-700 text-center">142</p>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 text-center">Fast Moving</p>
                        <p class="text-lg font-black text-slate-700 text-center">28%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function warehouseDashboard() {
        return {
            stats: {
                materialsCount: 0,
                warehousesCount: 0,
                pendingMIR: 0,
                approvedMIR: 0,
                rmStock: []
            },
            loading: false,
            async init() {
                this.loading = true;
                try {
                    const response = await fetch(`/api/v1/warehouses/dashboard-stats`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.stats = data.data;
                    }
                } catch (e) {
                    console.error('Failed to load warehouse stats', e);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection