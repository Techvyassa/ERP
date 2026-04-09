@extends('tenant.layouts.inventory')

@section('title', 'Bin Locations')
@section('page-title', 'Bin Location Master')

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
 .bin-name {
     font-size: 16px;
     font-weight: bold;
     margin-bottom: 10px;
 }
 .bin-code {
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
<div x-data="binData()" x-init="loadData()">
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
                    <h3 class="text-lg font-semibold text-gray-900">Bin Location Barcode</h3>
                    <button @click="closeBarcodeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="barcode-content" class="barcode-container">
                    <template x-if="barcodeModal.bin">
                        <div>
                            <div class="bin-name" x-text="barcodeModal.bin.bin_code"></div>
                            <div x-show="barcodeModal.loading" class="text-sm text-gray-500">Generating barcode...</div>
                            <div x-show="!barcodeModal.loading && barcodeModal.error" class="text-sm text-red-600" x-text="barcodeModal.error"></div>
                            <div class="mt-3" x-show="!barcodeModal.loading && !barcodeModal.error" x-html="barcodeModal.barcodeHtml"></div>
                            <div class="bin-code" x-show="!barcodeModal.loading && !barcodeModal.error" x-text="barcodeModal.bin.bin_code"></div>
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

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Bin Locations</h2>
                <p class="text-gray-600 mt-1">Manage warehouse bin locations and storage positions</p>
            </div>
            <div class="flex gap-3">
                <button @click="downloadCSVTemplate()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download CSV Template
                </button>
                <button @click="$refs.csvFileInput.click()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-file-import mr-2"></i>Import CSV
                </button>
                <input type="file" x-ref="csvFileInput" @change="handleCSVUpload($event)" accept=".csv" class="hidden">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations/create' : '/org/' . $organization->org_slug . '/bin-locations/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Bin Location
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData" placeholder="Search by bin code..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" x-model="filters.warehouse_id" @input="loadData" placeholder="Filter by warehouse ID..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.is_active" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bin Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aisle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rack</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shelf</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Weight (kg)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading bin locations...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-th text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No bin locations found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.bin_code"></span></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <template x-if="item.warehouse">
                                    <span x-text="item.warehouse.warehouse_name"></span>
                                </template>
                                <template x-if="!item.warehouse">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.aisle || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.rack || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.shelf || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.max_weight_kg || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
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
    </div>
</div>

<script>
function binData() {
    return {
        items: [],
        loading: false,
        filters: { search: '', warehouse_id: '', is_active: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        barcodeModal: {
            show: false,
            bin: null,
            barcodeHtml: '',
            loading: false,
            error: ''
        },
        errorModal: {
            show: false,
            imported: 0,
            errors: [],
            rawErrors: [],
            failedRows: []
        },
        uploadedCSVData: [],
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.warehouse_id) params.append('warehouse_id', this.filters.warehouse_id);
                if (this.filters.is_active) params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/bin-locations?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to load bin locations');
                
                const data = await response.json();
                // API returns data directly as array, not nested
                this.items = Array.isArray(data.data) ? data.data : (data.data?.bin_locations || []);
                
                // Update pagination (simplified since API might not return pagination)
                this.pagination = {
                    current_page: 1,
                    last_page: 1,
                    per_page: this.items.length,
                    total: this.items.length,
                    from: this.items.length > 0 ? 1 : 0,
                    to: this.items.length
                };
            } catch (error) {
                console.error('Failed to load bin locations:', error);
                this.showNotification('Failed to load bin locations', 'error');
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
            this.filters = { search: '', warehouse_id: '', is_active: '' };
            this.loadData();
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },

        async showBarcodeModal(item) {
            this.barcodeModal.bin = item;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.error = '';
            this.barcodeModal.loading = true;
            this.barcodeModal.show = true;

            try {
                const response = await fetch(`/api/v1/bin-locations/barcode?code=${encodeURIComponent(item.bin_code)}`, {
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
            this.barcodeModal.bin = null;
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
            const headers = ['bin_code', 'warehouse', 'aisle', 'rack', 'shelf', 'max_weight_kg', 'is_active', 'error'];
            const csvRows = [headers.join(',')];
            
            this.errorModal.failedRows.forEach(row => {
                const escapedRow = [
                    row.bin_code || '',
                    row.warehouse || '',
                    row.aisle || '',
                    row.rack || '',
                    row.shelf || '',
                    row.max_weight_kg || '',
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
            link.setAttribute('download', `bin_locations_import_errors_${timestamp}.csv`);
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
                        <title>Bin Location Barcode</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .barcode-container { text-align: center; padding: 20px; border: 1px solid #ccc; display: inline-block; }
                            div.b128 { border-left: 1px solid black; height: 30px; margin-left: 1px; width: 2px; display: inline-block; }
                            .bin-name { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                            .bin-code { font-size: 14px; margin-top: 8px; color: #666; }
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
            if (confirm('Are you sure you want to deactivate bin location: ' + item.bin_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/bin-locations/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            bin_code: item.bin_code,
                            warehouse_id: item.warehouse_id,
                            aisle: item.aisle,
                            rack: item.rack,
                            shelf: item.shelf,
                            max_weight_kg: item.max_weight_kg,
                            is_active: false 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to deactivate bin location', 'error');
                        return;
                    }
                    
                    this.showNotification('Bin location deactivated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to deactivate bin location:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },
        
        async activateItem(item) {
            if (confirm('Are you sure you want to activate bin location: ' + item.bin_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/bin-locations/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            bin_code: item.bin_code,
                            warehouse_id: item.warehouse_id,
                            aisle: item.aisle,
                            rack: item.rack,
                            shelf: item.shelf,
                            max_weight_kg: item.max_weight_kg,
                            is_active: true 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to activate bin location', 'error');
                        return;
                    }
                    
                    this.showNotification('Bin location activated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to activate bin location:', error);
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
                'bin_code,warehouse,aisle,rack,shelf,max_weight_kg,is_active',
                ',RM,A,R1,S1,500,true',
                ',RM,A,R1,S2,500,true',
                ',FG,B,R2,S1,1000,true'
            ].join('\n');

            // Create blob and download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'bin_locations_import_template.csv');
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
                
                console.log('Cleaned CSV Content:', cleanedCSVContent.substring(0, 200)); // Debug log
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
                
                const response = await fetch('/api/v1/bin-locations/import-csv', {
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
                    this.showNotification(`Successfully imported ${imported} bin location(s)`, 'success');
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
                            bin_code: originalRow.bin_code || '',
                            warehouse: originalRow.warehouse || '',
                            aisle: originalRow.aisle || '',
                            rack: originalRow.rack || '',
                            shelf: originalRow.shelf || '',
                            max_weight_kg: originalRow.max_weight_kg || '',
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
