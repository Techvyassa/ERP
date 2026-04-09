@extends('tenant.layouts.inventory')

@section('title', 'Warehouses')
@section('page-title', 'Warehouse Master')

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
 .warehouse-name {
     font-size: 16px;
     font-weight: bold;
     margin-bottom: 10px;
 }
 .warehouse-code {
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
<div x-data="warehouseData()" x-init="loadData()">
    <!-- Import Modal -->
    <div x-show="importModal.show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @click.self="closeImportModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Import Warehouses</h3>
                    <button @click="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">Instructions:</h4>
                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                            <li>Download the sample CSV template</li>
                            <li>Fill in your warehouse data</li>
                            <li>Upload the completed CSV file</li>
                            <li>Review any errors and fix them</li>
                        </ul>
                    </div>

                    <div>
                        <button @click="downloadTemplate" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-download mr-2"></i>Download CSV Template
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
                        <input type="file" 
                               accept=".csv"
                               @change="handleFileSelect"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div x-show="importModal.uploading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                        <p class="text-gray-600 mt-2">Processing import...</p>
                    </div>

                    <div x-show="importModal.results.length > 0" class="mt-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Import Results:</h4>
                        <div class="bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto">
                            <template x-for="(result, index) in importModal.results" :key="index">
                                <div class="text-sm py-1" :class="result.success ? 'text-green-600' : 'text-red-600'">
                                    <i :class="result.success ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                                    <span x-text="result.message"></span>
                                </div>
                            </template>
                        </div>
                        <button @click="downloadFailedRows" 
                                x-show="importModal.failedRows.length > 0"
                                class="mt-3 w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                            <i class="fas fa-download mr-2"></i>Download Failed Rows
                        </button>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="closeImportModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                </div>
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
                    <h3 class="text-lg font-semibold text-gray-900">Warehouse Barcode</h3>
                    <button @click="closeBarcodeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="barcode-content" class="barcode-container">
                    <template x-if="barcodeModal.warehouse">
                        <div>
                            <div class="warehouse-name" x-text="barcodeModal.warehouse.warehouse_name"></div>
                            <div x-show="barcodeModal.loading" class="text-sm text-gray-500">Generating barcode...</div>
                            <div x-show="!barcodeModal.loading && barcodeModal.error" class="text-sm text-red-600" x-text="barcodeModal.error"></div>
                            <div class="mt-3" x-show="!barcodeModal.loading && !barcodeModal.error" x-html="barcodeModal.barcodeHtml"></div>
                            <div class="warehouse-code" x-show="!barcodeModal.loading && !barcodeModal.error" x-text="barcodeModal.warehouse.warehouse_code"></div>
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
                <h2 class="text-2xl font-bold text-gray-900">Warehouse Master</h2>
                <p class="text-gray-600 mt-1">Manage warehouse locations and storage facilities</p>
            </div>
            <div class="flex gap-3">
                <button @click="exportData" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
                <button @click="importData" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-upload mr-2"></i>Import
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses/create' : '/org/' . $organization->org_slug . '/warehouses/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Warehouse
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
            <select x-model="filters.warehouse_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="RM">Raw Material</option>
                <option value="FG">Finished Goods</option>
                <option value="PKG">Packaging</option>
                <option value="REJECTION">Rejection</option>
                <option value="WIP">Work in Progress</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Incharge</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading warehouses...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-warehouse text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No warehouses found. Click "Add Warehouse" to create one.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="item.warehouse_code"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900" x-text="item.warehouse_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-blue-100 text-blue-800': item.warehouse_type === 'RM',
                                          'bg-green-100 text-green-800': item.warehouse_type === 'FG',
                                          'bg-yellow-100 text-yellow-800': item.warehouse_type === 'PKG',
                                          'bg-red-100 text-red-800': item.warehouse_type === 'REJECTION',
                                          'bg-purple-100 text-purple-800': item.warehouse_type === 'WIP'
                                      }"
                                      x-text="item.warehouse_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <template x-if="item.incharge_user">
                                    <span x-text="item.incharge_user.name"></span>
                                </template>
                                <template x-if="item.incharge_name">
                                    <span x-text="item.incharge_name"></span>
                                </template>
                                <template x-if="!item.incharge_user && !item.incharge_name">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
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
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> warehouses
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
function warehouseData() {
    return {
        items: [],
        loading: false,
        filters: { search: '', warehouse_type: '', is_active: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        barcodeModal: {
            show: false,
            warehouse: null,
            barcodeHtml: '',
            loading: false,
            error: ''
        },
        importModal: {
            show: false,
            uploading: false,
            results: [],
            failedRows: []
        },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.warehouse_type) params.append('warehouse_type', this.filters.warehouse_type);
                if (this.filters.is_active) params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/warehouses?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to load warehouses');
                
                const data = await response.json();
                // API returns data directly as array, not nested
                this.items = Array.isArray(data.data) ? data.data : (data.data?.warehouses || []);
                
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
                console.error('Failed to load warehouses:', error);
                this.showNotification('Failed to load warehouses', 'error');
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
            this.filters = { search: '', warehouse_type: '', is_active: '' };
            this.loadData();
        },
        
        openCreateModal() {
            alert('Create warehouse form - Coming soon');
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },

        async showBarcodeModal(item) {
            this.barcodeModal.warehouse = item;
            this.barcodeModal.barcodeHtml = '';
            this.barcodeModal.error = '';
            this.barcodeModal.loading = true;
            this.barcodeModal.show = true;

            try {
                const response = await fetch(`/api/v1/warehouses/barcode?code=${encodeURIComponent(item.warehouse_code)}`, {
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
            this.barcodeModal.warehouse = null;
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
                        <title>Warehouse Barcode</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .barcode-container { text-align: center; padding: 20px; border: 1px solid #ccc; display: inline-block; }
                            div.b128 { border-left: 1px solid black; height: 30px; margin-left: 1px; width: 2px; display: inline-block; }
                            .warehouse-name { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                            .warehouse-code { font-size: 14px; margin-top: 8px; color: #666; }
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
            if (confirm('Are you sure you want to deactivate warehouse: ' + item.warehouse_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/warehouses/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            warehouse_code: item.warehouse_code,
                            warehouse_name: item.warehouse_name,
                            warehouse_type: item.warehouse_type,
                            address: item.address,
                            city: item.city,
                            state: item.state,
                            pincode: item.pincode,
                            incharge_name: item.incharge_name,
                            contact_number: item.contact_number,
                            email: item.email,
                            is_active: false 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to deactivate warehouse', 'error');
                        return;
                    }
                    
                    this.showNotification('Warehouse deactivated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to deactivate warehouse:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },
        
        async activateItem(item) {
            if (confirm('Are you sure you want to activate warehouse: ' + item.warehouse_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/warehouses/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            warehouse_code: item.warehouse_code,
                            warehouse_name: item.warehouse_name,
                            warehouse_type: item.warehouse_type,
                            address: item.address,
                            city: item.city,
                            state: item.state,
                            pincode: item.pincode,
                            incharge_name: item.incharge_name,
                            contact_number: item.contact_number,
                            email: item.email,
                            is_active: true 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to activate warehouse', 'error');
                        return;
                    }
                    
                    this.showNotification('Warehouse activated successfully', 'success');
                    this.loadData(); // Refresh the list
                } catch (error) {
                    console.error('Failed to activate warehouse:', error);
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

        // Export functionality
        exportData() {
            const headers = ['warehouse_code', 'warehouse_name', 'warehouse_type', 'address', 'city', 'state', 'pincode', 'incharge_name', 'contact_number', 'email', 'is_active'];
            const csvRows = [headers.join(',')];
            
            this.items.forEach(item => {
                const row = [
                    item.warehouse_code || '',
                    item.warehouse_name || '',
                    item.warehouse_type || '',
                    item.address || '',
                    item.city || '',
                    item.state || '',
                    item.pincode || '',
                    item.incharge_name || '',
                    item.contact_number || '',
                    item.email || '',
                    item.is_active ? 'true' : 'false'
                ];
                csvRows.push(row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(','));
            });
            
            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `warehouses_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            this.showNotification('Warehouses exported successfully', 'success');
        },

        // Import functionality
        importData() {
            this.importModal.show = true;
            this.importModal.results = [];
            this.importModal.failedRows = [];
        },

        closeImportModal() {
            this.importModal.show = false;
            this.importModal.uploading = false;
            this.importModal.results = [];
            this.importModal.failedRows = [];
        },

        downloadTemplate() {
            const csvContent = [
                'warehouse_code,warehouse_name,warehouse_type,address,city,state,pincode,incharge_name,contact_number,email,is_active',
                ',Sample Warehouse,RM,123 Main St,Mumbai,Maharashtra,400001,John Doe,9876543210,john@example.com,true',
                ',FG Warehouse,FG,456 Park Ave,Delhi,Delhi,110001,Jane Smith,9876543211,jane@example.com,true'
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'warehouse_import_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.importModal.uploading = true;
            this.importModal.results = [];
            this.importModal.failedRows = [];

            try {
                const text = await file.text();
                const lines = text.split('\n').filter(line => line.trim());
                const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g, ''));
                
                const dataRows = lines.slice(1);
                let successCount = 0;
                let failCount = 0;
                let skippedCount = 0;

                // First, fetch existing warehouses to check for duplicates
                const existingResponse = await fetch('/api/v1/warehouses', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const existingData = await existingResponse.json();
                const existingWarehouses = Array.isArray(existingData.data) ? existingData.data : (existingData.data?.warehouses || []);
                const existingNames = existingWarehouses.map(w => w.warehouse_name.toLowerCase().trim());

                // Track names in current import batch
                const importedNames = new Set();

                for (let i = 0; i < dataRows.length; i++) {
                    const values = this.parseCSVLine(dataRows[i]);
                    const rowData = {};
                    
                    headers.forEach((header, index) => {
                        rowData[header] = values[index] || '';
                    });

                    const originalRow = { ...rowData };
                    const warehouseName = rowData.warehouse_name.trim();
                    const warehouseNameLower = warehouseName.toLowerCase();

                    // Check for duplicate in existing warehouses
                    if (existingNames.includes(warehouseNameLower)) {
                        skippedCount++;
                        this.importModal.results.push({
                            success: false,
                            message: `Row ${i + 1}: Skipped - Warehouse "${warehouseName}" already exists`
                        });
                        continue;
                    }

                    // Check for duplicate in current import batch
                    if (importedNames.has(warehouseNameLower)) {
                        skippedCount++;
                        this.importModal.results.push({
                            success: false,
                            message: `Row ${i + 1}: Skipped - Duplicate warehouse name "${warehouseName}" in import file`
                        });
                        continue;
                    }

                    try {
                        const payload = {
                            warehouse_name: warehouseName,
                            warehouse_type: rowData.warehouse_type,
                            address: rowData.address,
                            city: rowData.city,
                            state: rowData.state,
                            pincode: rowData.pincode,
                            incharge_name: rowData.incharge_name,
                            contact_number: rowData.contact_number,
                            email: rowData.email,
                            is_active: rowData.is_active === 'true' || rowData.is_active === '1'
                        };

                        const response = await fetch('/api/v1/warehouses', {
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
                            successCount++;
                            importedNames.add(warehouseNameLower);
                            this.importModal.results.push({
                                success: true,
                                message: `Row ${i + 1}: ${warehouseName} imported successfully`
                            });
                        } else {
                            failCount++;
                            const errorMsg = result.message || 'Unknown error';
                            this.importModal.results.push({
                                success: false,
                                message: `Row ${i + 1}: ${errorMsg}`
                            });
                            this.importModal.failedRows.push({
                                ...originalRow,
                                error: errorMsg
                            });
                        }
                    } catch (error) {
                        failCount++;
                        this.importModal.results.push({
                            success: false,
                            message: `Row ${i + 1}: ${error.message}`
                        });
                        this.importModal.failedRows.push({
                            ...originalRow,
                            error: error.message
                        });
                    }
                }

                const summaryMsg = `Import completed: ${successCount} success, ${failCount} failed, ${skippedCount} skipped (duplicates)`;
                this.showNotification(summaryMsg, successCount > 0 ? 'success' : 'error');
                
                if (successCount > 0) {
                    this.loadData();
                }
            } catch (error) {
                this.showNotification('Failed to process CSV file', 'error');
                console.error('Import error:', error);
            } finally {
                this.importModal.uploading = false;
                event.target.value = '';
            }
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

        downloadFailedRows() {
            const headers = ['warehouse_code', 'warehouse_name', 'warehouse_type', 'address', 'city', 'state', 'pincode', 'incharge_name', 'contact_number', 'email', 'is_active', 'error'];
            const csvRows = [headers.join(',')];
            
            this.importModal.failedRows.forEach(row => {
                const escapedRow = [
                    row.warehouse_code || '',
                    row.warehouse_name || '',
                    row.warehouse_type || '',
                    row.address || '',
                    row.city || '',
                    row.state || '',
                    row.pincode || '',
                    row.incharge_name || '',
                    row.contact_number || '',
                    row.email || '',
                    row.is_active || '',
                    row.error || ''
                ];
                csvRows.push(escapedRow.map(field => `"${String(field).replace(/"/g, '""')}"`).join(','));
            });
            
            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `warehouses_failed_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    }
}
</script>
@endsection
