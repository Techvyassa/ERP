@extends('tenant.layouts.bom')

@section('title', 'BOM Header')
@section('page-title', 'Bill of Materials - Header')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

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
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewDetails(item)" 
                                            class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors" 
                                            title="View Details">
                                        <i class="fas fa-eye mr-1"></i>
                                        View
                                    </button>
                                    <button @click="edit(item)" 
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" 
                                            title="Edit">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <template x-if="item.bom_status === 'ACTIVE'">
                                        <button @click="deactivateItem(item)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors" 
                                                title="Make Obsolete">
                                            <i class="fas fa-ban mr-1"></i>
                                            Deactivate
                                        </button>
                                    </template>
                                    <template x-if="item.bom_status === 'OBSOLETE'">
                                        <button @click="activateItem(item)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors" 
                                                title="Make Active">
                                            <i class="fas fa-check mr-1"></i>
                                            Activate
                                        </button>
                                    </template>
                                    <template x-if="item.bom_status === 'DRAFT'">
                                        <button @click="activateItem(item)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" 
                                                title="Make Active">
                                            <i class="fas fa-play mr-1"></i>
                                            Activate
                                        </button>
                                    </template>
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
function bomData() {
    return {
        items: [],
        loading: false,
        filters: { search: '', product: '', bom_status: '' },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await fetch(`/api/v1/bom-headers?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load BOMs');
                }

                // API returns data directly as array, not nested
                let boms = Array.isArray(data.data) ? data.data : (data.data && data.data.boms) ? data.data.boms : [];
                
                // Client-side filtering for product
                if (this.filters.product) {
                    const productLower = this.filters.product.toLowerCase();
                    boms = boms.filter(b => 
                        (b.product && b.product.product_name && b.product.product_name.toLowerCase().includes(productLower)) ||
                        (b.product && b.product.product_code && b.product.product_code.toLowerCase().includes(productLower))
                    );
                }

                // Transform data for display
                this.items = boms.map(b => ({
                    id: b.id,
                    bom_code: b.bom_code,
                    product_id: b.product_id,
                    product_name: b.product ? b.product.product_name : 'N/A',
                    version: b.version || 'v1.0',
                    batch_size: b.batch_size || 1,
                    output_uom_id: b.output_uom_id,
                    output_uom_name: b.output_uom ? b.output_uom.uom_code : '',
                    effective_from: b.effective_from || '-',
                    bom_status: b.bom_status || 'DRAFT'
                }));

                // Apply status filter
                if (this.filters.bom_status) {
                    this.items = this.items.filter(item => item.bom_status === this.filters.bom_status);
                }
            } catch (error) {
                console.error('Failed to load BOMs:', error);
                alert(error.message || 'Failed to load BOMs. Please try again.');
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        
        getBOMStatus(bom) {
            return bom.bom_status || 'DRAFT';
        },
        
        resetFilters() {
            this.filters = { search: '', product: '', bom_status: '' };
            this.loadData();
        },
        
        viewDetails(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}';
            window.location.href = `${baseUrl}/${item.id}/view`;
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        async deactivateItem(item) {
            if (confirm('Are you sure you want to make BOM: ' + item.bom_code + ' obsolete?')) {
                try {
                    const response = await fetch(`/api/v1/bom-headers/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            bom_code: item.bom_code,
                            product_id: item.product_id,
                            version: item.version,
                            batch_size: item.batch_size,
                            output_uom_id: item.output_uom_id,
                            effective_from: item.effective_from,
                            bom_status: 'OBSOLETE'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to deactivate BOM', 'error');
                        return;
                    }
                    
                    this.showNotification('BOM made obsolete successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to deactivate BOM:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },

        async activateItem(item) {
            const action = item.bom_status === 'DRAFT' ? 'activate' : 'reactivate';
            if (confirm(`Are you sure you want to ${action} BOM: ` + item.bom_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/bom-headers/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            bom_code: item.bom_code,
                            product_id: item.product_id,
                            version: item.version,
                            batch_size: item.batch_size,
                            output_uom_id: item.output_uom_id,
                            effective_from: item.effective_from,
                            bom_status: 'ACTIVE'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to activate BOM', 'error');
                        return;
                    }
                    
                    this.showNotification('BOM activated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to activate BOM:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}
</script>
@endsection
