@extends('tenant.layouts.vendor')

@section('title', 'Edit Vendor Contact')
@section('page-title', 'Edit Vendor Contact')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="contactEditForm()" x-init="loadContact()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Vendor Contact</h2>
                    <p class="text-gray-600 mt-1">Update contact person information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="bg-white rounded-xl shadow p-12 text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
            <p class="text-gray-600 mt-4">Loading contact details...</p>
        </div>

        <!-- Form -->
        <form x-show="!loading" @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Contact Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Contact Information</h3>
                <div class="space-y-6">
                    <!-- Vendor (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vendor
                        </label>
                        <div class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700">
                            <span x-text="form.vendor_name"></span>
                            <span class="text-gray-500 text-sm ml-2" x-text="'(' + form.vendor_code + ')'"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Vendor cannot be changed after creation</p>
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
                            <option value="TECHNICAL">Technical</option>
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

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!saving">Update Contact</span>
                    <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function contactEditForm() {
    return {
        loading: true,
        saving: false,
        contactId: null,
        form: {
            contact_name: '',
            contact_type: '',
            phone: '',
            email: '',
            is_primary: false,
            is_active: true,
            vendor_name: '',
            vendor_code: ''
        },
        
        async loadContact() {
            this.loading = true;
            try {
                // Get contact ID from URL
                const pathParts = window.location.pathname.split('/');
                this.contactId = pathParts[pathParts.length - 2];

                const response = await fetch(`/api/v1/vendor-contacts/${this.contactId}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load contact');
                }

                const contact = data.data.contact;
                this.form = {
                    contact_name: contact.contact_name || '',
                    contact_type: contact.contact_type || '',
                    phone: contact.phone || '',
                    email: contact.email || '',
                    is_primary: contact.is_primary || false,
                    is_active: contact.is_active !== undefined ? contact.is_active : true,
                    vendor_name: contact.vendor ? contact.vendor.vendor_name : '',
                    vendor_code: contact.vendor ? contact.vendor.vendor_code : ''
                };
            } catch (error) {
                console.error('Failed to load contact:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Failed to load contact. Please try again.', type: 'error' } }));
                const baseUrl = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}";
                setTimeout(() => {
                    window.location.href = baseUrl;
                }, 1500);
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.saving = true;
            try {
                const response = await fetch(`/api/v1/vendor-contacts/${this.contactId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        contact_name: this.form.contact_name,
                        contact_type: this.form.contact_type,
                        phone: this.form.phone,
                        email: this.form.email,
                        is_primary: this.form.is_primary,
                        is_active: this.form.is_active
                    })
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to update contact');
                }

                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Vendor contact updated successfully!', type: 'success' } }));
                const baseUrl = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-contacts' : '/org/' . $organization->org_slug . '/vendor-contacts') }}";
                setTimeout(() => {
                    window.location.href = baseUrl;
                }, 1500);
            } catch (error) {
                console.error('Failed to update contact:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Failed to update contact. Please try again.', type: 'error' } }));
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
