@extends('tenant.layouts.vendor')

@section('title', $organization->org_name . ' - Vendor & Procurement')
@section('page-title', 'Vendor & Procurement')

@section('content')
<div x-data="vendorDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}" 
               class="text-gray-600 hover:text-primary">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Vendor & Procurement</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">handshake</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Vendor & Procurement</h2>
                <p class="text-white/90">Manage vendors, contacts, and approved vendor list (AVL)</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">handshake</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.vendors">0</h3>
            <p class="text-sm text-gray-600">Vendors</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">contacts</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Total</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.contacts">0</h3>
            <p class="text-sm text-gray-600">Contacts</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">link</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">AVL</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.mappings">0</h3>
            <p class="text-sm text-gray-600">Material Mappings</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">shopping_cart</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.purchaseOrders">0</h3>
            <p class="text-sm text-gray-600">Purchase Orders</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Vendors -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-amber-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('vendors')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-3xl">handshake</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Vendor Master</h4>
                    <p class="text-xs text-gray-600">Vendor information</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage vendor details, terms, and conditions</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-amber-600" x-text="stats.vendors + ' Active'">0 Active</span>
                <span class="material-symbols-outlined text-amber-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Vendor Contacts -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('vendor-contacts')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">contacts</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Vendor Contacts</h4>
                    <p class="text-xs text-gray-600">Multiple vendor contacts</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage multiple contacts per vendor</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.contacts + ' Contacts'">0 Contacts</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Vendor Material Map (AVL) -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('vendor-material-map')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-green-600 text-3xl">link</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Vendor Material Map</h4>
                    <p class="text-xs text-gray-600">Approved Vendor List (AVL)</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Map materials to approved vendors with pricing</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-green-600" x-text="stats.mappings + ' Mappings'">0 Mappings</span>
                <span class="material-symbols-outlined text-green-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function vendorDashboard() {
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
            vendors: 0,
            contacts: 0,
            mappings: 0,
            purchaseOrders: 0
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const response = await fetch('/api/v1/dashboard/master-stats', { headers: headers() });
                const data = await response.json();

                if (data.success && data.data?.vendor) {
                    this.stats = data.data.vendor;
                }
            } catch (error) {
                console.error('Failed to load vendor dashboard stats:', error);
            }
        },

        navigateTo(page) {
            const routes = {
                'vendors': `${baseUrl}/vendors`,
                'vendor-contacts': `${baseUrl}/vendor-contacts`,
                'vendor-material-map': `${baseUrl}/vendor-material-map`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            }
        }
    }
}
</script>
@endsection
