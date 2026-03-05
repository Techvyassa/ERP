@extends('tenant.layouts.inventory')

@section('title', $organization->org_name . ' - Inventory & Material Management')
@section('page-title', 'Inventory & Material Management')

@section('content')
<div x-data="inventoryDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}" 
               class="text-gray-600 hover:text-primary">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Inventory & Material Management</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">inventory</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Inventory & Material Management</h2>
                <p class="text-white/90">Manage materials, products, warehouses, and stock levels</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">inventory_2</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.materials">0</h3>
            <p class="text-sm text-gray-600">Materials</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">category</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.products">0</h3>
            <p class="text-sm text-gray-600">Products</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-cyan-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-cyan-600 text-2xl">warehouse</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.warehouses">0</h3>
            <p class="text-sm text-gray-600">Warehouses</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">location_on</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.binLocations">0</h3>
            <p class="text-sm text-gray-600">Bin Locations</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">straighten</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.uom">0</h3>
            <p class="text-sm text-gray-600">UOM</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Materials -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('materials')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">inventory_2</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Material Master</h4>
                    <p class="text-xs text-gray-600">Raw materials & consumables</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage raw materials, consumables, and components</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.materials + ' Items'">0 Items</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('products')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-purple-600 text-3xl">category</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Product Master</h4>
                    <p class="text-xs text-gray-600">Finished goods</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage finished products and SKUs</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-purple-600" x-text="stats.products + ' Items'">0 Items</span>
                <span class="material-symbols-outlined text-purple-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Warehouses -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-cyan-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('warehouses')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-cyan-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-cyan-600 text-3xl">warehouse</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Warehouse Master</h4>
                    <p class="text-xs text-gray-600">Storage warehouses</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage storage locations and warehouses</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-cyan-600" x-text="stats.warehouses + ' Locations'">0 Locations</span>
                <span class="material-symbols-outlined text-cyan-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Bin Locations -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('bin-locations')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-green-600 text-3xl">location_on</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Bin Locations</h4>
                    <p class="text-xs text-gray-600">Rack/bin locations</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage rack and bin locations within warehouses</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-green-600" x-text="stats.binLocations + ' Bins'">0 Bins</span>
                <span class="material-symbols-outlined text-green-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- UOM -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-amber-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('uom')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-3xl">straighten</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Unit of Measure</h4>
                    <p class="text-xs text-gray-600">Measurement units</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Define units of measurement for materials</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-amber-600" x-text="stats.uom + ' Units'">0 Units</span>
                <span class="material-symbols-outlined text-amber-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function inventoryDashboard() {
    return {
        stats: {
            materials: 0,
            products: 0,
            warehouses: 0,
            binLocations: 0,
            uom: 0
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            // TODO: Load from API
            this.stats = {
                materials: 45,
                products: 23,
                warehouses: 3,
                binLocations: 24,
                uom: 12
            };
        },

        navigateTo(page) {
            const orgSlug = '{{ $organization->org_slug }}';
            const routes = {
                'materials': `/org/${orgSlug}/materials`,
                'products': `/org/${orgSlug}/products`,
                'warehouses': `/org/${orgSlug}/warehouses`,
                'bin-locations': `/org/${orgSlug}/bin-locations`,
                'uom': `/org/${orgSlug}/uom`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            }
        }
    }
}
</script>
@endsection
