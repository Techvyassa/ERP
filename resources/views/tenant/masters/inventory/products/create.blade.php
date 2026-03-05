@extends('tenant.layouts.app')

@section('title', 'Create Product')
@section('page-title', 'Create New Product')

@section('content')
<div x-data="productForm()" x-init="loadDropdowns()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Product</h2>
                    <p class="text-gray-600 mt-1">Add finished goods master</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Product Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Product Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.product_code" required maxlength="30"
                               placeholder="FG-0001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Product Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.product_name" required maxlength="200"
                               placeholder="Masala Powder 100g"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Product Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Category
                        </label>
                        <input type="text" x-model="form.product_category" maxlength="60"
                               placeholder="Spice / Blend / Condiment"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Pack Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pack Size <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.pack_size" required min="0" step="0.001"
                               placeholder="100, 250, 1000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">100, 250, 1000 (per pack uom)</p>
                    </div>

                    <!-- Pack UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pack UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.pack_uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ uom_master(uom_id)</p>
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
                        <p class="text-xs text-gray-500 mt-1">→ hsn_codes(hsn_id)</p>
                    </div>
                </div>
            </div>

            <!-- Costing & Pricing -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Costing & Pricing</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Standard Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Cost <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.standard_cost" required min="0" step="0.01"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Cost per unit</p>
                    </div>

                    <!-- MRP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            MRP (Maximum Retail Price)
                        </label>
                        <input type="number" x-model="form.mrp" min="0" step="0.01"
                               placeholder="Optional"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Maximum retail price</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Product</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Product Master</p>
                        <p>Finished Goods master. The raw_materials JSON column has been REMOVED and replaced by bom_header + bom_detail for proper relational integrity.</p>
                        <p class="mt-2 text-xs">Used in: BOM, Sales Orders, Production Planning, Dispatch, Costing</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Product</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function productForm() {
    return {
        loading: false,
        uoms: [],
        hsnCodes: [],
        form: {
            product_code: '',
            product_name: '',
            product_category: '',
            pack_size: '',
            pack_uom_id: '',
            hsn_code_id: '',
            standard_cost: 0,
            mrp: '',
            is_active: true
        },
        
        async loadDropdowns() {
            try {
                // TODO: Replace with actual API calls
                this.uoms = [];
                this.hsnCodes = [];
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Product creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create product:', error);
                alert('Failed to create product. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
