@extends('tenant.layouts.bom')

@section('title', 'Create BOM Detail')
@section('page-title', 'Create New BOM Component')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="bomDetailForm()" x-init="loadDropdowns()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New BOM Component</h2>
                    <p class="text-gray-600 mt-1">Add material component to BOM</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail' : '/org/' . $organization->org_slug . '/bom-detail') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Component Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Component Information</h3>
                <div class="space-y-6">
                    <!-- BOM Header -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            BOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model.number="form.bom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select BOM</option>
                            <template x-for="bom in boms" :key="bom.id">
                                <option :value="bom.id" x-text="bom.bom_code + ' - v' + bom.version"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the BOM this material belongs to</p>
                    </div>

                    <!-- Material -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Material <span class="text-red-500">*</span>
                        </label>
                        <select x-model.number="form.material_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Material</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the material for this BOM line</p>
                    </div>

                    <!-- Line Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Line Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model.number="form.line_no" required min="1"
                               placeholder="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Display sort order (1, 2, 3...)</p>
                    </div>

                    <!-- Quantity Required -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity Required <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model.number="form.qty_required" required min="0.0001" step="0.0001"
                               placeholder="100.0000" @input="calculateEffectiveQty"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Quantity needed per batch</p>
                    </div>

                    <!-- UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Unit of Measurement <span class="text-red-500">*</span>
                        </label>
                        <select x-model.number="form.uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_code + ' - ' + uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Unit of measurement for quantity</p>
                    </div>

                    <!-- Deviation Percentage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Deviation Percentage
                        </label>
                        <input type="number" x-model.number="form.scrap_percent" min="0" max="100" step="0.01"
                               placeholder="0.00" @input="calculateEffectiveQty"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Process loss percentage (0-100)</p>
                    </div>

                    <!-- Effective Quantity (Calculated) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Effective Quantity:</span>
                                <p class="text-xs text-gray-500 mt-1">qty × (1 + deviation%/100)</p>
                            </div>
                            <span class="text-lg font-bold text-blue-600" x-text="effectiveQty.toFixed(4)"></span>
                        </div>
                    </div>

                    <!-- Substitute Material -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Substitute Material
                        </label>
                        <select x-model.number="form.substitute_material_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No Substitute</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Alternate material if primary is unavailable</p>
                    </div>

                    <!-- Critical Component -->
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" x-model="form.is_critical" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-2 focus:ring-red-500">
                            <span class="text-sm font-medium text-gray-700">Critical Component</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">No substitute allowed if checked</p>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Remarks
                        </label>
                        <textarea x-model="form.remarks" rows="2" maxlength="500"
                                  placeholder="Additional notes about this material..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Max 500 characters</p>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About BOM Detail</p>
                        <p>Each BOM Detail represents a material component required to produce the product. Sequence numbers must be unique within a BOM.</p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <template x-if="error">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                        <div class="text-sm text-red-800">
                            <p class="font-semibold">Error</p>
                            <p x-text="error"></p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Debug Info -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="text-xs text-yellow-800">
                    <p class="font-semibold mb-2">Debug Info:</p>
                    <p>BOMs: <span x-text="boms.length"></span> loaded</p>
                    <p>Materials: <span x-text="materials.length"></span> loaded</p>
                    <p>UOMs: <span x-text="uoms.length"></span> loaded</p>
                    <p class="mt-2 text-gray-600">Check browser console (F12) for detailed logs</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-detail' : '/org/' . $organization->org_slug . '/bom-detail') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Component</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bomDetailForm() {
    return {
        loading: false,
        error: '',
        boms: [],
        materials: [],
        uoms: [],
        tenantType: '{{ request()->get("tenant_type") }}',
        orgSlug: '{{ $organization->org_slug ?? "" }}',
        form: {
            bom_id: '',
            material_id: '',
            line_no: '',
            qty_required: '',
            uom_id: '',
            scrap_percent: 0,
            substitute_material_id: '',
            is_critical: false,
            remarks: ''
        },
        
        get effectiveQty() {
            const qty = parseFloat(this.form.qty_required) || 0;
            const scrap = parseFloat(this.form.scrap_percent) || 0;
            return qty * (1 + scrap / 100);
        },
        
        calculateEffectiveQty() {
            // Trigger Alpine reactivity
            this.$nextTick(() => {
                // Effective quantity is calculated via getter
            });
        },
        
        async loadDropdowns() {
            console.log('Starting loadDropdowns...');
            try {
                // Load BOMs
                console.log('Loading BOMs...');
                try {
                    const bomsResponse = await fetch('/api/v1/bom-headers', {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    console.log('BOMs response status:', bomsResponse.status);
                    
                    if (bomsResponse.ok) {
                        const bomsData = await bomsResponse.json();
                        console.log('BOMs data:', bomsData);
                        this.boms = Array.isArray(bomsData.data) ? bomsData.data : (bomsData.data && bomsData.data.boms) ? bomsData.data.boms : [];
                        console.log('BOMs loaded:', this.boms.length, this.boms);
                    } else {
                        const errorText = await bomsResponse.text();
                        console.error('BOM Headers error:', bomsResponse.status, errorText);
                        if (bomsResponse.status === 403) {
                            this.error = 'You do not have permission to access BOM Headers. Please contact your administrator.';
                        } else {
                            this.error = `Failed to load BOMs: ${bomsResponse.status}`;
                        }
                    }
                } catch (e) {
                    console.error('Failed to load BOMs:', e);
                    this.error = `Error loading BOMs: ${e.message}`;
                }
                
                // Load Materials
                console.log('Loading Materials...');
                try {
                    const materialsResponse = await fetch('/api/v1/materials', {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    console.log('Materials response status:', materialsResponse.status);
                    
                    if (materialsResponse.ok) {
                        const materialsData = await materialsResponse.json();
                        console.log('Materials data:', materialsData);
                        this.materials = Array.isArray(materialsData.data) ? materialsData.data : (materialsData.data && materialsData.data.materials) ? materialsData.data.materials : [];
                        console.log('Materials loaded:', this.materials.length, this.materials);
                    } else {
                        const errorText = await materialsResponse.text();
                        console.error('Materials error:', materialsResponse.status, errorText);
                        if (materialsResponse.status === 403) {
                            this.error = 'You do not have permission to access Materials. Please contact your administrator.';
                        } else {
                            this.error = `Failed to load Materials: ${materialsResponse.status}`;
                        }
                    }
                } catch (e) {
                    console.error('Failed to load materials:', e);
                    this.error = `Error loading Materials: ${e.message}`;
                }
                
                // Load UOMs
                console.log('Loading UOMs...');
                try {
                    const uomsResponse = await fetch('/api/v1/uoms', {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    console.log('UOMs response status:', uomsResponse.status);
                    
                    if (uomsResponse.ok) {
                        const uomsData = await uomsResponse.json();
                        console.log('UOMs data:', uomsData);
                        this.uoms = Array.isArray(uomsData.data) ? uomsData.data : (uomsData.data && uomsData.data.uoms) ? uomsData.data.uoms : [];
                        console.log('UOMs loaded:', this.uoms.length, this.uoms);
                    } else {
                        const errorText = await uomsResponse.text();
                        console.error('UOMs error:', uomsResponse.status, errorText);
                        if (uomsResponse.status === 403) {
                            this.error = 'You do not have permission to access UOMs. Please contact your administrator.';
                        } else {
                            this.error = `Failed to load UOMs: ${uomsResponse.status}`;
                        }
                    }
                } catch (e) {
                    console.error('Failed to load UOMs:', e);
                    this.error = `Error loading UOMs: ${e.message}`;
                }
                
                console.log('Final state - BOMs:', this.boms.length, 'Materials:', this.materials.length, 'UOMs:', this.uoms.length);
                if (this.boms.length === 0 && this.materials.length === 0 && this.uoms.length === 0) {
                    if (!this.error) {
                        this.error = 'No data loaded from API. Check console for details.';
                    }
                }
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
                this.error = 'Failed to load form data: ' + error.message;
            }
        },
        
        async submitForm() {
            this.error = '';
            this.loading = true;
            
            try {
                // Validate required fields
                if (!this.form.bom_id) throw new Error('BOM is required');
                if (!this.form.material_id) throw new Error('Material is required');
                if (!this.form.line_no) throw new Error('Line Number is required');
                if (!this.form.qty_required) throw new Error('Quantity Required is required');
                if (!this.form.uom_id) throw new Error('UOM is required');
                
                // Prepare data with proper types
                const submitData = {
                    bom_id: parseInt(this.form.bom_id),
                    material_id: parseInt(this.form.material_id),
                    line_no: parseInt(this.form.line_no),
                    qty_required: parseFloat(this.form.qty_required),
                    uom_id: parseInt(this.form.uom_id),
                    scrap_percent: parseFloat(this.form.scrap_percent) || 0,
                    substitute_material_id: this.form.substitute_material_id ? parseInt(this.form.substitute_material_id) : null,
                    is_critical: this.form.is_critical,
                    remarks: this.form.remarks || null
                };
                
                console.log('Submitting BOM Detail:', submitData);
                
                const response = await fetch('/api/v1/bom-details', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(submitData)
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);
                
                if (!response.ok) {
                    let errorMessage = result.message || 'Failed to create BOM detail';
                    if (result.error?.details) {
                        const errors = Object.entries(result.error.details)
                            .map(([key, value]) => `${key}: ${Array.isArray(value) ? value.join(', ') : value}`)
                            .join('\n');
                        errorMessage = errors || errorMessage;
                    }
                    throw new Error(errorMessage);
                }
                
                alert('BOM detail created successfully');
                if (this.tenantType === 'subdomain') {
                    window.location.href = '/bom-detail';
                } else {
                    window.location.href = `/org/${this.orgSlug}/bom-detail`;
                }
            } catch (error) {
                console.error('Failed to create BOM detail:', error);
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
