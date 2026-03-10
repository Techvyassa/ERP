@extends('tenant.layouts.inventory')

@section('title', 'Create Warehouse')
@section('page-title', 'Create New Warehouse')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="warehouseForm()" x-init="loadUsers()">
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
                    <h2 class="text-2xl font-bold text-gray-900">Create New Warehouse</h2>
                    <p class="text-gray-600 mt-1">Physical storage location master</p>
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
                            Warehouse Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
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
                                               placeholder="WH"
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
                                           x-model="form.warehouse_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="20"
                                           placeholder="WH-0001"
                                           :class="{
                                               'border-red-500 focus:ring-red-500': errors.warehouse_code, 
                                               'border-gray-300 focus:ring-blue-500': !errors.warehouse_code
                                           }"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Generated warehouse code (auto-updates from prefix and number)</p>
                                    <template x-if="errors.warehouse_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.warehouse_code) ? errors.warehouse_code[0] : errors.warehouse_code"></p>
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
                                            <p class="text-xs mt-1">Code will be generated based on warehouse type:
                                                <span x-show="form.warehouse_type === 'RM'">RM-XXXX</span>
                                                <span x-show="form.warehouse_type === 'FG'">FG-XXXX</span>
                                                <span x-show="form.warehouse_type === 'PKG'">PKG-XXXX</span>
                                                <span x-show="form.warehouse_type === 'REJECTION'">REJ-XXXX</span>
                                                <span x-show="form.warehouse_type === 'WIP'">WIP-XXXX</span>
                                                <span x-show="!form.warehouse_type">WH-XXXX (default)</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <select x-model="form.warehouse_type" 
                                @change="handleWarehouseTypeChange()"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RM">RM - Raw Material</option>
                            <option value="FG">FG - Finished Goods</option>
                            <option value="PKG">PKG - Packaging</option>
                            <option value="REJECTION">REJECTION - Rejection</option>
                            <option value="WIP">WIP - Work in Progress</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Type determines auto-generated code prefix</p>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Physical Address
                        </label>
                        <textarea x-model="form.address" rows="3"
                                  placeholder="Enter warehouse physical address..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Physical address</p>
                    </div>

                    <!-- Incharge User -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Incharge User
                        </label>
                        <select x-model="form.incharge_user_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select User</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ users(user_id)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Warehouse</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Warehouse Master</p>
                        <p>Physical storage location master. Supports multiple warehouse types (RM, FG, Packaging, Rejection).</p>
                        <p class="mt-2 text-xs">Used in: Material Master, GRN, Stock Ledger, Inventory Transfer</p>
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
                    <span x-show="!loading">Create Warehouse</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function warehouseForm() {
    return {
        loading: false,
        users: [],
        form: {
            warehouse_code: '',
            warehouse_name: '',
            warehouse_type: '',
            address: '',
            incharge_user_id: '',
            is_active: true,
            auto_generate_code: false,
            manual_prefix: '',
            manual_number: ''
        },
        
        handleWarehouseTypeChange() {
            if (this.form.auto_generate_code) {
                this.showAutoGeneratedCode();
            } else {
                // Update manual prefix when warehouse type changes
                this.form.manual_prefix = this.getDefaultPrefix(this.form.warehouse_type);
                this.updateManualCode();
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.warehouse_code = ''; // Clear field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
                this.errors.warehouse_code = null; // Clear any validation errors
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = this.getDefaultPrefix(this.form.warehouse_type);
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        getDefaultPrefix(warehouseType) {
            const prefixes = {
                'RM': 'RM',
                'FG': 'FG',
                'PKG': 'PKG',
                'REJECTION': 'REJ',
                'WIP': 'WIP'
            };
            return prefixes[warehouseType] || 'WH';
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.warehouse_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.warehouse_code = '';
            }
        },
        
        showAutoGeneratedCode() {
            if (this.form.auto_generate_code && this.form.warehouse_type) {
                const prefix = {
                    'RM': 'RM',
                    'FG': 'FG',
                    'PKG': 'PKG',
                    'REJECTION': 'REJ',
                    'WIP': 'WIP'
                }[this.form.warehouse_type] || 'WH';
                
                // Show a preview of what code will be
                console.log(`Auto-generated warehouse code will be: ${prefix}-XXXX (sequential)`);
            }
        },
        
        async loadUsers() {
            this.loading = true;
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
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await fetch('/api/v1/warehouses', {
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
                        this.showNotification(data.message || 'Failed to create warehouse', 'error');
                    }
                    return;
                }
                
                this.showNotification('Warehouse created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to create warehouse:', error);
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
