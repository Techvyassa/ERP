@extends('tenant.layouts.bom')

@section('title', 'View BOM')
@section('page-title', 'Bill of Materials Details')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="bomView()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Bill of Materials Details</h2>
                    <p class="text-gray-600 mt-1">View BOM header information</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                    <a :href="editUrl" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading BOM details...</p>
            </div>
        </template>

        <!-- BOM Details -->
        <template x-if="!loading && bom">
            <div class="space-y-6">
                <!-- BOM Header Information -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">BOM Header Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">BOM Code</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="bom.bom_code"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Product</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="bom.product && bom.product.product_name ? bom.product.product_name : 'N/A'"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Version</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="bom.version ? 'v' + bom.version : '-'"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Effective From</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="formatDate(bom.effective_from)"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Effective To</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="bom.effective_to ? formatDate(bom.effective_to) : 'Currently Active'"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                            <span class="px-3 py-1 text-sm rounded-full font-semibold"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': bom.bom_status === 'DRAFT',
                                      'bg-green-100 text-green-800': bom.bom_status === 'ACTIVE',
                                      'bg-red-100 text-red-800': bom.bom_status === 'OBSOLETE'
                                  }"
                                  x-text="bom.bom_status"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Output UOM</label>
                            <p class="text-lg font-semibold text-gray-900" x-text="bom.output_uom && bom.output_uom.uom_name ? bom.output_uom.uom_name : 'N/A'"></p>
                        </div>
                    </div>
                    <template x-if="bom.remarks">
                        <div class="mt-6 pt-6 border-t">
                            <label class="block text-sm font-medium text-gray-600 mb-2">Remarks</label>
                            <p class="text-gray-700 whitespace-pre-wrap" x-text="bom.remarks"></p>
                        </div>
                    </template>
                </div>

                <!-- BOM Details (Items) -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">BOM Components (Items)</h3>
                    
                    <template x-if="bom.bom_details && bom.bom_details.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border rounded-lg">
                                <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-4 py-3">Line #</th>
                                        <th class="px-4 py-3">Material</th>
                                        <th class="px-4 py-3">Qty Required</th>
                                        <th class="px-4 py-3">UOM</th>
                                        <th class="px-4 py-3">Scrap %</th>
                                        <th class="px-4 py-3">Sub. Material</th>
                                        <th class="px-4 py-3 text-center">Critical</th>
                                        <th class="px-4 py-3">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in bom.bom_details" :key="index">
                                        <tr class="border-b bg-white hover:bg-gray-50">
                                            <td class="px-4 py-2" x-text="item.line_no || (index + 1)"></td>
                                            <td class="px-4 py-2 font-medium text-gray-900" x-text="item.material ? (item.material.material_code + ' - ' + item.material.material_name) : '-'"></td>
                                            <td class="px-4 py-2" x-text="item.qty_required"></td>
                                            <td class="px-4 py-2" x-text="item.uom ? item.uom.uom_name : '-'"></td>
                                            <td class="px-4 py-2" x-text="(item.scrap_percent || 0) + '%'"></td>
                                            <td class="px-4 py-2" x-text="item.substitute_material ? (item.substitute_material.material_code + ' - ' + item.substitute_material.material_name) : '-'"></td>
                                            <td class="px-4 py-2 text-center">
                                                <template x-if="item.is_critical">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Yes</span>
                                                </template>
                                                <template x-if="!item.is_critical">
                                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">No</span>
                                                </template>
                                            </td>
                                            <td class="px-4 py-2 text-gray-500" x-text="item.remarks || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    
                    <template x-if="!bom.bom_details || bom.bom_details.length === 0">
                        <div class="text-center py-6 border rounded-lg bg-gray-50">
                            <p class="text-gray-500">No components defined for this BOM.</p>
                        </div>
                    </template>
                </div>

                <!-- Audit Information -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Audit Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Created By</label>
                            <p class="text-gray-900" x-text="bom.creator && bom.creator.email ? bom.creator.email : 'N/A'"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Approved By</label>
                            <p class="text-gray-900" x-text="bom.approver && bom.approver.email ? bom.approver.email : 'Not Approved'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Error State -->
        <template x-if="!loading && !bom">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-red-400 mb-4"></i>
                <p class="text-gray-600" x-text="errorMessage || 'Failed to load BOM details.'"></p>
            </div>
        </template>
    </div>
</div>

<script>
function bomView() {
    return {
        loading: true,
        bom: null,
        errorMessage: '',
        bomId: {{ $bomId }},
        
        get editUrl() {
            const baseUrl = '{{ $tenantType }}' === 'subdomain' ? '' : '/org/{{ $organization->org_slug }}';
            return `${baseUrl}/bom-header/${this.bomId}/edit`;
        },
        
        async init() {
            await this.loadData();
        },
        
        async loadData() {
            try {
                console.log('=== BOM View Page ===');
                console.log('Loading BOM with ID:', this.bomId);
                
                const url = `/api/v1/bom-headers/${this.bomId}`;
                console.log('API URL:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                console.log('API Response Status:', response.status);
                console.log('API Response Headers:', response.headers);
                
                const data = await response.json();
                console.log('API Response Data:', data);
                
                if (response.ok && data && data.success) {
                    this.bom = data.data;
                    console.log('✓ BOM loaded successfully:', this.bom);
                } else {
                    this.errorMessage = data && data.message ? data.message : 'Failed to load BOM details.';
                    console.error('✗ API Error:', this.errorMessage);
                    console.error('Full error response:', data);
                }
            } catch (error) {
                console.error('✗ Exception occurred:', error);
                this.errorMessage = error.message || 'Failed to load BOM details.';
            } finally {
                this.loading = false;
            }
        },
        formatDate(val) {
            if (!val) return '-';
            try {
                // Handle ISO strings or YYYY-MM-DD
                const date = new Date(val);
                if (isNaN(date.getTime())) return val;
                return date.toLocaleDateString('en-GB', { 
                    day: 'numeric', 
                    month: 'short', 
                    year: 'numeric' 
                });
            } catch (e) {
                return val;
            }
        },
        async init() {
            await this.loadData();
        }
    }
}
</script>
@endsection
