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
            <input type="text" x-model="filters.bom" @input="loadData" placeholder="Filter by BOM code..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" x-model="filters.material" @input="loadData" placeholder="Filter by material..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BOM Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scrap %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective Qty</th>
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
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.bom_code"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.line_no"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="item.material_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><span x-text="item.qty_required"></span> <span x-text="item.uom_name"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.scrap_percent + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.effective_qty"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_critical ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'" x-text="item.is_critical ? 'Critical' : 'Normal'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
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
        items: [], loading: false, filters: { bom: '', material: '' },
        async loadData() { this.loading = true; try { this.items = []; } catch (e) { alert('Failed to load BOM details.'); } finally { this.loading = false; } },
        resetFilters() { this.filters = { bom: '', material: '' }; this.loadData(); },
        openCreateModal() { alert('Add BOM line - Coming soon'); },
        edit(item) { alert('Edit BOM line - Coming soon'); },
        async deleteItem(item) { if (confirm('Delete this BOM line?')) { alert('Delete functionality - Coming soon'); } }
    }
}
</script>
@endsection
