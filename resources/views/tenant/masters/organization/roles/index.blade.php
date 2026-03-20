@extends('tenant.layouts.organization')

@section('title', 'Roles')
@section('page-title', 'Role Management')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="roleManager()" x-init="init()">
    <!-- Main Roles List -->
    <div class="bg-white rounded-xl shadow">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Roles</h2>
                <p class="text-gray-600 mt-1">Manage user roles and permissions</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/roles/create' : '/org/' . $organization->org_slug . '/roles/create') }}"
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>Add Role</span>
            </a>
        </div>

        <!-- Search and Filters -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="searchQuery" 
                           @input="fetchRoles()"
                           placeholder="Search roles by name or code..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <select x-model="filterActive" 
                        @change="fetchRoles()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            <p class="text-gray-600 mt-4">Loading roles...</p>
        </div>

        <!-- Roles Table -->
        <div x-show="!loading && roles.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="role in roles" :key="role.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="role.role_code"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="role.role_name"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600" x-text="role.description || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="role.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                      class="px-2 py-1 text-xs font-semibold rounded-full" 
                                      x-text="role.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="role.is_system_role ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'" 
                                      class="px-2 py-1 text-xs font-semibold rounded-full" 
                                      x-text="role.is_system_role ? 'System' : 'Custom'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openPermissionsModal(role)" 
                                            class="text-purple-600 hover:text-purple-900 p-1" 
                                            title="Manage Permissions">
                                        <span class="material-symbols-outlined text-sm">lock</span>
                                    </button>
                                    <button @click="openEditModal(role)" 
                                            class="text-blue-600 hover:text-blue-900 p-1" 
                                            title="Edit">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <template x-if="role.is_active && !role.is_system_role">
                                        <button @click="deactivateRole(role)" 
                                                class="text-red-600 hover:text-red-900 p-1" 
                                                title="Deactivate">
                                            <span class="material-symbols-outlined text-sm">block</span>
                                        </button>
                                    </template>
                                    <template x-if="!role.is_active && !role.is_system_role">
                                        <button @click="activateRole(role)" 
                                                class="text-green-600 hover:text-green-900 p-1" 
                                                title="Activate">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && roles.length === 0" class="text-center py-12">
            <span class="material-symbols-outlined text-6xl text-gray-300">shield_person</span>
            <h3 class="text-xl font-semibold text-gray-700 mb-2 mt-4">No Roles Found</h3>
            <p class="text-gray-600">Create your first role to get started.</p>
        </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <div x-show="showModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="closeModal()">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900" x-text="editingRole ? 'Edit Role' : 'Create New Role'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form @submit.prevent="saveRole()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role Code *</label>
                    <input type="text" 
                           x-model="formData.role_code" 
                           :disabled="editingRole"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100"
                           placeholder="e.g., ADMIN, MANAGER">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                    <input type="text" 
                           x-model="formData.role_name" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="e.g., Administrator">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea x-model="formData.description" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Brief description of the role"></textarea>
                </div>
                
                <div x-show="editingRole" class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.is_active" 
                           id="is_active"
                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" 
                            @click="closeModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="saving"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50">
                        <span x-show="!saving" x-text="editingRole ? 'Update' : 'Create'"></span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Permissions Modal -->
    <div x-show="showPermissionsModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="closePermissionsModal()">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Manage Permissions</h3>
                    <p class="text-sm text-gray-600 mt-1" x-text="selectedRole ? selectedRole.role_name : ''"></p>
                </div>
                <button @click="closePermissionsModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                <div x-show="loadingPermissions" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <p class="text-gray-600 mt-4">Loading permissions...</p>
                </div>

                <div x-show="!loadingPermissions" class="space-y-4">
                    <template x-for="module in availableModules" :key="module.code">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900" x-text="module.name"></h4>
                            </div>
                            <div class="grid grid-cols-5 gap-3">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" 
                                           :checked="getPermission(module.code, 'can_view')"
                                           @change="updatePermission(module.code, 'can_view', $event.target.checked)"
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <span class="text-sm text-gray-700">View</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" 
                                           :checked="getPermission(module.code, 'can_create')"
                                           @change="updatePermission(module.code, 'can_create', $event.target.checked)"
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <span class="text-sm text-gray-700">Create</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" 
                                           :checked="getPermission(module.code, 'can_edit')"
                                           @change="updatePermission(module.code, 'can_edit', $event.target.checked)"
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <span class="text-sm text-gray-700">Edit</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" 
                                           :checked="getPermission(module.code, 'can_approve')"
                                           @change="updatePermission(module.code, 'can_approve', $event.target.checked)"
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <span class="text-sm text-gray-700">Approve</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" 
                                           :checked="getPermission(module.code, 'can_delete')"
                                           @change="updatePermission(module.code, 'can_delete', $event.target.checked)"
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <span class="text-sm text-gray-700">Delete</span>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3 p-6 border-t border-gray-200">
                <button type="button" 
                        @click="closePermissionsModal()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" 
                        @click="savePermissions()"
                        :disabled="savingPermissions"
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingPermissions">Save Permissions</span>
                    <span x-show="savingPermissions">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function roleManager() {
    return {
        roles: [],
        loading: false,
        saving: false,
        searchQuery: '',
        filterActive: '',
        showModal: false,
        showPermissionsModal: false,
        editingRole: null,
        selectedRole: null,
        formData: {
            role_code: '',
            role_name: '',
            description: '',
            is_active: true
        },
        permissions: [],
        loadingPermissions: false,
        savingPermissions: false,
        availableModules: [
            { code: 'SETTINGS', name: 'Settings' },
            { code: 'PROCUREMENT', name: 'Procurement' },
            { code: 'INVENTORY', name: 'Inventory' },
            { code: 'PRODUCTION', name: 'Production' },
            { code: 'QUALITY', name: 'Quality' },
            { code: 'SALES', name: 'Sales' },
            { code: 'FINANCE', name: 'Finance' },
            { code: 'HR', name: 'Human Resources' }
        ],

        init() {
            this.fetchRoles();
        },

        async fetchRoles() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('search', this.searchQuery);
                if (this.filterActive) params.append('is_active', this.filterActive);

                const response = await fetch(`/api/v1/roles?${params}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.roles = data.data.roles;
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError('Failed to fetch roles');
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.editingRole = null;
            this.formData = {
                role_code: '',
                role_name: '',
                description: '',
                is_active: true
            };
            this.showModal = true;
        },

        openEditModal(role) {
            this.editingRole = role;
            this.formData = {
                role_code: role.role_code,
                role_name: role.role_name,
                description: role.description || '',
                is_active: role.is_active
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingRole = null;
        },

        async saveRole() {
            this.saving = true;
            try {
                const url = this.editingRole 
                    ? `/api/v1/roles/${this.editingRole.id}`
                    : '/api/v1/roles';
                
                const method = this.editingRole ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(data.message);
                    this.closeModal();
                    this.fetchRoles();
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError('Failed to save role');
            } finally {
                this.saving = false;
            }
        },

        async deactivateRole(role) {
            if (!confirm(`Are you sure you want to deactivate the role "${role.role_name}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/v1/roles/${role.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        role_code: role.role_code,
                        role_name: role.role_name,
                        description: role.description,
                        is_active: false
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    this.showError(data.message || 'Failed to deactivate role');
                    return;
                }
                
                this.showSuccess('Role deactivated successfully');
                this.fetchRoles();
            } catch (error) {
                console.error('Failed to deactivate role:', error);
                this.showError('Failed to deactivate role');
            }
        },

        async activateRole(role) {
            if (!confirm(`Are you sure you want to activate the role "${role.role_name}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/v1/roles/${role.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        role_code: role.role_code,
                        role_name: role.role_name,
                        description: role.description,
                        is_active: true
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    this.showError(data.message || 'Failed to activate role');
                    return;
                }
                
                this.showSuccess('Role activated successfully');
                this.fetchRoles();
            } catch (error) {
                console.error('Failed to activate role:', error);
                this.showError('Failed to activate role');
            }
        },

        async openPermissionsModal(role) {
            this.selectedRole = role;
            this.showPermissionsModal = true;
            this.loadingPermissions = true;

            try {
                const response = await fetch(`/api/v1/roles/${role.id}/permissions`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.permissions = data.data.permissions || [];
                }
            } catch (error) {
                this.showError('Failed to load permissions');
            } finally {
                this.loadingPermissions = false;
            }
        },

        closePermissionsModal() {
            this.showPermissionsModal = false;
            this.selectedRole = null;
            this.permissions = [];
        },

        getPermission(moduleCode, permissionType) {
            const perm = this.permissions.find(p => p.module_code === moduleCode);
            return perm ? perm[permissionType] : false;
        },

        updatePermission(moduleCode, permissionType, value) {
            const existingIndex = this.permissions.findIndex(p => p.module_code === moduleCode);
            
            if (existingIndex >= 0) {
                this.permissions[existingIndex][permissionType] = value;
            } else {
                this.permissions.push({
                    module_code: moduleCode,
                    can_view: permissionType === 'can_view' ? value : false,
                    can_create: permissionType === 'can_create' ? value : false,
                    can_edit: permissionType === 'can_edit' ? value : false,
                    can_approve: permissionType === 'can_approve' ? value : false,
                    can_delete: permissionType === 'can_delete' ? value : false
                });
            }
        },

        async savePermissions() {
            this.savingPermissions = true;
            try {
                const response = await fetch(`/api/v1/roles/${this.selectedRole.id}/permissions`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ permissions: this.permissions })
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(data.message);
                    this.closePermissionsModal();
                    this.fetchRoles();
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError('Failed to save permissions');
            } finally {
                this.savingPermissions = false;
            }
        },

        showSuccess(message) {
            alert(message);
        },

        showError(message) {
            alert(message);
        }
    };
}
</script>
@endsection
