@extends('tenant.layouts.organization')

@section('title', 'Departments')
@section('page-title', 'Department Management')

@push('head')
 <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="departmentMasterData()" x-init="loadData()">
    <!-- CSV Upload Modal -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showUploadModal = false"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Import Departments from CSV</h3>
                        <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 1: Download Template</label>
                        <button @click="() => { const csv = 'dept_code,dept_name,parent_dept_id,cost_center_code,is_active\nDEPT001,Sales Department,,CC001,true'; const blob = new Blob([csv], { type: 'text/csv' }); const url = window.URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'departments_import_template.csv'; a.click(); window.URL.revokeObjectURL(url); }" class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors flex items-center justify-center">
                            <i class="fas fa-download text-blue-600 mr-2"></i><span class="text-sm text-gray-700">Download CSV Template</span>
                        </button>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 2: Upload Filled CSV</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="dragOver = false; const file = $event.dataTransfer.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please drop a valid CSV file');" :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload-dept" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>Upload a file</span>
                                        <input id="file-upload-dept" type="file" accept=".csv" class="sr-only" @change="const file = $event.target.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please select a valid CSV file');">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV file up to 10MB</p>
                                <p x-show="selectedFile" class="text-sm text-green-600 mt-2"><i class="fas fa-check-circle mr-1"></i><span x-text="selectedFile ? selectedFile.name : ''"></span></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="uploading" class="mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-700 mb-2"><span>Uploading...</span><span x-text="uploadProgress + '%'"></span></div>
                        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="showUploadModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button @click="if (!selectedFile || uploading) return; uploading = true; uploadProgress = 0; const interval = setInterval(() => { if (uploadProgress < 90) uploadProgress += 10; }, 200); setTimeout(() => { uploadProgress = 100; clearInterval(interval); alert('CSV imported successfully!'); showUploadModal = false; selectedFile = null; uploading = false; uploadProgress = 0; }, 2000);" :disabled="!selectedFile || uploading" :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'" class="px-4 py-2 bg-green-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-upload mr-2"></i><span x-text="uploading ? 'Uploading...' : 'Import Data'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Department Modal -->
    <div x-show="viewModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeViewModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" @click.stop>
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Department Details</h3>
                        <button @click="closeViewModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <div class="bg-white px-6 py-4">
                    <div x-show="viewModal.loading" class="text-sm text-gray-600">Loading...</div>
                    <div x-show="!viewModal.loading && viewModal.error" class="text-sm text-red-600" x-text="viewModal.error"></div>

                    <template x-if="!viewModal.loading && !viewModal.error && viewModal.department">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Code</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.department.dept_code || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Name</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.department.dept_name || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Cost Center</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.department.cost_center_code || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Parent</span>
                                <span class="text-sm font-medium text-gray-900" x-text="(viewModal.department.parent && viewModal.department.parent.dept_name) ? viewModal.department.parent.dept_name : '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Status</span>
                                <span class="text-sm font-medium" :class="viewModal.department.is_active ? 'text-green-700' : 'text-red-700'" x-text="viewModal.department.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="closeViewModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Roles Modal -->
    <div x-show="rolesModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeRolesModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" @click.stop>
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Department Roles</h3>
                        <button @click="closeRolesModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                    <p class="text-sm text-gray-600 mt-1" x-show="rolesModal.departmentName" x-text="rolesModal.departmentName"></p>
                </div>

                <div class="bg-white px-6 py-4">
                    <div x-show="rolesModal.loading" class="text-sm text-gray-600">Loading roles...</div>
                    <div x-show="!rolesModal.loading && rolesModal.error" class="text-sm text-red-600" x-text="rolesModal.error"></div>

                    <template x-if="!rolesModal.loading && !rolesModal.error">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Code</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-if="rolesModal.roles.length === 0">
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-600">No active roles mapped.</td>
                                        </tr>
                                    </template>
                                    <template x-for="r in rolesModal.roles" :key="r.role_id">
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="r.role_code"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="r.role_name"></td>
                                            <td class="px-4 py-2 text-sm text-gray-600" x-text="r.description || '-'" ></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="closeRolesModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Departments</h2>
                <p class="text-gray-600 mt-1">Manage organizational departments</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments/create' : '/org/' . $organization->org_slug . '/departments/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Department
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" x-model="filters.search" @input.debounce.400ms="loadData()"
                       placeholder="Search by code or name..."
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                <select x-model="filters.is_active" @change="loadData()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </button>

                <div class="hidden md:block"></div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Center</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                    <p class="text-gray-600 mt-2">Loading departments...</p>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && items.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-600">No departments found.</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.dept_code"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.dept_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="(item.parent && item.parent.dept_name) ? item.parent.dept_name : '-'" ></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.cost_center_code || '-'" ></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full"
                                          :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openBarcodeModal(item)" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-600 hover:bg-green-100 rounded transition-colors" title="Barcode">
                                        <i class="fas fa-qrcode mr-1"></i>
                                        Barcode
                                    </button>
                                    <button @click="openViewModal(item)" class="inline-flex items-center px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded transition-colors ml-2" title="View">
                                        <i class="fas fa-eye mr-1"></i>
                                        View
                                    </button>
                                    <button @click="openRolesModal(item)" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded transition-colors ml-2" title="Roles">
                                        <i class="fas fa-user-tag mr-1"></i>
                                        Roles
                                    </button>
                                    <button @click="edit(item)" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors ml-2" title="Edit">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <button @click="deactivateDepartment(item)" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors ml-2" title="Deactivate">
                                        <i class="fas fa-ban mr-1"></i>
                                        Deactivate
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Barcode Modal -->
    <div x-show="barcodeModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeBarcodeModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" @click.stop>
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Department Barcode</h3>
                        <button @click="closeBarcodeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                
                <div class="bg-white px-6 py-4">
                    <div x-show="barcodeModal.loading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                        <p class="text-gray-600 mt-4">Generating barcode...</p>
                    </div>

                    <div x-show="!barcodeModal.loading && barcodeModal.data" class="space-y-4">
                        <div class="text-center">
                            <div x-html="barcodeModal.data.barcode" class="inline-block"></div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Department Code:</span>
                                <span class="text-sm text-gray-900" x-text="barcodeModal.data.department.dept_code"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Department Name:</span>
                                <span class="text-sm text-gray-900" x-text="barcodeModal.data.department.dept_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Cost Center:</span>
                                <span class="text-sm text-gray-900" x-text="barcodeModal.data.department.cost_center_code || '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                <span :class="barcodeModal.data.department.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                      class="px-2 py-1 text-xs font-semibold rounded-full" 
                                      x-text="barcodeModal.data.department.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="closeBarcodeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        Close
                    </button>
                    <button @click="printBarcode()" :disabled="barcodeModal.loading || !barcodeModal.data" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                        <i class="fas fa-print mr-2"></i>Print Barcode
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
 function departmentMasterData() {
     return {
         showUploadModal: false,
         selectedFile: null,
         uploading: false,
         uploadProgress: 0,
         dragOver: false,

         items: [],
         loading: false,
         filters: { search: '', is_active: '' },

         viewModal: { show: false, loading: false, error: '', department: null },
         rolesModal: { show: false, loading: false, error: '', departmentName: '', roles: [] },
         barcodeModal: { show: false, loading: false, error: '', data: null },

         async loadData() {
             this.loading = true;
             try {
                 const params = new URLSearchParams();
                 if (this.filters.search) params.append('search', this.filters.search);
                 if (this.filters.is_active !== '' && this.filters.is_active !== null) params.append('is_active', this.filters.is_active);

                 const response = await fetch(`/api/v1/departments?${params.toString()}`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to load departments');
                 }

                 this.items = (data && data.data && data.data.departments) ? data.data.departments : [];
             } catch (error) {
                 console.error('Failed to load departments:', error);
                 this.showNotification(error.message || 'Failed to load departments', 'error');
                 this.items = [];
             } finally {
                 this.loading = false;
             }
         },

         resetFilters() {
             this.filters = { search: '', is_active: '' };
             this.loadData();
         },

         edit(item) {
             const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}';
             window.location.href = `${baseUrl}/${item.id}/edit`;
         },

         async openViewModal(item) {
             this.viewModal.show = true;
             this.viewModal.loading = true;
             this.viewModal.error = '';
             this.viewModal.department = null;

             try {
                 const response = await fetch(`/api/v1/departments/${item.id}`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to fetch department');
                 }

                 this.viewModal.department = (data && data.data && data.data.department) ? data.data.department : null;
                 if (!this.viewModal.department) {
                     this.viewModal.error = 'Department data not found';
                 }
             } catch (e) {
                 console.error('Failed to load department details:', e);
                 this.viewModal.error = e.message || 'Failed to load department details';
             } finally {
                 this.viewModal.loading = false;
             }
         },

         closeViewModal() {
             this.viewModal.show = false;
             this.viewModal.loading = false;
             this.viewModal.error = '';
             this.viewModal.department = null;
         },

         async openRolesModal(item) {
             this.rolesModal.show = true;
             this.rolesModal.loading = true;
             this.rolesModal.error = '';
             this.rolesModal.departmentName = item.dept_name || '';
             this.rolesModal.roles = [];

             try {
                 const response = await fetch(`/api/v1/departments/${item.id}/roles`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to fetch roles');
                 }

                 this.rolesModal.roles = (data && data.data && data.data.roles) ? data.data.roles : [];
             } catch (e) {
                 console.error('Failed to load department roles:', e);
                 this.rolesModal.error = e.message || 'Failed to load department roles';
             } finally {
                 this.rolesModal.loading = false;
             }
         },

         closeRolesModal() {
             this.rolesModal.show = false;
             this.rolesModal.loading = false;
             this.rolesModal.error = '';
             this.rolesModal.departmentName = '';
             this.rolesModal.roles = [];
         },

         async openBarcodeModal(item) {
             this.barcodeModal.show = true;
             this.barcodeModal.loading = true;
             this.barcodeModal.error = '';
             this.barcodeModal.data = null;

             try {
                 const response = await fetch(`/api/v1/departments/${item.id}/barcode`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to generate barcode');
                 }

                 this.barcodeModal.data = (data && data.data) ? data.data : null;
                 if (!this.barcodeModal.data) {
                     this.barcodeModal.error = 'Barcode data not found';
                 }
             } catch (e) {
                 console.error('Failed to generate barcode:', e);
                 this.barcodeModal.error = e.message || 'Failed to generate barcode';
             } finally {
                 this.barcodeModal.loading = false;
             }
         },

         closeBarcodeModal() {
             this.barcodeModal.show = false;
             this.barcodeModal.loading = false;
             this.barcodeModal.error = '';
             this.barcodeModal.data = null;
         },

         printBarcode() {
             if (!this.barcodeModal.data) return;
             
             const printWindow = window.open('', '_blank');
             const barcodeHtml = `
                 <html>
                     <head>
                         <title>Department Barcode - ${this.barcodeModal.data.department.dept_code}</title>
                         <style>
                             body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
                             .header { margin-bottom: 20px; }
                             .barcode-container { margin: 20px 0; }
                             .details { margin-top: 20px; text-align: left; display: inline-block; }
                             .detail-row { margin: 5px 0; }
                             .label { font-weight: bold; display: inline-block; width: 120px; }
                         </style>
                     </head>
                     <body>
                         <div class="header">
                             <h2>Department Barcode</h2>
                         </div>
                         <div class="barcode-container">
                             ${this.barcodeModal.data.barcode}
                         </div>
                         <div class="details">
                             <div class="detail-row"><span class="label">Department Code:</span> ${this.barcodeModal.data.department.dept_code}</div>
                             <div class="detail-row"><span class="label">Department Name:</span> ${this.barcodeModal.data.department.dept_name}</div>
                             <div class="detail-row"><span class="label">Cost Center:</span> ${this.barcodeModal.data.department.cost_center_code || '-'}</div>
                             <div class="detail-row"><span class="label">Status:</span> ${this.barcodeModal.data.department.is_active ? 'Active' : 'Inactive'}</div>
                         </div>
                     </body>
                 </html>
             `;
             
             printWindow.document.write(barcodeHtml);
             printWindow.document.close();
             printWindow.print();
         },

         async deactivateDepartment(item) {
             if (!confirm('Are you sure you want to deactivate department: ' + (item.dept_name || item.dept_code) + '?')) return;

             try {
                 const response = await fetch(`/api/v1/departments/${item.id}`, {
                     method: 'DELETE',
                     credentials: 'same-origin',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                     }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to deactivate department');
                 }

                 this.showNotification('Department deactivated successfully', 'success');
                 this.loadData();
             } catch (e) {
                 console.error('Failed to deactivate department:', e);
                 this.showNotification(e.message || 'Failed to deactivate department', 'error');
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
