@extends('tenant.layouts.bom')

@section('title', 'Multiple BOM Create')
@section('page-title', 'Multiple BOM Create')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="multipleBomCreate()" x-init="loadDropdowns()" class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Create Multiple BOMs</h2>
                <p class="text-sm text-gray-500 mt-1">Build several BOM headers in one submission and send them to the bulk API.</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="addBom()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    Add Another BOM
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <template x-if="message">
        <div class="rounded-xl p-4 border"
            :class="messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
            <p class="font-medium" x-text="message"></p>
        </div>
    </template>

    <template x-for="(bom, bomIndex) in form.boms" :key="bom.uid">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" x-text="'BOM #' + (bomIndex + 1)"></h3>
                    <p class="text-sm text-gray-500">Header and component lines for one BOM.</p>
                </div>
                <button type="button" @click="removeBom(bomIndex)" :disabled="form.boms.length === 1"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-lg">delete</span>
                    Remove
                </button>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">BOM Code</label>
                        <input type="text" x-model="bom.bom_code" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="BOM-FG001-0001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                        <select x-model="bom.product_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Select product</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.product_code + ' - ' + product.product_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Version</label>
                        <input type="number" min="1" x-model="bom.version" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="bom.bom_status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="DRAFT">DRAFT</option>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="OBSOLETE">OBSOLETE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective From</label>
                        <input type="date" x-model="bom.effective_from" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective To</label>
                        <input type="date" x-model="bom.effective_to" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Output UOM</label>
                        <select x-model="bom.output_uom_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Select UOM</option>
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                        <textarea rows="2" x-model="bom.remarks" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Optional remarks"></textarea>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="font-semibold text-gray-900">Component Lines</h4>
                        <button type="button" @click="addItem(bomIndex)"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200">
                            <span class="material-symbols-outlined text-lg">playlist_add</span>
                            Add Item
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white">
                                <tr class="border-b border-gray-100">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Material</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">UOM</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Scrap %</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Substitute</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase">Critical</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, itemIndex) in bom.items" :key="item.uid">
                                    <tr>
                                        <td class="px-4 py-3 min-w-64">
                                            <select x-model="item.material_id" @change="setItemDefaultUom(bomIndex, itemIndex)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                                <option value="">Select material</option>
                                                <template x-for="material in materials" :key="material.id">
                                                    <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 min-w-32">
                                            <input type="number" min="0.0001" step="0.0001" x-model="item.qty_required" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                        </td>
                                        <td class="px-4 py-3 min-w-40">
                                            <select x-model="item.uom_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                                <option value="">Select UOM</option>
                                                <template x-for="uom in uoms" :key="uom.id">
                                                    <option :value="uom.id" x-text="uom.uom_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 min-w-32">
                                            <input type="number" min="0" max="100" step="0.01" x-model="item.scrap_percent" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                        </td>
                                        <td class="px-4 py-3 min-w-56">
                                            <select x-model="item.substitute_material_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                                <option value="">None</option>
                                                <template x-for="material in materials" :key="'sub-' + material.id">
                                                    <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" x-model="item.is_critical" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" @click="removeItem(bomIndex, itemIndex)" :disabled="bom.items.length === 1"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div class="flex justify-end">
        <button type="button" @click="submitAll" :disabled="loading"
            class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
            <span class="material-symbols-outlined text-lg" x-show="!loading">save</span>
            <span class="material-symbols-outlined animate-spin text-lg" x-show="loading">progress_activity</span>
            <span x-text="loading ? 'Creating BOMs...' : 'Create All BOMs'"></span>
        </button>
    </div>

    <template x-if="result">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Bulk Create Result</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-gray-500">Created</p>
                    <p class="text-3xl font-bold text-green-600 mt-1" x-text="result.created_count || 0"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Failed</p>
                    <p class="text-3xl font-bold text-red-500 mt-1" x-text="result.error_count || 0"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Submitted BOMs</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="form.boms.length"></p>
                </div>
            </div>
            <template x-if="result.errors && result.errors.length">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Input Row</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">BOM</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(issue, index) in result.errors" :key="index">
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-700" x-text="issue.row || '-'"></td>
                                    <td class="px-6 py-4 text-sm text-gray-700" x-text="issue.bom_code || '-'"></td>
                                    <td class="px-6 py-4 text-sm text-red-600" x-text="issue.error || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function multipleBomCreate() {
    const newItem = () => ({
        uid: crypto.randomUUID(),
        material_id: '',
        qty_required: 1,
        uom_id: '',
        scrap_percent: 0,
        substitute_material_id: '',
        is_critical: false,
        remarks: ''
    });

    const newBom = () => ({
        uid: crypto.randomUUID(),
        bom_code: '',
        product_id: '',
        version: 1,
        effective_from: '',
        effective_to: '',
        bom_status: 'DRAFT',
        output_uom_id: '',
        remarks: '',
        items: [newItem()]
    });

    return {
        loading: false,
        message: '',
        messageType: 'success',
        result: null,
        products: [],
        uoms: [],
        materials: [],
        form: {
            boms: [newBom()]
        },

        setMessage(message, type = 'success') {
            this.message = message;
            this.messageType = type;
        },

        addBom() {
            this.form.boms.push(newBom());
        },

        removeBom(index) {
            if (this.form.boms.length > 1) {
                this.form.boms.splice(index, 1);
            }
        },

        addItem(bomIndex) {
            this.form.boms[bomIndex].items.push(newItem());
        },

        removeItem(bomIndex, itemIndex) {
            if (this.form.boms[bomIndex].items.length > 1) {
                this.form.boms[bomIndex].items.splice(itemIndex, 1);
            }
        },

        setItemDefaultUom(bomIndex, itemIndex) {
            const item = this.form.boms[bomIndex].items[itemIndex];
            const material = this.materials.find(entry => String(entry.id) === String(item.material_id));
            if (material && material.uom_id) {
                item.uom_id = material.uom_id;
            }
        },

        async loadDropdowns() {
            try {
                const [productResponse, uomResponse, materialResponse] = await Promise.all([
                    fetch('/api/v1/products?per_page=1000', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/uoms?per_page=1000', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/materials?per_page=1000', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                ]);

                const productData = await productResponse.json();
                const uomData = await uomResponse.json();
                const materialData = await materialResponse.json();

                this.products = productData.data?.products || productData.data || [];
                this.uoms = Array.isArray(uomData.data) ? uomData.data : (uomData.data?.uoms || []);
                this.materials = Array.isArray(materialData.data) ? materialData.data : (materialData.data?.materials || []);
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
                this.setMessage('Failed to load products, materials, or UOMs.', 'error');
            }
        },

        buildPayload() {
            return {
                boms: this.form.boms.map((bom) => ({
                    bom_code: bom.bom_code,
                    product_id: parseInt(bom.product_id),
                    version: parseInt(bom.version),
                    effective_from: bom.effective_from,
                    effective_to: bom.effective_to || null,
                    bom_status: bom.bom_status,
                    output_uom_id: parseInt(bom.output_uom_id),
                    remarks: bom.remarks || null,
                    items: bom.items.map((item) => ({
                        material_id: parseInt(item.material_id),
                        qty_required: parseFloat(item.qty_required),
                        uom_id: parseInt(item.uom_id),
                        scrap_percent: item.scrap_percent === '' ? 0 : parseFloat(item.scrap_percent),
                        substitute_material_id: item.substitute_material_id ? parseInt(item.substitute_material_id) : null,
                        is_critical: Boolean(item.is_critical),
                        remarks: item.remarks || null
                    }))
                }))
            };
        },

        async submitAll() {
            this.loading = true;
            this.result = null;
            this.setMessage('', 'success');

            try {
                const response = await fetch('/api/v1/bom-headers/bulk', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.buildPayload())
                });

                const data = await response.json();
                this.result = data.data || null;

                if (!response.ok || data.success !== true) {
                    this.setMessage(data.message || 'Failed to create BOMs.', 'error');
                    return;
                }

                this.setMessage(data.message || 'BOMs created successfully.', 'success');
            } catch (error) {
                console.error('Bulk BOM create failed:', error);
                this.setMessage('Network error while creating multiple BOMs.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
