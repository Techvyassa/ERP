@extends('tenant.layouts.inventory')

@section('title', 'Create Material')
@section('page-title', 'Create New Material')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="materialForm()" x-init="loadDropdowns()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Material</h2>
                    <p class="text-gray-600 mt-1">Add raw material, packaging, consumable or semi-finished item</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Material Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
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
                                               placeholder="RM"
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
                                           x-model="form.material_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="30"
                                           placeholder="RM-0001"
                                           :class="{
                                               'border-red-500 focus:ring-red-500': errors.material_code, 
                                               'border-gray-300 focus:ring-blue-500': !errors.material_code
                                           }"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Generated material code (auto-updates from prefix and number)</p>
                                    <template x-if="errors.material_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.material_code) ? errors.material_code[0] : errors.material_code"></p>
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
                                            <p class="text-xs mt-1">Code will be generated based on material type:
                                                <span x-show="form.material_type === 'RAW'">RM-XXXX</span>
                                                <span x-show="form.material_type === 'PACKAGING'">PKG-XXXX</span>
                                                <span x-show="form.material_type === 'CONSUMABLE'">CON-XXXX</span>
                                                <span x-show="form.material_type === 'SEMI'">SF-XXXX</span>
                                                <span x-show="!form.material_type">Select a material type first</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Material Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.material_name" required maxlength="200"
                               placeholder="Cinnamon Bark, Dhaniya..."
                               :class="{'border-red-500 focus:ring-red-500': errors.material_name, 'border-gray-300 focus:ring-blue-500': !errors.material_name}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.material_name">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.material_name) ? errors.material_name[0] : errors.material_name"></p>
                        </template>
                    </div>

                    <!-- Material Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.material_type" 
        @change="handleMaterialTypeChange()"
        required
        :class="{'border-red-500 focus:ring-red-500': errors.material_type, 'border-gray-300 focus:ring-blue-500': !errors.material_type}"
        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RAW">Raw Material</option>
                            <option value="PACKAGING">Packaging</option>
                            <option value="CONSUMABLE">Consumable</option>
                            <option value="SEMI">Semi-finished</option>
                        </select>
                        <template x-if="errors.material_type">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.material_type) ? errors.material_type[0] : errors.material_type"></p>
                        </template>
                    </div>

                    <!-- UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Base UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_id" required
                                :class="{'border-red-500 focus:ring-red-500': errors.uom_id, 'border-gray-300 focus:ring-blue-500': !errors.uom_id}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <template x-if="errors.uom_id">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_id) ? errors.uom_id[0] : errors.uom_id"></p>
                        </template>
                    </div>

                    <!-- Purchase UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Purchase UOM
                        </label>
                        <select x-model="form.purchase_uom_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Same as Base UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- HSN Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            HSN Code
                        </label>
                        <select x-model="form.hsn_code_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select HSN Code</option>
                            <template x-for="hsn in hsnCodes" :key="hsn.id">
                                <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Inventory Settings -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Inventory Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Default Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default Warehouse
                        </label>
                        <select x-model="form.default_warehouse_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Warehouse</option>
                            <template x-for="warehouse in warehouses" :key="warehouse.id">
                                <option :value="warehouse.id" x-text="warehouse.warehouse_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Reorder Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reorder Level
                        </label>
                        <input type="number" x-model="form.reorder_level" min="0" step="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum stock level</p>
                    </div>

                    <!-- Safety Stock -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Safety Stock
                        </label>
                        <input type="number" x-model="form.safety_stock" min="0" step="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Buffer quantity</p>
                    </div>

                    <!-- Lead Time -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lead Time (Days)
                        </label>
                        <input type="number" x-model="form.lead_time_days" min="0" step="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Procurement lead time</p>
                    </div>

                    <!-- Shelf Life -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Shelf Life (Days)
                        </label>
                        <input type="number" x-model="form.shelf_life_days" min="0" step="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Expiry tracking</p>
                    </div>
                </div>
            </div>

            <!-- Quality Control -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Quality Control</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- QC Required -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.qc_required" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">QC Required</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Quality check needed</p>
                    </div>

                    <!-- Inspection Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Inspection Type
                        </label>
                        <select x-model="form.inspection_type"
                                :disabled="!form.qc_required"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
                            <option value="AQL">AQL - Acceptable Quality Limit</option>
                            <option value="100%">100% - Full Inspection</option>
                            <option value="random">Random Sampling</option>
                        </select>
                    </div>

                    <!-- Batch Tracking -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_batch_tracked" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Lot Tracking</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Track by lot number</p>
                    </div>
                </div>
            </div>

            <!-- Costing -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Costing</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Standard Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Cost
                        </label>
                        <input type="number" x-model="form.standard_cost" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Base cost per UOM</p>
                    </div>

                    <!-- Valuation Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Valuation Method
                        </label>
                        <select x-model="form.valuation_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="FIFO">FIFO - First In First Out</option>
                            <option value="LIFO">LIFO - Last In First Out</option>
                            <option value="weighted">Weighted Average</option>
                            <option value="standard">Standard Cost</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Material</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Enable for transactions</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Material Master</p>
                        <p>Raw materials, packaging, consumables and semi-finished goods with inventory tracking and costing.</p>
                        <p class="mt-2 text-xs">Used in: Purchase Orders, Sales Orders, Production, Inventory</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Material</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function materialForm() {
    return {
        loading: false,
        errors: {},
        uoms: [],
        hsnCodes: [],
        warehouses: [],
        form: {
            material_code: '',
            material_name: '',
            material_type: '',
            uom_id: '',
            purchase_uom_id: '',
            hsn_code_id: '',
            default_warehouse_id: '',
            reorder_level: 0,
            safety_stock: 0,
            lead_time_days: 0,
            shelf_life_days: '',
            qc_required: true,
            inspection_type: 'AQL',
            is_batch_tracked: false,
            standard_cost: 0,
            valuation_method: 'FIFO',
            is_active: true,
            auto_generate_code: false,
            manual_prefix: '',
            manual_number: ''
        },
        
        handleMaterialTypeChange() {
            if (this.form.auto_generate_code) {
                this.showAutoGeneratedCode();
            } else {
                // Update manual prefix when material type changes
                this.form.manual_prefix = this.getDefaultPrefix(this.form.material_type);
                this.updateManualCode();
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.material_code = ''; // Clear the field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
                this.errors.material_code = null; // Clear any validation errors
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = this.getDefaultPrefix(this.form.material_type);
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        getDefaultPrefix(materialType) {
            const prefixes = {
                'RAW': 'RM',
                'PACKAGING': 'PKG',
                'CONSUMABLE': 'CON',
                'SEMI': 'SF'
            };
            return prefixes[materialType] || 'MAT';
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.material_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.material_code = '';
            }
        },
        
        showAutoGeneratedCode() {
            if (this.form.auto_generate_code && this.form.material_type) {
                const prefix = {
                    'RAW': 'RM',
                    'PACKAGING': 'PKG',
                    'CONSUMABLE': 'CON',
                    'SEMI': 'SF'
                }[this.form.material_type] || 'MAT';
                
                // Show a preview of what the code will be
                console.log(`Auto-generated code will be: ${prefix}-XXXX (sequential)`);
            }
        },
        
        async loadDropdowns() {
            this.loading = true;
            try {
                // Load UOMs, Warehouses, and HSN Codes in parallel
                const [uomsResponse, warehousesResponse, hsnResponse] = await Promise.all([
                    fetch('/api/v1/uoms', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    }),
                    fetch('/api/v1/warehouses', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    }),
                    fetch('/api/v1/hsn-codes', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    })
                ]);
                
                if (uomsResponse.ok) {
                    const uomsData = await uomsResponse.json();
                    // UOM API returns data directly as array
                    this.uoms = Array.isArray(uomsData.data) ? uomsData.data : [];
                }
                
                if (warehousesResponse.ok) {
                    const warehousesData = await warehousesResponse.json();
                    // Warehouse API returns data.warehouses
                    this.warehouses = warehousesData.data?.warehouses || [];
                }
                
                if (hsnResponse.ok) {
                    const hsnData = await hsnResponse.json();
                    // HSN Code API returns data.hsn_codes
                    this.hsnCodes = hsnData.data?.hsn_codes || [];
                }
                
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
                this.showNotification('Failed to load dropdown options', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await fetch('/api/v1/materials', {
                    method: 'POST',
                    credentials: 'same-origin',
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
                        this.showNotification(data.message || 'Failed to create material', 'error');
                    }
                    return;
                }
                
                this.showNotification('Material created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}";
                }, 1500);
                
            } catch (error) {
                console.error('Failed to create material:', error);
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

// Watch for material type changes
document.addEventListener('alpine:init', () => {
    Alpine.watch('form.material_type', (value) => {
        if (value && window.materialForm?.form?.auto_generate_code) {
            window.materialForm.showAutoGeneratedCode();
        }
    });
});
</script>
@endsection
