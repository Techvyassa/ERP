@extends('tenant.layouts.inventory')

@section('title', 'Edit Material')
@section('page-title', 'Edit Material')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
@endpush

@section('content')
<div x-data="materialEditForm()" x-init="initialize()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-40">
        <div class="bg-white rounded-xl p-6 flex items-center gap-3 shadow-xl">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium" x-text="loadingMessage">Loading...</span>
        </div>
    </div>

    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-3 rounded-xl shadow-md">
                        <span class="material-symbols-outlined text-white text-2xl">edit_note</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Edit Material</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Modify material details, inventory settings and costing</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm text-gray-700">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500 text-2xl">info</span>
                    Basic Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Material Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Material Code</label>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-gray-600 font-mono text-sm">
                            <span x-text="form.material_code"></span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Immutable material identifier</p>
                    </div>

                    <!-- Material Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Material Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.material_name" required maxlength="200"
                               placeholder="e.g. Cumin Seeds (Premium)..."
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <template x-if="errors.material_name">
                            <p class="mt-1 text-xs text-red-600" x-text="errors.material_name[0]"></p>
                        </template>
                    </div>

                    <!-- Material Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Material Type <span class="text-red-500">*</span></label>
                        <select x-model="form.material_type" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="RAW">Raw Material</option>
                            <option value="PACKAGING">Packaging</option>
                            <option value="CONSUMABLE">Consumable</option>
                            <option value="SEMI">Semi-finished</option>
                        </select>
                    </div>

                    <!-- Stock UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock UOM <span class="text-red-500">*</span></label>
                        <select x-model="form.uom_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name" :selected="form.uom_id == uom.id"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Purchase UOM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase UOM</label>
                        <select x-model="form.purchase_uom_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="">Same as Stock UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name" :selected="form.purchase_uom_id == uom.id"></option>
                            </template>
                        </select>
                    </div>

                    <!-- HSN Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">HSN Code <span class="text-red-500">*</span></label>
                        <select x-model="form.hsn_code_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="">Select HSN Code</option>
                            <template x-for="hsn in hsnCodes" :key="hsn.id">
                                <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description" :selected="form.hsn_code_id == hsn.id"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Inventory Management -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500 text-2xl">warehouse</span>
                    Inventory Management
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Warehouse</label>
                        <select x-model="form.default_warehouse_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="">No Default</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name" :selected="form.default_warehouse_id == wh.id"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                        <input type="number" x-model="form.reorder_level" step="1" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <p class="text-xs text-gray-400 mt-1">Auto PR trigger quantity</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Safety Stock</label>
                        <input type="number" x-model="form.safety_stock" step="1" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <p class="text-xs text-gray-400 mt-1">Minimum buffer quantity</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lead Time (Days)</label>
                        <input type="number" x-model="form.lead_time_days" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shelf Life (Days)</label>
                        <input type="number" x-model="form.shelf_life_days" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                </div>
            </div>

            <!-- Quality & Costing -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500 text-2xl">verified</span>
                    Quality & Costing
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" x-model="form.qc_required" id="qc_required"
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="qc_required" class="text-sm font-medium text-gray-700 cursor-pointer">QC Required on Receipt</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Inspection Type</label>
                        <select x-model="form.inspection_type" :disabled="!form.qc_required"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all disabled:bg-gray-100 bg-white">
                            <option value="AQL">AQL Sampling</option>
                            <option value="100%">100% Inspection</option>
                            <option value="random">Random Selection</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" x-model="form.is_batch_tracked" id="is_batch_tracked"
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="is_batch_tracked" class="text-sm font-medium text-gray-700 cursor-pointer">Batch/Lot Tracking</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard Cost</label>
                        <input type="number" x-model="form.standard_cost" step="0.001" min="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valuation Method</label>
                        <select x-model="form.valuation_method"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-white">
                            <option value="FIFO">FIFO (First In First Out)</option>
                            <option value="LIFO">LIFO</option>
                            <option value="weighted">Weighted Average</option>
                            <option value="standard">Standard Cost</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" x-model="form.is_active" id="is_active"
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active Material</label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 p-6 bg-gray-50/50 rounded-xl border border-gray-100 shadow-sm">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}" 
                   class="px-6 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium text-gray-700">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-8 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all disabled:opacity-50 text-sm font-semibold shadow-md shadow-indigo-200">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span>
                        Update Material
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        Updating...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function materialEditForm() {
    return {
        loading: false,
        loadingMessage: 'Loading material...',
        errors: {},
        uoms: [],
        hsnCodes: [],
        warehouses: [],
        materialId: null,
        
        form: {
            material_code: '', material_name: '', material_type: '',
            uom_id: '', purchase_uom_id: '', hsn_code_id: '',
            default_warehouse_id: '', reorder_level: 0, safety_stock: 0,
            lead_time_days: 0, shelf_life_days: '', qc_required: true,
            inspection_type: 'AQL', is_batch_tracked: false,
            standard_cost: 0, valuation_method: 'FIFO', is_active: true
        },

        async initialize() {
            const urlParts = window.location.pathname.split('/');
            this.materialId = urlParts[urlParts.length - 2];
            
            this.loading = true;
            try {
                // Parallel load dropdowns and material data
                await Promise.all([
                    this.loadDropdowns(),
                    this.loadMaterialData()
                ]);
            } catch (e) {
                console.error('Initialization failed:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadDropdowns() {
            try {
                const [uRes, wRes, hRes] = await Promise.all([
                    fetch('/api/v1/uoms', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/warehouses', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/hsn-codes', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                ]);
                
                if (uRes.ok) {
                    const d = await uRes.json();
                    this.uoms = Array.isArray(d.data) ? d.data : [];
                }
                if (wRes.ok) {
                    const d = await wRes.json();
                    this.warehouses = d.data?.warehouses || [];
                }
                if (hRes.ok) {
                    const d = await hRes.json();
                    this.hsnCodes = d.data?.hsn_codes || [];
                }
            } catch (e) { console.error('Failed to load dropdowns', e); }
        },

        async loadMaterialData() {
            try {
                const res = await fetch(`/api/v1/materials/${this.materialId}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (!res.ok) throw new Error('Material not found');
                
                const json = await res.json();
                const m = json.data?.material;
                
                if (m) {
                    this.form = {
                        material_code: m.material_code || '',
                        material_name: m.material_name || '',
                        material_type: m.material_type || '',
                        uom_id: m.uom_id || '',
                        purchase_uom_id: m.purchase_uom_id || '',
                        hsn_code_id: m.hsn_code_id || '',
                        default_warehouse_id: m.default_warehouse_id || '',
                        reorder_level: m.reorder_level || 0,
                        safety_stock: m.safety_stock || 0,
                        lead_time_days: m.lead_time_days || 0,
                        shelf_life_days: m.shelf_life_days || '',
                        qc_required: m.qc_required === true || m.qc_required == 1,
                        inspection_type: m.inspection_type || 'AQL',
                        is_batch_tracked: m.is_batch_tracked === true || m.is_batch_tracked == 1,
                        standard_cost: m.standard_cost || 0,
                        valuation_method: m.valuation_method || 'FIFO',
                        is_active: m.is_active === true || m.is_active == 1
                    };
                }
            } catch (e) {
                console.error('Load failed', e);
                this.showNotification('Failed to load material information', 'error');
            }
        },

        async submitForm() {
            this.loading = true;
            this.loadingMessage = 'Updating material...';
            this.errors = {};
            
            try {
                const response = await fetch(`/api/v1/materials/${this.materialId}`, {
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
                
                if (!response.ok) {
                    if (data.error?.details) {
                        this.errors = data.error.details;
                        this.showNotification('Validation failed. Please check the form.', 'error');
                    } else {
                        this.showNotification(data.message || 'Failed to update material', 'error');
                    }
                    return;
                }
                
                this.showNotification('Material updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}";
                }, 1500);
            } catch (error) {
                console.error('Update failed', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                this.loading = false;
            }
        },

        showNotification(message, type = 'info') {
            const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500', info: 'bg-blue-500' };
            const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
            const el = document.createElement('div');
            el.className = `fixed top-4 right-4 px-5 py-3 rounded-lg shadow-lg z-50 text-white text-sm font-medium flex items-center gap-2 ${colors[type] || colors.info}`;
            el.style.animation = 'slideIn 0.3s ease-out';
            el.innerHTML = `<span class="material-symbols-outlined text-lg">${icons[type] || 'info'}</span>${message}`;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 300); }, 3500);
        }
    }
}
</script>
<style>
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
@endsection
