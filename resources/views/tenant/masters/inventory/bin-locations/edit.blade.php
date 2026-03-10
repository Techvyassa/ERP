@extends('tenant.layouts.inventory')

@section('title', 'Edit Bin Location')
@section('page-title', 'Edit Bin Location')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="binForm()" x-init="loadBinData()">
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
                    <h2 class="text-2xl font-bold text-gray-900">Edit Bin Location</h2>
                    <p class="text-gray-600 mt-1">Update bin location information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Bin Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Bin Location Information</h3>
                <div class="space-y-6">
                    <!-- Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.warehouse_id" required
                                :class="{'border-red-500 focus:ring-red-500': errors.warehouse_id, 'border-gray-300 focus:ring-blue-500': !errors.warehouse_id}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select Warehouse</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name"></option>
                            </template>
                        </select>
                        <template x-if="errors.warehouse_id">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.warehouse_id) ? errors.warehouse_id[0] : errors.warehouse_id"></p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">→ warehouse_master(warehouse_id)</p>
                    </div>

                    <!-- Bin Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bin Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.bin_code" required maxlength="30"
                               placeholder="R01-S02-B03 (Rack-Shelf-Bin)"
                               :class="{'border-red-500 focus:ring-red-500': errors.bin_code, 'border-gray-300 focus:ring-blue-500': !errors.bin_code}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.bin_code">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.bin_code) ? errors.bin_code[0] : errors.bin_code"></p>
                        </template>
                    </div>

                    <!-- Aisle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Aisle
                        </label>
                        <input type="text" x-model="form.aisle" maxlength="10"
                               placeholder="Aisle identifier"
                               :class="{'border-red-500 focus:ring-red-500': errors.aisle, 'border-gray-300 focus:ring-blue-500': !errors.aisle}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.aisle">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.aisle) ? errors.aisle[0] : errors.aisle"></p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">Aisle identifier in warehouse layout</p>
                    </div>

                    <!-- Rack -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Rack
                        </label>
                        <input type="text" x-model="form.rack" maxlength="10"
                               placeholder="Rack identifier"
                               :class="{'border-red-500 focus:ring-red-500': errors.rack, 'border-gray-300 focus:ring-blue-500': !errors.rack}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.rack">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.rack) ? errors.rack[0] : errors.rack"></p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">Rack number/identifier</p>
                    </div>

                    <!-- Shelf -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Shelf
                        </label>
                        <input type="text" x-model="form.shelf" maxlength="10"
                               placeholder="Shelf identifier"
                               :class="{'border-red-500 focus:ring-red-500': errors.shelf, 'border-gray-300 focus:ring-blue-500': !errors.shelf}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.shelf">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.shelf) ? errors.shelf[0] : errors.shelf"></p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">Shelf level/position</p>
                    </div>

                    <!-- Max Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Max Weight (kg)
                        </label>
                        <input type="number" x-model="form.max_weight_kg" min="0" step="0.01"
                               placeholder="Maximum weight capacity"
                               :class="{'border-red-500 focus:ring-red-500': errors.max_weight_kg, 'border-gray-300 focus:ring-blue-500': !errors.max_weight_kg}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.max_weight_kg">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.max_weight_kg) ? errors.max_weight_kg[0] : errors.max_weight_kg"></p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">Maximum weight capacity in kilograms</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Bin Location</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Enable for inventory transactions</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Bin Locations</p>
                        <p>Physical storage positions within warehouse racks for precise inventory management and tracking.</p>
                        <p class="mt-2 text-xs">Used in: Material Receipts, Inventory Transfers, Stock Taking, Picking</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Update Bin Location</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function binForm() {
    return {
        loading: false,
        errors: {},
        warehouses: [],
        binId: null,
        form: {
            warehouse_id: '',
            bin_code: '',
            aisle: '',
            rack: '',
            shelf: '',
            max_weight_kg: '',
            is_active: true
        },
        
        async loadBinData() {
            // Get bin location ID from URL
            const urlParts = window.location.pathname.split('/');
            this.binId = urlParts[urlParts.length - 2]; // Get ID before /edit
            
            console.log('URL Path:', window.location.pathname);
            console.log('Extracted Bin Location ID:', this.binId);
            
            if (!this.binId || isNaN(this.binId)) {
                console.error('Invalid bin location ID:', this.binId);
                this.showNotification('Invalid bin location ID', 'error');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}';
                }, 2000);
                return;
            }
            
            this.loading = true;
            try {
                // Load bin location data and warehouses
                const binResponse = await fetch(`/api/v1/bin-locations/${this.binId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Bin Location API Response Status:', binResponse.status);
                
                if (!binResponse.ok) {
                    const errorData = await binResponse.json();
                    console.error('Bin Location API Error:', errorData);
                    throw new Error(errorData.message || 'Failed to load bin location data');
                }
                
                const binData = await binResponse.json();
                console.log('Bin Location Data:', binData);
                
                this.form = {
                    warehouse_id: binData.data?.bin_location?.warehouse_id || '',
                    bin_code: binData.data?.bin_location?.bin_code || '',
                    aisle: binData.data?.bin_location?.aisle || '',
                    rack: binData.data?.bin_location?.rack || '',
                    shelf: binData.data?.bin_location?.shelf || '',
                    max_weight_kg: binData.data?.bin_location?.max_weight_kg || '',
                    is_active: binData.data?.bin_location?.is_active !== undefined ? binData.data.bin_location.is_active : true
                };
                
                console.log('Form populated:', this.form);
                
                // Load warehouses separately
                await this.loadWarehouses();
                
            } catch (error) {
                console.error('Failed to load bin location data:', error);
                this.showNotification('Failed to load bin location data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async loadWarehouses() {
            try {
                const response = await fetch('/api/v1/warehouses');
                if (!response.ok) throw new Error('Failed to load warehouses');
                
                const data = await response.json();
                this.warehouses = data.data?.warehouses || [];
            } catch (error) {
                console.error('Failed to load warehouses:', error);
                this.showNotification('Failed to load warehouses', 'error');
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                console.log('Submitting bin location update with data:', this.form);
                console.log('Bin Location ID:', this.binId);
                
                const response = await fetch(`/api/v1/bin-locations/${this.binId}`, {
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
                        this.showNotification(data.message || 'Failed to update bin location', 'error');
                    }
                    return;
                }
                
                this.showNotification('Bin location updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to update bin location:', error);
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
