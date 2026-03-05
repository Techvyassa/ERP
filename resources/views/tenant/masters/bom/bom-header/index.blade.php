@extends('tenant.layouts.bom')

@section('title', 'BOM Header')
@section('page-title', 'Bill of Materials - Header')

@section('content')
<div x-data="bomData()" x-init="loadData()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Bill of Materials (BOM)</h2>
                <p class="text-gray-600 mt-1">Manage product BOMs and manufacturing recipes</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header/create' : '/org/' . $organization->org_slug . '/bom-header/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                <i class="fas fa-plus mr-2"></i>Create BOM
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData" placeholder="Search by BOM code..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" x-model="filters.product" @input="loadData" placeholder="Filter by product..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.bom_status" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="DRAFT">Draft</option>
                <option value="ACTIVE">Active</option>
                <option value="OBSOLETE">Obsolete</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BOM Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective From</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading BOMs...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-list-alt text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No BOMs found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.bom_code"></span></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="item.product_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="'v' + item.version"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><span x-text="item.batch_size"></span> <span x-text="item.output_uom_name"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.effective_from"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': item.bom_status === 'DRAFT',
                                          'bg-green-100 text-green-800': item.bom_status === 'ACTIVE',
                                          'bg-red-100 text-red-800': item.bom_status === 'OBSOLETE'
                                      }"
                                      x-text="item.bom_status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="viewDetails(item)" class="text-green-600 hover:text-green-900 mr-3" title="View Details"><i class="fas fa-eye"></i></button>
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
function bomData() {
    return {
        items: [], loading: false, filters: { search: '', product: '', bom_status: '' },
        async loadData() { this.loading = true; try { this.items = []; } catch (e) { alert('Failed to load BOMs.'); } finally { this.loading = false; } },
        resetFilters() { this.filters = { search: '', product: '', bom_status: '' }; this.loadData(); },
        openCreateModal() { alert('Create BOM - Coming soon'); },
        viewDetails(item) { alert('View BOM details: ' + item.bom_code + ' - Coming soon'); },
        edit(item) { alert('Edit BOM: ' + item.bom_code + ' - Coming soon'); },
        async deleteItem(item) { if (confirm('Delete BOM: ' + item.bom_code + '?')) { alert('Delete functionality - Coming soon'); } }
    }
}
</script>
@endsection
