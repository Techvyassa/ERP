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

    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Material Master</h2>
                <p class="text-gray-600 mt-1">Manage raw materials, packaging, and consumables</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials/create' : '/org/' . $organization->org_slug . '/materials/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
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
                                <template x-if="item.is_active">
                                    <button @click="deactivateItem(item)" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors ml-2" title="Deactivate">
                                        <i class="fas fa-ban mr-1"></i>
                                        Deactivate
                                    </button>
                                </template>
                                <template x-if="!item.is_active">
                                    <button @click="activateItem(item)" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors ml-2" title="Activate">
                                        <i class="fas fa-check mr-1"></i>
                                        Activate
                                    </button>
                                </template>
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
                params.append('page', this.pagination.current_page);
                
                const response = await fetch(`/api/v1/materials?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load materials');
                }
                
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    this.items = result.data;
                    
                    if (result.pagination) {
                        this.pagination = {
                            current_page: result.pagination.current_page,
                            last_page: result.pagination.last_page,
                            per_page: result.pagination.per_page,
                            total: result.pagination.total,
                            from: (result.pagination.current_page - 1) * result.pagination.per_page + 1,
                            to: Math.min(result.pagination.current_page * result.pagination.per_page, result.pagination.total)
                        };
                    }
                } else {
                    this.items = [];
                }
            } catch (error) {
                console.error('Failed to load materials:', error);
                alert('Failed to load materials. Please try again.');
                this.items = [];
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
        
        edit(item) {
            const baseUrl = '{{ url(request()->get("tenant_type") === "subdomain" ? "/materials" : "/org/" . $organization->org_slug . "/materials") }}';
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
        
        async deactivateItem(item) {
            if (confirm('Are you sure you want to deactivate material: ' + item.material_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/materials/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ...item,
                            is_active: false
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to deactivate material', 'error');
                        return;
                    }
                    
                    this.showNotification('Material deactivated successfully', 'success');
                    this.loadData(); // Refresh list
                } catch (error) {
                    console.error('Failed to deactivate material:', error);
                    this.showNotification('Network error. Please try again.', 'error');
                }
            }
        },

        async activateItem(item) {
            if (confirm('Are you sure you want to activate material: ' + item.material_code + '?')) {
                try {
                    const response = await fetch(`/api/v1/materials/${item.id}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ...item,
                            is_active: true
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.showNotification(data.message || 'Failed to activate material', 'error');
                        return;
                    }
                    
                    this.showNotification('Material activated successfully', 'success');
                    this.loadData(); // Refresh list
                } catch (error) {
                    console.error('Failed to activate material:', error);
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
