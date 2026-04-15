@extends('tenant.layouts.bom')

@section('title', 'BOM Detail')
@section('page-title', 'Bill of Materials - Detail Lines')

@section('content')
<div x-data="bomDetailData()" x-init="loadData()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">BOM Detail Lines</h2>
                <p class="text-gray-600 mt-1">Manage material components in BOMs</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail/create' : '/org/' . $organization->org_slug . '/bom-detail/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                <i class="fas fa-plus mr-2"></i>Add Line
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.bom_id" @input="loadData" placeholder="Filter by BOM ID..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.is_critical" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Materials</option>
                <option value="true">Critical Only</option>
                <option value="false">Non-Critical Only</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BOM ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deviation %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Critical</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading BOM details...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-list-ol text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No BOM detail lines found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.bom_id"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.line_no"></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span x-text="item.material?.material_code + ' - ' + item.material?.material_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="parseFloat(item.qty_required).toFixed(4)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.uom?.uom_code"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="parseFloat(item.scrap_percent || 0).toFixed(2) + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_critical ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" x-text="item.is_critical ? 'Yes' : 'No'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a :href="editUrl(item.id)" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" title="Edit">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function bomDetailData() {
    return {
        items: [],
        loading: false,
        filters: { bom_id: '', is_critical: '' },
        tenantType: '{{ request()->get("tenant_type") }}',
        orgSlug: '{{ $organization->org_slug ?? "" }}',
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.bom_id) params.append('bom_id', this.filters.bom_id);
                if (this.filters.is_critical !== '') params.append('is_critical', this.filters.is_critical);
                
                const response = await fetch(`/api/v1/bom-details?${params}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const result = await response.json();
                this.items = result.data || [];
            } catch (error) {
                console.error('Failed to load BOM details:', error);
                alert('Failed to load BOM details: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { bom_id: '', is_critical: '' };
            this.loadData();
        },
        
        editUrl(id) {
            if (this.tenantType === 'subdomain') {
                return `/bom-detail/${id}/edit`;
            }
            return `/org/${this.orgSlug}/bom-detail/${id}/edit`;
        }
    }
}
</script>
@endsection
