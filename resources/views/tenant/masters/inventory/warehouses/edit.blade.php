@extends('tenant.layouts.inventory')

@section('title', 'Edit Warehouse')
@section('page-title', 'Edit Warehouse')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="warehouseForm()" x-init="loadWarehouseData()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Warehouse</h2>
                    <p class="text-gray-600 mt-1">Update warehouse information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Warehouse Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Warehouse Information</h3>
                <div class="space-y-6">
                    <!-- Warehouse Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.warehouse_code" required maxlength="20"
                               placeholder="WH-001, WH-002"
                               :class="{'border-red-500 focus:ring-red-500': errors.warehouse_code, 'border-gray-300 focus:ring-blue-500': !errors.warehouse_code}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.warehouse_code">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.warehouse_code) ? errors.warehouse_code[0] : errors.warehouse_code"></p>
                        </template>
                    </div>

                    <!-- Warehouse Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.warehouse_name" required maxlength="100"
                               placeholder="Masala RM Store"
                               :class="{'border-red-500 focus:ring-red-500': errors.warehouse_name, 'border-gray-300 focus:ring-blue-500': !errors.warehouse_name}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.warehouse_name">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.warehouse_name) ? errors.warehouse_name[0] : errors.warehouse_name"></p>
                        </template>
                    </div>

                    <!-- Warehouse Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.warehouse_type" required
                                :class="{'border-red-500 focus:ring-red-500': errors.warehouse_type, 'border-gray-300 focus:ring-blue-500': !errors.warehouse_type}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RM">Raw Material</option>
                            <option value="FG">Finished Goods</option>
                            <option value="PKG">Packaging</option>
                            <option value="REJECTION">Rejection</option>
                            <option value="WIP">Work in Progress</option>
                        </select>
                        <template x-if="errors.warehouse_type">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.warehouse_type) ? errors.warehouse_type[0] : errors.warehouse_type"></p>
                        </template>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Address
                        </label>
                        <textarea x-model="form.address" rows="3"
                                  :class="{'border-red-500 focus:ring-red-500': errors.address, 'border-gray-300 focus:ring-blue-500': !errors.address}"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent"
                                  placeholder="Complete warehouse address"></textarea>
                        <template x-if="errors.address">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.address) ? errors.address[0] : errors.address"></p>
                        </template>
                    </div>

                    <!-- Incharge User -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Incharge
                        </label>
                        <select x-model="form.incharge_user_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Incharge</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Warehouse</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Enable for transactions</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Warehouse Master</p>
                        <p>Physical storage locations for inventory management with tracking and reporting capabilities.</p>
                        <p class="mt-2 text-xs">Used in: Material Receipts, Inventory Transfers, Production, Sales</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Update Warehouse</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function warehouseForm() {
    return {
        loading: false,
        errors: {},
        users: [],
        warehouseId: null,
        form: {
            warehouse_code: '',
            warehouse_name: '',
            warehouse_type: '',
            address: '',
            incharge_user_id: '',
            is_active: true
        },
        
        async loadWarehouseData() {
            // Get warehouse ID from URL
            const urlParts = window.location.pathname.split('/');
            this.warehouseId = urlParts[urlParts.length - 2]; // Get ID before /edit
            
            console.log('URL Path:', window.location.pathname);
            console.log('Extracted Warehouse ID:', this.warehouseId);
            
            if (!this.warehouseId || isNaN(this.warehouseId)) {
                console.error('Invalid warehouse ID:', this.warehouseId);
                this.showNotification('Invalid warehouse ID', 'error');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}';
                }, 2000);
                return;
            }
            
            this.loading = true;
            try {
                // Load warehouse data and users
                const warehouseResponse = await fetch(`/api/v1/warehouses/${this.warehouseId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Warehouse API Response Status:', warehouseResponse.status);
                
                if (!warehouseResponse.ok) {
                    const errorData = await warehouseResponse.json();
                    console.error('Warehouse API Error:', errorData);
                    throw new Error(errorData.message || 'Failed to load warehouse data');
                }
                
                const warehouseData = await warehouseResponse.json();
                console.log('Warehouse Data:', warehouseData);
                
                this.form = {
                    warehouse_code: warehouseData.data?.warehouse?.warehouse_code || '',
                    warehouse_name: warehouseData.data?.warehouse?.warehouse_name || '',
                    warehouse_type: warehouseData.data?.warehouse?.warehouse_type || '',
                    address: warehouseData.data?.warehouse?.address || '',
                    incharge_user_id: warehouseData.data?.warehouse?.incharge_user_id || '',
                    is_active: warehouseData.data?.warehouse?.is_active !== undefined ? warehouseData.data.warehouse.is_active : true
                };
                
                console.log('Form populated:', this.form);
                
                // Load users separately
                await this.loadUsers();
                
            } catch (error) {
                console.error('Failed to load warehouse data:', error);
                this.showNotification('Failed to load warehouse data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async loadUsers() {
            try {
                // For users, we'll use a mock array for now since the API might not be implemented
                this.users = [
                    { id: 1, name: 'Admin User', email: 'admin@example.com' },
                    { id: 2, name: 'Manager', email: 'manager@example.com' },
                    { id: 3, name: 'Supervisor', email: 'supervisor@example.com' }
                ];
            } catch (error) {
                console.error('Failed to load users:', error);
                this.showNotification('Failed to load users', 'error');
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                console.log('Submitting warehouse update with data:', this.form);
                console.log('Warehouse ID:', this.warehouseId);
                
                const response = await fetch(`/api/v1/warehouses/${this.warehouseId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                console.log('Update API Response Status:', response.status);
                
                const data = await response.json();
                console.log('Update API Response Data:', data);
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        this.errors = data.error.details;
                        console.log('Validation errors:', this.errors);
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        console.log('API Error:', data);
                        this.showNotification(data.message || 'Failed to update warehouse', 'error');
                    }
                    return;
                }
                
                this.showNotification('Warehouse updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to update warehouse:', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                this.loading = false;
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
