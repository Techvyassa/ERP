@extends('tenant.layouts.vendor')

@section('title', 'Edit Vendor Material Map')
@section('page-title', 'Edit Vendor Material Mapping')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="mapEditForm()" x-init="loadData()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Vendor Material Mapping</h2>
                    <p class="text-gray-600 mt-1">Update vendor-specific pricing and lead times</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <template x-if="initialLoading">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading mapping data...</p>
            </div>
        </template>

        <!-- Form -->
        <form @submit.prevent="submitForm" x-show="!initialLoading" class="bg-white rounded-xl shadow p-6">
            <!-- Mapping Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Mapping Information</h3>
                <div class="space-y-6">
                    <!-- Vendor (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vendor <span class="text-red-500">*</span>
                        </label>
                        <input type="text" :value="vendorName" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Vendor cannot be changed after creation</p>
                    </div>

                    <!-- Material (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material <span class="text-red-500">*</span>
                        </label>
                        <input type="text" :value="materialName" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Material cannot be changed after creation</p>
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
                    <span x-show="!loading">Update Mapping</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function mapEditForm() {
    return {
        initialLoading: true,
        loading: false,
        mappingId: {{ $id }},
        vendorName: '',
        materialName: '',
        form: {
            vendor_material_code: '',
            last_purchase_price: '',
            lead_time_days: '',
            min_order_qty: '',
            is_preferred: false,
            is_active: true
        },
        
        async loadData() {
            this.initialLoading = true;
            try {
                const response = await fetch(`/api/v1/vendor-material-map/${this.mappingId}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load mapping');
                }

                const mapping = data.data.mapping;
                
                // Set display names
                this.vendorName = mapping.vendor ? mapping.vendor.vendor_name : 'N/A';
                this.materialName = mapping.material ? (mapping.material.material_code + ' - ' + mapping.material.material_name) : 'N/A';
                
                // Populate form
                this.form.vendor_material_code = mapping.vendor_material_code || '';
                this.form.last_purchase_price = mapping.last_purchase_price || '';
                this.form.lead_time_days = mapping.lead_time_days || '';
                this.form.min_order_qty = mapping.min_order_qty || '';
                this.form.is_preferred = mapping.is_preferred || false;
                this.form.is_active = mapping.is_active !== undefined ? mapping.is_active : true;
            } catch (error) {
                console.error('Failed to load mapping:', error);
                alert(error.message || 'Failed to load mapping. Redirecting to list...');
                const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}';
                window.location.href = baseUrl;
            } finally {
                this.initialLoading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/vendor-material-map/${this.mappingId}`, {
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
                    throw new Error((data && data.message) ? data.message : 'Failed to update mapping');
                }

                alert('Vendor-material mapping updated successfully!');
                const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendor-material-map' : '/org/' . $organization->org_slug . '/vendor-material-map') }}';
                window.location.href = baseUrl;
            } catch (error) {
                console.error('Failed to update mapping:', error);
                alert(error.message || 'Failed to update mapping. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
