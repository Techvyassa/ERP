@extends('layouts.production')

@section('title', 'Demand Forecast - ' . $organization->org_name)
@section('page-title', 'Demand Forecast')

@section('content')
<div x-data="forecastPage()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Demand Forecast</h2>
            <p class="text-sm text-gray-500 mt-1">Predict future demand based on sales orders and historical data</p>
        </div>
        <div class="flex gap-3">
            <button @click="showGenerateModal = true" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">auto_graph</span>
                Generate Forecast
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Start Date</label>
                <input type="date" x-model="filters.start_date" @change="loadForecasts()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">End Date</label>
                <input type="date" x-model="filters.end_date" @change="loadForecasts()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Product</label>
                <select x-model="filters.product_id" @change="loadForecasts()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">All Products</option>
                    <!-- Products will be loaded dynamically -->
                </select>
            </div>
            <div class="flex items-end">
                <button @click="loadForecasts()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-semibold">
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Forecast Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Forecast Accuracy</span>
                <span class="material-symbols-outlined text-purple-600">verified</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="summary.accuracy.toFixed(1) + '%'">0%</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Forecasted</span>
                <span class="material-symbols-outlined text-blue-600">inventory</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="summary.total_forecasted">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Actual Demand</span>
                <span class="material-symbols-outlined text-emerald-600">shopping_cart</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="summary.actual_demand">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Products Tracked</span>
                <span class="material-symbols-outlined text-amber-600">category</span>
            </div>
            <p class="text-3xl font-black text-gray-900" x-text="summary.products_count">0</p>
        </div>
    </div>

    <!-- Forecast Data Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Forecast Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Forecasted</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Actual Demand</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Current Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Planned Prod.</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="item in forecastData" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 text-sm" x-text="item.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="item.product_code"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="item.date"></td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="item.forecasted_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-blue-600" x-text="item.actual_demand_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600" x-text="item.current_stock.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-purple-600" x-text="item.planned_production.toFixed(2)"></td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2 py-1 text-xs font-bold rounded" 
                                    :class="{
                                        'bg-blue-100 text-blue-700': item.source === 'SALES_ORDER',
                                        'bg-purple-100 text-purple-700': item.source === 'SYSTEM',
                                        'bg-gray-100 text-gray-700': item.source === 'MANUAL',
                                        'bg-amber-100 text-amber-700': item.source === 'HISTORICAL'
                                    }"
                                    x-text="item.source"></span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="forecastData.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">trending_up</span>
                                <p class="text-sm font-bold">No forecast data available. Click "Generate Forecast" to create.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Generate Forecast Modal -->
    <div x-show="showGenerateModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" @click.away="showGenerateModal = false">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Generate Forecast</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                    <input type="date" x-model="generateForm.start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                    <input type="date" x-model="generateForm.end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="showGenerateModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="generateForecast()" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Generate
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function forecastPage() {
    return {
        filters: {
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            product_id: ''
        },
        forecastData: [],
        summary: {
            accuracy: 0,
            total_forecasted: 0,
            actual_demand: 0,
            products_count: 0
        },
        showGenerateModal: false,
        generateForm: {
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        },
        async init() {
            await this.loadForecasts();
        },
        async loadForecasts() {
            try {
                const params = new URLSearchParams({
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date,
                });
                const response = await fetch(`/api/v1/production-planning/forecast?${params}`);
                const result = await response.json();

                if (result.success) {
                    this.forecastData = result.data; // flat array now
                    this.calculateSummary();
                }
            } catch (e) {
                console.error('Failed to load forecasts', e);
            }
        },
        calculateSummary() {
            this.summary.total_forecasted = this.forecastData.reduce((s, i) => s + i.forecasted_qty, 0).toFixed(2);
            this.summary.actual_demand    = this.forecastData.reduce((s, i) => s + i.actual_demand_qty, 0).toFixed(2);
            this.summary.products_count   = new Set(this.forecastData.map(i => i.product_id)).size;

            const withActual = this.forecastData.filter(i => i.actual_demand_qty > 0);
            if (withActual.length > 0) {
                const avgError = withActual.reduce((s, i) => {
                    return s + Math.abs(i.forecasted_qty - i.actual_demand_qty) / i.actual_demand_qty * 100;
                }, 0) / withActual.length;
                this.summary.accuracy = Math.max(0, 100 - avgError);
            } else {
                this.summary.accuracy = 0;
            }
        },
        async generateForecast() {
            try {
                const response = await fetch('/api/v1/production-planning/forecast/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.generateForm)
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Forecast generated successfully!');
                    this.showGenerateModal = false;
                    await this.loadForecasts();
                } else {
                    alert('Failed: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to generate forecast', e);
                alert('Error generating forecast');
            }
        }
    }
}
</script>
@endsection
