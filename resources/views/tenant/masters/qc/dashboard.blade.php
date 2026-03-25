@extends('tenant.layouts.app')

@section('title', $organization->org_name . ' - Quality Masters')
@section('page-title', 'Quality Masters')

@section('content')
<div x-data="qualityMasterDashboard()" x-init="init()">
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ url($tenantType === 'subdomain' ? '/master-setup' : "/org/{$organization->org_slug}/master-setup") }}"
               class="text-gray-600 hover:text-primary">Master Setup</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Quality</span>
        </nav>
    </div>

    <div class="bg-gradient-to-r from-sky-500 to-cyan-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">biotech</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Quality Master Setup</h2>
                <p class="text-white/90">Configure QC test types and material-wise inspection specifications</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-6 mb-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Recommended Flow</h3>
            <div class="mt-4 space-y-4">
                <div class="flex gap-3">
                    <div class="h-8 w-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold">1</div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Create QC test types</p>
                        <p class="text-xs text-gray-500">Standardize groups like Visual, Chemical, and Physical first.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="h-8 w-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold">2</div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Add QC parameters</p>
                        <p class="text-xs text-gray-500">Map material-specific limits, methods, and critical checks.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="h-8 w-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold">3</div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Run inspections with clean defaults</p>
                        <p class="text-xs text-gray-500">Inspection lots will reuse this setup during QC execution.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Current Coverage</h3>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-sky-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Types</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900" x-text="stats.testTypes">0</p>
                </div>
                <div class="rounded-2xl bg-cyan-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Parameters</p>
                    <p class="mt-2 text-3xl font-bold text-cyan-900" x-text="stats.parameters">0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-sky-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="navigateTo('qc-test-types')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-sky-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-sky-600 text-3xl">science</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">QC Test Types</h4>
                    <p class="text-xs text-gray-600">Visual, chemical, physical, microbiological</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Define reusable inspection type groups for QC specifications.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-sky-600" x-text="stats.testTypes + ' Configured'">0 Configured</span>
                <span class="material-symbols-outlined text-sky-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-cyan-500 hover:shadow-xl transition-all cursor-pointer group p-6"
             @click="navigateTo('qc-parameters')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-cyan-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-cyan-600 text-3xl">biotech</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">QC Parameters</h4>
                    <p class="text-xs text-gray-600">Material-wise specification master</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Maintain parameter limits, methods, tolerance types, and critical checks.</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-cyan-600" x-text="stats.parameters + ' Parameters'">0 Parameters</span>
                <span class="material-symbols-outlined text-cyan-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function qualityMasterDashboard() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const baseUrl = '{{ $tenantType }}' === 'subdomain' ? '' : `/org/${orgSlug}`;
    const headers = () => ({
        'Authorization': `Bearer ${token()}`,
        'Accept': 'application/json',
        'X-Org-Slug': orgSlug
    });

    return {
        stats: {
            testTypes: 0,
            parameters: 0
        },

        async init() {
            await Promise.all([this.loadTestTypes(), this.loadParameters()]);
        },

        async loadTestTypes() {
            try {
                const response = await fetch('/api/v1/qc-test-types', { headers: headers() });
                const data = await response.json();
                if (data.success) {
                    this.stats.testTypes = data.data.length;
                }
            } catch (error) {
                console.error('Failed to load QC test types', error);
            }
        },

        async loadParameters() {
            try {
                const response = await fetch('/api/v1/qc-parameters', { headers: headers() });
                const data = await response.json();
                if (data.success) {
                    this.stats.parameters = data.data.length;
                }
            } catch (error) {
                console.error('Failed to load QC parameters', error);
            }
        },

        navigateTo(page) {
            const routes = {
                'qc-test-types': `${baseUrl}/qc-test-types`,
                'qc-parameters': `${baseUrl}/qc-parameters`
            };

            if (routes[page]) {
                window.location.href = routes[page];
            }
        }
    }
}
</script>
@endsection
