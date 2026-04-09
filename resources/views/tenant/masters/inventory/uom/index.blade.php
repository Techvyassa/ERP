@extends('tenant.layouts.inventory')

@section('title', 'UOM')
@section('page-title', 'Unit of Measurement Master')

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
 .uom-name {
     font-size: 16px;
     font-weight: bold;
     margin-bottom: 10px;
 }
 .uom-code {
     font-size: 14px;
     margin-top: 8px;
     color: #666;
 }
 @media print {
     body { margin: 0; }
     .barcode-container { border: none; }
 }
</style>
@endpush

@section('content')
<div x-data="uomData()" x-init="loadData()">
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

    <!-- Barcode Modal -->
    <div x-show="barcodeModal.show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @click.self="closeBarcodeModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" @click.stop>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">UOM Barcode</h3>
                    <button @click="closeBarcodeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="barcode-content" class="barcode-container">
                    <template x-if="barcodeModal.uom">
                        <div>
                            <div class="uom-name" x-text="barcodeModal.uom.uom_name"></div>
                            <div x-show="barcodeModal.loading" class="text-sm text-gray-500">Generating barcode...</div>
                            <div x-show="!barcodeModal.loading && barcodeModal.error" class="text-sm text-red-600" x-text="barcodeModal.error"></div>
                            <div class="mt-3" x-show="!barcodeModal.loading && !barcodeModal.error" x-html="barcodeModal.barcodeHtml"></div>
                            <div class="uom-code" x-show="!barcodeModal.loading && !barcodeModal.error" x-text="barcodeModal.uom.uom_code"></div>
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

    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Unit of Measurement (UOM)</h2>
                <p class="text-gray-600 mt-1">Manage measurement units and conversions</p>
            </div>
            <div class="flex gap-3">
                <button @click="downloadCSVTemplate()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download CSV Template
                </button>
                <button @click="$refs.csvFileInput.click()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-file-import mr-2"></i>Import CSV
                </button>
                <input type="file" x-ref="csvFileInput" @change="handleCSVUpload($event)" accept=".csv" class="hidden">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom/create' : '/org/' . $organization->org_slug . '/uom/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add UOM
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
            <select x-model="filters.uom_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="weight">Weight</option>
                <option value="volume">Volume</option>
                <option value="qty">Quantity</option>
                <option value="length">Length</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base UOM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading UOMs...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-balance-scale text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No UOMs found. Click "Add UOM" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.uom_code"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="item.uom_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-blue-100 text-blue-800': item.uom_type === 'weight',
                                          'bg-green-100 text-green-800': item.uom_type === 'volume',
                                          'bg-yellow-100 text-yellow-800': item.uom_type === 'qty',
                                          'bg-purple-100 text-purple-800': item.uom_type === 'length'
                                      }"
                                      x-text="item.uom_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.base_uom?.uom_name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.conversion_factor || '1'"></td>
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
                                <button @click="deactivateItem(item)" 
                                        x-show="item.is_active"
                                        class="inline-flex items-center px-3 py-1.5 bg-orange-50 text-orange-600 hover:bg-orange-100 rounded transition-colors ml-2" 
                                        title="Deactivate">
                                    <i class="fas fa-ban mr-1"></i>
                                    Deactive
                                </button>
                                <button @click="activateItem(item)" 
                                        x-show="!item.is_active"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors ml-2" 
                                        title="Activate">
                                    <i class="fas fa-check mr-1"></i>
                                    Activate
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
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> UOMs
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
function uomData() {
    return {
        items: [],
        loading: false,
        filters: { search: '', uom_type: '', is_active: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        barcodeModal: {
            show: false,
            uom: null,
            barcodeHtml: '',
            loading: false,
            error: ''
        },
        errorModal: {
            show: false,
            imported: 0,
            errors: [],
            rawErrors: [], // Store original error data for CSV export
            failedRows: [] // Store the actual CSV data rows that failed
        },
        uploadedCSVData: [], // Store all uploaded CSV rows
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.uom_type) params.append('uom_type', this.filters.uom_type);
                if (this.filters.is_active) params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/uoms?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to load UOMs');
                
                const data = await response.json();
                // API returns data directly as array, not nested
                this.items = Array.isArray(data.data) ? data.data : (data.data?.uoms || []);
                
                // Update pagination (simplified since API doesn't return pagination)
                this.pagination = {
                    current_page: 1,
                    last_page: 1,
                    per_page: this.items.length,
                    total: this.items.length,
                    from: this.items.length > 0 ? 1 : 0,
                    to: this.items.length
                };
            } catch (error) {
                console.error('Failed to load UOMs:', error);
                this.showNotification('Failed to load UOMs', 'error');
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
            this.filters = { search: '', uom_type: '', is_active: '' };
            this.loadData();
        },
        
        openCreateModal() {
            alert('Create UOM form - Coming soon');
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/uom' : '/org/' . $organization->org_slug . '/uom') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },

        async showBarcodeModal(item) {
            this.barcodeModal.uom = item;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.error = '';
            this.barcodeModal.loading = true;
            this.barcodeModal.show = true;

            try {
                const response = await fetch(`/api/v1/uoms/barcode?code=${encodeURIComponent(item.uom_code)}`, {
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
            this.barcodeModal.uom = null;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.loading = false;
            this.barcodeModal.error = '';
        },

        closeErrorModal() {
            this.errorModal.show = false;
            this.errorModal.imported = 0;
            this.errorModal.errors = [];
            this.errorModal.rawErrors = [];
            this.errorModal.failedRows = [];
        },

        downloadErrorCSV() {
            if (this.errorModal.failedRows.length === 0) {
                this.showNotification('No error rows to download', 'error');
                return;
            }

            // Create CSV content with the original data structure
            const headers = ['uom_code', 'uom_name', 'uom_type', 'base_uom_code', 'conversion_factor', 'is_active', 'error'];
            const csvRows = [headers.join(',')];
            
            this.errorModal.failedRows.forEach(row => {
                const escapedRow = [
                    row.uom_code || '',
                    row.uom_name || '',
                    row.uom_type || '',
                    row.base_uom_code || '',
                    row.conversion_factor || '',
                    row.is_active || '',
                    `"${(row.error || '').replace(/"/g, '""')}"`
                ].join(',');
                csvRows.push(escapedRow);
            });

            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            link.setAttribute('href', url);
            link.setAttribute('download', `uom_import_errors_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showNotification('Error CSV downloaded', 'success');
        },

        printBarcode() {
            const printContent = document.getElementById('barcode-content').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>UOM Barcode</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .barcode-container { text-align: center; padding: 20px; border: 1px solid #ccc; display: inline-block; }
                            div.b128 { border-left: 1px solid black; height: 30px; margin-left: 1px; width: 2px; display: inline-block; }
                            .uom-name { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                            .uom-code { font-size: 14px; margin-top: 8px; color: #666; }
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
        
        async deactivateItem(item) {
            if (confirm('Are you sure you want to deactivate UOM: ' + item.uom_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/uoms/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            uom_code: item.uom_code,
                            uom_name: item.uom_name,
                            uom_type: item.uom_type,
                            base_uom_id: item.base_uom_id,
                            conversion_factor: item.conversion_factor,
                            is_active: false 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to deactivate UOM', 'error');
                        return;
                    }
                    
                    this.showNotification('UOM deactivated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to deactivate UOM:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },
        
        async activateItem(item) {
            if (confirm('Are you sure you want to activate UOM: ' + item.uom_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/uoms/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            uom_code: item.uom_code,
                            uom_name: item.uom_name,
                            uom_type: item.uom_type,
                            base_uom_id: item.base_uom_id,
                            conversion_factor: item.conversion_factor,
                            is_active: true 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to activate UOM', 'error');
                        return;
                    }
                    
                    this.showNotification('UOM activated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to activate UOM:', error);
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
        },

        downloadCSVTemplate() {
            // Create CSV content with headers and sample rows
            const csvContent = [
                'uom_code,uom_name,uom_type,base_uom_code,conversion_factor,is_active',
                ',Kilogram,weight,,1,true',
                ',Gram,weight,KG,0.001,true',
                ',Liter,volume,,1,true',
                ',Milliliter,volume,L,0.001,true',
                ',Piece,qty,,1,true',
                ',Meter,length,,1,true'
            ].join('\n');

            // Create blob and download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'uom_import_template.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showNotification('CSV template downloaded', 'success');
        },

        async handleCSVUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.name.endsWith('.csv')) {
                this.showNotification('Please upload a CSV file', 'error');
                event.target.value = '';
                return;
            }

            // Clear previous CSV data before storing new data
            this.uploadedCSVData = [];

            // Parse CSV to store the data
            let cleanedCSVContent = '';
            try {
                const text = await file.text();
                const lines = text.split('\n').filter(line => line.trim());
                const headers = lines[0].split(',').map(h => h.trim());
                
                // Find the index of 'error' column if it exists
                const errorColumnIndex = headers.findIndex(h => h.toLowerCase() === 'error');
                
                // Create cleaned headers (without error column)
                const cleanedHeaders = headers.filter(h => h.toLowerCase() !== 'error');
                cleanedCSVContent = cleanedHeaders.join(',') + '\n';
                
                for (let i = 1; i < lines.length; i++) {
                    const values = this.parseCSVLine(lines[i]);
                    const row = {};
                    const cleanedValues = [];
                    
                    headers.forEach((header, index) => {
                        // Ignore 'error' column if it exists
                        if (header.toLowerCase() !== 'error') {
                            row[header] = values[index] || '';
                            cleanedValues.push(values[index] || '');
                        }
                    });
                    
                    row.rowNumber = i + 1; // Store original row number
                    this.uploadedCSVData.push(row);
                    
                    // Add cleaned row to CSV content
                    cleanedCSVContent += cleanedValues.join(',') + '\n';
                }
            } catch (error) {
                console.error('Failed to parse CSV:', error);
                this.showNotification('Failed to parse CSV file', 'error');
                event.target.value = '';
                return;
            }

            // Create a new file with cleaned CSV content (without error column)
            const cleanedFile = new File([cleanedCSVContent], file.name, { type: 'text/csv' });
            
            const formData = new FormData();
            formData.append('csv_file', cleanedFile);

            try {
                this.showNotification('Uploading CSV file...', 'info');
                
                const response = await fetch('/api/v1/uoms/import-csv', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const errorMsg = data.message || 'Failed to import CSV';
                    const errors = data.data?.errors || [];
                    
                    if (errors.length > 0) {
                        // Map errors to original CSV rows
                        const failedRows = this.mapErrorsToRows(errors);
                        
                        // Show errors in modal
                        this.errorModal.imported = data.data?.imported || 0;
                        this.errorModal.errors = errors;
                        this.errorModal.rawErrors = errors;
                        this.errorModal.failedRows = failedRows;
                        this.errorModal.show = true;
                    } else {
                        // No specific errors, show generic message
                        const details = data.error?.details;
                        if (details && typeof details === 'object') {
                            const errorDetails = Object.values(details).flat().join(', ');
                            alert(`Import Failed\n\n${errorMsg}\n\nDetails: ${errorDetails}`);
                        } else {
                            alert(`Import Failed\n\n${errorMsg}`);
                        }
                    }
                    return;
                }

                // Show success message with errors if any
                const imported = data.data?.imported || 0;
                const errors = data.data?.errors || [];
                
                if (errors.length > 0) {
                    // Partial success - show modal with errors
                    const failedRows = this.mapErrorsToRows(errors);
                    
                    this.errorModal.imported = imported;
                    this.errorModal.errors = errors;
                    this.errorModal.rawErrors = errors;
                    this.errorModal.failedRows = failedRows;
                    this.errorModal.show = true;
                } else {
                    // Complete success
                    this.showNotification(`Successfully imported ${imported} UOM(s)`, 'success');
                }
                
                this.loadData(); // Refresh the list
            } catch (error) {
                console.error('CSV import failed:', error);
                alert('Network Error\n\nFailed to import CSV file. Please check your connection and try again.');
            } finally {
                event.target.value = ''; // Reset file input
            }
        },

        parseCSVLine(line) {
            const result = [];
            let current = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                
                if (char === '"') {
                    if (inQuotes && line[i + 1] === '"') {
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

        mapErrorsToRows(errors) {
            const failedRows = [];
            
            errors.forEach(error => {
                // Parse row number from error message: "Row 2: error message"
                const match = error.match(/^Row (\d+): (.+)$/);
                if (match) {
                    const rowNum = parseInt(match[1]);
                    const errorMsg = match[2];
                    
                    // Find the corresponding row in uploaded data
                    const originalRow = this.uploadedCSVData.find(row => row.rowNumber === rowNum);
                    
                    if (originalRow) {
                        failedRows.push({
                            uom_code: originalRow.uom_code || '',
                            uom_name: originalRow.uom_name || '',
                            uom_type: originalRow.uom_type || '',
                            base_uom_code: originalRow.base_uom_code || '',
                            conversion_factor: originalRow.conversion_factor || '',
                            is_active: originalRow.is_active || '',
                            error: errorMsg
                        });
                    }
                }
            });
            
            return failedRows;
        }
    }
}
</script>
@endsection
