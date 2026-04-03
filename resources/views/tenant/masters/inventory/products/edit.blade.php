@extends('tenant.layouts.inventory')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
<style>
    .input-field { width: 100%; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 8px; font-weight: 500; font-size: 14px; outline: none; transition: 0.2s; background: #f8fafc; }
    .input-field:focus { border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
    .label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
    [x-cloak] { display: none !important; }
    .section-title { font-size: 14px; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px; display: flex; items-center; gap: 8px; }
    .immutable-field { background: #f1f5f9; border-color: #e2e8f0; color: #64748b; cursor: not-allowed; font-family: monospace; }
</style>
@endpush

@section('content')
<div x-data="productForm()" x-init="loadProduct()" class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between bg-white p-6 rounded-2xl border shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Update Product</h1>
            <p class="text-slate-500 text-sm font-medium">Modify existing master record details</p>
        </div>
        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" 
           class="px-5 py-2.5 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-200 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to List
        </a>
    </div>

    <!-- Main Edit Form -->
    <div class="bg-white border rounded-2xl shadow-sm overflow-hidden p-8 space-y-10 animate-in" x-show="!loading" x-cloak>
        <!-- Section 1: Basic Info -->
        <div>
            <div class="section-title">
                <span class="material-symbols-outlined text-blue-600">info</span>
                1. Basic Product Details
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="label">Product Name *</label>
                    <input type="text" x-model="form.product_name" required class="input-field" placeholder="Full Product Name">
                </div>
                <div>
                    <label class="label">Category</label>
                    <input type="text" x-model="form.product_category" class="input-field" placeholder="e.g. Spices, Grains">
                </div>
                <div class="md:col-span-2">
                    <label class="label">System Identification Code</label>
                    <input type="text" x-model="form.product_code" disabled class="input-field immutable-field" title="Product code cannot be changed after creation">
                    <p class="text-[10px] text-slate-400 mt-2 font-medium">This code is unique and cannot be updated once saved.</p>
                </div>
            </div>
        </div>

        <!-- Section 2: Weight & Package -->
        <div>
            <div class="section-title">
                <span class="material-symbols-outlined text-green-600">scale</span>
                2. Weight & Packaging
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="label">Weight / Pack Size</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="form.pack_size" step="0.001" class="w-1/2 input-field" placeholder="0.00">
                        <select x-model="form.pack_uom_id" class="w-1/2 input-field">
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="label">HSN Code *</label>
                    <select x-model="form.hsn_code_id" required class="input-field">
                        <template x-for="hsn in hsnCodes" :key="hsn.id">
                            <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Pricing -->
        <div>
            <div class="section-title">
                <span class="material-symbols-outlined text-orange-600">payments</span>
                3. Update Prices (₹)
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-200">
                <div>
                    <label class="label">Internal Cost (₹)</label>
                    <input type="number" x-model="form.standard_cost" step="0.0001" class="input-field !bg-white">
                </div>
                <div>
                    <label class="label text-blue-700">Retail MRP (₹) *</label>
                    <input type="number" x-model="form.mrp" step="0.01" class="input-field !bg-white border-blue-200 text-blue-700 font-bold text-lg">
                </div>
            </div>
        </div>

        <!-- Section 4: Status -->
        <div class="bg-blue-50/50 p-6 rounded-2xl flex items-center justify-between border border-blue-100">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center p-2 bg-white rounded-lg shadow-sm">
                    <input type="checkbox" x-model="form.is_active" class="w-6 h-6 rounded-md text-blue-600 focus:ring-blue-500">
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-700 block">Product is Active</span>
                    <span class="text-[11px] text-slate-500 font-medium">When disabled, this product will not be visible in sales or production orders.</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6">
            <button @click="submitUpdate" :disabled="saving" class="px-14 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-xl shadow-blue-100 disabled:opacity-50 flex items-center gap-2">
                <span x-show="saving" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                <span x-text="saving ? 'Saving...' : 'Update Record'"></span>
            </button>
        </div>
    </div>

    <!-- Spinner -->
    <div x-show="loading" class="bg-white border rounded-2xl p-20 flex flex-col items-center justify-center gap-6 shadow-sm">
        <div class="relative w-16 h-16">
            <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Synchronizing Master Data...</p>
    </div>
</div>

<script>
function productForm() {
    return {
        loading: true, saving: false, productId: null, uoms: [], hsnCodes: [],
        form: { product_code: '', product_name: '', product_category: '', pack_size: 0, pack_uom_id: '', hsn_code_id: '', standard_cost: 0, mrp: 0, is_active: true },

        async loadProduct() {
            const parts = window.location.pathname.split('/');
            this.productId = parts[parts.length - 2];
            try {
                const [uRes, hRes, pRes] = await Promise.all([
                    fetch('/api/v1/uoms', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/hsn-codes', { headers: { 'Accept': 'application/json' } }),
                    fetch(`/api/v1/products/${this.productId}`, { headers: { 'Accept': 'application/json' } })
                ]);
                this.uoms = (await uRes.json()).data || [];
                this.hsnCodes = (await hRes.json()).data?.hsn_codes || [];
                const pData = (await pRes.json()).data?.product;
                if(pData) {
                    this.form = { ...this.form, ...pData };
                }
            } finally { this.loading = false; }
        },

        async submitUpdate() {
            if (!this.form.product_name || !this.form.mrp) {
                alert('Product Name and MRP are required.');
                return;
            }
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/products/${this.productId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(this.form)
                });
                if(res.ok) { window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}"; }
            } finally { this.saving = false; }
        }
    };
}
</script>
@endsection
