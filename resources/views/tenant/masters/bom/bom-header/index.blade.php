@extends('tenant.layouts.bom')

@section('title', 'Bill of Materials')
@section('page-title', 'Bill of Materials')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="bomData()" x-init="loadData()">

    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-3 rounded-xl shadow-md">
                    <span class="material-symbols-outlined text-white text-2xl">account_tree</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Bill of Materials</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage product BOMs, versions, and component recipes</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="downloadTemplate()" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all shadow-sm font-medium">
                    <span class="material-symbols-outlined text-lg">download</span>
                    Download CSV Template
                </button>
                <button @click="openImportModal()" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all shadow-sm font-medium">
                    <span class="material-symbols-outlined text-lg">upload</span>
                    Import CSV
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header/multiple-create' : '/org/' . $organization->org_slug . '/bom-header/multiple-create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-orange-200 text-orange-700 rounded-lg hover:bg-orange-50 transition-all shadow-sm font-medium">
                    <span class="material-symbols-outlined text-lg">library_add</span>
                    Multiple Create
                </a>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header/create' : '/org/' . $organization->org_slug . '/bom-header/create') }}" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all shadow-md hover:shadow-lg font-medium">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Create BOM
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="bg-blue-50 p-2.5 rounded-lg">
                <span class="material-symbols-outlined text-blue-600">inventory_2</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900" x-text="items.length">0</p>
                <p class="text-xs text-gray-500 font-medium">Total BOMs</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="bg-green-50 p-2.5 rounded-lg">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600" x-text="items.filter(i => i.bom_status === 'ACTIVE').length">0</p>
                <p class="text-xs text-gray-500 font-medium">Active</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="bg-yellow-50 p-2.5 rounded-lg">
                <span class="material-symbols-outlined text-yellow-600">edit_note</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-yellow-600" x-text="items.filter(i => i.bom_status === 'DRAFT').length">0</p>
                <p class="text-xs text-gray-500 font-medium">Draft</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="bg-red-50 p-2.5 rounded-lg">
                <span class="material-symbols-outlined text-red-600">block</span>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-500" x-text="items.filter(i => i.bom_status === 'OBSOLETE').length">0</p>
                <p class="text-xs text-gray-500 font-medium">Obsolete</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadData" placeholder="Search BOM code..." 
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors text-sm">
            </div>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">inventory</span>
                <input type="text" x-model="filters.product" @input.debounce.300ms="loadData" placeholder="Filter by product..." 
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors text-sm">
            </div>
            <select x-model="filters.bom_status" @change="loadData" 
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors text-sm">
                <option value="">All Status</option>
                <option value="DRAFT">Draft</option>
                <option value="ACTIVE">Active</option>
                <option value="OBSOLETE">Obsolete</option>
            </select>
            <button @click="resetFilters" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium text-gray-600">
                <span class="material-symbols-outlined text-lg">refresh</span>
                Reset Filters
            </button>
        </div>
    </div>

    <!-- BOM Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">BOM Code</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ver.</th>

                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Effective From</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <!-- Loading -->
                    <template x-if="loading">
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-orange-500 mb-3"></div>
                                    <p class="text-sm text-gray-500">Loading BOMs...</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <template x-if="!loading && filteredItems.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <span class="material-symbols-outlined text-4xl text-gray-400">account_tree</span>
                                    </div>
                                    <p class="text-gray-600 font-medium mb-1">No BOMs found</p>
                                    <p class="text-sm text-gray-400">Create your first Bill of Materials to get started.</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Rows -->
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr class="hover:bg-orange-50/40 transition-colors">
                            <!-- BOM Code -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900" x-text="item.bom_code"></span>
                            </td>
                            <!-- Product -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="bg-orange-50 p-1.5 rounded">
                                        <span class="material-symbols-outlined text-orange-500 text-sm">category</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="item.product_name"></p>
                                        <p class="text-xs text-gray-400" x-text="item.product_code"></p>
                                    </div>
                                </div>
                            </td>
                            <!-- Version -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded bg-blue-50 text-blue-700" x-text="'v' + item.version"></span>
                            </td>

                            <!-- Items Count -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full"
                                      :class="item.items_count > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
                                      x-text="item.items_count + ' items'"></span>
                            </td>
                            <!-- Effective From -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600" x-text="item.effective_from_formatted"></span>
                            </td>
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': item.bom_status === 'DRAFT',
                                          'bg-green-100 text-green-800': item.bom_status === 'ACTIVE',
                                          'bg-red-100 text-red-800': item.bom_status === 'OBSOLETE'
                                      }"
                                      x-text="item.bom_status"></span>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="viewDetails(item)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 rounded-lg transition-all" 
                                            title="View Details">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        View
                                    </button>
                                    <button @click="edit(item)" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 hover:border-blue-300 rounded-lg transition-all" 
                                            title="Edit">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                        Edit
                                    </button>
                                    <template x-if="item.bom_status === 'ACTIVE'">
                                        <button @click="deactivateItem(item)" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-white border border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 rounded-lg transition-all" 
                                                title="Make Obsolete">
                                            <span class="material-symbols-outlined text-sm">block</span>
                                        </button>
                                    </template>
                                    <template x-if="item.bom_status === 'OBSOLETE' || item.bom_status === 'DRAFT'">
                                        <button @click="activateItem(item)" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-white border border-green-200 text-green-600 hover:bg-green-50 hover:border-green-300 rounded-lg transition-all" 
                                                title="Make Active">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
            <p class="text-xs text-gray-500">
                Showing <span class="font-semibold text-gray-700" x-text="filteredItems.length"></span> of
                <span class="font-semibold text-gray-700" x-text="items.length"></span> BOMs
            </p>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div x-show="showImportModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @click.self="closeImportModal()">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4" @click.stop>
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-purple-600">upload_file</span>
                        Import BOM Headers from CSV
                    </h3>
                    <button @click="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <template x-if="!uploadComplete">
                    <div>
                        <!-- File Upload Area -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center"
                                 :class="{'border-purple-500 bg-purple-50': dragOver}"
                                 @dragover.prevent="dragOver = true"
                                 @dragleave.prevent="dragOver = false"
                                 @drop.prevent="handleFileDrop($event)">
                                <input type="file" 
                                       id="csvFileInput" 
                                       accept=".csv" 
                                       @change="handleFileSelect($event)" 
                                       class="hidden">
                                
                                <template x-if="!selectedFile">
                                    <div>
                                        <span class="material-symbols-outlined text-5xl text-gray-400 mb-3">cloud_upload</span>
                                        <p class="text-gray-600 mb-2">Drag and drop your CSV file here, or</p>
                                        <button @click="document.getElementById('csvFileInput').click()" 
                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                            Browse Files
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="selectedFile">
                                    <div class="flex items-center justify-center gap-3">
                                        <span class="material-symbols-outlined text-3xl text-green-600">description</span>
                                        <div class="text-left">
                                            <p class="text-sm font-medium text-gray-900" x-text="selectedFile.name"></p>
                                            <p class="text-xs text-gray-500" x-text="(selectedFile.size / 1024).toFixed(2) + ' KB'"></p>
                                        </div>
                                        <button @click="clearFile()" class="text-red-600 hover:text-red-800">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">info</span>
                                Import Instructions
                            </h4>
                            <ul class="text-xs text-blue-800 space-y-1 ml-6 list-disc">
                                <li>Download the CSV template first to see the required format</li>
                                <li><strong>bom_code column must be BLANK</strong> - it will be auto-generated</li>
                                <li>Fill in all required fields: product_code, version, batch_size, output_uom_code, effective_from</li>
                                <li><strong>batch_size:</strong> Quantity produced per batch (default: 1)</li>
                                <li>Use valid product codes (e.g., FG-0001, SPCE-0011) from your Product Master</li>
                                <li><strong>UOM codes:</strong> Use full names like "Kilogram", "Piece", "Liter" or codes like "KG", "PC", "LTR" that exist in your UOM Master</li>
                                <li>Material codes must exist in your Material Master (e.g., RM-0001, PKG-0001)</li>
                                <li>Date format: YYYY-MM-DD (e.g., 2024-04-15)</li>
                                <li>Multiple rows with same product_code will create BOM with multiple materials</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3">
                            <button @click="closeImportModal()" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button @click="uploadCSV()" 
                                    :disabled="!selectedFile || uploading"
                                    :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-purple-700'"
                                    class="px-6 py-2 bg-purple-600 text-white rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined" x-show="!uploading">upload</span>
                                <span class="material-symbols-outlined animate-spin" x-show="uploading">progress_activity</span>
                                <span x-text="uploading ? 'Uploading...' : 'Upload & Import'"></span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Upload Complete -->
                <template x-if="uploadComplete">
                    <div>
                        <div class="text-center py-6">
                            <span class="material-symbols-outlined text-6xl mb-4"
                                  :class="uploadErrors.length === 0 ? 'text-green-500' : 'text-yellow-500'"
                                  x-text="uploadErrors.length === 0 ? 'check_circle' : 'warning'"></span>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2" x-text="uploadMessage"></h4>
                            
                            <template x-if="uploadErrors.length > 0">
                                <div class="mt-4 max-h-60 overflow-y-auto">
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-left">
                                        <h5 class="text-sm font-semibold text-red-900 mb-2">Errors:</h5>
                                        <ul class="text-xs text-red-800 space-y-1">
                                            <template x-for="error in uploadErrors" :key="error">
                                                <li x-text="error"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <div class="flex justify-end">
                            <button @click="closeImportModal()" 
                                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function bomData() {
    // Format ISO date to readable string
    function formatDate(val) {
        if (!val || val === '-') return '-';
        try {
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            return d.toLocaleDateString('en-IN', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        } catch (e) {
            return val;
        }
    }



    return {
        items: [],
        loading: false,
        filters: { search: '', product: '', bom_status: '' },
        
        // Import modal state
        showImportModal: false,
        selectedFile: null,
        uploading: false,
        uploadComplete: false,
        uploadMessage: '',
        uploadErrors: [],
        dragOver: false,

        get filteredItems() {
            let result = this.items;
            if (this.filters.bom_status) {
                result = result.filter(item => item.bom_status === this.filters.bom_status);
            }
            return result;
        },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await fetch(`/api/v1/bom-headers?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Failed to load BOMs');
                }

                let boms = Array.isArray(data.data) ? data.data : (data.data && data.data.boms) ? data.data.boms : [];
                
                // Client-side filtering for product
                if (this.filters.product) {
                    const productLower = this.filters.product.toLowerCase();
                    boms = boms.filter(b => 
                        (b.product && b.product.product_name && b.product.product_name.toLowerCase().includes(productLower)) ||
                        (b.product && b.product.product_code && b.product.product_code.toLowerCase().includes(productLower))
                    );
                }

                // Transform data for display
                this.items = boms.map(b => ({
                    id: b.id,
                    bom_code: b.bom_code,
                    product_id: b.product_id,
                    product_name: b.product ? b.product.product_name : 'N/A',
                    product_code: b.product ? b.product.product_code : '',
                    version: b.version || 1,

                    output_uom_id: b.output_uom_id,
                    output_uom_name: b.output_uom ? b.output_uom.uom_name : '',
                    items_count: b.bom_details ? b.bom_details.length : 0,
                    effective_from: b.effective_from || '-',
                    effective_from_formatted: formatDate(b.effective_from),
                    bom_status: b.bom_status || 'DRAFT'
                }));
            } catch (error) {
                console.error('Failed to load BOMs:', error);
                alert(error.message || 'Failed to load BOMs. Please try again.');
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { search: '', product: '', bom_status: '' };
            this.loadData();
        },
        
        viewDetails(item) {
            const baseUrl = '{{ url(request()->get("tenant_type") === "subdomain" ? "/bom-header" : "/org/" . $organization->org_slug . "/bom-header") }}';
            window.location.href = `${baseUrl}/${item.id}/view`;
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get("tenant_type") === "subdomain" ? "/bom-header" : "/org/" . $organization->org_slug . "/bom-header") }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        async deactivateItem(item) {
            if (!confirm('Are you sure you want to make BOM "' + item.bom_code + '" obsolete?')) return;
            try {
                const response = await fetch(`/api/v1/bom-headers/${item.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ bom_status: 'OBSOLETE' })
                });
                
                const data = await response.json();
                if (!response.ok) {
                    this.showNotification(data.message || 'Failed to deactivate BOM', 'error');
                    return;
                }
                this.showNotification('BOM made obsolete successfully', 'success');
                this.loadData();
            } catch (error) {
                console.error('Failed to deactivate BOM:', error);
                this.showNotification('Network error. Please try again.', 'error');
            }
        },

        async activateItem(item) {
            const action = item.bom_status === 'DRAFT' ? 'activate' : 'reactivate';
            if (!confirm(`Are you sure you want to ${action} BOM "${item.bom_code}"?`)) return;
            try {
                const response = await fetch(`/api/v1/bom-headers/${item.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ bom_status: 'ACTIVE' })
                });
                
                const data = await response.json();
                if (!response.ok) {
                    this.showNotification(data.message || 'Failed to activate BOM', 'error');
                    return;
                }
                this.showNotification('BOM activated successfully', 'success');
                this.loadData();
            } catch (error) {
                console.error('Failed to activate BOM:', error);
                this.showNotification('Network error. Please try again.', 'error');
            }
        },

        downloadTemplate() {
            window.location.href = '/api/v1/bom-headers/import/template';
        },

        openImportModal() {
            this.showImportModal = true;
            this.selectedFile = null;
            this.uploading = false;
            this.uploadComplete = false;
            this.uploadMessage = '';
            this.uploadErrors = [];
        },

        closeImportModal() {
            this.showImportModal = false;
            this.selectedFile = null;
            this.uploading = false;
            this.uploadComplete = false;
            this.uploadMessage = '';
            this.uploadErrors = [];
            if (this.uploadComplete) {
                this.loadData();
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.name.endsWith('.csv')) {
                this.selectedFile = file;
            } else {
                alert('Please select a valid CSV file');
            }
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file && file.name.endsWith('.csv')) {
                this.selectedFile = file;
            } else {
                alert('Please drop a valid CSV file');
            }
        },

        clearFile() {
            this.selectedFile = null;
            document.getElementById('csvFileInput').value = '';
        },

        async uploadCSV() {
            if (!this.selectedFile) {
                alert('Please select a file first');
                return;
            }

            this.uploading = true;
            this.uploadComplete = false;

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);

                const response = await fetch('/api/v1/bom-headers/import', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();
                
                console.log('Import response:', data); // Debug log
                
                this.uploading = false;
                this.uploadComplete = true;

                if (data.success) {
                    this.uploadMessage = data.message;
                    this.uploadErrors = data.data?.errors || [];
                    
                    // Reload data after successful import
                    setTimeout(() => {
                        this.loadData();
                    }, 2000);
                } else {
                    this.uploadMessage = 'Import failed';
                    // Show detailed errors
                    if (data.error && data.error.details) {
                        if (Array.isArray(data.error.details)) {
                            this.uploadErrors = data.error.details.map(err => {
                                if (typeof err === 'object') {
                                    return `Row ${err.row || '?'}: ${err.error || JSON.stringify(err)}`;
                                }
                                return String(err);
                            });
                        } else {
                            this.uploadErrors = [JSON.stringify(data.error.details)];
                        }
                    } else {
                        this.uploadErrors = [data.message || 'Unknown error'];
                    }
                }
            } catch (error) {
                console.error('Upload error:', error);
                this.uploading = false;
                this.uploadComplete = true;
                this.uploadMessage = 'Import failed';
                this.uploadErrors = ['Network error occurred: ' + error.message];
            }
        },
        
        showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            const icons = {
                success: 'check_circle',
                error: 'error',
                info: 'info'
            };
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-5 py-3 rounded-lg shadow-lg z-50 text-white text-sm font-medium flex items-center gap-2 ${colors[type] || colors.info}`;
            notification.style.animation = 'slideInRight 0.3s ease-out';
            notification.innerHTML = `<span class="material-symbols-outlined text-lg">${icons[type] || icons.info}</span>${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }
}
</script>

<style>
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
@endsection
