@extends('tenant.layouts.tax')

@section('title', $organization->org_name . ' - Tax & Financial')
@section('page-title', 'Tax & Financial')

@section('content')
<div x-data="taxDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}" 
               class="text-gray-600 hover:text-primary">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Tax & Financial</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">receipt_long</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Tax & Financial</h2>
                <p class="text-white/90">Manage HSN codes, GST taxes, and currency settings</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">qr_code</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.hsnCodes">0</h3>
            <p class="text-sm text-gray-600">HSN Codes</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">percent</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Configured</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.gstTaxes">0</h3>
            <p class="text-sm text-gray-600">GST Tax Slabs</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">currency_exchange</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.currencies">0</h3>
            <p class="text-sm text-gray-600">Currencies</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">account_balance</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Default</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.baseCurrency">INR</h3>
            <p class="text-sm text-gray-600">Base Currency</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- HSN Codes -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('hsn-codes')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-green-600 text-3xl">qr_code</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">HSN Codes</h4>
                    <p class="text-xs text-gray-600">HSN classification</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage Harmonized System of Nomenclature codes</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-green-600" x-text="stats.hsnCodes + ' Codes'">0 Codes</span>
                <span class="material-symbols-outlined text-green-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- GST Taxes -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('gst-taxes')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">percent</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">GST Taxes</h4>
                    <p class="text-xs text-gray-600">GST tax slabs</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Configure GST tax rates and categories</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.gstTaxes + ' Slabs'">0 Slabs</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Currency -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-amber-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('currency')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-amber-600 text-3xl">currency_exchange</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Currency Master</h4>
                    <p class="text-xs text-gray-600">Currency exchange rates</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage currencies and exchange rates</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-amber-600" x-text="stats.currencies + ' Currencies'">0 Currencies</span>
                <span class="material-symbols-outlined text-amber-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function taxDashboard() {
    return {
        stats: {
            hsnCodes: 0,
            gstTaxes: 0,
            currencies: 0,
            baseCurrency: 'INR'
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const token = this.getToken();
                const orgSlug = this.getOrgSlug();
                
                // Load HSN codes count
                const hsnResponse = await fetch('/api/v1/hsn-codes?is_active=1', {
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'X-Org-Slug': orgSlug
                    }
                });
                const hsnData = await hsnResponse.json();
                if (hsnData.success) {
                    this.stats.hsnCodes = hsnData.data.hsn_codes.length;
                }
                
                // Load GST taxes count
                const gstResponse = await fetch('/api/v1/gst-taxes?is_active=1', {
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'X-Org-Slug': orgSlug
                    }
                });
                const gstData = await gstResponse.json();
                if (gstData.success) {
                    this.stats.gstTaxes = gstData.data.gst_taxes.length;
                }
                
                // Load currencies count and base currency
                const currencyResponse = await fetch('/api/v1/currencies?is_active=1', {
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'X-Org-Slug': orgSlug
                    }
                });
                const currencyData = await currencyResponse.json();
                if (currencyData.success) {
                    this.stats.currencies = currencyData.data.currencies.length;
                    const baseCurrency = currencyData.data.currencies.find(c => c.is_base_currency);
                    if (baseCurrency) {
                        this.stats.baseCurrency = baseCurrency.currency_code;
                    }
                }
            } catch (e) {
                console.error('Failed to load tax dashboard data:', e);
            }
        },

        navigateTo(page) {
            const orgSlug = '{{ $organization->org_slug }}';
            const routes = {
                'hsn-codes': `/org/${orgSlug}/hsn-codes`,
                'gst-taxes': `/org/${orgSlug}/gst-taxes`,
                'currency': `/org/${orgSlug}/currency`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            }
        },
        
        getToken() {
            return localStorage.getItem('access_token') || '';
        },
        
        getOrgSlug() {
            return localStorage.getItem('org_slug') || '';
        }
    }
}
</script>
@endsection
