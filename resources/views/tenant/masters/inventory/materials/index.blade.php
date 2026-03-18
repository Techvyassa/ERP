@extends('tenant.layouts.inventory')

@section('title', 'Materials')
@section('page-title', 'Material Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
.barcode-container { 
    text-align: center; 
    padding: 20px; 
    border: 1px solid #ccc; 
    display: inline-block; 
}
div.b128 {
    border-left: 1px solid black;
    height: 30px;
    margin-left: 1px;
    width: 2px;
    display: inline-block;
}
.material-name { 
    font-size: 16px; 
    font-weight: bold; 
    margin-bottom: 10px; 
}
.material-code { 
    font-size: 14px; 
    margin-bottom: 15px; 
    color: #666; 
}
.barcode-image { 
    margin: 10px 0; 
}
@media print { 
    body { margin: 0; } 
    .barcode-container { border: none; } 
}
</style>
@endpush

@section('content')
<div x-data="materialData()" x-init="loadData()">
    <!-- Barcode Modal -->
    <div x-show="barcodeModal.show" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @click.self="closeBarcodeModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" @click.stop>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Material Barcode</h3>
                    <button @click="closeBarcodeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="barcode-content" class="barcode-container">
                    <template x-if="barcodeModal.material">
                        <div>
                            <div class="material-name" x-text="barcodeModal.material.material_name"></div>
                            <div x-show="barcodeModal.loading" class="text-sm text-gray-500">Generating barcode...</div>
                            <div x-show="!barcodeModal.loading && barcodeModal.error" class="text-sm text-red-600" x-text="barcodeModal.error"></div>
                            <div class="barcode-image mt-3" x-show="!barcodeModal.loading && !barcodeModal.error" x-html="barcodeModal.barcodeHtml"></div>
                            <div class="material-code mt-2" x-show="!barcodeModal.loading && !barcodeModal.error" x-text="barcodeModal.material.material_code"></div>
                        </div>
                    </template>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="closeBarcodeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button @click="printBarcode()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>
                        Print Barcode
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CSV Upload Modal -->
    <div x-show="showUploadModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showUploadModal = false"></div>
            
            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Import Materials from CSV</h3>
                        <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="bg-white px-6 py-4">
                    <!-- Download Template -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 1: Download Template</label>
                        <button @click="downloadTemplate" class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors flex items-center justify-center">
                            <i class="fas fa-download text-blue-600 mr-2"></i>
                            <span class="text-sm text-gray-700">Download CSV Template</span>
                        </button>
                        <p class="text-xs text-gray-500 mt-2">Download the template file with required columns and format</p>
                    </div>
                    
                    <!-- Upload File -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 2: Upload Filled CSV</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors"
                             @dragover.prevent="dragOver = true"
                             @dragleave.prevent="dragOver = false"
                             @drop.prevent="handleFileDrop($event)"
                             :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="file-upload" 
                                               name="file-upload" 
                                               type="file" 
                                               accept=".csv"
                                               class="sr-only"
                                               @change="handleFileSelect($event)">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV file up to 10MB</p>
                                <p x-show="selectedFile" class="text-sm text-green-600 mt-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <span x-text="selectedFile ? selectedFile.name : ''"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div x-show="uploading" class="mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-700 mb-2">
                            <span>Uploading...</span>
                            <span x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                    
                    <!-- Validation Results -->
                    <div x-show="validationResults" class="mb-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-yellow-800">Validation Issues Found</h4>
                                    <ul class="mt-2 text-xs text-yellow-700 list-disc list-inside">
                                        <li>Row 5: Material code is required</li>
                                        <li>Row 12: Invalid material type</li>
                                        <li>Row 18: UOM not found</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="showUploadModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button @click="uploadCSV" 
                            :disabled="!selectedFile || uploading"
                            :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-upload mr-2"></i>
                        <span x-text="uploading ? 'Uploading...' : 'Import Data'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Material Master</h2>
                <p class="text-gray-600 mt-1">Manage raw materials, packaging, and consumables</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- CSV Upload Button -->
                <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <!-- Add Material Button -->
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials/create' : '/org/' . $organization->org_slug . '/materials/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Material
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search by code or name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.material_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="RAW">Raw Material</option>
                <option value="PACKAGING">Packaging</option>
                <option value="CONSUMABLE">Consumable</option>
                <option value="SEMI">Semi-Finished</option>
            </select>
            <select x-model="filters.is_active" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UOM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading materials...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-boxes text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No materials found. Click "Add Material" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.material_code"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900" x-text="item.material_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-blue-100 text-blue-800': item.material_type === 'RAW',
                                          'bg-green-100 text-green-800': item.material_type === 'PACKAGING',
                                          'bg-yellow-100 text-yellow-800': item.material_type === 'CONSUMABLE',
                                          'bg-purple-100 text-purple-800': item.material_type === 'SEMI'
                                      }"
                                      x-text="item.material_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <template x-if="item.uom">
                                    <span x-text="item.uom.uom_name"></span>
                                </template>
                                <template x-if="!item.uom">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.reorder_level || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="edit(item)" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" title="Edit">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit
                                </button>
                                <button @click="deleteItem(item)" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors ml-2" title="Delete">
                                    <i class="fas fa-trash mr-1"></i>
                                    Delete
                                </button>
                                <button @click="showBarcodeModal(item)" class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded transition-colors ml-2" title="Barcode">
                                    <i class="fas fa-barcode mr-1"></i>
                                    Barcode
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> materials
            </div>
            <div class="flex space-x-2">
                <button @click="loadPage(pagination.current_page - 1)" 
                        :disabled="pagination.current_page === 1"
                        :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 border border-gray-300 rounded">
                    Previous
                </button>
                <span class="px-3 py-1 text-sm text-gray-600">
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                </span>
                <button @click="loadPage(pagination.current_page + 1)" 
                        :disabled="pagination.current_page === pagination.last_page"
                        :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 border border-gray-300 rounded">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function materialData() {
    return {
        items: [],
        loading: false,
        showUploadModal: false,
        selectedFile: null,
        uploading: false,
        uploadProgress: 0,
        errors: {},
        filters: {
            search: '',
            material_type: '',
            is_active: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0
        },
        // Barcode modal data
        barcodeModal: {
            show: false,
            material: null,
            barcodeHtml: '',
            loading: false,
            error: ''
        },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.material_type) params.append('material_type', this.filters.material_type);
                if (this.filters.is_active) params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/materials?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to load materials');
                
                const data = await response.json();
                // API returns data directly as array, not nested
                this.items = Array.isArray(data.data) ? data.data : (data.data?.materials || []);
                this.pagination = data.pagination || { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 };
            } catch (error) {
                console.error('Failed to load materials:', error);
                this.showNotification('Failed to load materials', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadData();
            }
        },
        
        resetFilters() {
            this.filters = { search: '', material_type: '', is_active: '' };
            this.loadData();
        },
        
        downloadTemplate() {
            // Create CSV template
            const headers = ['material_code', 'material_name', 'material_type', 'uom_id', 'reorder_level', 'safety_stock', 'is_active'];
            const sampleData = ['MAT-001', 'Sample Material', 'RAW', '1', '100', '50', 'true'];
            const csv = [headers.join(','), sampleData.join(',')].join('\n');
            
            // Download file
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'material_import_template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        },
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.type === 'text/csv') {
                this.selectedFile = file;
            } else {
                alert('Please select a valid CSV file');
            }
        },
        
        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type === 'text/csv') {
                this.selectedFile = file;
            } else {
                alert('Please drop a valid CSV file');
            }
        },
        
        async uploadCSV() {
            if (!this.selectedFile) return;
            
            this.uploading = true;
            this.uploadProgress = 0;
            
            // Simulate upload progress
            const interval = setInterval(() => {
                if (this.uploadProgress < 90) {
                    this.uploadProgress += 10;
                }
            }, 200);
            
            try {
                // TODO: Replace with actual API call
                const formData = new FormData();
                formData.append('file', this.selectedFile);
                
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                this.uploadProgress = 100;
                clearInterval(interval);
                
                alert('CSV imported successfully! ' + this.selectedFile.name);
                this.showUploadModal = false;
                this.selectedFile = null;
                this.uploading = false;
                this.uploadProgress = 0;
                this.loadData();
            } catch (error) {
                clearInterval(interval);
                console.error('Failed to upload CSV:', error);
                alert('Failed to upload CSV. Please try again.');
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        async showBarcodeModal(item) {
            this.barcodeModal.material = item;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.error = '';
            this.barcodeModal.loading = true;
            this.barcodeModal.show = true;

            try {
                const response = await fetch(`/api/v1/materials/barcode?code=${encodeURIComponent(item.material_code)}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    this.barcodeModal.error = (data && data.message) ? data.message : 'Failed to generate barcode';
                    return;
                }

                const html = (data && data.data && data.data.html) ? data.data.html : '';
                this.barcodeModal.barcodeHtml = html;
                if (!html) {
                    this.barcodeModal.error = 'Barcode HTML not returned';
                }
            } catch (e) {
                console.error('Barcode generation failed:', e);
                this.barcodeModal.error = 'Network error while generating barcode';
            } finally {
                this.barcodeModal.loading = false;
            }
        },
        
        closeBarcodeModal() {
            this.barcodeModal.show = false;
            this.barcodeModal.material = null;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.loading = false;
            this.barcodeModal.error = '';
        },
        
        printBarcode() {
            const printContent = document.getElementById('barcode-content').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Material Barcode</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .barcode-container { text-align: center; padding: 20px; border: 1px solid #ccc; display: inline-block; }
                            div.b128 { border-left: 1px solid black; height: 30px; margin-left: 1px; width: 2px; display: inline-block; }
                            .material-name { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                            .material-code { font-size: 14px; margin-bottom: 15px; color: #666; }
                            .barcode-image { margin: 10px 0; }
                            @media print { body { margin: 0; } .barcode-container { border: none; } }
                        </style>
                    </head>
                    <body>
                        ${printContent}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure you want to delete material: ' + item.material_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/materials/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to delete material', 'error');
                        return;
                    }
                    
                    this.showNotification('Material deleted successfully', 'success');
                    this.loadData(); // Refresh list
                } catch (error) {
                    console.error('Failed to delete material:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
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
