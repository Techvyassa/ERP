@extends('tenant.layouts.tax')

@section('title', 'Create Currency')
@section('page-title', 'Create New Currency')

@section('content')
<div x-data="currencyForm()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Currency</h2>
                    <p class="text-gray-600 mt-1">Add currency for international vendor procurement</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/currency' : '/org/' . $organization->org_slug . '/currency') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Currency Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Currency Information</h3>
                <div class="space-y-6">
                    <!-- Currency Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Currency Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.currency_code" required maxlength="3"
                               placeholder="INR, USD, EUR, AED, SGD"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">3-character ISO code</p>
                    </div>

                    <!-- Currency Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Currency Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.currency_name" required maxlength="60"
                               placeholder="Indian Rupee, US Dollar..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Symbol -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Symbol <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.symbol" required maxlength="5"
                               placeholder="₹, $, €"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Exchange Rate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Exchange Rate <span class="text-red-500"></span>
                        </label>
                        <input type="number" x-model="form.exchange_rate" min="0" step="0.000001"
                               placeholder="1.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Rate vs base currency (INR). For INR = 1, USD might be 83.50</p>
                    </div>

                    <!-- Base Currency -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_base_currency" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Base Currency (INR)</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Only one record = true (INR)</p>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active Currency</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Currency Master</p>
                        <p>Multi-currency support for international vendor procurement. INR is the base currency.</p>
                        <p class="mt-2 text-xs">Used in: Vendor Master, PO, Purchase Invoice, Payment</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/currency' : '/org/' . $organization->org_slug . '/currency') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Currency</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function currencyForm() {
    return {
        loading: false,
        form: {
            currency_code: '',
            currency_name: '',
            symbol: '',
            exchange_rate: 1,
            is_base_currency: false,
            is_active: true
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Currency creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create currency:', error);
                alert('Failed to create currency. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
