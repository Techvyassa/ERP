@extends('tenant.layouts.app')

@section('title', 'Create Vendor Material Map')
@section('page-title', 'Create New Vendor Material Mapping')

@section('content')
<div x-data="mapForm()" x-init="loadDropdowns()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create Vendor Material Mapping</h2>
                    <p class="text-gray-600 mt-1">Define vendor-specific pricing and lead times</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Mapping Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Mapping Information</h3>
                <div class="space-y-6">
                    <!-- Vendor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vendor <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.vendor_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option :value="vendor.id" x-text="vendor.vendor_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ vendor_master(vendor_id)</p>
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

                    <!-- Vendor Material Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vendor Material Code
                        </label>
                        <input type="text" x-model="form.vendor_material_code" maxlength="50"
                               placeholder="Vendor's own SKU/part number"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Vendor's own SKU/part number</p>
                    </div>

                    <!-- Last Purchase Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Last Purchase Price
                        </label>
                        <input type="number" x-model="form.last_purchase_price" min="0" step="0.0001"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Last transacted price</p>
                    </div>

                    <!-- Lead Time Days -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Lead Time (Days)
                        </label>
                        <input type="number" x-model="form.lead_time_days" min="0"
                               placeholder="7"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Vendor-specific lead time</p>
                    </div>

                    <!-- Minimum Order Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Order Quantity (MOQ)
                        </label>
                        <input type="number" x-model="form.min_order_qty" min="0" step="0.001"
                               placeholder="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum order quantity (MOQ)</p>
                    </div>

                    <!-- Preferred Vendor -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_preferred" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Preferred Vendor (L1)</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">L1 / preferred vendor flag</p>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active Mapping</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Vendor Material Map</p>
                        <p>Approved Vendor List (AVL) — defines which vendor can supply which material, with vendor-specific pricing, MOQ, and lead time.</p>
                        <p class="mt-2 text-xs">Used in: RFQ auto-shortlist, PO vendor selection, Comparative analysis</p>
                        <p class="mt-1 text-xs font-semibold">Unique Constraint: (vendor_id, material_id)</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Mapping</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function mapForm() {
    return {
        loading: false,
        vendors: [],
        materials: [],
        form: {
            vendor_id: '',
            material_id: '',
            vendor_material_code: '',
            last_purchase_price: '',
            lead_time_days: '',
            min_order_qty: '',
            is_preferred: false,
            is_active: true
        },
        
        async loadDropdowns() {
            try {
                // TODO: Replace with actual API calls
                this.vendors = [];
                this.materials = [];
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Vendor material mapping creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create mapping:', error);
                alert('Failed to create mapping. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
