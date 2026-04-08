@extends('tenant.layouts.vendor')

@section('title', 'Create Vendor Contact')
@section('page-title', 'Create New Vendor Contact')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="contactForm()" x-init="loadVendors()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Vendor Contact</h2>
                    <p class="text-gray-600 mt-1">Add contact person for vendor communication</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Contact Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Contact Information</h3>
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

                    <!-- Contact Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Contact Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.contact_name" required maxlength="100"
                               placeholder="Full name of contact person"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Contact Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Contact Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.contact_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="SALES">Sales</option>
                            <option value="FINANCE">Finance</option>
                            <option value="LOGISTICS">Logistics</option>
                            <option value="GM">General Manager</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone
                        </label>
                        <input type="text" x-model="form.phone" maxlength="20"
                               placeholder="Mobile / landline"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" x-model="form.email" maxlength="150"
                               placeholder="Email for RFQ/PO dispatch"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Primary Contact -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_primary" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Primary Contact</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">Primary contact flag</p>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active Contact</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Vendor Contacts</p>
                        <p>Stores multiple contacts per vendor across different roles (Sales, Finance, Logistics). Replaces single contact fields in vendor_master.</p>
                        <p class="mt-2 text-xs">Used in: RFQ dispatch, PO email, Payment coordination, Delivery follow-up</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Contact</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function contactForm() {
    return {
        loading: false,
        vendors: [],
        form: {
            vendor_id: '',
            contact_name: '',
            contact_type: 'SALES',
            phone: '',
            email: '',
            is_primary: false,
            is_active: true
        },
        
        async loadVendors() {
            try {
                const response = await fetch('/api/v1/vendors?per_page=1000&blacklisted=0', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load vendors');
                }

                this.vendors = (data && data.data && data.data.vendors) ? data.data.vendors : [];
            } catch (error) {
                console.error('Failed to load vendors:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Failed to load vendors. Please refresh the page.', type: 'error' } }));
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch('/api/v1/vendor-contacts', {
                    method: 'POST',
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
                    throw new Error((data && data.message) ? data.message : 'Failed to create contact');
                }

                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Vendor contact created successfully!', type: 'success' } }));
                const baseUrl = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}";
                setTimeout(() => {
                    window.location.href = baseUrl;
                }, 1500);
            } catch (error) {
                console.error('Failed to create contact:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Failed to create contact. Please try again.', type: 'error' } }));
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
