@extends('tenant.layouts.app')

@section('title', 'Vendor Material Map')
@section('page-title', 'Vendor Material Mapping')

@section('content')
<div x-data="mapData()" x-init="loadData()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Material Mapping</h2>
                <p class="text-gray-600 mt-1">Map materials to vendors with pricing and lead times</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Mapping
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.vendor" @input="loadData" placeholder="Filter by vendor..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" x-model="filters.material" @input="loadData" placeholder="Filter by material..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.is_preferred" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All</option>
                <option value="1">Preferred</option>
                <option value="0">Non-Preferred</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Order Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferred</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="9" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading mappings...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="9" class="px-6 py-12 text-center"><i class="fas fa-link text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No vendor-material mappings found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="item.vendor_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="item.material_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.vendor_material_code || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">₹<span x-text="item.last_purchase_price || '-'"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><span x-text="item.lead_time_days || '-'"></span> days</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.min_order_qty || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_preferred ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" x-text="item.is_preferred ? 'Yes' : 'No'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
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
function mapData() {
    return {
        items: [], loading: false, filters: { vendor: '', material: '', is_preferred: '' },
        async loadData() { this.loading = true; try { this.items = []; } catch (e) { alert('Failed to load mappings.'); } finally { this.loading = false; } },
        resetFilters() { this.filters = { vendor: '', material: '', is_preferred: '' }; this.loadData(); },
        openCreateModal() { alert('Create mapping - Coming soon'); },
        edit(item) { alert('Edit mapping - Coming soon'); },
        async deleteItem(item) { if (confirm('Delete this mapping?')) { alert('Delete functionality - Coming soon'); } }
    }
}
</script>
@endsection
