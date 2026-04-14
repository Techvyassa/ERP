@extends('tenant.layouts.organization')

@section('title', 'Departments')
@section('page-title', 'Department Management')

@push('head')
 <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="departmentMasterData()" x-init="loadData()">
    <!-- Import CSV Modal -->
    <div x-show="showImportModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="closeImportModal()">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900">Import Departments from CSV</h3>
                <button @click="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Upload Section -->
                <div x-show="!uploading && !uploadComplete">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-500 transition-colors"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleFileDrop($event)"
                         :class="{'border-purple-500 bg-purple-50': dragOver}">
                        <input type="file" 
                               id="csvFileInput" 
                               accept=".csv" 
                               @change="handleFileSelect($event)" 
                               class="hidden">
                        <label for="csvFileInput" class="cursor-pointer">
                            <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">upload_file</span>
                            <p class="text-gray-700 font-medium mb-2">Drop CSV file here or click to browse</p>
                            <p class="text-sm text-gray-500">Maximum file size: 10MB</p>
                        </label>
                    </div>
                    
                    <div x-show="selectedFile" class="mt-4 p-3 bg-gray-50 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gray-600">description</span>
                            <span class="text-sm text-gray-700" x-text="selectedFile ? selectedFile.name : ''"></span>
                        </div>
                        <button @click="clearFile()" class="text-red-600 hover:text-red-800">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div x-show="uploading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                    <p class="text-gray-700 font-medium">Uploading and processing...</p>
                    <p class="text-sm text-gray-500 mt-2">Please wait while we import your departments</p>
                </div>

                <!-- Upload Complete -->
                <div x-show="uploadComplete" class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <span class="material-symbols-outlined text-4xl text-green-600">check_circle</span>
                    </div>
                    <p class="text-gray-700 font-medium mb-2" x-text="uploadMessage"></p>
                    
                    <!-- Show errors if any -->
                    <div x-show="uploadErrors.length > 0" class="mt-4 max-h-48 overflow-y-auto">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-left">
                            <p class="text-sm font-medium text-red-800 mb-2">Errors:</p>
                            <ul class="text-sm text-red-700 space-y-1">
                                <template x-for="error in uploadErrors" :key="error">
                                    <li x-text="error"></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex gap-3 p-6 border-t border-gray-200">
                <button type="button" 
                        @click="closeImportModal()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <span x-show="!uploadComplete">Cancel</span>
                    <span x-show="uploadComplete">Close</span>
                </button>
                <button type="button" 
                        x-show="!uploading && !uploadComplete"
                        @click="uploadCSV()"
                        :disabled="!selectedFile"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Upload & Import
                </button>
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
                <button @click="downloadTemplate()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                    <i class="fas fa-download mr-2"></i>Download CSV Template
                </button>
                <button @click="openImportModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
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
                                    <button @click="openViewModal(item)" class="inline-flex items-center px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded transition-colors" title="View">
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
</div>

<script>
 function departmentMasterData() {
     return {
         showImportModal: false,
         selectedFile: null,
         uploading: false,
         uploadComplete: false,
         uploadMessage: '',
         uploadErrors: [],
         dragOver: false,

         items: [],
         loading: false,
         filters: { search: '', is_active: '' },

         viewModal: { show: false, loading: false, error: '', department: null },
         rolesModal: { show: false, loading: false, error: '', departmentName: '', roles: [] },

         async loadData() {
             this.loading = true;
             try {
                 const params = new URLSearchParams();
                 if (this.filters.search) params.append('search', this.filters.search);
                 if (this.filters.is_active !== '' && this.filters.is_active !== null) params.append('is_active', this.filters.is_active);

                 const response = await fetch(`{{ url('api/v1/departments') }}?${params.toString()}`, {
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
             const baseUrl = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}";
             window.location.href = `${baseUrl}/${item.id}/edit`;
         },

         async openViewModal(item) {
             this.viewModal.show = true;
             this.viewModal.loading = true;
             this.viewModal.error = '';
             this.viewModal.department = null;

             try {
                 const response = await fetch(`{{ url('api/v1/departments') }}/${item.id}`, {
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
                 const response = await fetch(`{{ url('api/v1/departments') }}/${item.id}/roles`, {
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

         async deactivateDepartment(item) {
             if (!confirm('Are you sure you want to deactivate department: ' + (item.dept_name || item.dept_code) + '?')) return;

             try {
                 const response = await fetch(`{{ url('api/v1/departments') }}/${item.id}`, {
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

         downloadTemplate() {
             window.location.href = '/api/v1/departments/import/template';
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

                 const response = await fetch('/api/v1/departments/import', {
                     method: 'POST',
                     credentials: 'same-origin',
                     headers: {
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                     },
                     body: formData
                 });

                 const data = await response.json();
                 
                 this.uploading = false;
                 this.uploadComplete = true;

                 if (data.success) {
                     this.uploadMessage = data.message;
                     this.uploadErrors = data.data.errors || [];
                     
                     // Reload page after successful import
                     setTimeout(() => {
                         window.location.reload();
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
