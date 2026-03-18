@extends('tenant.layouts.bom')

@section('title', 'Edit BOM')
@section('page-title', 'Edit Bill of Materials')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="bomForm()" x-init="loadData()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Bill of Materials</h2>
                    <p class="text-gray-600 mt-1">Update product BOM details</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- BOM Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">BOM Header Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- BOM Code (Read-only) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            BOM Code
                        </label>
                        <input type="text" 
                               x-model="form.bom_code"
                               readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        <p class="text-xs text-gray-500 mt-1">BOM code cannot be changed</p>
                    </div>

                    <!-- Product (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product
                        </label>
                        <select disabled
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                            <option x-text="form.product_name || 'Loading...'"></option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Product cannot be changed</p>
                    </div>

                    <!-- Version (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Version
                        </label>
                        <input type="number" x-model="form.version" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        <p class="text-xs text-gray-500 mt-1">Version cannot be changed</p>
                    </div>

                    <!-- Effective From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective From <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="form.effective_from" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">BOM valid from this date</p>
                    </div>

                    <!-- Effective To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective To
                        </label>
                        <input type="date" x-model="form.effective_to"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">NULL = currently active BOM</p>
                    </div>

                    <!-- Batch Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Batch Size <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.batch_size" required min="0.001" step="0.001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Output quantity per batch</p>
                    </div>

                    <!-- Output UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Output UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.output_uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_code + ' - ' + uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ uom_master(output_uom_id)</p>
                    </div>

                    <!-- BOM Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            BOM Status <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.bom_status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="DRAFT">DRAFT</option>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="OBSOLETE">OBSOLETE</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">DRAFT / ACTIVE / OBSOLETE</p>
                    </div>

                    <!-- Remarks -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Remarks
                        </label>
                        <textarea x-model="form.remarks" rows="2" maxlength="1000"
                                  placeholder="Change notes, reason for version..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Change notes, reason for version (max 1000 chars)</p>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About BOM Header</p>
                        <p>Bill of Materials header with version management. Supports multiple BOM versions per product with effective date ranges.</p>
                        <p class="mt-2 text-xs">Used in: Production Work Orders, MRP, Material Planning, Costing</p>
                        <p class="mt-1 text-xs font-semibold">Note: BOM Code, Product, and Version cannot be changed after creation</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Update BOM</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bomForm() {
    return {
        loading: false,
        products: [],
        uoms: [],
        bomId: {{ $bomId }},
        form: {
            bom_code: '',
            product_id: '',
            product_name: '',
            version: 1,
            effective_from: '',
            effective_to: '',
            batch_size: 100,
            output_uom_id: '',
            bom_status: 'DRAFT',
            remarks: ''
        },
        
        async loadData() {
            try {
                // Load UOMs
                const uomResponse = await fetch('/api/v1/uoms?per_page=1000', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (uomResponse.ok) {
                    const uomData = await uomResponse.json();
                    if (uomData && uomData.success && uomData.data) {
                        this.uoms = Array.isArray(uomData.data) ? uomData.data : (uomData.data.uoms || []);
                    }
                }

                // Load BOM data
                const bomResponse = await fetch(`/api/v1/bom-headers/${this.bomId}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (bomResponse.ok) {
                    const bomData = await bomResponse.json();
                    if (bomData && bomData.success && bomData.data) {
                        const bom = bomData.data;
                        this.form = {
                            bom_code: bom.bom_code || '',
                            product_id: bom.product_id || '',
                            product_name: bom.product ? bom.product.product_name : '',
                            version: bom.version || 1,
                            effective_from: bom.effective_from || '',
                            effective_to: bom.effective_to || '',
                            batch_size: bom.batch_size || 100,
                            output_uom_id: bom.output_uom_id || '',
                            bom_status: bom.bom_status || 'DRAFT',
                            remarks: bom.remarks || ''
                        };
                    }
                }
            } catch (error) {
                console.error('Failed to load data:', error);
                alert('Failed to load BOM data. Please refresh the page.');
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // Convert string values to proper types
                const formData = {
                    effective_from: this.form.effective_from,
                    effective_to: this.form.effective_to || null,
                    batch_size: parseFloat(this.form.batch_size),
                    output_uom_id: parseInt(this.form.output_uom_id),
                    bom_status: this.form.bom_status,
                    remarks: this.form.remarks || null
                };

                const response = await fetch(`/api/v1/bom-headers/${this.bomId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (!response.ok || !data || data.success !== true) {
                    const errorMsg = data && data.error && data.error.details 
                        ? JSON.stringify(data.error.details) 
                        : (data && data.message) ? data.message : 'Failed to update BOM';
                    throw new Error(errorMsg);
                }
                
                alert('BOM header updated successfully!');
                window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}';
            } catch (error) {
                console.error('Failed to update BOM:', error);
                alert(error.message || 'Failed to update BOM. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
