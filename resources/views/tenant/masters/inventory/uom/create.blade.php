@extends('tenant.layouts.inventory')

@section('title', 'Create UOM')
@section('page-title', 'Create New UOM')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="uomForm()" x-init="loadBaseUoms()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Processing...</span>
        </div>
    </div>

    <!-- Notification Container (for non-toast notifications) -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New UOM</h2>
                    <p class="text-gray-600 mt-1">Units of Measurement with base UOM conversion factors</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- UOM Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">UOM Information</h3>
                <div class="space-y-6">
                    <!-- UOM Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
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
                                               placeholder="UOM"
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
                                           x-model="form.uom_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="10"
                                           placeholder="UOM-0001"
                                           :class="{
                                               'border-red-500 focus:ring-red-500': errors.uom_code, 
                                               'border-gray-300 focus:ring-blue-500': !errors.uom_code
                                           }"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Generated UOM code (auto-updates from prefix and number)</p>
                                    <template x-if="errors.uom_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_code) ? errors.uom_code[0] : errors.uom_code"></p>
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
                                            <p class="text-xs mt-1">Code will be generated based on UOM type:
                                                <span x-show="form.uom_type === 'weight'">KG-XXXX</span>
                                                <span x-show="form.uom_type === 'volume'">LIT-XXXX</span>
                                                <span x-show="form.uom_type === 'qty'">PCS-XXXX</span>
                                                <span x-show="form.uom_type === 'length'">MTR-XXXX</span>
                                                <span x-show="!form.uom_type">UOM-XXXX (default)</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UOM Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.uom_name" required maxlength="50"
                               placeholder="Kilogram, Gram, Litre..."
                               :class="{'border-red-500 focus:ring-red-500': errors.uom_name, 'border-gray-300 focus:ring-blue-500': !errors.uom_name}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.uom_name">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_name) ? errors.uom_name[0] : errors.uom_name"></p>
                        </template>
                    </div>

                    <!-- UOM Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_type" 
                                @change="handleUOMTypeChange()"
                                required
                                :class="{'border-red-500 focus:ring-red-500': errors.uom_type, 'border-gray-300 focus:ring-blue-500': !errors.uom_type}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="weight">weight - Weight</option>
                            <option value="volume">volume - Volume</option>
                            <option value="qty">qty - Quantity</option>
                            <option value="length">length - Length</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Type determines auto-generated code prefix</p>
                        <template x-if="errors.uom_type">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_type) ? errors.uom_type[0] : errors.uom_type"></p>
                        </template>
                    </div>

                    <!-- Base UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Base UOM
                        </label>
                        <select x-model="form.base_uom_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">None (This is base UOM)</option>
                            <template x-for="uom in baseUoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Self-ref → uom_master(uom_id)</p>
                    </div>

                    <!-- Conversion Factor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Conversion Factor <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.conversion_factor" required min="0" step="0.000001"
                               placeholder="1"
                               :class="{'border-red-500 focus:ring-red-500': errors.conversion_factor, 'border-gray-300 focus:ring-blue-500': !errors.conversion_factor}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">1 GM = 0.001 KG (conversion factor)</p>
                        <template x-if="errors.conversion_factor">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.conversion_factor) ? errors.conversion_factor[0] : errors.conversion_factor"></p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active UOM</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About UOM Master</p>
                        <p>Units of Measurement with base UOM conversion factors for cross-unit calculations.</p>
                        <p class="mt-2 text-xs">Used in: Material, Product, BOM, GRN</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create UOM</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function uomForm() {
    return {
        loading: false,
        baseUoms: [],
        errors: {},
        form: {
            uom_code: '',
            uom_name: '',
            uom_type: '',
            base_uom_id: '',
            conversion_factor: 1,
            is_active: true,
            auto_generate_code: false,
            manual_prefix: '',
            manual_number: ''
        },
        
        handleUOMTypeChange() {
            if (this.form.auto_generate_code) {
                this.showAutoGeneratedCode();
            } else {
                // Update manual prefix when UOM type changes
                this.form.manual_prefix = this.getDefaultPrefix(this.form.uom_type);
                this.updateManualCode();
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.uom_code = ''; // Clear field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
                this.errors.uom_code = null; // Clear any validation errors
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = this.getDefaultPrefix(this.form.uom_type);
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        getDefaultPrefix(uomType) {
            const prefixes = {
                'weight': 'KG',
                'volume': 'LIT',
                'qty': 'PCS',
                'length': 'MTR'
            };
            return prefixes[uomType] || 'UOM';
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.uom_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.uom_code = '';
            }
        },
        
        showAutoGeneratedCode() {
            if (this.form.auto_generate_code && this.form.uom_type) {
                const prefix = this.getDefaultPrefix(this.form.uom_type);
                // Show a preview of what code will be
                console.log(`Auto-generated UOM code will be: ${prefix}-XXXX (sequential)`);
            }
        },
        
        async loadBaseUoms() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/uoms', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to load UOMs');
                const data = await response.json();
                console.log('Base UOMs loaded:', data);
                this.baseUoms = data.data?.uoms || data.data || [];
            } catch (error) {
                console.error('Failed to load base UOMs:', error);
                this.showNotification('Failed to load base UOMs', 'error');
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await fetch('/api/v1/uoms', {
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
                        this.showNotification('Please fix the validation errors', 'error');
                    } else {
                        this.showNotification(data.message || 'Failed to create UOM', 'error');
                    }
                    return;
                }
                
                this.showNotification('UOM created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to create UOM:', error);
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
