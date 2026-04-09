@extends('tenant.layouts.inventory')

@section('title', 'Product Master')
@section('page-title', 'Product Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
<style>
    .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .status-active { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
    .status-inactive { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="productDashboard()" x-init="loadData()" class="space-y-6 max-w-[1600px] mx-auto">
    <!-- Import Errors Modal -->
    <div x-show="errorModal.show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @click.self="closeErrorModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[80vh] flex flex-col" @click.stop>
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">CSV Import Results</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="text-green-600 font-medium" x-text="errorModal.imported"></span> imported successfully, 
                            <span class="text-red-600 font-medium" x-text="errorModal.errors.length"></span> errors
                        </p>
                    </div>
                    <button @click="closeErrorModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div class="space-y-2">
                    <template x-for="(error, index) in errorModal.errors" :key="index">
                        <div class="flex items-start p-3 bg-red-50 border border-red-200 rounded-lg">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                            <span class="text-sm text-red-800" x-text="error"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button @click="closeErrorModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Close
                </button>
                <button @click="downloadErrorCSV()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Download Errors CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Product Master</h1>
            <p class="text-gray-500 text-sm">Manage finished goods and pricing</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="downloadCSVTemplate()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Download CSV Template
            </button>
            <button @click="$refs.csvFileInput.click()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-file-import mr-2"></i>Import CSV
            </button>
            <input type="file" x-ref="csvFileInput" @change="handleCSVUpload($event)" accept=".csv" class="hidden">
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/products/create' : '/org/' . $organization->org_slug . '/products/create') }}" 
               class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg flex items-center gap-2 transition-all">
                <span class="material-symbols-outlined text-lg">add</span>
                New Product
            </a>
        </div>
    </div>

    <!-- Metrics Bar -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card p-5">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Items</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1" x-text="pagination.total">0</h3>
        </div>
        <div class="card p-5 bg-blue-50/30">
            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Active Only</p>
            <h3 class="text-2xl font-bold text-blue-700 mt-1" x-text="activeCount">0</h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
        <div class="flex flex-col md:flex-row gap-4 items-center">
            <div class="flex-1 relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400">search</span>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadData()"
                       placeholder="Search products..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            <div class="w-full md:w-48">
                <select x-model="filters.product_category" @change="loadData()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all bg-white text-sm">
                    <option value="">All Categories</option>
                    <template x-for="cat in categories" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </select>
            </div>
            <div class="w-full md:w-48">
                <select x-model="filters.is_active" @change="loadData()"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all bg-white text-sm">
                    <option value="">Status: All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button @click="resetFilters" class="px-4 py-2 text-sm font-bold text-blue-600 hover:bg-blue-50 rounded-lg">Reset</button>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Category & HSN</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Weight / Unit</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pricing</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">Loading products...</td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No products found.</td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-blue-600 font-mono block" x-text="item.product_code"></span>
                                <span class="text-sm font-bold text-gray-900" x-text="item.product_name"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-gray-500 block" x-text="item.product_category || 'Uncategorized'"></span>
                                <span class="text-[10px] text-gray-400" x-text="item.hsn_code ? 'HSN: ' + item.hsn_code.hsn_code : 'No HSN'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-700" x-text="item.pack_size"></span>
                                <span class="text-xs text-gray-400" x-text="item.pack_uom ? item.pack_uom.uom_name : 'Units'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs">
                                    <div class="flex justify-between w-24"><span class="text-gray-400">Cost:</span> <span class="font-bold" x-text="'₹' + item.standard_cost"></span></div>
                                    <div class="flex justify-between w-24 text-green-600 font-bold"><span class="text-green-500 font-bold">MRP:</span> <span x-text="'₹' + item.mrp"></span></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span x-text="item.is_active ? 'Active' : 'Inactive'" 
                                      class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="item.is_active ? 'status-active' : 'status-inactive'"></span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="edit(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button @click="showBarcode(item)" class="p-2 text-gray-500 hover:bg-gray-50 rounded-lg transition-all" title="Barcode">
                                    <span class="material-symbols-outlined text-lg">barcode_scanner</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
    <!-- Barcode Modal -->
    <div x-show="barcodeModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         @click.away="barcodeModal = false">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-6 text-center space-y-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest">Product Label</h3>
                    <button @click="barcodeModal = false" class="text-slate-300 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="bg-slate-50 p-8 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center">
                    <p class="text-xs font-black text-blue-600 mb-1" x-text="activeProduct?.product_code"></p>
                    <p class="text-sm font-bold text-slate-800 mb-6" x-text="activeProduct?.product_name"></p>
                    <svg id="barcodeCanvas" class="max-w-full"></svg>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button @click="window.print()" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined text-lg">print</span> Print
                    </button>
                    <button @click="downloadBarcode()" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined text-lg">download</span> Save JPG
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
function productDashboard() {
    return {
        items: [], categories: [], loading: false, activeCount: 0,
        barcodeModal: false, activeProduct: null,
        filters: { search: '', product_category: '', is_active: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        errorModal: {
            show: false,
            imported: 0,
            errors: [],
            failedRows: []
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({...this.filters, page: this.pagination.current_page});
                const response = await fetch(`/api/v1/products?${params}`, { headers: { 'Accept': 'application/json' } });
                const result = await response.json();
                if (result.success && result.data) {
                    this.items = result.data.products || [];
                    this.pagination = result.data.pagination || this.pagination;
                    this.activeCount = this.items.filter(i => i.is_active).length;
                    if(!this.categories.length) this.categories = [...new Set(this.items.map(i => i.product_category).filter(Boolean))];
                }
            } catch (error) { console.error('Load failed', error); } finally { this.loading = false; }
        },
        
        loadPage(p) { if(p >= 1 && p <= this.pagination.last_page) { this.pagination.current_page = p; this.loadData(); } },
        resetFilters() { this.filters = { search: '', product_category: '', is_active: '' }; this.pagination.current_page = 1; this.loadData(); },
        edit(item) { window.location.href = `{{ url(request()->get('tenant_type') === 'subdomain' ? '/products' : '/org/' . $organization->org_slug . '/products') }}/${item.id}/edit`; },
        
        showBarcode(item) {
            this.activeProduct = item;
            this.barcodeModal = true;
            this.$nextTick(() => {
                JsBarcode("#barcodeCanvas", item.product_code, {
                    format: "CODE128",
                    lineColor: "#0f172a",
                    width: 2,
                    height: 80,
                    displayValue: true,
                    fontSize: 14,
                    fontOptions: "bold"
                });
            });
        },

        downloadBarcode() {
            const svg = document.getElementById("barcodeCanvas");
            const svgData = new XMLSerializer().serializeToString(svg);
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            const img = new Image();
            img.onload = () => {
                canvas.width = img.width + 40;
                canvas.height = img.height + 40;
                ctx.fillStyle = "white";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 20, 20);
                const url = canvas.toDataURL("image/jpeg");
                const a = document.createElement("a");
                a.href = url;
                a.download = `barcode-${this.activeProduct.product_code}.jpg`;
                a.click();
            };
            img.src = "data:image/svg+xml;base64," + btoa(svgData);
        },

        downloadCSVTemplate() {
            const csvContent = [
                'product_code,product_name,product_category,hsn_code,pack_size,pack_uom,standard_cost,mrp,is_active',
                ',Red chilli Blend,SPICES,980003,100,Gram,80,100,true',
                ',Turmeric Powder,SPICES,980001,100,Gram,60,90,true',
                ',Cinnamon Powder,SPICES,980005,100,Gram,150,200,true',
                ',Clove,SPICES,9800722,1000,Gram,3000,4000,true',
                ',Wheat Flour,GRAINS,1101,1,Kilogram,40,60,true',
                ',Basmati Rice,GRAINS,1006,5,Kilogram,250,350,true'
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const timestamp = new Date().getTime();
            a.download = `product_import_template_${timestamp}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            this.showNotification('Template downloaded - Use HSN codes that exist in your HSN Master', 'success');
        },

        async handleCSVUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.loading = true;
            const errors = [];
            const failedRows = [];
            let imported = 0;

            try {
                const text = await file.text();
                const lines = text.split('\n').filter(line => line.trim());
                const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g, ''));
                const dataRows = lines.slice(1);

                // Fetch existing products
                const existingResponse = await fetch('/api/v1/products', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const existingData = await existingResponse.json();
                const existingProducts = existingData.data?.products || [];
                const existingNames = existingProducts.map(p => p.product_name.toLowerCase().trim());

                // Fetch UOM and HSN data
                const uomResponse = await fetch('/api/v1/uoms', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const uomData = await uomResponse.json();
                console.log('UOM API Response:', uomData);
                
                const uomList = uomData.data?.uoms || uomData.data || [];
                console.log('UOM List:', uomList);
                
                const uomMap = {};
                uomList.forEach(uom => {
                    if (uom.uom_code) {
                        uomMap[uom.uom_code.toUpperCase()] = uom.id;
                    }
                    if (uom.uom_name) {
                        uomMap[uom.uom_name.toUpperCase()] = uom.id;
                    }
                });
                console.log('UOM Map:', uomMap);

                const hsnResponse = await fetch('/api/v1/hsn-codes', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const hsnData = await hsnResponse.json();
                console.log('HSN API Response:', hsnData);
                
                const hsnList = hsnData.data?.hsn_codes || hsnData.data || [];
                console.log('HSN List:', hsnList);
                
                const hsnMap = {};
                hsnList.forEach(hsn => {
                    if (hsn.hsn_code) {
                        hsnMap[hsn.hsn_code] = hsn.id;
                    }
                });
                console.log('HSN Map:', hsnMap);

                const importedNames = new Set();

                for (let i = 0; i < dataRows.length; i++) {
                    const values = this.parseCSVLine(dataRows[i]);
                    const rowData = {};
                    
                    headers.forEach((header, index) => {
                        rowData[header] = values[index] || '';
                    });

                    const originalRow = { ...rowData };
                    const productName = (rowData.product_name || '').trim();
                    
                    if (!productName) {
                        errors.push(`Row ${i + 2}: Product name is empty`);
                        failedRows.push(originalRow);
                        continue;
                    }
                    
                    const productNameLower = productName.toLowerCase();

                    if (existingNames.includes(productNameLower)) {
                        errors.push(`Row ${i + 2}: Product "${productName}" already exists`);
                        failedRows.push(originalRow);
                        continue;
                    }

                    if (importedNames.has(productNameLower)) {
                        errors.push(`Row ${i + 2}: Duplicate product name "${productName}" in CSV`);
                        failedRows.push(originalRow);
                        continue;
                    }

                    try {
                        const packUomCode = (rowData.pack_uom || '').toUpperCase().trim();
                        if (!packUomCode) {
                            errors.push(`Row ${i + 2}: UOM code is required`);
                            failedRows.push(originalRow);
                            continue;
                        }

                        if (packUomCode.length > 10 || /^\d+/.test(packUomCode)) {
                            errors.push(`Row ${i + 2}: "${packUomCode}" looks like HSN code, not UOM. Use short codes like KG, GM, PC`);
                            failedRows.push(originalRow);
                            continue;
                        }

                        const packUomId = uomMap[packUomCode];
                        if (!packUomId) {
                            const availableUoms = Object.keys(uomMap).slice(0, 10).join(', ');
                            errors.push(`Row ${i + 2}: Invalid UOM "${rowData.pack_uom}". Available: ${availableUoms}...`);
                            failedRows.push(originalRow);
                            continue;
                        }

                        const hsnCode = (rowData.hsn_code || '').trim();
                        if (!hsnCode) {
                            errors.push(`Row ${i + 2}: HSN code is required`);
                            failedRows.push(originalRow);
                            continue;
                        }

                        const hsnCodeId = hsnMap[hsnCode];
                        if (!hsnCodeId) {
                            errors.push(`Row ${i + 2}: Invalid HSN code "${rowData.hsn_code}". Create it first in HSN Master.`);
                            failedRows.push(originalRow);
                            continue;
                        }

                        const payload = {
                            product_name: productName,
                            product_category: rowData.product_category,
                            hsn_code_id: hsnCodeId,
                            pack_size: parseFloat(rowData.pack_size),
                            pack_uom_id: packUomId,
                            standard_cost: rowData.standard_cost ? parseFloat(rowData.standard_cost) : 0,
                            mrp: rowData.mrp ? parseFloat(rowData.mrp) : 0,
                            is_active: rowData.is_active === 'true' || rowData.is_active === '1',
                            auto_generate_code: true
                        };

                        const response = await fetch('/api/v1/products', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (response.ok) {
                            imported++;
                            importedNames.add(productNameLower);
                        } else {
                            let errorMsg = result.message || 'Unknown error';
                            if (result.error && result.error.details) {
                                const errorDetails = result.error.details;
                                const errorMessages = [];
                                for (const field in errorDetails) {
                                    errorMessages.push(`${field}: ${errorDetails[field].join(', ')}`);
                                }
                                if (errorMessages.length > 0) {
                                    errorMsg = errorMessages.join('; ');
                                }
                            }
                            errors.push(`Row ${i + 2}: ${errorMsg}`);
                            failedRows.push(originalRow);
                        }
                    } catch (error) {
                        errors.push(`Row ${i + 2}: ${error.message}`);
                        failedRows.push(originalRow);
                    }
                }

                if (errors.length > 0) {
                    this.errorModal.show = true;
                    this.errorModal.imported = imported;
                    this.errorModal.errors = errors;
                    this.errorModal.failedRows = failedRows;
                } else {
                    this.showNotification(`Successfully imported ${imported} products`, 'success');
                }

                if (imported > 0) {
                    this.loadData();
                }
            } catch (error) {
                this.showNotification('Failed to process CSV file', 'error');
                console.error('Import error:', error);
            } finally {
                this.loading = false;
                event.target.value = '';
            }
        },

        closeErrorModal() {
            this.errorModal.show = false;
            this.errorModal.imported = 0;
            this.errorModal.errors = [];
            this.errorModal.failedRows = [];
        },

        downloadErrorCSV() {
            const headers = ['product_code', 'product_name', 'product_category', 'hsn_code', 'pack_size', 'pack_uom', 'standard_cost', 'mrp', 'is_active'];
            const csvRows = [headers.join(',')];
            
            this.errorModal.failedRows.forEach(row => {
                const escapedRow = [
                    row.product_code || '',
                    row.product_name || '',
                    row.product_category || '',
                    row.hsn_code || '',
                    row.pack_size || '',
                    row.pack_uom || '',
                    row.standard_cost || '',
                    row.mrp || '',
                    row.is_active || ''
                ];
                csvRows.push(escapedRow.map(field => `"${String(field).replace(/"/g, '""')}"`).join(','));
            });
            
            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `products_errors_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        parseCSVLine(line) {
            const result = [];
            let current = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                const nextChar = line[i + 1];
                
                if (char === '"') {
                    if (inQuotes && nextChar === '"') {
                        current += '"';
                        i++;
                    } else {
                        inQuotes = !inQuotes;
                    }
                } else if (char === ',' && !inQuotes) {
                    result.push(current.trim());
                    current = '';
                } else {
                    current += char;
                }
            }
            result.push(current.trim());
            return result;
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
    };
}
</script>
@endsection