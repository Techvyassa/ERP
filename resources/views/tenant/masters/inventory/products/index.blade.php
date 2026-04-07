@extends('tenant.layouts.inventory')

@section('title', 'Product Master')
@section('page-title', 'Product Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
<style>
    .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .status-active { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
    .status-inactive { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="productDashboard()" x-init="loadData()" class="space-y-6 max-w-[1600px] mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Product Master</h1>
            <p class="text-gray-500 text-sm">Manage finished goods and pricing</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="loadData()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-all">
                Refresh
            </button>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products/create' : '/org/' . $organization->org_slug . '/products/create') }}" 
               class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg flex items-center gap-2 transition-all">
                <span class="material-symbols-outlined text-lg">add</span>
                New Product
            </a>
        </div>
    </div>

    <!-- Metrics Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Items</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1" x-text="pagination.total">0</h3>
        </div>
        <div class="card p-5 bg-blue-50/30">
            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Active Only</p>
            <h3 class="text-2xl font-bold text-blue-700 mt-1" x-text="activeCount">0</h3>
        </div>
        <div class="card p-5 cursor-pointer hover:border-blue-400 transition-all border-dashed">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Reports</p>
            <div class="flex items-center gap-1 mt-1 text-blue-600 font-bold text-sm">
                <span class="material-symbols-outlined text-lg">file_download</span> Export CSV
            </div>
        </div>
        <div class="card p-5 cursor-pointer hover:border-blue-400 transition-all border-dashed">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Bulk Actions</p>
            <div class="flex items-center gap-1 mt-1 text-gray-600 font-bold text-sm">
                <span class="material-symbols-outlined text-lg">upload_file</span> Import Data
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
        <div class="flex flex-col md:flex-row gap-4 items-center">
            <div class="flex-1 relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400">search</span>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadData()"
                       placeholder="Search products..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            <div class="w-full md:w-48">
                <select x-model="filters.product_category" @change="loadData()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all bg-white text-sm">
                    <option value="">All Categories</option>
                    <template x-for="cat in categories" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </select>
            </div>
            <div class="w-full md:w-48">
                <select x-model="filters.is_active" @change="loadData()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all bg-white text-sm">
                    <option value="">Status: All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button @click="resetFilters" class="px-4 py-2 text-sm font-bold text-blue-600 hover:bg-blue-50 rounded-lg">Reset</button>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Category & HSN</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Weight / Unit</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pricing</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">Loading products...</td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No products found.</td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-blue-600 font-mono block" x-text="item.product_code"></span>
                                <span class="text-sm font-bold text-gray-900" x-text="item.product_name"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-gray-500 block" x-text="item.product_category || 'Uncategorized'"></span>
                                <span class="text-[10px] text-gray-400" x-text="item.hsn_code ? 'HSN: ' + item.hsn_code.hsn_code : 'No HSN'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-700" x-text="item.pack_size"></span>
                                <span class="text-xs text-gray-400" x-text="item.pack_uom ? item.pack_uom.uom_name : 'Units'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs">
                                    <div class="flex justify-between w-24"><span class="text-gray-400">Cost:</span> <span class="font-bold" x-text="'₹' + item.standard_cost"></span></div>
                                    <div class="flex justify-between w-24 text-green-600 font-bold"><span class="text-green-500 font-bold">MRP:</span> <span x-text="'₹' + item.mrp"></span></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span x-text="item.is_active ? 'Active' : 'Inactive'" 
                                      class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="item.is_active ? 'status-active' : 'status-inactive'"></span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="edit(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button @click="showBarcode(item)" class="p-2 text-gray-500 hover:bg-gray-50 rounded-lg transition-all" title="Barcode">
                                    <span class="material-symbols-outlined text-lg">barcode_scanner</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
    <!-- Barcode Modal -->
    <div x-show="barcodeModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         @click.away="barcodeModal = false">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-6 text-center space-y-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest">Product Label</h3>
                    <button @click="barcodeModal = false" class="text-slate-300 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="bg-slate-50 p-8 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center">
                    <p class="text-xs font-black text-blue-600 mb-1" x-text="activeProduct?.product_code"></p>
                    <p class="text-sm font-bold text-slate-800 mb-6" x-text="activeProduct?.product_name"></p>
                    <svg id="barcodeCanvas" class="max-w-full"></svg>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button @click="window.print()" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined text-lg">print</span> Print
                    </button>
                    <button @click="downloadBarcode()" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined text-lg">download</span> Save JPG
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
function productDashboard() {
    return {
        items: [], categories: [], loading: false, activeCount: 0,
        barcodeModal: false, activeProduct: null,
        filters: { search: '', product_category: '', is_active: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({...this.filters, page: this.pagination.current_page});
                const response = await fetch(`/api/v1/products?${params}`, { headers: { 'Accept': 'application/json' } });
                const result = await response.json();
                if (result.success && result.data) {
                    this.items = result.data.products || [];
                    this.pagination = result.data.pagination || this.pagination;
                    this.activeCount = this.items.filter(i => i.is_active).length;
                    if(!this.categories.length) this.categories = [...new Set(this.items.map(i => i.product_category).filter(Boolean))];
                }
            } catch (error) { console.error('Load failed', error); } finally { this.loading = false; }
        },
        
        loadPage(p) { if(p >= 1 && p <= this.pagination.last_page) { this.pagination.current_page = p; this.loadData(); } },
        resetFilters() { this.filters = { search: '', product_category: '', is_active: '' }; this.pagination.current_page = 1; this.loadData(); },
        edit(item) { window.location.href = `{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}/${item.id}/edit`; },
        
        showBarcode(item) {
            this.activeProduct = item;
            this.barcodeModal = true;
            this.$nextTick(() => {
                JsBarcode("#barcodeCanvas", item.product_code, {
                    format: "CODE128",
                    lineColor: "#0f172a",
                    width: 2,
                    height: 80,
                    displayValue: true,
                    fontSize: 14,
                    fontOptions: "bold"
                });
            });
        },

        downloadBarcode() {
            const svg = document.getElementById("barcodeCanvas");
            const svgData = new XMLSerializer().serializeToString(svg);
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            const img = new Image();
            img.onload = () => {
                canvas.width = img.width + 40;
                canvas.height = img.height + 40;
                ctx.fillStyle = "white";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 20, 20);
                const url = canvas.toDataURL("image/jpeg");
                const a = document.createElement("a");
                a.href = url;
                a.download = `barcode-${this.activeProduct.product_code}.jpg`;
                a.click();
            };
            img.src = "data:image/svg+xml;base64," + btoa(svgData);
        }
    };
}
</script>
@endsection