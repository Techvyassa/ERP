@extends('tenant.layouts.inventory')

@section('title', 'Materials')
@section('page-title', 'Material Master')

@section('content')
<div x-data="materialData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Material Master</h2>
                <p class="text-gray-600 mt-1">Manage raw materials, packaging, and consumables</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials/create' : '/org/' . $organization->org_slug . '/materials/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Material
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search by code or name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.material_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="RAW">Raw Material</option>
                <option value="PACKAGING">Packaging</option>
                <option value="CONSUMABLE">Consumable</option>
                <option value="SEMI">Semi-Finished</option>
            </select>
            <select x-model="filters.is_active" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading materials...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-boxes text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No materials found. Click "Add Material" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.material_code"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900" x-text="item.material_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-blue-100 text-blue-800': item.material_type === 'RAW',
                                          'bg-green-100 text-green-800': item.material_type === 'PACKAGING',
                                          'bg-yellow-100 text-yellow-800': item.material_type === 'CONSUMABLE',
                                          'bg-purple-100 text-purple-800': item.material_type === 'SEMI'
                                      }"
                                      x-text="item.material_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.uom_name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.reorder_level || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> materials
            </div>
            <div class="flex space-x-2">
                <button @click="loadPage(pagination.current_page - 1)" 
                        :disabled="pagination.current_page === 1"
                        :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 border border-gray-300 rounded">
                    Previous
                </button>
                <span class="px-3 py-1 text-sm text-gray-600">
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                </span>
                <button @click="loadPage(pagination.current_page + 1)" 
                        :disabled="pagination.current_page === pagination.last_page"
                        :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 border border-gray-300 rounded">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function materialData() {
    return {
        items: [],
        loading: false,
        filters: {
            search: '',
            material_type: '',
            is_active: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },
        
        async loadData() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                // const response = await apiClient.get('/api/materials', { params: this.filters });
                // this.items = response.data.data.materials;
                // this.pagination = response.data.data.pagination;
                
                // Placeholder data
                this.items = [];
                this.pagination = { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 };
            } catch (error) {
                console.error('Failed to load materials:', error);
                alert('Failed to load materials. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        
        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadData();
            }
        },
        
        resetFilters() {
            this.filters = { search: '', material_type: '', is_active: '' };
            this.loadData();
        },
        
        openCreateModal() {
            alert('Create material form - Coming soon');
        },
        
        edit(item) {
            alert('Edit material: ' + item.material_code + ' - Coming soon');
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure you want to delete material: ' + item.material_code + '?')) {
                try {
                    // TODO: Replace with actual API call
                    // await apiClient.delete('/api/materials/' + item.id);
                    alert('Delete functionality - Coming soon');
                    // this.loadData();
                } catch (error) {
                    console.error('Failed to delete material:', error);
                    alert('Failed to delete material. Please try again.');
                }
            }
        }
    }
}
</script>
@endsection
