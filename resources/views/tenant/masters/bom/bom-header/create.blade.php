@extends('tenant.layouts.bom')

@section('title', 'Create BOM')
@section('page-title', 'Create New Bill of Materials')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="bomForm()" x-init="loadDropdowns()">
    <div class="max-w-4xl mx-auto relative">
        <!-- Notification Toast -->
        <div x-show="notification.show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-xl shadow-2xl border-l-4 p-4 pointer-events-auto"
            :class="notification.type === 'success' ? 'border-green-500' : 'border-red-500'"
            style="display: none;">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <template x-if="notification.type === 'success'">
                        <div class="bg-green-100 rounded-full p-1">
                            <i class="fas fa-check text-green-600 text-xs"></i>
                        </div>
                    </template>
                    <template x-if="notification.type === 'error'">
                        <div class="bg-red-100 rounded-full p-1">
                            <i class="fas fa-times text-red-600 text-xs"></i>
                        </div>
                    </template>
                </div>
                <div class="ml-3 pr-8">
                    <p class="text-sm font-semibold text-gray-900" x-text="notification.type === 'success' ? 'Success' : 'Error'"></p>
                    <p class="text-xs text-gray-600" x-text="notification.message"></p>
                </div>
                <button @click="notification.show = false" class="ml-auto text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Bill of Materials</h2>
                    <p class="text-gray-600 mt-1">Define product BOM with version management</p>
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
                    <!-- BOM Code -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            BOM Code <span class="text-red-500">*</span>
                        </label>

                        <!-- Auto-generate checkbox -->
                        <div class="mb-3">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="autoGenerate" @change="toggleAutoGenerate"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">Auto-generate code</span>
                            </label>
                        </div>

                        <!-- Manual input (when auto-generate is off) -->
                        <div x-show="!autoGenerate">
                            <input type="text"
                                x-model="form.bom_code"
                                required
                                maxlength="30"
                                placeholder="BOM-FG001-V1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Unique BOM identifier (max 30 chars)</p>
                        </div>

                        <!-- Auto-generate fields (when auto-generate is on) -->
                        <div x-show="autoGenerate" class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
                                    <input type="text"
                                        x-model="codeGeneration.prefix"
                                        @input.debounce.500ms="generateCode"
                                        maxlength="10"
                                        placeholder="BOM"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                                    <input type="text"
                                        x-model="codeGeneration.number"
                                        @input="generateCode"
                                        maxlength="10"
                                        placeholder="0001"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>

                            <!-- Generated code display -->
                            <div>
                                <input type="text"
                                    x-model="form.bom_code"
                                    readonly
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
                                <p class="text-xs text-gray-500 mt-1">Generated BOM code (auto-updates from prefix and number)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Product -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Product <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.product_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Product</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.product_code + ' - ' + product.product_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ product</p>
                    </div>

                    <!-- Version -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Version <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.version" required min="1"
                            placeholder="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Version number (1, 2, 3...)</p>
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



                    <!-- Output UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Output UOM <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.output_uom_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ uom</p>
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

            <!-- BOM Details (Items) -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4 pb-2 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">BOM Components (Items)</h3>
                    <button type="button" @click="addItem" class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm font-medium transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border rounded-lg">
                        <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3">Material <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3">Qty <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3">UOM <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3">Sub. Material</th>
                                <th class="px-4 py-3 w-24">Deviation %</th>
                                <th class="px-4 py-3 w-32 bg-gray-100 text-gray-700">Effective Qty</th>
                                <th class="px-4 py-3 w-20 text-center">Critical</th>
                                <th class="px-4 py-3 w-16 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in form.items" :key="index">
                                <tr class="border-b bg-white hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-2">
                                        <select x-model="item.material_id" @change="updateItemUom(item)" required class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Material</option>
                                            <template x-for="mat in filteredMaterials(index)" :key="mat.id">
                                                <option :value="mat.id" x-text="mat.material_code + ' - ' + mat.material_name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" x-model="item.qty_required" required min="0.0001" step="0.0001" class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </td>
                                    <td class="px-4 py-2">
                                        <select x-model="item.uom_id" required class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select UOM</option>
                                            <template x-for="uom in uoms" :key="uom.id">
                                                <option :value="uom.id" x-text="uom.uom_name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <select x-model="item.substitute_material_id" class="w-full px-2 py-1 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">None</option>
                                            <template x-for="mat in materials" :key="mat.id">
                                                <option :value="mat.id" x-text="mat.material_code"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="relative">
                                            <input type="number" x-model="item.scrap_percent" min="0" max="100" step="0.1"
                                                placeholder="0"
                                                class="w-full px-2 py-1 pr-6 border rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 bg-gray-50">
                                        <input type="text" :value="calculateEffectiveQty(item)" readonly class="w-full px-2 py-1 border rounded bg-gray-100 text-gray-600 cursor-not-allowed font-medium">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" x-model="item.is_critical" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 transition-colors" :disabled="form.items.length === 1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-4 py-3 text-right">Total Material Weight:</td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-900" x-text="totalWeight.toFixed(4)"></span>
                                </td>
                                <td colspan="3" class="px-4 py-3 text-sm italic border-x">
                                    <span class="text-gray-500">Formulation balance not calculated</span>
                                </td>
                                <td class="px-4 py-3 bg-gray-100 text-gray-700" x-text="totalEffectiveQty.toFixed(4)"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- Info Box -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-2 mr-4">
                        <i class="fas fa-lightbulb text-indigo-600"></i>
                    </div>
                    <div class="text-sm text-indigo-900">
                        <p class="font-bold text-indigo-950 mb-1">Quick Guide: BOM Versioning</p>
                        <p class="leading-relaxed">A Bill of Materials (BOM) is like a recipe for your product. You can create different versions of the same recipe over time, but each version must have a unique number (like v1, v2, etc.).</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium uppercase tracking-wider">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Production Recipes</span>
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Material Planning</span>
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Cost Calculation</span>
                        </div>
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
                    <span x-show="!loading">Create BOM</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function bomForm() {
        return {
            loading: false,
            notification: {
                show: false,
                message: '',
                type: 'success'
            },
            showNotification(message, type = 'success') {
                this.notification.message = message;
                this.notification.type = type;
                this.notification.show = true;
                setTimeout(() => {
                    this.notification.show = false;
                }, 4000);
            },
            products: [],
            uoms: [],
            materials: [],
            autoGenerate: false,
            codeGeneration: {
                prefix: 'BOM',
                number: '0001'
            },
            form: {
                bom_code: '',
                product_id: '',
                version: 1,
                effective_from: '',
                effective_to: '',
                output_uom_id: '',
                bom_status: 'DRAFT',
                remarks: '',
                items: [{
                    material_id: '',
                    qty_required: 1,
                    uom_id: '',
                    scrap_percent: 0,
                    substitute_material_id: '',
                    is_critical: false,
                    remarks: ''
                }]
            },

            get filteredMaterialsBase() {
                // Only show Raw Materials (RAW) and Semi-Finished Goods (SFG) as components
                return this.materials.filter(m => ['RAW', 'SEMI_FINISHED', 'RM', 'SFG'].includes(m.material_type));
            },

            get totalWeight() {
                return this.form.items.reduce((sum, item) => sum + (parseFloat(item.qty_required) || 0), 0);
            },

            get totalEffectiveQty() {
                return this.form.items.reduce((sum, item) => sum + (parseFloat(this.calculateEffectiveQty(item)) || 0), 0);
            },


            calculateEffectiveQty(item) {
                const qty = parseFloat(item.qty_required) || 0;
                const scrap = parseFloat(item.scrap_percent) || 0;
                return (qty * (1 + (scrap / 100))).toFixed(4);
            },

            addItem() {
                this.form.items.push({
                    material_id: '',
                    qty_required: 1,
                    uom_id: '',
                    scrap_percent: 0,
                    substitute_material_id: '',
                    is_critical: false,
                    remarks: ''
                });
            },

            get outputUomCode() {
                const uom = this.uoms.find(u => u.id == this.form.output_uom_id);
                return uom ? uom.uom_code : '';
            },

            get outputUomCode() {
                const uom = this.uoms.find(u => u.id == this.form.output_uom_id);
                return uom ? uom.uom_code : '';
            },

            filteredMaterials(currentIndex) {
                const selectedIds = this.form.items
                    .map((item, idx) => idx !== currentIndex ? parseInt(item.material_id) : null)
                    .filter(id => id !== null && !isNaN(id));

                return this.filteredMaterialsBase.filter(mat => !selectedIds.includes(mat.id));
            },

            updateItemUom(item) {
                const mat = this.materials.find(m => m.id == item.material_id);
                if (mat && mat.uom_id) {
                    item.uom_id = mat.uom_id;
                }
            },

            removeItem(index) {
                if (this.form.items.length > 1) {
                    this.form.items.splice(index, 1);
                }
            },

            async toggleAutoGenerate() {
                if (this.autoGenerate) {
                    await this.fetchNextNumber();
                } else {
                    this.form.bom_code = '';
                }
            },

            async fetchNextNumber() {
                const prefix = this.codeGeneration.prefix || 'BOM';
                try {
                    const response = await fetch(`/api/v1/bom-headers/next-code?prefix=${prefix}`, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            this.codeGeneration.number = result.data.next_number;
                            this.generateCode();
                        }
                    }
                } catch (e) {
                    console.error("Fetch next number failed", e);
                }
            },

            generateCode() {
                if (this.autoGenerate) {
                    const prefix = this.codeGeneration.prefix || 'BOM';
                    const number = this.codeGeneration.number || '0001';
                    this.form.bom_code = `${prefix}-${number}`;
                }
            },

            async loadDropdowns() {
                try {
                    // Load products
                    const productResponse = await fetch('/api/v1/products?per_page=1000', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (productResponse.ok) {
                        const productData = await productResponse.json();
                        if (productData && productData.success && productData.data) {
                            this.products = productData.data.products || productData.data || [];
                        }
                    }

                    // Load UOMs
                    const uomResponse = await fetch('/api/v1/uoms?per_page=1000', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (uomResponse.ok) {
                        const uomData = await uomResponse.json();
                        if (uomData && uomData.success && uomData.data) {
                            this.uoms = Array.isArray(uomData.data) ? uomData.data : (uomData.data.uoms || []);
                        }
                    }

                    // Load Materials
                    const matResponse = await fetch('/api/v1/materials?per_page=1000', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (matResponse.ok) {
                        const matData = await matResponse.json();
                        if (matData && matData.success && matData.data) {
                            this.materials = Array.isArray(matData.data) ? matData.data : (matData.data.materials || []);
                        }
                    }
                } catch (error) {
                    console.error('Failed to load dropdowns:', error);
                    alert('Failed to load products/UOMs. Please refresh the page.');
                }
            },

            async submitForm() {
                this.loading = true;
                try {
                    // Convert string values to proper types
                    const formData = {
                        bom_code: this.form.bom_code,
                        product_id: parseInt(this.form.product_id),
                        version: parseInt(this.form.version),
                        effective_from: this.form.effective_from,
                        effective_to: this.form.effective_to || null,
                        output_uom_id: parseInt(this.form.output_uom_id),
                        bom_status: this.form.bom_status,
                        remarks: this.form.remarks || null,
                        items: this.form.items.map(item => ({
                            material_id: parseInt(item.material_id),
                            qty_required: parseFloat(item.qty_required),
                            uom_id: parseInt(item.uom_id),
                            scrap_percent: item.scrap_percent ? parseFloat(item.scrap_percent) : 0,
                            substitute_material_id: item.substitute_material_id ? parseInt(item.substitute_material_id) : null,
                            is_critical: Boolean(item.is_critical),
                            remarks: item.remarks || null
                        }))
                    };

                    const response = await fetch('/api/v1/bom-headers', {
                        method: 'POST',
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
                        const errorMsg = data && data.error && data.error.details ?
                            JSON.stringify(data.error.details) :
                            (data && data.message) ? data.message : 'Failed to create BOM';
                        throw new Error(errorMsg);
                    }

                    this.showNotification('BOM header created successfully!', 'success');
                    setTimeout(() => {
                        const baseUrl = '{{ $tenantType }}' === 'subdomain' ? '' : '/org/{{ $organization->org_slug }}';
                        window.location.href = baseUrl + '/bom-header';
                    }, 1000);
                } catch (error) {
                    console.error('Failed to create BOM:', error);
                    this.showNotification(error.message || 'Failed to create BOM. Please try again.', 'error');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection