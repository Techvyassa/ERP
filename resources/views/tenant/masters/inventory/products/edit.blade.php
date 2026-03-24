@extends('tenant.layouts.inventory')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="productForm()" x-init="loadProductData()">
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
                    <h2 class="text-2xl font-bold text-gray-900">Edit Product</h2>
                    <p class="text-gray-600 mt-1">Update product information</p>
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
                            Product Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.product_code" required maxlength="30"
                               placeholder="FG-0001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <input type="text" x-model="form.product_category" maxlength="60"
                               placeholder="Spice / Blend / Condiment"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Product category for classification</p>
                    </div>

                    <!-- Pack Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pack Size <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.pack_size" required step="0.001" min="0"
                               placeholder="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Size per pack (in UOM)</p>
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
                        <p class="text-xs text-gray-500 mt-1">Unit of measurement for pack size</p>
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
                        <p class="text-xs text-gray-500 mt-1">HSN code for tax purposes</p>
                    </div>

                    <!-- Standard Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Cost
                        </label>
                        <input type="number" x-model="form.standard_cost" step="0.0001" min="0"
                               placeholder="0.0000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Cost per unit (4 decimal places)</p>
                    </div>

                    <!-- MRP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Retail Price (MRP)
                        </label>
                        <input type="number" x-model="form.mrp" step="0.01" min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Maximum retail price (2 decimal places)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Product</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Enable for transactions and sales</p>
            </div>

            <!-- Info Box -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-purple-600 mt-1 mr-3"></i>
                    <div class="text-sm text-purple-800">
                        <p class="font-semibold mb-1">About Product Master</p>
                        <p>Finished goods and products for sales and inventory management with pricing and tax information.</p>
                        <p class="mt-2 text-xs">Used in: Sales Orders, Invoices, Production, Inventory Management</p>
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
                    <span x-show="!loading">Update Product</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
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
        productId: null,
        form: {
            product_code: '',
            product_name: '',
            product_category: '',
            pack_size: '',
            pack_uom_id: '',
            hsn_code_id: '',
            standard_cost: 0,
            mrp: '',
            is_active: true
        },
        
        async loadProductData() {
            // Get product ID from URL
            const urlParts = window.location.pathname.split('/');
            this.productId = urlParts[urlParts.length - 2]; // Get ID before /edit
            
            console.log('URL Path:', window.location.pathname);
            console.log('Extracted Product ID:', this.productId);
            
            if (!this.productId || isNaN(this.productId)) {
                console.error('Invalid product ID:', this.productId);
                this.showNotification('Invalid product ID', 'error');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}';
                }, 2000);
                return;
            }
            
            this.loading = true;
            try {
                // Load product data and dropdowns
                const productResponse = await fetch(`/api/v1/products/${this.productId}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Product API Response Status:', productResponse.status);
                
                if (!productResponse.ok) {
                    const errorData = await productResponse.json();
                    console.error('Product API Error:', errorData);
                    throw new Error(errorData.message || 'Failed to load product data');
                }
                
                const productData = await productResponse.json();
                console.log('Product Data:', productData);
                
                this.form = {
                    product_code: productData.data?.product?.product_code || '',
                    product_name: productData.data?.product?.product_name || '',
                    product_category: productData.data?.product?.product_category || '',
                    pack_size: productData.data?.product?.pack_size || '',
                    pack_uom_id: productData.data?.product?.pack_uom_id || '',
                    hsn_code_id: productData.data?.product?.hsn_code_id || '',
                    standard_cost: productData.data?.product?.standard_cost || 0,
                    mrp: productData.data?.product?.mrp || '',
                    is_active: productData.data?.product?.is_active !== undefined ? productData.data.product.is_active : true
                };
                
                console.log('Form populated:', this.form);
                
                // Load dropdowns separately
                await this.loadDropdowns();
                
            } catch (error) {
                console.error('Failed to load product data:', error);
                this.showNotification('Failed to load product data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async loadDropdowns() {
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
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                console.log('Submitting product update with data:', this.form);
                console.log('Product ID:', this.productId);
                
                const response = await fetch(`/api/v1/products/${this.productId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                console.log('Update API Response Status:', response.status);
                
                const data = await response.json();
                console.log('Update API Response Data:', data);
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        console.log('Validation errors:', data.error.details);
                        this.showNotification('Please fix validation errors', 'error');
                    } else {
                        console.log('API Error:', data);
                        this.showNotification(data.message || 'Failed to update product', 'error');
                    }
                    return;
                }
                
                this.showNotification('Product updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}';
                }, 1500);
                
            } catch (error) {
                console.error('Failed to update product:', error);
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
