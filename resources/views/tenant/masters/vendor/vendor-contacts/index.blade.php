@extends('tenant.layouts.vendor')

@section('title', 'Vendor Contacts')
@section('page-title', 'Vendor Contact Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="contactData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Contacts</h2>
                <p class="text-gray-600 mt-1">Manage vendor contact persons and details</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts/create' : '/org/' . $organization->org_slug . '/vendor-contacts/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                <i class="fas fa-plus mr-2"></i>Add Contact
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.vendor_id" @input="loadData" placeholder="Vendor ID..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.contact_type" @change="loadData" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="SALES">Sales</option>
                <option value="FINANCE">Finance</option>
                <option value="LOGISTICS">Logistics</option>
                <option value="GM">General Manager</option>
                <option value="TECHNICAL">Technical</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Primary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading contacts...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <i class="fas fa-address-book text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No vendor contacts found. Click "Add Contact" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900" x-text="item.vendor ? item.vendor.vendor_name : '-'"></div>
                                    <div class="text-gray-500" x-text="item.vendor ? item.vendor.vendor_code : ''"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.contact_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800" x-text="item.contact_type || 'N/A'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.phone || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="item.email || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="item.is_primary ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" 
                                      x-text="item.is_primary ? 'Yes' : 'No'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                      x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="edit(item)" 
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" 
                                            title="Edit">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <template x-if="item.is_active">
                                        <button @click="deactivateItem(item)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors" 
                                                title="Deactivate">
                                            <i class="fas fa-ban mr-1"></i>
                                            Deactivate
                                        </button>
                                    </template>
                                    <template x-if="!item.is_active">
                                        <button @click="activateItem(item)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors" 
                                                title="Activate">
                                            <i class="fas fa-check mr-1"></i>
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
function contactData() {
    return {
        items: [],
        loading: false,
        filters: { vendor_id: '', contact_type: '', is_active: '' }, // Show all statuses by default
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.vendor_id) params.append('vendor_id', this.filters.vendor_id);
                if (this.filters.contact_type) params.append('contact_type', this.filters.contact_type);
                if (this.filters.is_active !== '' && this.filters.is_active !== null) params.append('is_active', this.filters.is_active);

                const response = await fetch(`/api/v1/vendor-contacts?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load contacts');
                }

                this.items = (data && data.data && data.data.contacts) ? data.data.contacts : [];
            } catch (error) {
                console.error('Failed to load contacts:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Failed to load contacts. Please try again.', type: 'error' } }));
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { vendor_id: '', contact_type: '', is_active: '' }; // Reset to show all statuses
            this.loadData();
        },
        
        edit(item) {
            const baseUrl = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}";
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        deactivateItem(item) {
            window.dispatchEvent(new CustomEvent('open-confirm', {
                detail: {
                    title: 'Deactivate Contact',
                    message: `Are you sure you want to deactivate contact: ${item.contact_name}?`,
                    confirmText: 'Deactivate',
                    cancelText: 'Cancel',
                    confirmColor: 'red',
                    onConfirm: async () => {
                        try {
                            const response = await fetch(`/api/v1/vendor-contacts/${item.id}`, {
                                method: 'PUT',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    ...item,
                                    is_active: false
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (!response.ok) {
                                this.showNotification(data.message || 'Failed to deactivate contact', 'error');
                                return;
                            }
                            
                            this.showNotification('Contact deactivated successfully', 'success');
                            this.loadData();
                        } catch (error) {
                            console.error('Failed to deactivate contact:', error);
                            this.showNotification('Network error. Please try again.', 'error');
                        }
                    }
                }
            }));
        },

        activateItem(item) {
            window.dispatchEvent(new CustomEvent('open-confirm', {
                detail: {
                    title: 'Activate Contact',
                    message: `Are you sure you want to activate contact: ${item.contact_name}?`,
                    confirmText: 'Activate',
                    cancelText: 'Cancel',
                    confirmColor: 'blue',
                    onConfirm: async () => {
                        try {
                            const response = await fetch(`/api/v1/vendor-contacts/${item.id}`, {
                                method: 'PUT',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    ...item,
                                    is_active: true
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (!response.ok) {
                                this.showNotification(data.message || 'Failed to activate contact', 'error');
                                return;
                            }
                            
                            this.showNotification('Contact activated successfully', 'success');
                            this.loadData();
                        } catch (error) {
                            console.error('Failed to activate contact:', error);
                            this.showNotification('Network error. Please try again.', 'error');
                        }
                    }
                }
            }));
        },

        showNotification(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        }
    }
}
</script>
@endsection
