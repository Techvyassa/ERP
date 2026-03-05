@extends('tenant.layouts.app')

@section('title', 'Create HSN Code')
@section('page-title', 'Create New HSN Code')

@section('content')
<div x-data="hsnForm()" x-init="loadGstTaxes()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New HSN Code</h2>
                    <p class="text-gray-600 mt-1">Add Harmonized System of Nomenclature code</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/hsn-codes' : '/org/' . $organization->org_slug . '/hsn-codes') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- HSN Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">HSN Code Information</h3>
                <div class="space-y-6">
                    <!-- HSN Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            HSN Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.hsn_code" required maxlength="10"
                               placeholder="0904, 0906, 2103"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">4-8 digit HSN code</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="form.description" required maxlength="300" rows="3"
                                  placeholder="Pepper, Cinnamon, Sauces"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Goods classification description</p>
                    </div>

                    <!-- Default GST -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Default GST Rate <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.default_gst_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select GST Rate</option>
                            <template x-for="gst in gstTaxes" :key="gst.id">
                                <option :value="gst.id" x-text="gst.tax_name + ' (' + gst.cgst_rate + '% CGST + ' + gst.sgst_rate + '% SGST)'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ gst_taxes(gst_id)</p>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active HSN Code</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About HSN Codes</p>
                        <p>Harmonized System of Nomenclature codes for goods classification. Linked to default GST slab.</p>
                        <p class="mt-2 text-xs">Used in: Material Master, Product Master, GST Invoicing</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/hsn-codes' : '/org/' . $organization->org_slug . '/hsn-codes') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create HSN Code</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function hsnForm() {
    return {
        loading: false,
        gstTaxes: [],
        form: {
            hsn_code: '',
            description: '',
            default_gst_id: '',
            is_active: true
        },
        
        async loadGstTaxes() {
            try {
                // TODO: Replace with actual API call
                this.gstTaxes = [];
            } catch (error) {
                console.error('Failed to load GST taxes:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('HSN code creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create HSN code:', error);
                alert('Failed to create HSN code. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
