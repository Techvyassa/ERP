@extends('tenant.layouts.app')

@section('title', 'Create Material')
@section('page-title', 'Create New Material')

@section('content')
<div x-data="materialForm()" x-init="loadDropdowns()">
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
                            Material Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.material_code" required maxlength="30"
                               placeholder="RM-0001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">System code (unique)</p>
                    </div>

                    <!-- Material Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.material_name" required maxlength="200"
                               placeholder="Cinnamon Bark, Dhaniya..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Material Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.material_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RAW">RAW - Raw Material</option>
                            <option value="PACKAGING">PACKAGING - Packaging Material</option>
                            <option value="CONSUMABLE">CONSUMABLE - Consumable</option>
                            <option value="SEMI">SEMI - Semi-Finished</option>
                        </select>
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
                        <p class="text-xs text-gray-500 mt-1">→ uom_master(uom_id)</p>
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
                        <p class="text-xs text-gray-500 mt-1">Buying UOM (can differ) → uom_master</p>
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
                        <p class="text-xs text-gray-500 mt-1">Tax classification → hsn_codes(hsn_id)</p>
                    </div>
                </div>
            </div>

            <!-- Inventory Management -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Inventory Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Default Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default Warehouse
                        </label>
                        <select x-model="form.default_warehouse_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Warehouse</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ warehouse_master(warehouse_id)</p>
                    </div>

                    <!-- Reorder Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reorder Level <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.reorder_level" required min="0" step="0.001"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Stock qty triggering auto PR</p>
                    </div>

                    <!-- Safety Stock -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Safety Stock <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.safety_stock" required min="0" step="0.001"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum buffer stock</p>
                    </div>

                    <!-- Lead Time Days -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lead Time (Days) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.lead_time_days" required min="0"
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
                               placeholder="Optional"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">For FEFO expiry tracking</p>
                    </div>
                </div>
            </div>

            <!-- Quality Control -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Quality Control</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                            Inspection Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.inspection_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="AQL">AQL - Acceptance Quality Limit</option>
                            <option value="100PCT">100PCT - 100% Inspection</option>
                            <option value="SKIP">SKIP - Skip Lot</option>
                        </select>
                    </div>

                    <!-- Batch Tracked -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_batch_tracked" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Batch/Lot Tracked</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Batch/lot control enabled</p>
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
                            Standard Cost <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.standard_cost" required min="0" step="0.01"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Standard cost per base UOM</p>
                    </div>

                    <!-- Valuation Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Valuation Method <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.valuation_method" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="FIFO">FIFO - First In First Out</option>
                            <option value="AVG">AVG - Moving Average</option>
                            <option value="STD">STD - Standard Cost</option>
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
                <p class="text-xs text-gray-500 mt-1 ml-8">Soft delete</p>
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
            is_active: true
        },
        
        async loadDropdowns() {
            try {
                // TODO: Replace with actual API calls
                this.uoms = [];
                this.hsnCodes = [];
                this.warehouses = [];
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Material creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create material:', error);
                alert('Failed to create material. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
