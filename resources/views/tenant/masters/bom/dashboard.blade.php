@extends('tenant.layouts.bom')

@section('title', $organization->org_name . ' - Production & BOM')
@section('page-title', 'Production & BOM')

@section('content')
<div x-data="productionDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}" 
               class="text-gray-600 hover:text-primary">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Production & BOM</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">precision_manufacturing</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Production & BOM</h2>
                <p class="text-white/90">Manage Bill of Materials and production planning</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-orange-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-orange-600 text-2xl">account_tree</span>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.bomHeaders">0</h3>
            <p class="text-sm text-gray-600">BOM Headers</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">list_alt</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Components</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.bomDetails">0</h3>
            <p class="text-sm text-gray-600">BOM Details</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">factory</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.productionOrders">0</h3>
            <p class="text-sm text-gray-600">Production Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">category</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Products</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.products">0</h3>
            <p class="text-sm text-gray-600">Products with BOM</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- BOM Header -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-orange-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('bom-header')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-orange-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-orange-600 text-3xl">account_tree</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">BOM Header</h4>
                    <p class="text-xs text-gray-600">BOM version control</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage Bill of Materials headers and versions</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-orange-600" x-text="stats.bomHeaders + ' BOMs'">0 BOMs</span>
                <span class="material-symbols-outlined text-orange-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- BOM Detail -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('bom-detail')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">list_alt</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">BOM Detail</h4>
                    <p class="text-xs text-gray-600">BOM component list</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage BOM components and quantities</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.bomDetails + ' Components'">0 Components</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function productionDashboard() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const tenantType = '{{ $tenantType ?? 'path' }}';
    const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;
    const headers = () => ({
        'Authorization': `Bearer ${token()}`,
        'Accept': 'application/json',
        'X-Org-Slug': orgSlug
    });

    return {
        stats: {
            bomHeaders: 0,
            bomDetails: 0,
            productionOrders: 0,
            products: 0
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const response = await fetch('/api/v1/dashboard/master-stats', { headers: headers() });
                const data = await response.json();

                if (data.success && data.data?.bom) {
                    this.stats = data.data.bom;
                }
            } catch (error) {
                console.error('Failed to load BOM dashboard stats:', error);
            }
        },

        navigateTo(page) {
            const routes = {
                'bom-header': `${baseUrl}/bom-header`,
                'bom-detail': `${baseUrl}/bom-detail`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            }
        }
    }
}
</script>
@endsection
