@extends('tenant.layouts.vendor')

@section('title', 'Vendors')
@section('page-title', 'Vendor Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="vendorData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Master</h2>
                <p class="text-gray-600 mt-1">Manage suppliers and vendor information</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors/create' : '/org/' . $organization->org_slug . '/vendors/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                <i class="fas fa-plus mr-2"></i>Add Vendor
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search by code or name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.vendor_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="SUPPLIER">Supplier</option>
                <option value="SERVICE">Service Provider</option>
                <option value="TRADER">Trader</option>
            </select>
            <select x-model="filters.is_approved" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Approved</option>
                <option value="0">Pending</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GSTIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Terms</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading vendors...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-handshake text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No vendors found. Click "Add Vendor" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.vendor_code"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900" x-text="item.vendor_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-blue-100 text-blue-800': item.vendor_type === 'SUPPLIER',
                                          'bg-green-100 text-green-800': item.vendor_type === 'SERVICE',
                                          'bg-yellow-100 text-yellow-800': item.vendor_type === 'TRADER'
                                      }"
                                      x-text="item.vendor_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.gstin || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.payment_terms || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="item.is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                      x-text="item.is_approved ? 'Approved' : 'Pending'"></span>
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
                                            title="Blacklist">
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
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> vendors
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
function vendorData() {
    return {
        items: [],
        loading: false,
        filters: { search: '', vendor_type: '', is_approved: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('blacklisted', '0'); // Exclude blacklisted vendors by default
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.vendor_type) params.append('vendor_type', this.filters.vendor_type);
                if (this.filters.is_approved !== '' && this.filters.is_approved !== null) params.append('is_approved', this.filters.is_approved);
                params.append('page', this.pagination.current_page);
                params.append('per_page', this.pagination.per_page);

                const response = await fetch(`/api/v1/vendors?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load vendors');
                }

                this.items = (data && data.data && data.data.vendors) ? data.data.vendors : [];
                
                // Update pagination
                if (data && data.data && data.data.pagination) {
                    this.pagination = {
                        current_page: data.data.pagination.current_page,
                        last_page: data.data.pagination.last_page,
                        per_page: data.data.pagination.per_page,
                        total: data.data.pagination.total,
                        from: data.data.pagination.total > 0 ? ((data.data.pagination.current_page - 1) * data.data.pagination.per_page) + 1 : 0,
                        to: Math.min(data.data.pagination.current_page * data.data.pagination.per_page, data.data.pagination.total)
                    };
                }
            } catch (error) {
                console.error('Failed to load vendors:', error);
                alert(error.message || 'Failed to load vendors. Please try again.');
                this.items = [];
                this.pagination = { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 };
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
            this.filters = { search: '', vendor_type: '', is_approved: '' };
            this.loadData();
        },
        
        openCreateModal() {
            alert('Create vendor form - Coming soon');
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure you want to blacklist vendor: ' + item.vendor_code + '? This will prevent them from being used in RFQs and POs.')) {
                try {
                    const response = await fetch(`/api/v1/vendors/${item.id}`, {
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
                        throw new Error((data && data.message) ? data.message : 'Failed to blacklist vendor');
                    }

                    alert('Vendor blacklisted successfully');
                    this.loadData();
                } catch (error) {
                    console.error('Failed to blacklist vendor:', error);
                    alert(error.message || 'Failed to blacklist vendor. Please try again.');
                }
            }
        }
    }
}
</script>
@endsection
