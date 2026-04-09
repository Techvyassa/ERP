@extends('tenant.layouts.inventory')

@section('title', 'Create Bin Location')
@section('page-title', 'Create New Bin Location')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="binForm()" x-init="loadWarehouses()">
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
                    <h2 class="text-2xl font-bold text-gray-900">Create New Bin Location</h2>
                    <p class="text-gray-600 mt-1">Rack / Bin structural master defining physical slot locations</p>
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
                        <select x-model="form.warehouse_id" 
                                @change="handleWarehouseChange()"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Warehouse</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ warehouse_master(warehouse_id)</p>
                    </div>

                    <!-- Bin Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bin Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
                        </label>
                        <div class="space-y-3">
                            <!-- Auto-generate option -->
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" 
                                       x-model="form.auto_generate_code"
                                       @change="handleAutoGenerateChange()"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label class="text-sm text-gray-700 cursor-pointer">Auto-generate code</label>
                            </div>
                            
                            <!-- Manual code generation -->
                            <div x-show="!form.auto_generate_code" x-transition>
                                <div class="flex items-center space-x-2">
                                    <!-- Manual Prefix -->
                                    <div class="w-32">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
                                        <input type="text" 
                                               x-model="form.manual_prefix"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="BIN"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    
                                    <!-- Separator -->
                                    <div class="flex items-center pb-6">
                                        <span class="text-gray-500 font-medium">-</span>
                                    </div>
                                    
                                    <!-- Sequential Number -->
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                                        <input type="text" 
                                               x-model="form.manual_number"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="0001"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                </div>
                                
                                <!-- Generated Code Display -->
                                <div class="mt-2">
                                    <input type="text" 
                                           x-model="form.bin_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="30"
                                           placeholder="BIN-0001"
                                           :class="{
                                               'border-red-500 focus:ring-red-500': errors.bin_code, 
                                               'border-gray-300 focus:ring-blue-500': !errors.bin_code
                                           }"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Generated bin code (auto-updates from prefix and number)</p>
                                    <template x-if="errors.bin_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.bin_code) ? errors.bin_code[0] : errors.bin_code"></p>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Auto-generate info -->
                            <div x-show="form.auto_generate_code" x-transition>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-magic text-green-600 mr-2"></i>
                                        <div class="text-sm text-green-800">
                                            <p class="font-medium">Auto-generation enabled</p>
                                            <p class="text-xs mt-1">Code will be generated based on warehouse:
                                                <template x-if="selectedWarehouse">
                                                    <span x-text="selectedWarehouse.warehouse_code + '-XXXX'"></span>
                                                </template>
                                                <span x-show="!selectedWarehouse">Select a warehouse to see prefix</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aisle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Route/Path
                        </label>
                        <input type="text" x-model="form.aisle" maxlength="10"
                               placeholder="Aisle identifier"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Rack -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Rack
                        </label>
                        <input type="text" x-model="form.rack" maxlength="10"
                               placeholder="Rack identifier"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Shelf -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Shelf
                        </label>
                        <input type="text" x-model="form.shelf" maxlength="10"
                               placeholder="Shelf level"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Max Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Max Weight (kg)
                        </label>
                        <input type="number" x-model="form.max_weight_kg" min="0" step="0.01"
                               placeholder="Capacity limit in kg"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Capacity limit in kg</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Bin Location</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Bin Locations</p>
                        <p>Rack / Bin structural master defining physical slot locations within warehouses. Pure structural — no stock quantities stored here.</p>
                        <p class="mt-2 text-xs">Used in: GRN Put-Away, Material Issuance, Physical Count</p>
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
                    <span x-show="!loading">Create Bin Location</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function binForm() {
    return {
        loading: false,
        warehouses: [],
        form: {
            warehouse_id: '',
            bin_code: '',
            aisle: '',
            rack: '',
            shelf: '',
            max_weight_kg: '',
            is_active: true,
            auto_generate_code: false,
            manual_prefix: '',
            manual_number: ''
        },
        
        selectedWarehouse: null,
        
        handleWarehouseChange() {
            // Update selected warehouse when warehouse changes
            this.selectedWarehouse = this.warehouses.find(wh => wh.id == this.form.warehouse_id);
            
            if (this.form.auto_generate_code) {
                this.showAutoGeneratedCode();
            } else {
                // Update manual prefix when warehouse changes
                this.form.manual_prefix = this.getDefaultPrefix(this.selectedWarehouse);
                this.updateManualCode();
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.bin_code = ''; // Clear field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
                this.errors.bin_code = null; // Clear any validation errors
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = this.getDefaultPrefix(this.selectedWarehouse);
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        getDefaultPrefix(warehouse) {
            if (!warehouse) return 'BIN';
            
            // Extract prefix from warehouse code (use first part before dash)
            const warehouseCode = warehouse.warehouse_code || 'WH';
            return warehouseCode.split('-')[0] || 'BIN';
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.bin_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.bin_code = '';
            }
        },
        
        showAutoGeneratedCode() {
            if (this.form.auto_generate_code && this.selectedWarehouse) {
                const prefix = this.getDefaultPrefix(this.selectedWarehouse);
                // Show a preview of what code will be
                console.log(`Auto-generated bin code will be: ${prefix}-XXXX (sequential)`);
            }
        },
        
        async loadWarehouses() {
            this.loading = true;
            try {
                const response = await fetch('/api/v1/warehouses');
                if (!response.ok) throw new Error('Failed to load warehouses');
                
                const data = await response.json();
                this.warehouses = data.data?.warehouses || [];
            } catch (error) {
                console.error('Failed to load warehouses:', error);
                this.showNotification('Failed to load warehouses', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await fetch('/api/v1/bin-locations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        this.errors = data.error.details;
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        this.showNotification(data.message || 'Failed to create bin location', 'error');
                    }
                    return;
                }
                
                this.showNotification('Bin location created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to create bin location:', error);
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
