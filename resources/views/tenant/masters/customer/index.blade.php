@extends('tenant.layouts.app')

@section('title', 'Customer Master - ' . $organization->org_name)
@section('page-title', 'Customer Master')

@push('head')
<style>
[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="customerData()" x-init="init()">

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100">Customer Management</div>
                <h2 class="text-3xl font-bold">Customer Master</h2>
                <p class="text-indigo-50 mt-1">Manage your customer database with contact details and billing information.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="downloadTemplate()" 
                        class="px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-semibold">
                    <i class="fas fa-download mr-2"></i>Download CSV Template
                </button>
                <button @click="openImportModal()" 
                        class="px-4 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-semibold">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <button @click="openCreateModal()"
                    class="px-4 py-3 bg-white text-indigo-700 rounded-xl hover:bg-indigo-50 transition-colors font-semibold">
                    <i class="fas fa-plus mr-2"></i>New Customer
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
            <div class="rounded-2xl bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">Total Customers</p>
                <p class="mt-2 text-3xl font-bold text-indigo-900" x-text="customers.length">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
            <div class="rounded-2xl bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Active</p>
                <p class="mt-2 text-3xl font-bold text-emerald-900" x-text="activeCount()">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
            <div class="rounded-2xl bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Inactive</p>
                <p class="mt-2 text-3xl font-bold text-amber-900" x-text="inactiveCount()">0</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" x-model="filters.search" @input="loadCustomers()" 
                       placeholder="Customer name, code, email..." 
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select x-model="filters.is_active" @change="loadCustomers()" 
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()" class="w-full px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Customer Code</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Customer Name</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Contact Person</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Phone</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Email</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GSTIN</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="8" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && customers.length === 0">
                        <tr><td colspan="8" class="py-12 text-center text-gray-400">No customers found</td></tr>
                    </template>
                    <template x-for="c in customers" :key="c.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm font-mono" x-text="c.customer_code"></td>
                            <td class="py-3 px-5 text-sm font-medium text-gray-900" x-text="c.customer_name"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="c.contact_person || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="c.phone || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600" x-text="c.email || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-600 font-mono" x-text="c.gstin || '—'"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    x-text="c.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="py-3 px-5 text-right flex items-center justify-end gap-2">
                                <button @click="openEditModal(c)" title="Edit" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button x-show="c.is_active" @click="deactivate(c.id)" title="Deactivate"
                                    class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined text-lg">block</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900" x-text="editId ? 'Edit Customer' : 'New Customer'"></h3>
                    <button @click="showModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="saveCustomer()" class="p-6 space-y-4">
                    <!-- Auto-generated Code Preview -->
                    <div x-show="!editId" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-xs font-semibold text-blue-900 mb-1">
                            <i class="fas fa-info-circle mr-1"></i>Customer Code Auto-Generation
                        </p>
                        <p class="text-xs text-blue-800">
                            Code will be generated from: <strong>Customer Name Initials + Contact Person + Increment</strong>
                        </p>
                        <p class="text-xs text-blue-700 mt-1">
                            Example: "Acme Global Industries" + "John Doe" = <strong class="font-mono">AGI-JohnDoe-01</strong>
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Customer Name *</label>
                            <input type="text" required maxlength="255" x-model="form.customer_name"
                                placeholder="ABC Corporation"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Person</label>
                            <input type="text" maxlength="255" x-model="form.contact_person"
                                placeholder="John Doe"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                            <input type="text" maxlength="20" x-model="form.phone"
                                placeholder="9876543210"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email" maxlength="255" x-model="form.email"
                                placeholder="contact@abc.com"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">GSTIN</label>
                            <input type="text" maxlength="15" x-model="form.gstin"
                                placeholder="27AABCU9603R1ZX"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary uppercase">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Payment Terms</label>
                            <input type="text" maxlength="255" x-model="form.payment_terms"
                                placeholder="Net 30"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Credit Days</label>
                            <input type="number" min="0" x-model="form.credit_days"
                                placeholder="30"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Billing Address</label>
                        <textarea rows="2" x-model="form.billing_address"
                            placeholder="123 Main Street, City, State, PIN"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Shipping Address</label>
                        <textarea rows="2" x-model="form.shipping_address"
                            placeholder="456 Shipping Street, City, State, PIN"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" class="rounded border-gray-300" x-model="form.is_active">
                            <span class="font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving" x-text="editId ? 'Update' : 'Create'"></span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="closeImportModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-upload text-purple-600"></i>
                        Import Customers from CSV
                    </h3>
                    <button @click="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
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
                                            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                                            <p class="text-gray-600 mb-2">Drag and drop your CSV file here, or</p>
                                            <button @click="document.getElementById('csvFileInput').click()" 
                                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                                Browse Files
                                            </button>
                                        </div>
                                    </template>
                                    
                                    <template x-if="selectedFile">
                                        <div class="flex items-center justify-center gap-3">
                                            <i class="fas fa-file-csv text-3xl text-green-600"></i>
                                            <div class="text-left">
                                                <p class="text-sm font-medium text-gray-900" x-text="selectedFile.name"></p>
                                                <p class="text-xs text-gray-500" x-text="(selectedFile.size / 1024).toFixed(2) + ' KB'"></p>
                                            </div>
                                            <button @click="clearFile()" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    Import Instructions
                                </h4>
                                <ul class="text-xs text-blue-800 space-y-1 ml-6 list-disc">
                                    <li>Download the CSV template first to see the required format</li>
                                    <li><strong>customer_code column must be BLANK</strong> - it will be auto-generated</li>
                                    <li>Required field: customer_name</li>
                                    <li>Auto-generated customer_code format: <strong>Initials-ContactName-##</strong></li>
                                    <li>Example: "Acme Global Industries" + "John Doe" = <strong>AGI-JohnDoe-01</strong></li>
                                    <li>If no contact person provided, format will be: <strong>Initials-##</strong></li>
                                    <li>is_active: use "true" or "false" (default: true)</li>
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
                                    <i class="fas" :class="uploading ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                    <span x-text="uploading ? 'Uploading...' : 'Upload & Import'"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Upload Complete -->
                    <template x-if="uploadComplete">
                        <div>
                            <div class="text-center py-6">
                                <i class="fas text-6xl mb-4"
                                   :class="uploadErrors.length === 0 ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-yellow-500'"></i>
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

</div>

<script>
function customerData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 
        'Authorization': `Bearer ${token()}`, 
        'Accept': 'application/json', 
        'Content-Type': 'application/json', 
        'X-Org-Slug': orgSlug 
    });

    return {
        customers: [],
        loading: false,
        saving: false,
        showModal: false,
        editId: null,
        filters: {
            search: '',
            is_active: ''
        },
        
        // Import modal state
        showImportModal: false,
        selectedFile: null,
        uploading: false,
        uploadComplete: false,
        uploadMessage: '',
        uploadErrors: [],
        dragOver: false,
        
        form: {
            customer_name: '',
            contact_person: '',
            phone: '',
            email: '',
            billing_address: '',
            shipping_address: '',
            gstin: '',
            payment_terms: '',
            credit_days: null,
            is_active: true
        },

        async init() {
            await this.loadCustomers();
        },

        async loadCustomers() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.is_active !== '') params.append('is_active', this.filters.is_active);
                
                const res = await fetch(`/api/v1/customers?${params}`, { headers: headers() });
                const data = await res.json();
                if (data.success) this.customers = data.data || [];
            } finally { 
                this.loading = false; 
            }
        },

        resetFilters() {
            this.filters = { search: '', is_active: '' };
            this.loadCustomers();
        },

        openCreateModal() {
            this.editId = null;
            this.form = {
                customer_name: '',
                contact_person: '',
                phone: '',
                email: '',
                billing_address: '',
                shipping_address: '',
                gstin: '',
                payment_terms: '',
                credit_days: null,
                is_active: true
            };
            this.showModal = true;
        },

        openEditModal(c) {
            this.editId = c.id;
            this.form = {
                customer_name: c.customer_name,
                contact_person: c.contact_person || '',
                phone: c.phone || '',
                email: c.email || '',
                billing_address: c.billing_address || '',
                shipping_address: c.shipping_address || '',
                gstin: c.gstin || '',
                payment_terms: c.payment_terms || '',
                credit_days: c.credit_days,
                is_active: c.is_active
            };
            this.showModal = true;
        },

        async saveCustomer() {
            this.saving = true;
            try {
                const url = this.editId ? `/api/v1/customers/${this.editId}` : '/api/v1/customers';
                const method = this.editId ? 'PUT' : 'POST';
                const res = await fetch(url, { method, headers: headers(), body: JSON.stringify(this.form) });
                const data = await res.json();
                if (data.success) {
                    this.showModal = false;
                    await this.loadCustomers();
                } else {
                    alert(data.message || 'Failed to save customer');
                }
            } finally { 
                this.saving = false; 
            }
        },

        async deactivate(id) {
            if (!confirm('Deactivate this customer?')) return;
            const res = await fetch(`/api/v1/customers/${id}`, { method: 'DELETE', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadCustomers();
            else alert(data.message || 'Failed to deactivate');
        },

        downloadTemplate() {
            window.location.href = '/api/v1/customers/import/template';
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
                this.loadCustomers();
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

                const uploadHeaders = {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'X-Org-Slug': orgSlug
                };

                const response = await fetch('/api/v1/customers/import', {
                    method: 'POST',
                    headers: uploadHeaders,
                    body: formData
                });

                const data = await response.json();
                
                this.uploading = false;
                this.uploadComplete = true;

                if (data.success) {
                    this.uploadMessage = data.message;
                    this.uploadErrors = data.data?.errors || [];
                    
                    setTimeout(() => {
                        this.loadCustomers();
                    }, 2000);
                } else {
                    this.uploadMessage = 'Import failed';
                    this.uploadErrors = [data.message];
                }
            } catch (error) {
                this.uploading = false;
                this.uploadComplete = true;
                this.uploadMessage = 'Import failed';
                this.uploadErrors = ['Network error occurred'];
            }
        },

        activeCount() {
            return this.customers.filter(c => c.is_active).length;
        },

        inactiveCount() {
            return this.customers.filter(c => !c.is_active).length;
        }
    };
}
</script>
@endsection
