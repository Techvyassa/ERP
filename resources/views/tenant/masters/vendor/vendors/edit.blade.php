@extends('tenant.layouts.vendor')

@section('title', 'Edit Vendor')
@section('page-title', 'Edit Vendor')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="vendorEditForm()" x-init="loadVendor()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Vendor</h2>
                    <p class="text-gray-600 mt-1">Update vendor information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                <p class="text-gray-600 mt-2">Loading vendor data...</p>
            </div>
        </template>

        <!-- Form -->
        <template x-if="!loading">
            <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
                <!-- Basic Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Vendor Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="form.vendor_code" required maxlength="20"
                                   placeholder="VND-001"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Vendor Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="form.vendor_name" required maxlength="200"
                                   placeholder="Legal company name"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Vendor Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Type <span class="text-red-500">*</span>
                            </label>
                            <select x-model="form.vendor_type" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Type</option>
                                <option value="SUPPLIER">Supplier</option>
                                <option value="SERVICE">Service Provider</option>
                                <option value="TRADER">Trader</option>
                            </select>
                        </div>

                        <!-- GSTIN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                GSTIN
                            </label>
                            <input type="text" x-model="form.gstin" maxlength="20"
                                   placeholder="15-digit GSTIN (unique)"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- PAN Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                PAN Number
                            </label>
                            <input type="text" x-model="form.pan_number" maxlength="10"
                                   placeholder="10-char PAN"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- MSME Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                MSME Category
                            </label>
                            <select x-model="form.msme_category"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Category</option>
                                <option value="MICRO">Micro</option>
                                <option value="SMALL">Small</option>
                                <option value="MEDIUM">Medium</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Payment Terms</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Payment Terms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Payment Terms <span class="text-red-500">*</span>
                            </label>
                            <select x-model="form.payment_terms" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Terms</option>
                                <option value="NET30">NET30</option>
                                <option value="NET60">NET60</option>
                                <option value="ADVANCE">Advance</option>
                                <option value="COD">Cash on Delivery</option>
                            </select>
                        </div>

                        <!-- Credit Days -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Credit Days <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="form.credit_days" required min="0"
                                   placeholder="30"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Credit period in days</p>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select x-model="form.currency_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Currency</option>
                                <template x-for="curr in currencies" :key="curr.id">
                                    <option :value="curr.id" x-text="curr.currency_code + ' - ' + curr.currency_name"></option>
                                </template>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">→ currency_master(currency_id)</p>
                        </div>

                        <!-- Delivery Terms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Delivery Terms
                            </label>
                            <select x-model="form.delivery_terms"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Terms</option>
                                <option value="EXW">EXW - Ex Works</option>
                                <option value="DDP">DDP - Delivered Duty Paid</option>
                                <option value="CIF">CIF - Cost Insurance Freight</option>
                                <option value="FOB">FOB - Free on Board</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Banking Details -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Banking Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Bank Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Name
                            </label>
                            <input type="text" x-model="form.bank_name" maxlength="100"
                                   placeholder="Bank name"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Bank Account Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Account Number
                            </label>
                            <input type="text" x-model="form.bank_account_no" maxlength="30"
                                   placeholder="Encrypted account number"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- IFSC Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                IFSC Code
                            </label>
                            <input type="text" x-model="form.ifsc_code" maxlength="11"
                                   placeholder="11-char IFSC code"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Approval & Rating -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Approval & Rating</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Rating Score -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rating Score
                            </label>
                            <input type="number" x-model="form.rating_score" min="0" max="100" step="0.01"
                                   placeholder="0-100"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">0-100 vendor performance score</p>
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.is_approved" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Vendor Approved</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.blacklisted" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-2 focus:ring-red-500">
                                <span class="text-sm font-medium text-gray-700">Blacklisted (Block from RFQ/PO)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Active Vendor</span>
                    </label>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">About Vendor Master</p>
                            <p>Vendor master stores supplier information used in RFQ, PO, GRN, Invoice, Payment, and Vendor Rating processes.</p>
                            <p class="mt-2 text-xs">Used in: RFQ, PO, GRN, Invoice, Payment, Vendor Rating</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}" 
                       class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" :disabled="saving"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving">Update Vendor</span>
                        <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>

<script>
function vendorEditForm() {
    return {
        loading: true,
        saving: false,
        currencies: [],
        vendorId: {{ $vendorId }},
        form: {
            vendor_code: '',
            vendor_name: '',
            vendor_type: '',
            gstin: '',
            pan_number: '',
            msme_category: '',
            payment_terms: '',
            credit_days: 0,
            currency_id: '',
            delivery_terms: '',
            bank_name: '',
            bank_account_no: '',
            ifsc_code: '',
            is_approved: false,
            rating_score: '',
            blacklisted: false,
            is_active: true
        },
        
        async loadVendor() {
            this.loading = true;
            try {
                // Load currencies first
                await this.loadCurrencies();
                
                // Load vendor data
                const response = await fetch(`/api/v1/vendors/${this.vendorId}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load vendor');
                }
                
                const data = await response.json();
                
                if (data && data.success && data.data && data.data.vendor) {
                    const vendor = data.data.vendor;
                    this.form = {
                        vendor_code: vendor.vendor_code || '',
                        vendor_name: vendor.vendor_name || '',
                        vendor_type: vendor.vendor_type || '',
                        gstin: vendor.gstin || '',
                        pan_number: vendor.pan_number || '',
                        msme_category: vendor.msme_category || '',
                        payment_terms: vendor.payment_terms || '',
                        credit_days: vendor.credit_days || 0,
                        currency_id: vendor.currency_id || '',
                        delivery_terms: vendor.delivery_terms || '',
                        bank_name: vendor.bank_name || '',
                        bank_account_no: vendor.bank_account_no || '',
                        ifsc_code: vendor.ifsc_code || '',
                        is_approved: vendor.is_approved || false,
                        rating_score: vendor.rating_score || '',
                        blacklisted: vendor.blacklisted || false,
                        is_active: vendor.is_active !== false
                    };
                } else {
                    throw new Error('Invalid vendor data received');
                }
            } catch (error) {
                console.error('Failed to load vendor:', error);
                alert('Failed to load vendor data. Please try again.');
                window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}';
            } finally {
                this.loading = false;
            }
        },
        
        async loadCurrencies() {
            try {
                const currencyResponse = await fetch('/api/v1/currencies', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (currencyResponse.ok) {
                    const currencyData = await currencyResponse.json();
                    if (currencyData && currencyData.success && currencyData.data) {
                        this.currencies = currencyData.data.currencies || [];
                    }
                }
            } catch (error) {
                console.error('Failed to load currencies:', error);
                // Set default currency if API fails
                this.currencies = [
                    { id: 1, currency_code: 'INR', currency_name: 'Indian Rupee' },
                    { id: 2, currency_code: 'USD', currency_name: 'US Dollar' },
                    { id: 3, currency_code: 'EUR', currency_name: 'Euro' }
                ];
            }
        },
        
        async submitForm() {
            this.saving = true;
            try {
                const response = await fetch(`/api/v1/vendors/${this.vendorId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to update vendor');
                }
                
                alert('Vendor updated successfully!');
                window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}';
            } catch (error) {
                console.error('Failed to update vendor:', error);
                alert(error.message || 'Failed to update vendor. Please try again.');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
