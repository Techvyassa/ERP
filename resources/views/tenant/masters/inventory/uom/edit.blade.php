@extends('tenant.layouts.inventory')

@section('title', 'Edit UOM')
@section('page-title', 'Edit UOM')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="uomForm()" x-init="loadUomData()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit UOM</h2>
                    <p class="text-gray-600 mt-1">Update unit of measurement details</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- UOM Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">UOM Information</h3>
                <div class="space-y-6">
                    <!-- UOM Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.uom_code" required maxlength="10"
                               placeholder="KG, GM, LTR, PCS, BAG"
                               :class="{'border-red-500 focus:ring-red-500': errors.uom_code, 'border-gray-300 focus:ring-blue-500': !errors.uom_code}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.uom_code">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_code) ? errors.uom_code[0] : errors.uom_code"></p>
                        </template>
                    </div>

                    <!-- UOM Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.uom_name" required maxlength="50"
                               placeholder="Kilogram, Gram, Litre..."
                               :class="{'border-red-500 focus:ring-red-500': errors.uom_name, 'border-gray-300 focus:ring-blue-500': !errors.uom_name}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="errors.uom_name">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_name) ? errors.uom_name[0] : errors.uom_name"></p>
                        </template>
                    </div>

                    <!-- UOM Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UOM Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.uom_type" required
                                :class="{'border-red-500 focus:ring-red-500': errors.uom_type, 'border-gray-300 focus:ring-blue-500': !errors.uom_type}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="weight">weight - Weight</option>
                            <option value="volume">volume - Volume</option>
                            <option value="qty">qty - Quantity</option>
                            <option value="length">length - Length</option>
                        </select>
                        <template x-if="errors.uom_type">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.uom_type) ? errors.uom_type[0] : errors.uom_type"></p>
                        </template>
                    </div>

                    <!-- Base UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Base UOM
                        </label>
                        <select x-model="form.base_uom_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">None (This is base UOM)</option>
                            <template x-for="uom in baseUoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Self-ref → uom_master(uom_id)</p>
                    </div>

                    <!-- Conversion Factor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Conversion Factor <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.conversion_factor" required min="0" step="0.000001"
                               placeholder="1"
                               :class="{'border-red-500 focus:ring-red-500': errors.conversion_factor, 'border-gray-300 focus:ring-blue-500': !errors.conversion_factor}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">1 GM = 0.001 KG (conversion factor)</p>
                        <template x-if="errors.conversion_factor">
                            <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.conversion_factor) ? errors.conversion_factor[0] : errors.conversion_factor"></p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active UOM</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About UOM Master</p>
                        <p>Units of Measurement with base UOM conversion factors for cross-unit calculations.</p>
                        <p class="mt-2 text-xs">Used in: Material, Product, BOM, GRN</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Update UOM</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function uomForm() {
    return {
        loading: false,
        baseUoms: [],
        errors: {},
        uomId: null,
        form: {
            uom_code: '',
            uom_name: '',
            uom_type: '',
            base_uom_id: '',
            conversion_factor: 1,
            is_active: true
        },
        
        async loadUomData() {
            // Get UOM ID from URL - handle /uom/{id}/edit pattern
            const urlPath = window.location.pathname;
            console.log('URL Path:', urlPath);
            
            // Extract ID using regex for /uom/{id}/edit pattern
            const idMatch = urlPath.match(/\/uom\/(\d+)\/edit$/);
            
            this.uomId = idMatch ? idMatch[1] : null;
            
            console.log('Extracted UOM ID:', this.uomId);
            console.log('ID Match:', idMatch);
            
            if (!this.uomId || isNaN(this.uomId)) {
                console.error('Invalid UOM ID:', this.uomId);
                this.showNotification('Invalid UOM ID extracted from URL', 'error');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}';
                }, 2000);
                return;
            }
            
            this.loading = true;
            try {
                console.log('Fetching UOM data for ID:', this.uomId);
                
                const token = localStorage.getItem('access_token');
                
                // Load UOM data
                const [uomResponse, baseUomsResponse] = await Promise.all([
                    fetch(`/api/v1/uoms/${this.uomId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    }),
                    fetch('/api/v1/uoms', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    })
                ]);
                
                console.log('UOM Response status:', uomResponse.status);
                console.log('Base UOMs Response status:', baseUomsResponse.status);
                
                if (!uomResponse.ok) {
                    const errorData = await uomResponse.json();
                    console.error('UOM API Error:', errorData);
                    throw new Error(errorData.message || 'Failed to load UOM data');
                }
                if (!baseUomsResponse.ok) throw new Error('Failed to load base UOMs');
                
                const uomData = await uomResponse.json();
                const baseUomsData = await baseUomsResponse.json();
                
                console.log('UOM Data:', uomData);
                console.log('Base UOMs Data:', baseUomsData);
                
                this.form = {
                    uom_code: uomData.data?.uom?.uom_code || '',
                    uom_name: uomData.data?.uom?.uom_name || '',
                    uom_type: uomData.data?.uom?.uom_type || '',
                    base_uom_id: uomData.data?.uom?.base_uom_id || '',
                    conversion_factor: uomData.data?.uom?.conversion_factor || 1,
                    is_active: uomData.data?.uom?.is_active !== undefined ? uomData.data.uom.is_active : true
                };
                
                this.baseUoms = baseUomsData.data?.uoms || baseUomsData.data || [];
                
                console.log('Form data loaded:', this.form);
                console.log('Base UOMs loaded:', this.baseUoms);
                
            } catch (error) {
                console.error('Failed to load UOM data:', error);
                this.showNotification('Failed to load UOM data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await fetch(`/api/v1/uoms/${this.uomId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        this.errors = data.error.details;
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        this.showNotification(data.message || 'Failed to update UOM', 'error');
                    }
                    return;
                }
                
                this.showNotification('UOM updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to update UOM:', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}
</script>
@endsection
