@extends('tenant.layouts.inventory')

@section('title', 'Edit Material')
@section('page-title', 'Edit Material')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="materialForm()" x-init="loadMaterialData()">
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
                    <h2 class="text-2xl font-bold text-gray-900">Edit Material</h2>
                    <p class="text-gray-600 mt-1">Update material information</p>
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
                            Material Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.material_code" required maxlength="30"
                               placeholder="RM-0001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">System-generated unique code</p>
                    </div>

                    <!-- Material Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.material_name" required maxlength="200"
                               placeholder="Cinnamon Bark, Dhaniya..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Descriptive material name</p>
                    </div>

                    <!-- Material Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.material_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RAW">Raw Material</option>
                            <option value="PACKAGING">Packaging</option>
                            <option value="CONSUMABLE">Consumable</option>
                            <option value="SEMI">Semi-Finished</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Material classification</p>
                    </div>

                    <!-- Stock UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Stock UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Unit of measurement for stock</p>
                    </div>

                    <!-- Purchase UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Purchase UOM
                        </label>
                        <select x-model="form.purchase_uom_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Same as Stock UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Buying UOM (can differ from stock UOM)</p>
                    </div>

                    <!-- HSN Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            HSN Code <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.hsn_code_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select HSN Code</option>
                            <template x-for="hsn in hsnCodes" :key="hsn.id">
                                <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">HSN code for tax purposes</p>
                    </div>

                    <!-- Default Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default Warehouse
                        </label>
                        <select x-model="form.default_warehouse_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No Default</option>
                            <template x-for="warehouse in warehouses" :key="warehouse.id">
                                <option :value="warehouse.id" x-text="warehouse.warehouse_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Default storage location</p>
                    </div>
                </div>
            </div>

            <!-- Inventory Management -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Inventory Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Reorder Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reorder Level
                        </label>
                        <input type="number" x-model="form.reorder_level" step="0.001" min="0"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Stock qty triggering auto PR</p>
                    </div>

                    <!-- Safety Stock -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Safety Stock
                        </label>
                        <input type="number" x-model="form.safety_stock" step="0.001" min="0"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum buffer stock</p>
                    </div>

                    <!-- Lead Time Days -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lead Time (Days)
                        </label>
                        <input type="number" x-model="form.lead_time_days" min="0"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Default procurement lead time</p>
                    </div>

                    <!-- Shelf Life Days -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Shelf Life (Days)
                        </label>
                        <input type="number" x-model="form.shelf_life_days" min="0"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">For FEFO expiry tracking</p>
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
                        <p class="text-xs text-gray-500 mt-1 ml-8">Trigger QC on GRN</p>
                    </div>

                    <!-- Inspection Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Inspection Type
                        </label>
                        <select x-model="form.inspection_type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="100PCT">100% Inspection</option>
                            <option value="AQL">AQL Sampling</option>
                            <option value="SKIP">Skip Inspection</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Default inspection method</p>
                    </div>

                    <!-- Batch Tracked -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_batch_tracked" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Lot Tracked</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Lot-level control enabled</p>
                    </div>

                    <!-- Valuation Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Valuation Method
                        </label>
                        <select x-model="form.valuation_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="FIFO">FIFO (First In, First Out)</option>
                            <option value="AVG">Average Cost</option>
                            <option value="STD">Standard Cost</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Inventory valuation method</p>
                    </div>
                </div>
            </div>

            <!-- Financial -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Financial</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Standard Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Cost
                        </label>
                        <input type="number" x-model="form.standard_cost" step="0.0001" min="0"
                               placeholder="0.0000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Cost per base UOM (4 decimal places)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Material</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Enable for transactions and procurement</p>
            </div>

            <!-- Info Box -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-amber-600 mt-1 mr-3"></i>
                    <div class="text-sm text-amber-800">
                        <p class="font-semibold mb-1">About Material Master</p>
                        <p>Raw materials, packaging, consumables, and semi-finished goods for production and inventory management.</p>
                        <p class="mt-2 text-xs">Used in: Purchase Orders, GRN, Production, Inventory Management, BOM</p>
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
                    <span x-show="!loading">Update Material</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
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
        warehouses: [],
        hsnCodes: [],
        materialId: null,
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
            shelf_life_days: null,
            qc_required: true,
            inspection_type: 'AQL',
            is_batch_tracked: false,
            standard_cost: 0,
            valuation_method: 'FIFO',
            is_active: true
        },
        
        async loadMaterialData() {
            // Get material ID from URL
            const urlParts = window.location.pathname.split('/');
            this.materialId = urlParts[urlParts.length - 2]; // Get ID before /edit
            
            console.log('URL Path:', window.location.pathname);
            console.log('Extracted Material ID:', this.materialId);
            
            if (!this.materialId || isNaN(this.materialId)) {
                console.error('Invalid material ID:', this.materialId);
                this.showNotification('Invalid material ID', 'error');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}';
                }, 2000);
                return;
            }
            
            this.loading = true;
            try {
                // Load material data and dropdowns
                const materialResponse = await fetch(`/api/v1/materials/${this.materialId}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Material API Response Status:', materialResponse.status);
                
                if (!materialResponse.ok) {
                    const errorData = await materialResponse.json();
                    console.error('Material API Error:', errorData);
                    throw new Error(errorData.message || 'Failed to load material data');
                }
                
                const materialData = await materialResponse.json();
                console.log('Material Data:', materialData);
                
                this.form = {
                    material_code: materialData.data?.material?.material_code || '',
                    material_name: materialData.data?.material?.material_name || '',
                    material_type: materialData.data?.material?.material_type || '',
                    uom_id: materialData.data?.material?.uom_id || '',
                    purchase_uom_id: materialData.data?.material?.purchase_uom_id || '',
                    hsn_code_id: materialData.data?.material?.hsn_code_id || '',
                    default_warehouse_id: materialData.data?.material?.default_warehouse_id || '',
                    reorder_level: materialData.data?.material?.reorder_level || 0,
                    safety_stock: materialData.data?.material?.safety_stock || 0,
                    lead_time_days: materialData.data?.material?.lead_time_days || 0,
                    shelf_life_days: materialData.data?.material?.shelf_life_days || null,
                    qc_required: materialData.data?.material?.qc_required !== undefined ? materialData.data.material.qc_required : true,
                    inspection_type: materialData.data?.material?.inspection_type || 'AQL',
                    is_batch_tracked: materialData.data?.material?.is_batch_tracked !== undefined ? materialData.data.material.is_batch_tracked : false,
                    standard_cost: materialData.data?.material?.standard_cost || 0,
                    valuation_method: materialData.data?.material?.valuation_method || 'FIFO',
                    is_active: materialData.data?.material?.is_active !== undefined ? materialData.data.material.is_active : true
                };
                
                console.log('Form populated:', this.form);
                
                // Load dropdowns separately
                await this.loadDropdowns();
                
            } catch (error) {
                console.error('Failed to load material data:', error);
                this.showNotification('Failed to load material data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async loadDropdowns() {
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
                    // API returns data directly as array, not nested
                    this.uoms = Array.isArray(uomsData.data) ? uomsData.data : (uomsData.data?.uoms || []);
                }
                
                if (warehousesResponse.ok) {
                    const warehousesData = await warehousesResponse.json();
                    this.warehouses = Array.isArray(warehousesData.data) ? warehousesData.data : (warehousesData.data?.warehouses || []);
                }
                
                if (hsnResponse.ok) {
                    const hsnData = await hsnResponse.json();
                    this.hsnCodes = Array.isArray(hsnData.data) ? hsnData.data : (hsnData.data?.hsn_codes || []);
                }
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
                this.showNotification('Failed to load dropdown data', 'error');
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                console.log('Submitting material update with data:', this.form);
                console.log('Material ID:', this.materialId);
                
                const response = await fetch(`/api/v1/materials/${this.materialId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
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
                        console.log('Validation errors:', data.error.details);
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        console.log('API Error:', data);
                        this.showNotification(data.message || 'Failed to update material', 'error');
                    }
                    return;
                }
                
                this.showNotification('Material updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to update material:', error);
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
