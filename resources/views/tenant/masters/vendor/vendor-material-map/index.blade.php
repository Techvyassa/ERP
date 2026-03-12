@extends('tenant.layouts.vendor')

@section('title', 'Vendor Material Map')
@section('page-title', 'Vendor Material Mapping')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="mapData()" x-init="loadData()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Material Mapping</h2>
                <p class="text-gray-600 mt-1">Map materials to vendors with pricing and lead times</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map/create' : '/org/' . $organization->org_slug . '/vendor-material-map/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                <i class="fas fa-plus mr-2"></i>Add Mapping
            </a>
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
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="edit(item)" 
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" 
                                            title="Edit">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <button @click="deleteItem(item)" 
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors" 
                                            title="Deactivate">
                                        <i class="fas fa-ban mr-1"></i>
                                        Delete
                                    </button>
                                </div>
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
        items: [],
        loading: false,
        filters: { vendor: '', material: '', is_preferred: '' },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('is_active', '1'); // Default to active only
                if (this.filters.is_preferred !== '') params.append('is_preferred', this.filters.is_preferred);

                const response = await fetch(`/api/v1/vendor-material-map?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load mappings');
                }

                let mappings = (data && data.data && data.data.avl) ? data.data.avl : [];
                
                // Client-side filtering for vendor and material names
                if (this.filters.vendor) {
                    const vendorLower = this.filters.vendor.toLowerCase();
                    mappings = mappings.filter(m => 
                        (m.vendor && m.vendor.vendor_name && m.vendor.vendor_name.toLowerCase().includes(vendorLower)) ||
                        (m.vendor && m.vendor.vendor_code && m.vendor.vendor_code.toLowerCase().includes(vendorLower))
                    );
                }
                if (this.filters.material) {
                    const materialLower = this.filters.material.toLowerCase();
                    mappings = mappings.filter(m => 
                        (m.material && m.material.material_name && m.material.material_name.toLowerCase().includes(materialLower)) ||
                        (m.material && m.material.material_code && m.material.material_code.toLowerCase().includes(materialLower))
                    );
                }

                // Transform data for display
                this.items = mappings.map(m => ({
                    id: m.id,
                    vendor_name: m.vendor ? m.vendor.vendor_name : 'N/A',
                    material_name: m.material ? (m.material.material_code + ' - ' + m.material.material_name) : 'N/A',
                    vendor_material_code: m.vendor_material_code,
                    last_purchase_price: m.last_purchase_price,
                    lead_time_days: m.lead_time_days,
                    min_order_qty: m.min_order_qty,
                    is_preferred: m.is_preferred,
                    is_active: m.is_active
                }));
            } catch (error) {
                console.error('Failed to load mappings:', error);
                alert(error.message || 'Failed to load mappings. Please try again.');
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { vendor: '', material: '', is_preferred: '' };
            this.loadData();
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure you want to deactivate this vendor-material mapping?\n\nThis will set the mapping as inactive.')) {
                try {
                    const response = await fetch(`/api/v1/vendor-material-map/${item.id}`, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const data = await response.json();

                    if (!response.ok || !data || data.success !== true) {
                        throw new Error((data && data.message) ? data.message : 'Failed to deactivate mapping');
                    }

                    // Remove item from display immediately
                    this.items = this.items.filter(i => i.id !== item.id);
                    
                    alert('Vendor-material mapping deactivated successfully');
                } catch (error) {
                    console.error('Failed to deactivate mapping:', error);
                    alert(error.message || 'Failed to deactivate mapping. Please try again.');
                }
            }
        }
    }
}
</script>
@endsection
