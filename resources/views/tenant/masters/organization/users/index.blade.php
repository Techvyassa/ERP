@extends('tenant.layouts.organization')

@section('title', 'Users')
@section('page-title', 'User Management')

@push('head')
 <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="userMasterData()" x-init="loadData()">
    <!-- CSV Upload Modal -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showUploadModal = false"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Import Users from CSV</h3>
                        <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 1: Download Template</label>
                        <button @click="() => { const csv = 'employee_code,email,full_name,phone,dept_id,role_id,is_active\nEMP001,user@example.com,John Doe,1234567890,1,1,true'; const blob = new Blob([csv], { type: 'text/csv' }); const url = window.URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'users_import_template.csv'; a.click(); window.URL.revokeObjectURL(url); }" class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors flex items-center justify-center">
                            <i class="fas fa-download text-blue-600 mr-2"></i><span class="text-sm text-gray-700">Download CSV Template</span>
                        </button>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 2: Upload Filled CSV</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="dragOver = false; const file = $event.dataTransfer.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please drop a valid CSV file');" :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload-users" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>Upload a file</span>
                                        <input id="file-upload-users" type="file" accept=".csv" class="sr-only" @change="const file = $event.target.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please select a valid CSV file');">
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

    <!-- View User Modal -->
    <div x-show="viewModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeViewModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" @click.stop>
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">User Details</h3>
                        <button @click="closeViewModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <div class="bg-white px-6 py-4">
                    <div x-show="viewModal.loading" class="text-sm text-gray-600">Loading...</div>
                    <div x-show="!viewModal.loading && viewModal.error" class="text-sm text-red-600" x-text="viewModal.error"></div>

                    <template x-if="!viewModal.loading && !viewModal.error && viewModal.user">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Employee Code</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.user.employee_code || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Name</span>
                                <span class="text-sm font-medium text-gray-900" x-text="(viewModal.user.first_name || '') + ' ' + (viewModal.user.last_name || '')"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Email</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.user.email || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Phone</span>
                                <span class="text-sm font-medium text-gray-900" x-text="viewModal.user.phone || '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Department</span>
                                <span class="text-sm font-medium text-gray-900" x-text="(viewModal.user.department && viewModal.user.department.dept_name) ? viewModal.user.department.dept_name : '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Role</span>
                                <span class="text-sm font-medium text-gray-900" x-text="(viewModal.user.role && viewModal.user.role.role_name) ? viewModal.user.role.role_name : '-'" ></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Status</span>
                                <span class="text-sm font-medium" :class="viewModal.user.is_active ? 'text-green-700' : 'text-red-700'" x-text="viewModal.user.is_active ? 'Active' : 'Inactive'"></span>
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

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Users</h2>
                <p class="text-gray-600 mt-1">Manage system users and their access</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/users/create' : '/org/' . $organization->org_slug . '/users/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add User
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" x-model="filters.search" @input.debounce.400ms="loadData()"
                       placeholder="Search by name, email, employee code..."
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                <select x-model="filters.is_active" @change="loadData()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                <select x-model="filters.per_page" @change="loadData()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10 / page</option>
                    <option value="15">15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>

                <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                    <p class="text-gray-600 mt-2">Loading users...</p>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && items.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-600">No users found.</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.employee_code"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="(item.first_name || '') + ' ' + (item.last_name || '')"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.email"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="(item.department && item.department.dept_name) ? item.department.dept_name : '-'" ></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="(item.role && item.role.role_name) ? item.role.role_name : '-'" ></td>
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
                                    <button @click="edit(item)" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors ml-2" title="Edit">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <button @click="deactivateUser(item)" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded transition-colors ml-2" title="Deactivate">
                                        <i class="fas fa-user-slash mr-1"></i>
                                        Deactivate
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
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                    <span class="ml-2">(Total: <span x-text="pagination.total"></span>)</span>
                </div>
                <div class="flex space-x-2">
                    <button @click="loadPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1 || loading"
                            :class="(pagination.current_page === 1 || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded">
                        Previous
                    </button>
                    <button @click="loadPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page || loading"
                            :class="(pagination.current_page === pagination.last_page || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
 function userMasterData() {
     return {
         showUploadModal: false,
         selectedFile: null,
         uploading: false,
         uploadProgress: 0,
         dragOver: false,

         items: [],
         loading: false,
         filters: { search: '', is_active: '', per_page: 15 },
         pagination: { current_page: 1, last_page: 1, total: 0, per_page: 15 },

         viewModal: { show: false, loading: false, error: '', user: null },

         async loadData(page = 1) {
             this.loading = true;
             try {
                 const params = new URLSearchParams();
                 params.append('page', page);
                 params.append('per_page', this.filters.per_page || 15);
                 if (this.filters.search) params.append('search', this.filters.search);
                 if (this.filters.is_active !== '' && this.filters.is_active !== null) params.append('is_active', this.filters.is_active);

                 const response = await fetch(`/api/v1/users?${params.toString()}`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to load users');
                 }

                 this.items = (data && data.data && data.data.users) ? data.data.users : [];
                 const p = (data && data.data && data.data.pagination) ? data.data.pagination : null;
                 if (p) {
                     this.pagination = {
                         current_page: p.current_page || 1,
                         last_page: p.last_page || 1,
                         total: p.total || 0,
                         per_page: p.per_page || this.filters.per_page
                     };
                 } else {
                     this.pagination = { current_page: 1, last_page: 1, total: this.items.length, per_page: this.filters.per_page };
                 }
             } catch (error) {
                 console.error('Failed to load users:', error);
                 this.showNotification(error.message || 'Failed to load users', 'error');
                 this.items = [];
                 this.pagination = { current_page: 1, last_page: 1, total: 0, per_page: this.filters.per_page };
             } finally {
                 this.loading = false;
             }
         },

         loadPage(page) {
             if (this.loading) return;
             if (page < 1) return;
             if (page > this.pagination.last_page) return;
             this.loadData(page);
         },

         resetFilters() {
             this.filters = { search: '', is_active: '', per_page: 15 };
             this.loadData(1);
         },

         edit(item) {
             const baseUrl = '{{ url(request()->get('tenant_type') === 'subdomain' ? '/users' : '/org/' . $organization->org_slug . '/users') }}';
             window.location.href = `${baseUrl}/${item.id}/edit`;
         },

         async openViewModal(item) {
             this.viewModal.show = true;
             this.viewModal.loading = true;
             this.viewModal.error = '';
             this.viewModal.user = null;

             try {
                 const response = await fetch(`/api/v1/users/${item.id}`, {
                     credentials: 'same-origin',
                     headers: { 'Accept': 'application/json' }
                 });
                 const data = await response.json();

                 if (!response.ok || !data || data.success !== true) {
                     throw new Error((data && data.message) ? data.message : 'Failed to fetch user');
                 }

                 this.viewModal.user = (data && data.data && data.data.user) ? data.data.user : null;
                 if (!this.viewModal.user) {
                     this.viewModal.error = 'User data not found';
                 }
             } catch (e) {
                 console.error('Failed to load user details:', e);
                 this.viewModal.error = e.message || 'Failed to load user details';
             } finally {
                 this.viewModal.loading = false;
             }
         },

         closeViewModal() {
             this.viewModal.show = false;
             this.viewModal.loading = false;
             this.viewModal.error = '';
             this.viewModal.user = null;
         },

         async deactivateUser(item) {
             if (!confirm('Are you sure you want to deactivate user: ' + (item.email || item.employee_code) + '?')) return;

             try {
                 const response = await fetch(`/api/v1/users/${item.id}`, {
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
                     throw new Error((data && data.message) ? data.message : 'Failed to deactivate user');
                 }

                 this.showNotification('User deactivated successfully', 'success');
                 this.loadData(this.pagination.current_page);
             } catch (e) {
                 console.error('Failed to deactivate user:', e);
                 this.showNotification(e.message || 'Failed to deactivate user', 'error');
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
