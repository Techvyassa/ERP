@extends('layouts.production')

@section('title', 'Gap Analysis - ' . $organization->org_name)
@section('page-title', 'Gap Analysis')

@section('content')
<div x-data="gapAnalysisPage()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Production Gap Analysis</h2>
            <p class="text-sm text-gray-500 mt-1">Identify gaps between demand and production capacity</p>
        </div>
        <div class="flex gap-3">
            <button @click="runAnalysis()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">analytics</span>
                Run Analysis
            </button>
        </div>
    </div>

    <!-- Analysis Date Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Analysis Date</label>
                <input type="date" x-model="analysisDate" @change="loadGapAnalysis()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button @click="loadGapAnalysis()" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-semibold">
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Gap Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-red-50 rounded-lg border-2 border-red-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-red-600 uppercase tracking-widest">Critical</span>
                <span class="material-symbols-outlined text-red-600">error</span>
            </div>
            <p class="text-4xl font-black text-red-700" x-text="summary.critical">0</p>
            <p class="text-xs text-red-600 mt-1 font-semibold">>20% Shortage</p>
        </div>
        <div class="bg-amber-50 rounded-lg border-2 border-amber-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Shortage</span>
                <span class="material-symbols-outlined text-amber-600">warning</span>
            </div>
            <p class="text-4xl font-black text-amber-700" x-text="summary.shortage">0</p>
            <p class="text-xs text-amber-600 mt-1 font-semibold">0-20% Shortage</p>
        </div>
        <div class="bg-emerald-50 rounded-lg border-2 border-emerald-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Balanced</span>
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
            <p class="text-4xl font-black text-emerald-700" x-text="summary.balanced">0</p>
            <p class="text-xs text-emerald-600 mt-1 font-semibold">Within Range</p>
        </div>
        <div class="bg-blue-50 rounded-lg border-2 border-blue-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-blue-600 uppercase tracking-widest">Surplus</span>
                <span class="material-symbols-outlined text-blue-600">inventory</span>
            </div>
            <p class="text-4xl font-black text-blue-700" x-text="summary.surplus">0</p>
            <p class="text-xs text-blue-600 mt-1 font-semibold">>20% Surplus</p>
        </div>
    </div>

    <!-- Gap Analysis Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Gap Analysis Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Demand</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Available Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Planned Prod.</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Gap</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Capacity %</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Recommendations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="gap in gapData" :key="gap.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 text-sm" x-text="gap.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="gap.product_code"></p>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="gap.demand_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600" x-text="gap.available_stock.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-blue-600" x-text="gap.planned_production_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-black text-lg" 
                                :class="gap.gap_qty < 0 ? 'text-red-600' : 'text-emerald-600'" 
                                x-text="gap.gap_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-black rounded-full uppercase tracking-widest"
                                    :class="{
                                        'bg-red-100 text-red-700': gap.gap_status === 'CRITICAL',
                                        'bg-amber-100 text-amber-700': gap.gap_status === 'SHORTAGE',
                                        'bg-emerald-100 text-emerald-700': gap.gap_status === 'BALANCED',
                                        'bg-blue-100 text-blue-700': gap.gap_status === 'SURPLUS'
                                    }"
                                    x-text="gap.gap_status"></span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="gap.capacity_utilization.toFixed(1) + '%'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="gap.recommendations"></td>
                        </tr>
                    </template>
                    <template x-if="gapData.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">analytics</span>
                                <p class="text-sm font-bold">No gap analysis data. Click "Run Analysis" to generate.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function gapAnalysisPage() {
    return {
        analysisDate: new Date().toISOString().split('T')[0],
        gapData: [],
        summary: {
            critical: 0,
            shortage: 0,
            balanced: 0,
            surplus: 0
        },
        async init() {
            await this.loadGapAnalysis();
        },
        async loadGapAnalysis() {
            try {
                const response = await fetch(`/api/v1/production-planning/gap-analysis?analysis_date=${this.analysisDate}`);
                const result = await response.json();
                
                if (result.success) {
                    this.gapData = result.data;
                    this.calculateSummary();
                }
            } catch (e) {
                console.error('Failed to load gap analysis', e);
            }
        },
        calculateSummary() {
            this.summary = {
                critical: this.gapData.filter(g => g.gap_status === 'CRITICAL').length,
                shortage: this.gapData.filter(g => g.gap_status === 'SHORTAGE').length,
                balanced: this.gapData.filter(g => g.gap_status === 'BALANCED').length,
                surplus: this.gapData.filter(g => g.gap_status === 'SURPLUS').length
            };
        },
        async runAnalysis() {
            if (!confirm('Run gap analysis for ' + this.analysisDate + '?')) return;
            
            try {
                const response = await fetch('/api/v1/production-planning/gap-analysis/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        analysis_date: this.analysisDate
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Gap analysis completed successfully!');
                    await this.loadGapAnalysis();
                } else {
                    alert('Failed: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to run gap analysis', e);
                alert('Error running gap analysis');
            }
        }
    }
}
</script>
@endsection
