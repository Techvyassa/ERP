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
                    <template x-for="product in products" :key="product.id">
                        <option :value="product.id" x-text="product.product_name + (product.product_code ? ' (' + product.product_code + ')' : '')"></option>
                    </template>
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Month <span class="text-red-500">*</span></label>
                    <input type="month" x-model="generateForm.month" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Product <span class="text-red-500">*</span></label>
                    <select x-model="generateForm.product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Product</option>
                        <template x-for="product in products" :key="product.id">
                            <option :value="product.id" x-text="product.product_name + (product.product_code ? ' (' + product.product_code + ')' : '')"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-500 mt-1" x-show="products.length === 0">Loading products...</p>
                    <p class="text-xs text-emerald-600 mt-1" x-show="products.length > 0" x-text="products.length + ' products available'"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Growth % <span class="text-red-500">*</span></label>
                    <input type="number" x-model="generateForm.growth_percentage" required min="0" max="100" step="0.1" placeholder="e.g., 10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Expected growth percentage based on historical data</p>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="showGenerateModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="generateForecast()" :disabled="!generateForm.month || !generateForm.product_id || generateForm.growth_percentage === ''" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Generate Forecast
                </button>
            </div>
        </div>
    </div>

    <!-- Forecast Results Modal -->
    <div x-show="showResultsModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.away="showResultsModal = false">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-purple-600 text-3xl">auto_graph</span>
                <h3 class="text-xl font-bold text-gray-900">Forecast Results</h3>
            </div>

            <template x-if="forecastResult">
                <div class="space-y-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Product</p>
                            <p class="text-sm font-bold text-blue-600" x-text="forecastResult.product_name"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Forecast Month</p>
                            <p class="text-sm font-bold text-gray-900" x-text="forecastResult.forecast_month"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Previous Month Sales</p>
                            <p class="text-2xl font-black text-gray-900" x-text="forecastResult.previous_month_sales || 0"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Growth Percentage</p>
                            <p class="text-2xl font-black text-gray-900" x-text="forecastResult.growth_percentage + '%'"></p>
                        </div>
                    </div>

                    <!-- Forecast Quantity -->
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-6 border-2 border-purple-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Forecast Quantity</p>
                                <p class="text-4xl font-black text-purple-600" x-text="forecastResult.forecast_quantity"></p>
                            </div>
                            <span class="material-symbols-outlined text-purple-600 text-6xl opacity-20">trending_up</span>
                        </div>
                    </div>

                    <!-- Calculation Method -->
                    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <p class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Calculation Method</p>
                        <p class="text-sm font-mono text-gray-800" x-text="forecastResult.calculation_formula"></p>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest mb-2">Remarks (Optional)</label>
                        <textarea x-model="forecastResult.remarks" rows="3" placeholder="Enter any additional notes or comments about this forecast..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button @click="showResultsModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                            New Calculation
                        </button>
                        <button @click="saveForecast()" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">save</span>
                            Save Forecast
                        </button>
                    </div>
                </div>
            </template>
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
        products: [],
        summary: {
            accuracy: 0,
            total_forecasted: 0,
            actual_demand: 0,
            products_count: 0
        },
        showGenerateModal: false,
        showResultsModal: false,
        generateForm: {
            month: new Date().toISOString().slice(0, 7), // YYYY-MM format
            product_id: '',
            growth_percentage: ''
        },
        forecastResult: null,
        async init() {
            await this.loadProducts();
            await this.loadForecasts();
        },
        async loadProducts() {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = '{{ $organization->org_slug }}';
                
                console.log('Loading products...');
                
                // Fetch all products from the API
                const response = await fetch(`/api/v1/products?per_page=1000`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                const result = await response.json();
                console.log('Products API response:', result);
                
                if (result.success) {
                    // ProductController returns: { success: true, data: { products: [...], pagination: {...} } }
                    let productList = result.data?.products || [];
                    
                    // Ensure products have the required fields
                    this.products = productList.map(p => ({
                        id: p.id,
                        product_name: p.product_name || p.name || 'Unknown Product',
                        product_code: p.product_code || p.code || ''
                    }));
                    
                    console.log(`Loaded ${this.products.length} products from database`);
                } else {
                    console.error('Failed to load products:', result.message);
                    this.products = [];
                    alert('Failed to load products. Please refresh the page.');
                }
            } catch (e) {
                console.error('Error loading products:', e);
                this.products = [];
                alert('Error loading products. Please check your connection and try again.');
            }
        },
        async loadForecasts() {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = '{{ $organization->org_slug }}';
                
                const params = new URLSearchParams({
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date,
                });
                
                // Add product filter if selected
                if (this.filters.product_id) {
                    params.append('product_id', this.filters.product_id);
                }
                
                console.log('Loading forecasts with params:', params.toString());
                
                const response = await fetch(`/api/v1/production-planning/forecast?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                const result = await response.json();
                console.log('Forecast data response:', result);

                if (result.success) {
                    this.forecastData = result.data || []; // flat array now
                    this.calculateSummary();
                    console.log(`Loaded ${this.forecastData.length} forecast records`);
                } else {
                    console.error('Failed to load forecasts:', result.message);
                    this.forecastData = [];
                }
            } catch (e) {
                console.error('Failed to load forecasts', e);
                this.forecastData = [];
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
            if (!this.generateForm.month || !this.generateForm.product_id || this.generateForm.growth_percentage === '') {
                alert('Please fill in all required fields');
                return;
            }

            try {
                const token = localStorage.getItem('access_token');
                
                // Get historical sales data for the selected product
                const product = this.products.find(p => p.id == this.generateForm.product_id);
                
                // Calculate previous month sales (mock data - replace with actual API call)
                const previousMonthSales = await this.getPreviousMonthSales(this.generateForm.product_id, this.generateForm.month);
                
                // Calculate forecast quantity
                const growthFactor = 1 + (parseFloat(this.generateForm.growth_percentage) / 100);
                const forecastQuantity = Math.round(previousMonthSales * growthFactor);
                
                // Format the month for display
                const [year, month] = this.generateForm.month.split('-');
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                const forecastMonth = `${monthNames[parseInt(month) - 1]} ${year}`;
                
                // Create forecast result
                this.forecastResult = {
                    product_name: product.product_name,
                    product_code: product.product_code,
                    forecast_month: forecastMonth,
                    previous_month_sales: previousMonthSales,
                    growth_percentage: this.generateForm.growth_percentage,
                    forecast_quantity: forecastQuantity,
                    calculation_formula: `${previousMonthSales} × (1 + ${this.generateForm.growth_percentage}% / 100) = ${forecastQuantity}`,
                    remarks: ''
                };
                
                // Close generate modal and show results modal
                this.showGenerateModal = false;
                this.showResultsModal = true;
                
            } catch (e) {
                console.error('Failed to generate forecast', e);
                alert('Error generating forecast');
            }
        },
        async getPreviousMonthSales(productId, forecastMonth) {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = '{{ $organization->org_slug }}';
                
                // Calculate previous month
                const [year, month] = forecastMonth.split('-');
                const date = new Date(year, month - 1, 1);
                date.setMonth(date.getMonth() - 1);
                const prevYear = date.getFullYear();
                const prevMonth = String(date.getMonth() + 1).padStart(2, '0');
                
                // Calculate date range for previous month
                const startDate = `${prevYear}-${prevMonth}-01`;
                const lastDay = new Date(prevYear, parseInt(prevMonth), 0).getDate();
                const endDate = `${prevYear}-${prevMonth}-${lastDay}`;
                
                console.log(`Fetching sales for product ${productId} from ${startDate} to ${endDate}`);
                
                // Fetch sales orders for previous month
                const response = await fetch(`/api/v1/sales-orders?start_date=${startDate}&end_date=${endDate}&per_page=1000`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                const result = await response.json();
                console.log('Sales orders response:', result);
                
                if (result.success) {
                    // SalesOrderController returns paginated data: { success: true, data: { data: [...], current_page, ... } }
                    const orders = result.data?.data || [];
                    console.log(`Found ${orders.length} orders in previous month`);
                    
                    // Sum up quantities for the selected product from all orders
                    let totalQty = 0;
                    
                    orders.forEach(order => {
                        // Check if order has line_items
                        if (order.line_items && Array.isArray(order.line_items)) {
                            order.line_items.forEach(line => {
                                // Match product_id and sum quantities
                                if (line.product_id == productId) {
                                    const qty = parseFloat(line.qty || 0);
                                    totalQty += qty;
                                    console.log(`Found ${qty} units in order ${order.so_number}`);
                                }
                            });
                        }
                    });
                    
                    console.log(`Total previous month sales for product ${productId}: ${totalQty}`);
                    return totalQty;
                }
                
                console.warn('No sales data found, returning 0');
                return 0;
                
            } catch (e) {
                console.error('Failed to get previous month sales:', e);
                return 0;
            }
        },
        async saveForecast() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/production-planning/forecast', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: this.generateForm.product_id,
                        forecast_month: this.generateForm.month,
                        forecast_quantity: this.forecastResult.forecast_quantity,
                        previous_month_sales: this.forecastResult.previous_month_sales,
                        growth_percentage: this.generateForm.growth_percentage,
                        remarks: this.forecastResult.remarks
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Forecast saved successfully!');
                    this.showResultsModal = false;
                    await this.loadForecasts();
                } else {
                    alert('Failed: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to save forecast', e);
                alert('Error saving forecast');
            }
        }
    }
}
</script>
@endsection
