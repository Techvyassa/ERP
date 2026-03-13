@extends('layouts.quality')

@section('title', 'Quality Dashboard - ' . $organization->org_name)
@section('page-title', 'Quality Portal')

@section('content')
<div x-data="qualityDashboard()" x-init="init()">
    <!-- Department Header -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-4 rounded-xl">
                    <span class="material-symbols-outlined text-white text-4xl">biotech</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-1">Quality Portal</h2>
                    <p class="text-white/90">{{ $organization->org_name }}</p>
                </div>
            </div>
            <button class="px-6 py-3 bg-white text-sky-600 font-bold rounded-lg hover:shadow-lg transition-all">
                New Inspection
            </button>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">pending</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pendingTests">0</h3>
            <p class="text-sm text-gray-600 mb-2">Pending Tests</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">verified</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">24h</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.passedToday">0</h3>
            <p class="text-sm text-gray-600 mb-2">Passed Tests</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-red-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-2xl">dangerous</span>
                </div>
                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">Rate</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.rejectionRate">0%</h3>
            <p class="text-sm text-gray-600 mb-2">Rejection Rate</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-sky-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-sky-600 text-2xl">timer</span>
                </div>
                <span class="text-xs font-semibold text-sky-600 bg-sky-50 px-2 py-1 rounded">Avg</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.avgTAT">0</h3>
            <p class="text-sm text-gray-600 mb-2">Average TAT</p>
        </div>
    </div>

    <!-- Priority Lab Inspections -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Priority Lab Inspections</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Lot ID</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Material Name</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Sample Type</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Analyst</th>
                        <th class="text-right py-3 px-4 text-xs font-bold text-gray-500 uppercase">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="inspection in inspections" :key="inspection.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <p class="text-sm font-bold text-gray-900" x-text="inspection.lotId"></p>
                                <p class="text-xs text-gray-400" x-text="inspection.grn"></p>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-900" x-text="inspection.material"></td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold" x-text="inspection.sampleType"></span>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-600" x-text="inspection.analyst"></td>
                            <td class="py-4 px-4 text-right">
                                <span class="text-xs font-bold text-sky-600" x-text="inspection.progress + '%'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function qualityDashboard() {
    return {
        stats: {
            pendingTests: 42,
            passedToday: 128,
            rejectionRate: '2.4%',
            avgTAT: '3.2 Hrs'
        },
        inspections: [
            { id: 1, lotId: 'LT-A00451', grn: 'GRN: #88210', material: 'Stainless Steel Billets 304L', sampleType: 'CHEMICAL', analyst: 'Dr. Sarah Jenkins', progress: 65 }
        ],
        
        init() {
            // Load data from API
        }
    }
}
</script>
@endsection
