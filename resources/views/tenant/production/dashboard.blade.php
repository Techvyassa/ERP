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

    <!-- Production Planning Section -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-600 p-2 rounded-lg">
                        <span class="material-symbols-outlined text-white text-xl">analytics</span>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Production Planning</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Forecast & Gap Analysis</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="generateForecast()" class="text-xs font-bold px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors uppercase tracking-widest">
                        Generate Forecast
                    </button>
                    <button @click="runGapAnalysis()" class="text-xs font-bold px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors uppercase tracking-widest">
                        Run Analysis
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <!-- Forecast Summary -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-black text-gray-700 uppercase tracking-widest text-[10px]">Demand Forecast</h4>
                    <span class="text-[10px] text-gray-400 font-bold" x-text="'Accuracy: ' + planningSummary.forecast_accuracy.toFixed(1) + '%'"></span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-xs font-bold text-gray-600">Next 7 Days</span>
                        <span class="text-sm font-black text-gray-900" x-text="planningSummary.forecast_7days || '0'"></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-xs font-bold text-gray-600">Next 30 Days</span>
                        <span class="text-sm font-black text-gray-900" x-text="planningSummary.forecast_30days || '0'"></span>
                    </div>
                </div>
            </div>

            <!-- Gap Analysis Summary -->
            <div class="space-y-4">
                <h4 class="font-black text-gray-700 uppercase tracking-widest text-[10px]">Gap Analysis Status</h4>
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                        <p class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-1">Critical</p>
                        <p class="text-2xl font-black text-red-700" x-text="planningSummary.gap_summary.critical"></p>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-lg border border-amber-100">
                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1">Shortage</p>
                        <p class="text-2xl font-black text-amber-700" x-text="planningSummary.gap_summary.shortage"></p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Balanced</p>
                        <p class="text-2xl font-black text-emerald-700" x-text="planningSummary.gap_summary.balanced"></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Surplus</p>
                        <p class="text-2xl font-black text-blue-700" x-text="planningSummary.gap_summary.surplus"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gap Analysis Details Table -->
        <div class="border-t border-gray-100">
            <div class="px-6 py-3 bg-gray-50/50">
                <h4 class="font-black text-gray-700 uppercase tracking-widest text-[10px]">Current Gap Analysis</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100">Product</th>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Demand</th>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Stock</th>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Planned</th>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100 text-right">Gap</th>
                            <th class="px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <template x-for="gap in gapAnalysisData" :key="gap.id">
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-gray-900 text-xs" x-text="gap.product_name"></p>
                                    <p class="text-[10px] text-gray-400 font-mono" x-text="gap.product_code"></p>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="gap.demand_qty.toFixed(2)"></td>
                                <td class="px-4 py-3 text-right font-medium text-emerald-600" x-text="gap.available_stock.toFixed(2)"></td>
                                <td class="px-4 py-3 text-right font-medium text-blue-600" x-text="gap.planned_production_qty.toFixed(2)"></td>
                                <td class="px-4 py-3 text-right font-black" :class="gap.gap_qty < 0 ? 'text-red-600' : 'text-emerald-600'" x-text="gap.gap_qty.toFixed(2)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest"
                                        :class="{
                                            'bg-red-100 text-red-700': gap.gap_status === 'CRITICAL',
                                            'bg-amber-100 text-amber-700': gap.gap_status === 'SHORTAGE',
                                            'bg-emerald-100 text-emerald-700': gap.gap_status === 'BALANCED',
                                            'bg-blue-100 text-blue-700': gap.gap_status === 'SURPLUS'
                                        }"
                                        x-text="gap.gap_status"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!gapAnalysisData || gapAnalysisData.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    <span class="material-symbols-outlined text-3xl mb-2 opacity-50">analytics</span>
                                    <p class="text-xs font-bold uppercase tracking-widest">No gap analysis data. Click "Run Analysis" to generate.</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
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
            gapAnalysisData: [],
            planningSummary: {
                gap_summary: {
                    critical: 0,
                    shortage: 0,
                    balanced: 0,
                    surplus: 0
                },
                forecast_accuracy: 0,
                forecast_7days: 0,
                forecast_30days: 0
            },
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

                    // Load planning summary
                    await this.loadPlanningSummary();

                    // Load gap analysis
                    await this.loadGapAnalysis();
                } catch (e) {
                    console.error('Failed to load data', e);
                } finally {
                    this.loading = false;
                }
            },
            async loadPlanningSummary() {
                try {
                    const response = await fetch(`/api/v1/production-planning/summary`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.planningSummary = result.data;
                    }
                } catch (e) {
                    console.error('Failed to load planning summary', e);
                }
            },
            async loadGapAnalysis() {
                try {
                    const today = new Date().toISOString().split('T')[0];
                    const response = await fetch(`/api/v1/production-planning/gap-analysis?analysis_date=${today}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.gapAnalysisData = result.data;
                    }
                } catch (e) {
                    console.error('Failed to load gap analysis', e);
                }
            },
            async generateForecast() {
                if (!confirm('Generate forecast for the next 30 days?')) return;
                
                this.loading = true;
                try {
                    const startDate = new Date().toISOString().split('T')[0];
                    const endDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    
                    const response = await fetch(`/api/v1/production-planning/forecast/generate`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            start_date: startDate,
                            end_date: endDate
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        alert('Forecast generated successfully!');
                        await this.loadPlanningSummary();
                    } else {
                        alert('Failed to generate forecast: ' + result.message);
                    }
                } catch (e) {
                    console.error('Failed to generate forecast', e);
                    alert('Error generating forecast');
                } finally {
                    this.loading = false;
                }
            },
            async runGapAnalysis() {
                if (!confirm('Run gap analysis for today?')) return;
                
                this.loading = true;
                try {
                    const today = new Date().toISOString().split('T')[0];
                    
                    const response = await fetch(`/api/v1/production-planning/gap-analysis/run`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            analysis_date: today
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        alert('Gap analysis completed successfully!');
                        await this.loadPlanningSummary();
                        await this.loadGapAnalysis();
                    } else {
                        alert('Failed to run gap analysis: ' + result.message);
                    }
                } catch (e) {
                    console.error('Failed to run gap analysis', e);
                    alert('Error running gap analysis');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection