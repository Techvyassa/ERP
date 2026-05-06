@extends('layouts.production')

@section('title', 'Gap Analysis - ' . $organization->org_name)
@section('page-title', 'Gap Analysis')

@section('content')
<div x-data="gapAnalysisPage()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gap Analysis</h2>
        <p class="text-sm text-gray-500 mt-1">Calculate production gaps between forecast and available resources</p>
    </div>

    <!-- Gap Analysis Calculator Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
        <!-- Calculator Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-t-lg">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">calculate</span>
                <h3 class="text-lg font-bold">Gap Analysis Calculator</h3>
            </div>
        </div>

        <!-- Calculator Body -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Product Code -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">barcode</span>
                        Product Code
                    </label>
                    <select x-model="calculator.product_id" @change="fetchProductData()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Product</option>
                        <template x-for="product in products" :key="product.id">
                            <option :value="product.id" x-text="product.product_code + ' - ' + product.product_name"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-500 mt-1" x-show="products.length > 0" x-text="'Found ' + products.length + ' products in database'"></p>
                </div>

                <!-- Forecast Quantity -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">trending_up</span>
                        Forecast Quantity
                    </label>
                    <input type="number" x-model="calculator.forecast_qty" min="0" step="0.01" placeholder="Auto-filled from last forecast or enter manually" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Auto-filled from production_forecasts table</p>
                </div>
            </div>

            <!-- Auto-filled Fields -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- FG Stock -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">inventory_2</span>
                        Finished Goods Quantity
                    </label>
                    <div class="relative">
                        <input type="number" x-model="calculator.fg_stock" readonly placeholder="Auto-filled from database" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                        <button @click="fetchProductData()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600">
                            <span class="material-symbols-outlined text-lg">download</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-fetch from database</p>
                </div>

                <!-- SO Reserved -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">shopping_cart</span>
                        SO Reserved Quantity
                    </label>
                    <div class="relative">
                        <input type="number" x-model="calculator.so_reserved" readonly placeholder="Auto-filled from database" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                        <button @click="fetchProductData()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600">
                            <span class="material-symbols-outlined text-lg">download</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-fetch from database</p>
                </div>

                <!-- WIP -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">precision_manufacturing</span>
                        WIP Quantity
                    </label>
                    <div class="relative">
                        <input type="number" x-model="calculator.wip_qty" readonly placeholder="Auto-filled from database" class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                        <button @click="fetchProductData()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600">
                            <span class="material-symbols-outlined text-lg">download</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-fetch from database</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button @click="calculateGap()" :disabled="!calculator.product_id || !calculator.forecast_qty" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-bold flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">calculate</span>
                    Calculate Gap Analysis
                </button>
                <button @click="clearCalculator()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">close</span>
                    Clear
                </button>
                <button @click="fetchProductData()" :disabled="!calculator.product_id" class="px-6 py-3 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 disabled:opacity-50 disabled:cursor-not-allowed font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">database</span>
                    Use Database Data
                </button>
            </div>
        </div>
    </div>

    <!-- Business Logic Example -->
    <div x-show="showResults" class="bg-gradient-to-r from-cyan-50 to-pink-50 rounded-lg border border-cyan-200 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-cyan-600">lightbulb</span>
            <h3 class="text-lg font-bold text-gray-900">Business Logic Example</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Example Calculation -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-sm text-gray-600">receipt_long</span>
                    <h4 class="font-bold text-gray-900">Example Calculation</h4>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Forecast:</span>
                        <span class="font-bold" x-text="calculator.forecast_qty || 0"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">FG Stock:</span>
                        <span class="font-bold" x-text="calculator.fg_stock || 0"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">SO Reserved:</span>
                        <span class="font-bold" x-text="calculator.so_reserved || 0"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">WIP:</span>
                        <span class="font-bold" x-text="calculator.wip_qty || 0"></span>
                    </div>
                    <div class="flex justify-between bg-blue-100 px-2 py-1 rounded">
                        <span class="font-bold text-blue-900">Available Stock:</span>
                        <span class="font-black text-blue-900" x-text="results.available_stock"></span>
                    </div>
                    <div class="flex justify-between bg-yellow-100 px-2 py-1 rounded">
                        <span class="font-bold text-yellow-900">Required Production:</span>
                        <span class="font-black text-yellow-900" x-text="results.required_production"></span>
                    </div>
                </div>
            </div>

            <!-- Formulas -->
            <div>
                <h4 class="font-bold text-gray-900 mb-3">Formulas</h4>
                <div class="space-y-3 text-sm">
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="font-bold text-gray-700 mb-1">Available Stock</p>
                        <p class="text-pink-600 font-mono text-xs">Available Stock = FG Stock - SO Reserved + WIP</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="font-bold text-gray-700 mb-1">Required Production</p>
                        <p class="text-pink-600 font-mono text-xs">Required Production = Forecast - Available Stock</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="font-bold text-gray-700 mb-1">Safety Condition</p>
                        <p class="text-pink-600 font-mono text-xs">If Required Production < 0 → Set to 0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div x-show="gapData.length > 0" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Gap Analysis Results</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Forecast</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">FG Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">SO Reserved</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">WIP</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Available Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Required Prod.</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="gap in gapData" :key="gap.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 text-sm" x-text="gap.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="gap.product_code"></p>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="gap.forecast_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600" x-text="gap.fg_stock.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-orange-600" x-text="gap.so_reserved.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-blue-600" x-text="gap.wip_qty.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-bold text-blue-700 bg-blue-50" x-text="gap.available_stock.toFixed(2)"></td>
                            <td class="px-6 py-4 text-right font-bold text-yellow-700 bg-yellow-50" x-text="gap.required_production.toFixed(2)"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-black rounded-full uppercase tracking-widest"
                                    :class="{
                                        'bg-red-100 text-red-700': gap.status === 'CRITICAL',
                                        'bg-amber-100 text-amber-700': gap.status === 'SHORTAGE',
                                        'bg-emerald-100 text-emerald-700': gap.status === 'BALANCED'
                                    }"
                                    x-text="gap.status"></span>
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
        products: [],
        calculator: {
            product_id: '',
            forecast_qty: '',
            fg_stock: '',
            so_reserved: '',
            wip_qty: ''
        },
        results: {
            available_stock: 0,
            required_production: 0
        },
        showResults: false,
        gapData: [],
        
        async init() {
            await this.loadProducts();
        },
        
        async loadProducts() {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = '{{ $organization->org_slug }}';
                
                const response = await fetch(`/api/v1/products?per_page=1000`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                const result = await response.json();
                if (result.success) {
                    this.products = result.data?.products || [];
                    console.log(`Loaded ${this.products.length} products`);
                }
            } catch (e) {
                console.error('Failed to load products', e);
            }
        },
        
        async fetchProductData() {
            if (!this.calculator.product_id) {
                alert('Please select a product first');
                return;
            }
            
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = '{{ $organization->org_slug }}';
                
                // Fetch stock data for the product
                const response = await fetch(`/api/v1/production-planning/product-stock/${this.calculator.product_id}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                const result = await response.json();
                if (result.success) {
                    this.calculator.fg_stock = result.data.fg_stock || 0;
                    this.calculator.so_reserved = result.data.so_reserved || 0;
                    this.calculator.wip_qty = result.data.wip_qty || 0;
                    this.calculator.forecast_qty = result.data.forecast_qty || '';
                    
                    console.log('Fetched product data:', result.data);
                    
                    if (result.data.forecast_qty > 0) {
                        console.log(`Auto-filled forecast quantity: ${result.data.forecast_qty} (from ${result.data.forecast_date})`);
                    }
                } else {
                    alert('Failed to fetch product data: ' + result.message);
                }
            } catch (e) {
                console.error('Failed to fetch product data', e);
                alert('Error fetching product data');
            }
        },
        
        calculateGap() {
            if (!this.calculator.product_id || !this.calculator.forecast_qty) {
                alert('Please select a product and enter forecast quantity');
                return;
            }
            
            const forecast = parseFloat(this.calculator.forecast_qty) || 0;
            const fgStock = parseFloat(this.calculator.fg_stock) || 0;
            const soReserved = parseFloat(this.calculator.so_reserved) || 0;
            const wip = parseFloat(this.calculator.wip_qty) || 0;
            
            // Calculate Available Stock = FG Stock - SO Reserved + WIP
            const availableStock = fgStock - soReserved + wip;
            
            // Calculate Required Production = Forecast - Available Stock
            let requiredProduction = forecast - availableStock;
            
            // Safety condition: if negative, set to 0
            if (requiredProduction < 0) {
                requiredProduction = 0;
            }
            
            this.results.available_stock = availableStock.toFixed(2);
            this.results.required_production = requiredProduction.toFixed(2);
            this.showResults = true;
            
            // Determine status
            let status = 'BALANCED';
            if (requiredProduction > forecast * 0.2) {
                status = 'CRITICAL';
            } else if (requiredProduction > 0) {
                status = 'SHORTAGE';
            }
            
            // Add to results table
            const product = this.products.find(p => p.id == this.calculator.product_id);
            this.gapData.unshift({
                id: Date.now(),
                product_code: product?.product_code || '',
                product_name: product?.product_name || '',
                forecast_qty: forecast,
                fg_stock: fgStock,
                so_reserved: soReserved,
                wip_qty: wip,
                available_stock: availableStock,
                required_production: requiredProduction,
                status: status
            });
            
            console.log('Gap calculation complete:', this.results);
        },
        
        clearCalculator() {
            this.calculator = {
                product_id: '',
                forecast_qty: '',
                fg_stock: '',
                so_reserved: '',
                wip_qty: ''
            };
            this.results = {
                available_stock: 0,
                required_production: 0
            };
            this.showResults = false;
        }
    }
}
</script>
@endsection
