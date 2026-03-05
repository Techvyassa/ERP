@extends('tenant.layouts.bom')

@section('title', 'Create BOM Detail')
@section('page-title', 'Create New BOM Component')

@section('content')
<div x-data="bomDetailForm()" x-init="loadDropdowns()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New BOM Component</h2>
                    <p class="text-gray-600 mt-1">Add material component to BOM</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail' : '/org/' . $organization->org_slug . '/bom-detail') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Component Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Component Information</h3>
                <div class="space-y-6">
                    <!-- BOM Header -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            BOM Header <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.bom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select BOM</option>
                            <template x-for="bom in boms" :key="bom.id">
                                <option :value="bom.id" x-text="bom.bom_code + ' - ' + bom.product_name + ' (v' + bom.version + ')'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ bom_header(bom_id)</p>
                    </div>

                    <!-- Material -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.material_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Material</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ material_master(material_id)</p>
                    </div>

                    <!-- Quantity Required -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity Required <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.qty_required" required min="0" step="0.0001"
                               placeholder="10.5000"
                               @input="calculateEffectiveQty"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Required qty per batch_size</p>
                    </div>

                    <!-- UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_code + ' - ' + uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ uom_master(uom_id)</p>
                    </div>

                    <!-- Scrap Percent -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Scrap Percent <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.scrap_percent" required min="0" max="100" step="0.01"
                               placeholder="2.50"
                               @input="calculateEffectiveQty"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Process loss % (e.g. 2.50)</p>
                    </div>

                    <!-- Effective Quantity (Calculated) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Effective Quantity:</span>
                                <p class="text-xs text-gray-500 mt-1">qty × (1 + scrap%/100) — stored</p>
                            </div>
                            <span class="text-lg font-bold text-blue-600" x-text="effectiveQty.toFixed(4)"></span>
                        </div>
                    </div>

                    <!-- Substitute Material -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Substitute Material
                        </label>
                        <select x-model="form.substitute_material_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No Substitute</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Alternate material → material_master</p>
                    </div>

                    <!-- Critical Component -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_critical" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-2 focus:ring-red-500">
                            <span class="text-sm font-medium text-gray-700">Critical Component</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">No substitute allowed if true</p>
                    </div>

                    <!-- Line Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Line Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.line_no" required min="1"
                               placeholder="10"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Display sort order</p>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Remarks
                        </label>
                        <textarea x-model="form.remarks" rows="2" maxlength="200"
                                  placeholder="Component-level note"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About BOM Detail</p>
                        <p>BOM component lines per header. Replaces the raw_materials JSON column in product_master. Supports scrap %, computed effective qty, and substitute materials.</p>
                        <p class="mt-2 text-xs">Used in: MRP explosion, Material requirement calculation, Work Order issuance</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail' : '/org/' . $organization->org_slug . '/bom-detail') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Component</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bomDetailForm() {
    return {
        loading: false,
        boms: [],
        materials: [],
        uoms: [],
        effectiveQty: 0,
        form: {
            bom_id: '',
            material_id: '',
            qty_required: '',
            uom_id: '',
            scrap_percent: 0,
            substitute_material_id: '',
            is_critical: false,
            line_no: 10,
            remarks: ''
        },
        
        async loadDropdowns() {
            try {
                // TODO: Replace with actual API calls
                this.boms = [];
                this.materials = [];
                this.uoms = [];
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
            }
        },
        
        calculateEffectiveQty() {
            const qty = parseFloat(this.form.qty_required) || 0;
            const scrap = parseFloat(this.form.scrap_percent) || 0;
            this.effectiveQty = qty * (1 + scrap / 100);
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // Add calculated effective_qty to form data
                const submitData = {
                    ...this.form,
                    effective_qty: this.effectiveQty
                };
                // TODO: Replace with actual API call
                alert('BOM detail creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(submitData, null, 2));
            } catch (error) {
                console.error('Failed to create BOM detail:', error);
                alert('Failed to create BOM detail. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
