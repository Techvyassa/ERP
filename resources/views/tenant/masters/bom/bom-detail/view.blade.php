@extends('tenant.layouts.bom')

@section('title', 'View BOM Detail')
@section('page-title', 'BOM Component Details')

@section('content')
<div x-data="bomDetailView()" x-init="loadData()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">BOM Component Details</h2>
                    <p class="text-gray-600 mt-1">View material component information</p>
                </div>
                <div class="flex gap-2">
                    <a :href="editUrl" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail' : '/org/' . $organization->org_slug . '/bom-detail') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading BOM detail...</p>
            </div>
        </template>

        <!-- Error State -->
        <template x-if="error && !loading">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                    <div>
                        <p class="font-semibold text-red-800">Error</p>
                        <p class="text-red-700 mt-1" x-text="error"></p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Content -->
        <template x-if="!loading && !error && detail">
            <div class="space-y-6">
                <!-- Component Information -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Component Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- BOM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">BOM</label>
                            <p class="text-lg text-gray-900" x-text="detail.bom_header?.bom_code + ' - v' + detail.bom_header?.version"></p>
                        </div>

                        <!-- Line Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Line Number</label>
                            <p class="text-lg text-gray-900" x-text="detail.line_no"></p>
                        </div>

                        <!-- Material -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 mb-1">Material</label>
                            <p class="text-lg text-gray-900" x-text="detail.material?.material_code + ' - ' + detail.material?.material_name"></p>
                        </div>

                        <!-- Quantity Required -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Quantity Required</label>
                            <p class="text-lg text-gray-900" x-text="parseFloat(detail.qty_required).toFixed(4)"></p>
                        </div>

                        <!-- UOM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Unit of Measurement</label>
                            <p class="text-lg text-gray-900" x-text="detail.uom?.uom_code + ' - ' + detail.uom?.uom_name"></p>
                        </div>

                        <!-- Deviation Percentage -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Deviation Percentage</label>
                            <p class="text-lg text-gray-900" x-text="parseFloat(detail.scrap_percent || 0).toFixed(2) + '%'"></p>
                        </div>

                        <!-- Effective Quantity -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Effective Quantity</label>
                            <p class="text-lg font-semibold text-blue-600" x-text="parseFloat(detail.effective_qty).toFixed(4)"></p>
                        </div>

                        <!-- Critical Component -->
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Critical Component</label>
                            <span class="px-3 py-1 text-sm rounded-full" :class="detail.is_critical ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" x-text="detail.is_critical ? 'Yes' : 'No'"></span>
                        </div>

                        <!-- Substitute Material -->
                        <template x-if="detail.substitute_material">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Substitute Material</label>
                                <p class="text-lg text-gray-900" x-text="detail.substitute_material?.material_code + ' - ' + detail.substitute_material?.material_name"></p>
                            </div>
                        </template>

                        <!-- Remarks -->
                        <template x-if="detail.remarks">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Remarks</label>
                                <p class="text-gray-900 whitespace-pre-wrap" x-text="detail.remarks"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Metadata</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Created:</span>
                            <p class="text-gray-900" x-text="new Date(detail.created_at).toLocaleString()"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Last Updated:</span>
                            <p class="text-gray-900" x-text="new Date(detail.updated_at).toLocaleString()"></p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function bomDetailView() {
    return {
        loading: false,
        error: '',
        detail: null,
        tenantType: '{{ request()->get("tenant_type") }}',
        orgSlug: '{{ $organization->org_slug ?? "" }}',
        detailId: {{ $id }},
        
        get editUrl() {
            if (this.tenantType === 'subdomain') {
                return `/bom-detail/${this.detailId}/edit`;
            }
            return `/org/${this.orgSlug}/bom-detail/${this.detailId}/edit`;
        },
        
        async loadData() {
            console.log('Starting loadData...');
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                
                if (!token) {
                    this.error = 'Authentication token not found. Please login again.';
                    return;
                }
                
                const response = await fetch(`/api/v1/bom-details/${this.detailId}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    if (response.status === 404) {
                        this.error = 'BOM detail not found.';
                    } else if (response.status === 403) {
                        this.error = 'You do not have permission to view this BOM detail.';
                    } else {
                        this.error = `Failed to load BOM detail: ${response.status}`;
                    }
                    return;
                }
                
                const result = await response.json();
                this.detail = result.data;
                console.log('BOM detail loaded:', this.detail);
            } catch (error) {
                console.error('Failed to load BOM detail:', error);
                this.error = 'Error loading BOM detail: ' + error.message;
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
