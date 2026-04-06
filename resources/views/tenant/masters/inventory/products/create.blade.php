@extends('tenant.layouts.inventory')

@section('title', 'Add Product')
@section('page-title', 'Add New Product')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
<style>
    .input-field { width: 100%; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 8px; font-weight: 500; font-size: 14px; outline: none; transition: 0.2s; background: #f8fafc; }
    .input-field:focus { border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
    .label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
    [x-cloak] { display: none !important; }
    .section-title { font-size: 14px; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px; display: flex; items-center; gap: 8px; }
</style>
@endpush

@section('content')
<div x-data="productFlow()" x-init="initialize()" class="max-w-5xl mx-auto space-y-6">
    <!-- Header & Mode Toggle -->
    <div class="flex items-center justify-between bg-white p-6 rounded-2xl border shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight" x-text="mode === 'single' ? 'New Product Entry' : 'Quick Batch Upload'"></h1>
            <p class="text-slate-500 text-sm font-medium" x-text="mode === 'single' ? 'Create a single product record with full details' : 'Enter multiple products quickly in a spreadsheet-like view'"></p>
        </div>
        <button @click="toggleMode()" class="px-6 py-2.5 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition-all flex items-center gap-2 shadow-lg shadow-slate-100">
            <span class="material-symbols-outlined text-lg" x-text="mode === 'single' ? 'list_alt' : 'person_add'"></span>
            <span x-text="mode === 'single' ? 'Switch to Bulk Mode' : 'Switch to Single Entry'"></span>
        </button>
    </div>

    <!-- Single Form View -->
    <div x-show="mode === 'single'" class="bg-white border rounded-2xl shadow-sm overflow-hidden p-8 space-y-10">
        <!-- Section 1: Basic Info -->
        <div>
            <div class="section-title">
                <span class="material-symbols-outlined text-blue-600">info</span>
                1. Basic Product Details
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="label">Product Name *</label>
                    <input type="text" x-model="single.product_name" required class="input-field" placeholder="e.g. Black Pepper Powder 500g">
                </div>
                <div>
                    <label class="label">Category</label>
                    <select x-model="single.product_category" class="input-field">
                        <option value="">-- Choose Category --</option>
                        <option value="AGRICULTURE">Agri-Products</option>
                        <option value="SPICES">Spices & Powders</option>
                        <option value="PULSES">Pulses & Grains</option>
                        <option value="GRAINS">Grains</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex items-center gap-6 p-4 bg-blue-50/50 rounded-xl border border-blue-100">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" x-model="single.auto_generate_code" id="autoCode" class="w-5 h-5 rounded-md border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="autoCode" class="text-sm font-bold text-slate-700">Auto-generate Product Code</label>
                    </div>
                    <template x-if="!single.auto_generate_code">
                        <div class="flex-1 flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-400 uppercase">Custom Code:</span>
                            <input type="text" x-model="single.product_code" placeholder="e.g. FG-SPICE-001" class="flex-1 input-field !bg-white">
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Section 2: Weight & Taxation -->
        <div>
            <div class="section-title">
                <span class="material-symbols-outlined text-green-600">scale</span>
                2. Weight & Tax Configuration
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="label">Weight / Pack Size</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="single.pack_size" step="0.001" class="w-1/2 input-field" placeholder="0.00">
                        <select x-model="single.pack_uom_id" class="w-1/2 input-field">
                            <template x-for="uom in uoms" :key="uom.id">
                                <option :value="uom.id" x-text="uom.uom_name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="label">HSN Code & Tax Category *</label>
                    <select x-model="single.hsn_code_id" required class="input-field">
                        <option value="">-- Search & Select HSN Code --</option>
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
                3. Pricing (₹)
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-200">
                <div>
                    <label class="label">Manufacturing / Standard Cost (₹)</label>
                    <input type="number" x-model="single.standard_cost" step="0.01" class="input-field !bg-white" placeholder="0.00">
                    <p class="text-[10px] text-slate-400 mt-2 font-medium">Internal cost for valuation</p>
                </div>
                <div>
                    <label class="label text-blue-700">Retail Selling Price (MRP ₹) *</label>
                    <input type="number" x-model="single.mrp" step="0.01" class="input-field !bg-white border-blue-200 text-blue-700 font-bold text-lg" placeholder="0.00">
                    <p class="text-[10px] text-blue-500 mt-2 font-bold">Standard price for sales</p>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-4 pt-6">
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">Discard Draft</a>
            <button @click="submitSingle" :disabled="loading" class="px-12 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-xl shadow-blue-200 disabled:opacity-50 flex items-center gap-2">
                <span x-show="loading" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                <span x-text="loading ? 'Processing...' : 'Save Product Now'"></span>
            </button>
        </div>
    </div>

    <!-- Bulk Form View -->
    <div x-show="mode === 'bulk'" x-cloak class="space-y-6">
        <div class="bg-slate-900 rounded-2xl p-8 text-white flex items-center justify-between shadow-xl">
            <div class="flex items-center gap-6">
                <div class="bg-blue-500/20 p-4 rounded-2xl border border-blue-500/30">
                    <span class="material-symbols-outlined text-blue-400 text-3xl">grid_view</span>
                </div>
                <div>
                    <h3 class="text-xl font-black tracking-tight">Mass Product Entry</h3>
                    <p class="text-slate-400 text-xs font-medium mt-1">Ideal for entering many items from a physical list</p>
                </div>
            </div>
            <button @click="addBulkRow()" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                Add Empty Row
            </button>
        </div>

        <div class="bg-white border rounded-2xl overflow-hidden shadow-md">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b">
                    <tr class="text-[11px] font-black uppercase text-slate-500 tracking-wider">
                        <th class="px-4 py-4 w-1/3">Product Name</th>
                        <th class="px-4 py-4">Category</th>
                        <th class="px-4 py-4">Weight / Unit</th>
                        <th class="px-4 py-4">Selling Price (₹)</th>
                        <th class="px-4 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(item, i) in bulk" :key="i">
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-3 py-4">
                                <input type="text" x-model="item.product_name" placeholder="Enter Product Name..." class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                            </td>
                            <td class="px-3 py-4">
                                <select x-model="item.product_category" class="w-full px-2 py-2.5 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700">
                                    <option value="AGRICULTURE">Agriculture</option>
                                    <option value="SPICES">Spices</option>
                                    <option value="PULSES">Pulses</option>
                                    <option value="GRAINS">Grains</option>
                                </select>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex gap-1">
                                    <input type="number" x-model="item.pack_size" class="w-20 px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-xs font-black" placeholder="Size">
                                    <select x-model="item.pack_uom_id" class="w-20 px-2 py-2.5 bg-slate-100 border-none rounded-lg text-[10px] font-bold">
                                        <template x-for="uom in uoms" :key="uom.id">
                                            <option :value="uom.id" x-text="uom.uom_name"></option>
                                        </template>
                                    </select>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-400">₹</span>
                                    <input type="number" x-model="item.mrp" placeholder="MRP" class="w-24 px-3 py-2.5 bg-blue-50/50 border border-blue-100 rounded-lg text-xs font-black text-blue-700">
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <button @click="removeBulkRow(i)" class="p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <div class="flex items-center justify-between bg-white p-6 rounded-2xl border shadow-sm">
            <button @click="addBulkRow()" class="px-6 py-3 text-xs font-black text-blue-600 border border-dashed border-blue-200 bg-blue-50/20 rounded-xl hover:bg-blue-50 transition-all">
                + Append More Rows
            </button>
            <div class="flex items-center gap-4">
                <span class="text-xs font-bold text-slate-400" x-text="validBulkCount + ' items ready to save'"></span>
                <button @click="submitBulk" :disabled="loading || !validBulkCount" class="px-10 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xl shadow-blue-100 disabled:opacity-50 transition-all">
                    <span x-text="loading ? 'Creating Records...' : 'Save All Products'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function productFlow() {
    return {
        mode: 'single', loading: false, uoms: [], hsnCodes: [],
        single: { product_name: '', product_category: '', pack_size: 1, pack_uom_id: '', hsn_code_id: '', standard_cost: 0, mrp: 0, auto_generate_code: true, product_code: '' },
        bulk: [],
        get validBulkCount() { return this.bulk.filter(i => i.product_name && i.product_name.trim()).length; },

        async initialize() {
            try {
                const [uRes, hRes] = await Promise.all([
                    fetch('/api/v1/uoms', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/v1/hsn-codes', { headers: { 'Accept': 'application/json' } })
                ]);
                if (uRes.ok) this.uoms = (await uRes.json()).data || [];
                if (hRes.ok) this.hsnCodes = (await hRes.json()).data?.hsn_codes || [];
                if (this.uoms.length) this.single.pack_uom_id = this.uoms[0].id;
                if (this.hsnCodes.length) this.single.hsn_code_id = this.hsnCodes[0].id;
            } catch (e) { console.error('Init failed', e); }
            // Prefill with 5 rows for bulk entry
            for(let i=0; i<5; i++) this.addBulkRow();
        },

        toggleMode() { this.mode = this.mode === 'single' ? 'bulk' : 'single'; },
        addBulkRow() { 
            this.bulk.push({ 
                product_name: '', 
                product_category: 'SPICES', 
                pack_size: 1, 
                pack_uom_id: this.single.pack_uom_id, 
                hsn_code_id: this.single.hsn_code_id, 
                standard_cost: 0, 
                mrp: 0 
            }); 
        },
        removeBulkRow(i) { if(this.bulk.length > 1) this.bulk.splice(i, 1); },

        async submitSingle() {
            if (!this.single.product_name || !this.single.mrp) {
                alert('Please fill at least Product Name and Selling Price.');
                return;
            }
            this.loading = true;
            try {
                const res = await fetch('/api/v1/products', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, 
                    body: JSON.stringify(this.single) 
                });
                if (res.ok) { window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}"; }
            } finally { this.loading = false; }
        },

        async submitBulk() {
            this.loading = true;
            const products = this.bulk.filter(i => i.product_name && i.product_name.trim());
            try {
                const res = await fetch('/api/v1/products/bulk', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, 
                    body: JSON.stringify({ products }) 
                });
                if (res.ok) { window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}"; }
            } finally { this.loading = false; }
        }
    };
}
</script>
@endsection
