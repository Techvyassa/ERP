@extends('tenant.layouts.tax')

@section('title', 'Currency')
@section('page-title', 'Currency Master')

@section('content')
<div x-data="currencyData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Currency Master</h2>
                <p class="text-gray-600 mt-1">Manage currencies and exchange rates</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Download Template Button -->
                <button @click="downloadTemplate" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors inline-flex items-center">
                    <span class="material-symbols-outlined text-sm mr-2">download</span>Download Template
                </button>
                <!-- Import Button -->
                <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors inline-flex items-center">
                    <span class="material-symbols-outlined text-sm mr-2">upload</span>Import CSV
                </button>
                <!-- Add Currency Button -->
                <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                    <span class="material-symbols-outlined text-sm mr-2">add</span>Add Currency
                </button>
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
                        <h3 class="text-lg font-semibold text-gray-900">Import Currencies from CSV</h3>
                        <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="bg-white px-6 py-4">
                    <!-- Upload File -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="handleFileDrop($event)"
                            :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                            <div class="space-y-1 text-center">
                                <span class="material-symbols-outlined text-4xl text-gray-400 mb-3">cloud_upload</span>
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
                                    <span class="material-symbols-outlined text-sm align-middle">check_circle</span>
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
                    <div x-show="validationResults && validationResults.errors && validationResults.errors.length > 0" class="mb-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 max-h-60 overflow-y-auto">
                            <div class="flex items-start">
                                <span class="material-symbols-outlined text-yellow-600 mt-1 mr-3">warning</span>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-yellow-800">
                                        <span x-text="validationResults.imported"></span> imported successfully, 
                                        <span x-text="validationResults.errors.length"></span> errors found
                                    </h4>
                                    <ul class="mt-2 text-xs text-yellow-700 space-y-1 max-h-40 overflow-y-auto">
                                        <template x-for="(error, index) in validationResults.errors.slice(0, 20)" :key="index">
                                            <li class="flex items-start">
                                                <span class="mr-2">•</span>
                                                <span x-text="error"></span>
                                            </li>
                                        </template>
                                        <li x-show="validationResults.errors.length > 20" class="text-yellow-600 font-medium">
                                            + <span x-text="validationResults.errors.length - 20"></span> more errors...
                                        </li>
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
                        class="px-4 py-2 bg-green-600 text-white rounded-lg transition-colors inline-flex items-center">
                        <span class="material-symbols-outlined text-sm mr-2">upload</span>
                        <span x-text="uploading ? 'Uploading...' : 'Import Data'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.search" @input="loadData" placeholder="Search by code or name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.is_active" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-sm inline-block align-middle mr-2">refresh</span>Reset
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exchange Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Currency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-6 py-12 text-center">
                            <span class="material-symbols-outlined text-6xl text-gray-300 animate-spin">progress_activity</span>
                            <p class="text-gray-600 mt-2">Loading currencies...</p>
                        </td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="7" class="px-6 py-12 text-center">
                            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">payments</span>
                            <p class="text-gray-600">No currencies found.</p>
                        </td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.currency_code"></span></td>
                            <td class="px-6 py-4"><span class="text-sm text-gray-900" x-text="item.currency_name"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.symbol"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.exchange_rate"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_base_currency ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'" x-text="item.is_base_currency ? 'Base' : 'Foreign'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900" title="Delete">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900" x-text="editMode ? 'Edit Currency' : 'Add Currency'"></h3>
            </div>
            <form @submit.prevent="saveItem">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Code *</label>
                        <input type="text" x-model="formData.currency_code" required maxlength="3" :disabled="editMode" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100" placeholder="e.g., USD, EUR, INR">
                        <p class="text-xs text-gray-500 mt-1">3-letter ISO currency code</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Name *</label>
                        <input type="text" x-model="formData.currency_name" required maxlength="60" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., US Dollar">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol *</label>
                        <input type="text" x-model="formData.symbol" required maxlength="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., $, €, ₹">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Exchange Rate *</label>
                        <input type="number" x-model="formData.exchange_rate" required step="0.0001" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., 1.0000">
                        <p class="text-xs text-gray-500 mt-1">Rate relative to base currency</p>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="formData.is_base_currency" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Set as Base Currency</span>
                        </label>
                    </div>
                    <div x-show="editMode">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="formData.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <span x-text="editMode ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function currencyData() {
    return {
        items: [], 
        loading: false, 
        showModal: false, 
        editMode: false,
        showUploadModal: false,
        selectedFile: null,
        uploading: false,
        uploadProgress: 0,
        validationResults: null,
        dragOver: false,
        filters: { search: '', is_active: '' },
        formData: { currency_code: '', currency_name: '', symbol: '', exchange_rate: 1, is_base_currency: false, is_active: true },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.is_active !== '') params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/currencies?${params}`, {
                    headers: { 
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    }
                });
                const data = await response.json();
                if (data.success) this.items = data.data.currencies;
            } catch (e) {
                alert('Failed to load currencies.');
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { search: '', is_active: '' };
            this.loadData();
        },

        downloadTemplate() {
            const csvContent = [
                'currency_code,currency_name,symbol,exchange_rate,is_base_currency',
                'USD,US Dollar,$,1.0000,true',
                'EUR,Euro,€,0.9200,false',
                'GBP,British Pound,£,0.7900,false',
                'INR,Indian Rupee,₹,83.1200,false',
                'JPY,Japanese Yen,¥,149.5000,false',
                'AUD,Australian Dollar,A$,1.5300,false',
                'CAD,Canadian Dollar,C$,1.3600,false'
            ].join('\n');
            
            // Add UTF-8 BOM to ensure proper encoding
            const BOM = '\uFEFF';
            const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const timestamp = new Date().getTime();
            a.download = `currency_import_template_${timestamp}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            alert('Template downloaded successfully - File is UTF-8 encoded with BOM for Excel compatibility');
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.type === 'text/csv') {
                this.selectedFile = file;
                this.validationResults = null;
            } else {
                alert('Please select a valid CSV file');
            }
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type === 'text/csv') {
                this.selectedFile = file;
                this.validationResults = null;
            } else {
                alert('Please drop a valid CSV file');
            }
        },

        async uploadCSV() {
            if (!this.selectedFile) return;

            this.uploading = true;
            this.uploadProgress = 0;

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);

                this.uploadProgress = 30;

                const response = await fetch('/api/v1/currencies/import', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    },
                    body: formData
                });

                this.uploadProgress = 80;
                const result = await response.json();

                if (response.ok && result.success) {
                    const imported = result.data.imported || 0;
                    const errors = result.data.errors || [];

                    this.uploadProgress = 100;

                    if (errors.length > 0) {
                        this.validationResults = {
                            show: true,
                            imported: imported,
                            errors: errors
                        };
                        alert(`Imported ${imported} currencies with ${errors.length} errors`);
                    } else {
                        alert(`Successfully imported ${imported} currencies`);
                        this.showUploadModal = false;
                        this.selectedFile = null;
                        this.validationResults = null;
                    }

                    if (imported > 0) {
                        this.loadData();
                    }
                } else {
                    let errorMsg = result.message || 'Failed to import currencies';
                    alert(errorMsg);
                }
            } catch (error) {
                console.error('Failed to upload CSV:', error);
                alert('Failed to process CSV file: ' + error.message);
            } finally {
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },
        
        openCreateModal() {
            this.editMode = false;
            this.formData = { currency_code: '', currency_name: '', symbol: '', exchange_rate: 1, is_base_currency: false, is_active: true };
            this.showModal = true;
        },
        
        edit(item) {
            this.editMode = true;
            this.formData = { ...item };
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
        },
        
        async saveItem() {
            try {
                const url = this.editMode ? `/api/v1/currencies/${this.formData.id}` : '/api/v1/currencies';
                const method = this.editMode ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    this.closeModal();
                    this.loadData();
                } else {
                    alert(data.message || 'Operation failed');
                }
            } catch (e) {
                alert('Failed to save currency.');
            }
        },
        
        async deleteItem(item) {
            if (!confirm(`Deactivate currency: ${item.currency_code}?`)) return;
            
            try {
                const response = await fetch(`/api/v1/currencies/${item.id}`, {
                    method: 'DELETE',
                    headers: { 
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    this.loadData();
                } else {
                    alert(data.message || 'Delete failed');
                }
            } catch (e) {
                alert('Failed to delete currency.');
            }
        },
        
        getToken() {
            return localStorage.getItem('access_token') || '';
        },
        
        getOrgSlug() {
            return localStorage.getItem('org_slug') || '';
        }
    }
}
</script>
@endsection
