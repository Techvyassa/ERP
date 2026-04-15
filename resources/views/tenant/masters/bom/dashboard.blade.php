@extends('tenant.layouts.bom')

@section('title', $organization->org_name . ' - Production & BOM')
@section('page-title', 'Production & BOM')

@section('content')
<div x-data="productionDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}"
                class="text-gray-600 hover:text-primary transition-colors">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Production & BOM</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-4 rounded-xl">
                    <span class="material-symbols-outlined text-5xl">precision_manufacturing</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Production & BOM</h2>
                    <p class="text-white/90">Manage Bill of Materials, production recipes, and component planning</p>
                </div>
            </div>
            <a href="{{ url($tenantType === 'subdomain' ? '/bom-header/create' : '/org/' . $organization->org_slug . '/bom-header/create') }}"
                class="hidden md:inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 border border-white/30 text-white px-4 py-2.5 rounded-lg transition-all font-medium text-sm">
                <span class="material-symbols-outlined text-lg">add</span>
                New BOM
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-50 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-xl">inventory_2</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.totalBoms">-</h3>
            <p class="text-sm text-gray-500">Total BOMs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-50 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-xl">check_circle</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-green-600 mb-1" x-text="stats.activeBoms">-</h3>
            <p class="text-sm text-gray-500">Active BOMs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-yellow-50 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-yellow-600 text-xl">edit_note</span>
                </div>
                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded-full">Draft</span>
            </div>
            <h3 class="text-3xl font-bold text-yellow-600 mb-1" x-text="stats.draftBoms">-</h3>
            <p class="text-sm text-gray-500">Draft BOMs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-50 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-xl">category</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Products</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.productsWithBom">-</h3>
            <p class="text-sm text-gray-500">Products with BOM</p>
        </div>
    </div>

    <!-- Module Cards -->
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Modules</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- BOM Management -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-orange-400 hover:shadow-xl transition-all cursor-pointer group p-6"
            @click="navigateTo('bom-header')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-orange-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-orange-600 text-3xl">account_tree</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Bill of Materials</h4>
                    <p class="text-xs text-gray-500">Headers, components & versions</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create and manage BOMs with components, quantities, deviation rates, and substitute materials — all in one unified view.</p>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-orange-600" x-text="stats.totalBoms + ' BOMs'">0 BOMs</span>
                    <span class="text-xs text-gray-400">|</span>
                    <span class="text-xs text-green-600 font-medium" x-text="stats.activeBoms + ' active'">0 active</span>
                </div>
                <span class="material-symbols-outlined text-orange-500 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Production Orders (placeholder for future) -->
        <!-- <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-400 hover:shadow-xl transition-all cursor-pointer group p-6 opacity-90"
             @click="navigateTo('production-orders')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">factory</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Production Orders</h4>
                    <p class="text-xs text-gray-500">Work orders & scheduling</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create production orders from BOMs, track progress, manage material consumption and output quantities.</p>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm font-bold text-blue-600" x-text="stats.productionOrders + ' orders'">0 orders</span>
                <span class="material-symbols-outlined text-blue-500 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div> -->

        <!-- FG Confirmation -->
        <!-- <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-400 hover:shadow-xl transition-all cursor-pointer group p-6 opacity-90"
             @click="navigateTo('fg-confirmation')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-green-600 text-3xl">task_alt</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">FG Confirmation</h4>
                    <p class="text-xs text-gray-500">Finished goods output</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Confirm finished goods output for production orders, validate quantities, and update stock inventory.</p>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm font-bold text-green-600">Output tracking</span>
                <span class="material-symbols-outlined text-green-500 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div> -->
    </div>

    <!-- Recent BOMs -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-orange-500">history</span>
                <h3 class="text-base font-semibold text-gray-900">Recent BOMs</h3>
            </div>
            <a href="{{ url($tenantType === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}"
                class="text-sm text-orange-600 hover:text-orange-700 font-medium transition-colors">
                View All →
            </a>
        </div>

        <template x-if="recentBoms.length > 0">
            <div class="divide-y divide-gray-100">
                <template x-for="bom in recentBoms" :key="bom.id">
                    <div class="px-6 py-3.5 flex items-center justify-between hover:bg-gray-50/50 transition-colors cursor-pointer"
                        @click="navigateTo('bom-header/' + bom.id + '/view')">
                        <div class="flex items-center gap-4">
                            <div class="bg-orange-50 p-2 rounded-lg">
                                <span class="material-symbols-outlined text-orange-500 text-sm">description</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="bom.bom_code"></p>
                                <p class="text-xs text-gray-400" x-text="bom.product_name"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full"
                                :class="{
                                      'bg-yellow-100 text-yellow-800': bom.bom_status === 'DRAFT',
                                      'bg-green-100 text-green-800': bom.bom_status === 'ACTIVE',
                                      'bg-red-100 text-red-800': bom.bom_status === 'OBSOLETE'
                                  }"
                                x-text="bom.bom_status"></span>
                            <span class="text-xs text-gray-400" x-text="'v' + bom.version"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="recentBoms.length === 0 && !loading">
            <div class="px-6 py-10 text-center">
                <div class="bg-gray-100 p-3 rounded-full inline-block mb-3">
                    <span class="material-symbols-outlined text-3xl text-gray-400">account_tree</span>
                </div>
                <p class="text-sm text-gray-500">No BOMs created yet.</p>
                <a href="{{ url($tenantType === 'subdomain' ? '/bom-header/create' : '/org/' . $organization->org_slug . '/bom-header/create') }}"
                    class="inline-flex items-center gap-1 mt-2 text-sm text-orange-600 hover:text-orange-700 font-medium">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Create your first BOM
                </a>
            </div>
        </template>

        <template x-if="loading">
            <div class="px-6 py-10 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto mb-2"></div>
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </template>
    </div>
</div>

<script>
    function productionDashboard() {
        const orgSlug = '{{ $organization->org_slug }}';
        const tenantType = '{{ $tenantType ?? "path" }}';
        const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;

        return {
            loading: true,
            recentBoms: [],
            stats: {
                totalBoms: '-',
                activeBoms: '-',
                draftBoms: '-',
                productsWithBom: '-',
                productionOrders: 0
            },

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.loading = true;
                try {
                    const response = await fetch('/api/v1/bom-headers?per_page=1000', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();

                    if (data.success && data.data) {
                        const boms = Array.isArray(data.data) ? data.data : (data.data.boms || []);

                        // Compute stats from raw data
                        this.stats.totalBoms = boms.length;
                        this.stats.activeBoms = boms.filter(b => b.bom_status === 'ACTIVE').length;
                        this.stats.draftBoms = boms.filter(b => b.bom_status === 'DRAFT').length;

                        // Count unique products
                        const uniqueProducts = new Set(boms.map(b => b.product_id).filter(Boolean));
                        this.stats.productsWithBom = uniqueProducts.size;

                        // Take last 5 as recent
                        this.recentBoms = boms.slice(-5).reverse().map(b => ({
                            id: b.id,
                            bom_code: b.bom_code,
                            product_name: b.product ? b.product.product_name : 'N/A',
                            version: b.version || 1,
                            bom_status: b.bom_status || 'DRAFT'
                        }));
                    }
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                } finally {
                    this.loading = false;
                }
            },

            navigateTo(page) {
                window.location.href = `${baseUrl}/${page}`;
            }
        }
    }
</script>
@endsection