@extends('tenant.layouts.tax')

@section('title', 'Create GST Tax')
@section('page-title', 'Create New GST Tax Rate')

@section('content')
<div x-data="gstForm()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New GST Tax Rate</h2>
                    <p class="text-gray-600 mt-1">Define GST rate slab with CGST, SGST, IGST, UGST</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/gst-taxes' : '/org/' . $organization->org_slug . '/gst-taxes') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Tax Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Tax Rate Information</h3>
                <div class="space-y-6">
                    <!-- Tax Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tax Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.tax_code" required maxlength="20"
                               placeholder="GST5, GST12, GST18, GST28"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Tax Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tax Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.tax_name" required maxlength="60"
                               placeholder="GST @ 12%"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- CGST Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CGST Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.cgst_rate" required min="0" max="100" step="0.01"
                               placeholder="6.00"
                               @input="calculateTotal"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Central GST rate (e.g. 6.00)</p>
                    </div>

                    <!-- SGST Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SGST Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.sgst_rate" required min="0" max="100" step="0.01"
                               placeholder="6.00"
                               @input="calculateTotal"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">State GST rate (e.g. 6.00)</p>
                    </div>

                    <!-- IGST Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            IGST Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.igst_rate" required min="0" max="100" step="0.01"
                               placeholder="12.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Interstate GST rate (e.g. 12.00)</p>
                    </div>

                    <!-- UGST Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UGST Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.ugst_rate" required min="0" max="100" step="0.01"
                               placeholder="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Union Territory GST (0 for most)</p>
                    </div>

                    <!-- Total Rate Display -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total GST Rate (CGST + SGST):</span>
                            <span class="text-lg font-bold text-blue-600" x-text="totalRate + '%'"></span>
                        </div>
                    </div>

                    <!-- Effective From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective From <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="form.effective_from" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Rate effective from date</p>
                    </div>

                    <!-- Effective To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective To
                        </label>
                        <input type="date" x-model="form.effective_to"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">NULL = currently active rate</p>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active Tax Rate</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About GST Tax Rates</p>
                        <p>GST rate slab master with separate CGST, SGST, IGST, UGST rates. Supports rate history via effective dates.</p>
                        <p class="mt-2 text-xs">Used in: Purchase Invoice, Sales Invoice, GST Returns</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/gst-taxes' : '/org/' . $organization->org_slug . '/gst-taxes') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create GST Tax</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function gstForm() {
    return {
        loading: false,
        totalRate: 0,
        form: {
            tax_code: '',
            tax_name: '',
            cgst_rate: '',
            sgst_rate: '',
            igst_rate: '',
            ugst_rate: 0,
            effective_from: '',
            effective_to: '',
            is_active: true
        },
        
        calculateTotal() {
            const cgst = parseFloat(this.form.cgst_rate) || 0;
            const sgst = parseFloat(this.form.sgst_rate) || 0;
            this.totalRate = (cgst + sgst).toFixed(2);
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('GST tax creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create GST tax:', error);
                alert('Failed to create GST tax. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
