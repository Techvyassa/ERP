@extends('tenant.layouts.inventory')

@section('title', 'Create Product')
@section('page-title', 'Create New Product')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="productForm()" x-init="loadDropdowns()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Product</h2>
                    <p class="text-gray-600 mt-1">Add finished goods master</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Product Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Product Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
                        </label>
                        <div class="space-y-3">
                            <!-- Auto-generate option -->
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" 
                                       x-model="form.auto_generate_code"
                                       @change="handleAutoGenerateChange()"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label class="text-sm text-gray-700 cursor-pointer">Auto-generate code</label>
                            </div>
                            
                            <!-- Manual code generation -->
                            <div x-show="!form.auto_generate_code" x-transition>
                                <div class="flex items-center space-x-2">
                                    <!-- Manual Prefix -->
                                    <div class="w-32">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
                                        <input type="text" 
                                               x-model="form.manual_prefix"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="FG"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    
                                    <!-- Separator -->
                                    <div class="flex items-center pb-6">
                                        <span class="text-gray-500 font-medium">-</span>
                                    </div>
                                    
                                    <!-- Sequential Number -->
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                                        <input type="text" 
                                               x-model="form.manual_number"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="0001"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                </div>
                                
                                <!-- Generated Code Display -->
                                <div class="mt-2">
                                    <input type="text" 
                                           x-model="form.product_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="30"
                                           placeholder="FG-0001"
                                           :class="{
                                               'border-red-500 focus:ring-red-500': errors.product_code, 
                                               'border-gray-300 focus:ring-blue-500': !errors.product_code
                                           }"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Generated product code (auto-updates from prefix and number)</p>
                                    <template x-if="errors.product_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.product_code) ? errors.product_code[0] : errors.product_code"></p>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Auto-generate info -->
                            <div x-show="form.auto_generate_code" x-transition>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-magic text-green-600 mr-2"></i>
                                        <div class="text-sm text-green-800">
                                            <p class="font-medium">Auto-generation enabled</p>
                                            <p class="text-xs mt-1">Code will be generated based on product category:
                                                <span x-show="form.product_category === 'ELECTRONICS'">ELEC-XXXX</span>
                                                <span x-show="form.product_category === 'CLOTHING'">CLO-XXXX</span>
                                                <span x-show="form.product_category === 'FOOD'">FD-XXXX</span>
                                                <span x-show="form.product_category === 'BEVERAGES'">BEV-XXXX</span>
                                                <span x-show="form.product_category === 'FURNITURE'">FUR-XXXX</span>
                                                <span x-show="form.product_category === 'TOYS'">TOY-XXXX</span>
                                                <span x-show="form.product_category === 'BOOKS'">BK-XXXX</span>
                                                <span x-show="form.product_category === 'SPORTS'">SP-XXXX</span>
                                                <span x-show="form.product_category === 'BEAUTY'">BEA-XXXX</span>
                                                <span x-show="form.product_category === 'AUTOMOTIVE'">AUTO-XXXX</span>
                                                <span x-show="form.product_category && !['ELECTRONICS','CLOTHING','FOOD','BEVERAGES','FURNITURE','TOYS','BOOKS','SPORTS','BEAUTY','AUTOMOTIVE'].includes(form.product_category)">PROD-XXXX (default)</span>
                                                <span x-show="!form.product_category">Enter category to see prefix</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.product_name" required maxlength="200"
                               placeholder="Masala Powder 100g"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Product Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product Category
                        </label>
                        <input type="text" x-model="form.product_category" 
                               @change="handleProductCategoryChange()"
                               maxlength="60"
                               placeholder="Spice / Blend / Condiment"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Category determines auto-generated code prefix</p>
                    </div>

                    <!-- Pack Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pack Size <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.pack_size" required min="0" step="0.001"
                               placeholder="100, 250, 1000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">100, 250, 1000 (per pack uom)</p>
                    </div>

                    <!-- Pack UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pack UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.pack_uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ uom_master(uom_id)</p>
                    </div>

                    <!-- HSN Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            HSN Code <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.hsn_code_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select HSN Code</option>
                            <template x-for="hsn in hsnCodes" :key="hsn.id">
                                <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ hsn_codes(hsn_id)</p>
                    </div>
                </div>
            </div>

            <!-- Costing & Pricing -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Costing & Pricing</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Standard Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Cost <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.standard_cost" required min="0" step="0.01"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Cost per unit</p>
                    </div>

                    <!-- MRP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            MRP (Maximum Retail Price)
                        </label>
                        <input type="number" x-model="form.mrp" min="0" step="0.01"
                               placeholder="Optional"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Maximum retail price</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Product</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Product Master</p>
                        <p>Finished Goods master. The raw_materials JSON column has been REMOVED and replaced by bom_header + bom_detail for proper relational integrity.</p>
                        <p class="mt-2 text-xs">Used in: BOM, Sales Orders, Production Planning, Dispatch, Costing</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Product</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function productForm() {
    return {
        loading: false,
        uoms: [],
        hsnCodes: [],
        form: {
            product_code: '',
            product_name: '',
            product_category: '',
            pack_size: '',
            pack_uom_id: '',
            hsn_code_id: '',
            standard_cost: 0,
            mrp: '',
            is_active: true,
            auto_generate_code: false,
            manual_prefix: '',
            manual_number: ''
        },
        
        handleProductCategoryChange() {
            if (this.form.auto_generate_code) {
                this.showAutoGeneratedCode();
            } else {
                // Update manual prefix when product category changes
                this.form.manual_prefix = this.getDefaultPrefix(this.form.product_category);
                this.updateManualCode();
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.product_code = ''; // Clear the field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
                this.errors.product_code = null; // Clear any validation errors
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = this.getDefaultPrefix(this.form.product_category);
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        getDefaultPrefix(productCategory) {
            const prefixes = {
                'ELECTRONICS': 'ELEC',
                'CLOTHING': 'CLO',
                'FOOD': 'FD',
                'BEVERAGES': 'BEV',
                'FURNITURE': 'FUR',
                'TOYS': 'TOY',
                'BOOKS': 'BK',
                'SPORTS': 'SP',
                'BEAUTY': 'BEA',
                'AUTOMOTIVE': 'AUTO'
            };
            return prefixes[productCategory] || 'PROD';
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.product_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.product_code = '';
            }
        },
        
        showAutoGeneratedCode() {
            if (this.form.auto_generate_code && this.form.product_category) {
                const prefix = {
                    'ELECTRONICS': 'ELEC',
                    'CLOTHING': 'CLO',
                    'FOOD': 'FD',
                    'BEVERAGES': 'BEV',
                    'FURNITURE': 'FUR',
                    'TOYS': 'TOY',
                    'BOOKS': 'BK',
                    'SPORTS': 'SP',
                    'BEAUTY': 'BEA',
                    'AUTOMOTIVE': 'AUTO'
                }[this.form.product_category] || 'PROD';
                
                // Show a preview of what the code will be
                console.log(`Auto-generated product code will be: ${prefix}-XXXX (sequential)`);
            }
        },
        
        async loadDropdowns() {
            this.loading = true;
            try {
                // Load UOMs and HSN Codes in parallel
                const [uomsResponse, hsnResponse] = await Promise.all([
                    fetch('/api/v1/uoms', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    }),
                    fetch('/api/v1/hsn-codes', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    })
                ]);
                
                if (uomsResponse.ok) {
                    const uomsData = await uomsResponse.json();
                    // API returns data directly as array, not nested
                    this.uoms = Array.isArray(uomsData.data) ? uomsData.data : (uomsData.data?.uoms || []);
                }
                
                if (hsnResponse.ok) {
                    const hsnData = await hsnResponse.json();
                    this.hsnCodes = Array.isArray(hsnData.data) ? hsnData.data : (hsnData.data?.hsn_codes || []);
                }
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
                this.showNotification('Failed to load dropdown data', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch('/api/v1/products', {
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
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        this.showNotification(data.message || 'Failed to create product', 'error');
                    }
                    return;
                }
                
                this.showNotification('Product created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to create product:', error);
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
