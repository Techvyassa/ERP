@extends('tenant.layouts.inventory')

@section('title', 'Create UOM')
@section('page-title', 'Create New UOM')

@section('content')
<div x-data="uomForm()" x-init="loadBaseUoms()">
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
                            UOM Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.uom_code" required maxlength="10"
                               placeholder="KG, GM, LTR, PCS, BAG"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- UOM Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.uom_name" required maxlength="50"
                               placeholder="Kilogram, Gram, Litre..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- UOM Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="weight">weight - Weight</option>
                            <option value="volume">volume - Volume</option>
                            <option value="qty">qty - Quantity</option>
                            <option value="length">length - Length</option>
                        </select>
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
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">1 GM = 0.001 KG (conversion factor)</p>
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
        form: {
            uom_code: '',
            uom_name: '',
            uom_type: '',
            base_uom_id: '',
            conversion_factor: 1,
            is_active: true
        },
        
        async loadBaseUoms() {
            try {
                // TODO: Replace with actual API call
                this.baseUoms = [];
            } catch (error) {
                console.error('Failed to load base UOMs:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('UOM creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create UOM:', error);
                alert('Failed to create UOM. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
