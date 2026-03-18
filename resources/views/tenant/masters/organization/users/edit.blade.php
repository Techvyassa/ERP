@extends('tenant.layouts.organization')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div x-data="userEditForm()" x-init="loadUser(); loadDepartments(); loadRoles();">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit User</h2>
                    <p class="text-gray-600 mt-1">Update user information and permissions</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/users' : '/org/' . $organization->org_slug . '/users') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="initialLoading" class="bg-white rounded-xl shadow p-12 text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
            <p class="text-gray-600">Loading user data...</p>
        </div>

        <!-- Form -->
        <form x-show="!initialLoading" @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Employee Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.employee_code" required maxlength="20"
                               placeholder="EMP-0001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                               readonly>
                        <p class="text-xs text-gray-500 mt-1">Employee code cannot be changed</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" x-model="form.email" required
                               placeholder="user@example.com"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Login identifier (unique)</p>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.full_name" required
                               placeholder="John Doe"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Display name</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone
                        </label>
                        <input type="text" x-model="form.phone"
                               placeholder="+91 9876543210"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Contact number</p>
                    </div>
                </div>
            </div>

            <!-- Department & Role Assignment -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Department & Role Assignment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Department -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.dept_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Department</option>
                            <template x-for="dept in departments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.dept_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ department_master(dept_id)</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.role_id" required
                                @change="loadRolePermissions()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Role</option>
                            <template x-for="role in roles" :key="role.id">
                                <option :value="role.id" x-text="role.role_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ role_master(role_id)</p>
                    </div>
                </div>

                <!-- Role Permissions Preview -->
                <!-- <div x-show="form.role_id && rolePermissions.length > 0" x-transition class="mt-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Role Permissions Preview
                        </h4>
                        <p class="text-xs text-blue-700 mb-3">This user will inherit the following permissions from the selected role:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
                            <template x-for="permission in rolePermissions" :key="permission.module_code">
                                <div class="bg-white rounded-lg p-3 border border-blue-100">
                                    <div class="font-medium text-sm text-gray-900 mb-2" x-text="permission.module_code"></div>
                                    <div class="space-y-1">
                                        <div class="flex items-center text-xs">
                                            <i :class="permission.can_view ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-300'" class="w-4 mr-2"></i>
                                            <span :class="permission.can_view ? 'text-gray-700' : 'text-gray-400'">View</span>
                                        </div>
                                        <div class="flex items-center text-xs">
                                            <i :class="permission.can_create ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-300'" class="w-4 mr-2"></i>
                                            <span :class="permission.can_create ? 'text-gray-700' : 'text-gray-400'">Create</span>
                                        </div>
                                        <div class="flex items-center text-xs">
                                            <i :class="permission.can_edit ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-300'" class="w-4 mr-2"></i>
                                            <span :class="permission.can_edit ? 'text-gray-700' : 'text-gray-400'">Edit</span>
                                        </div>
                                        <div class="flex items-center text-xs">
                                            <i :class="permission.can_approve ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-300'" class="w-4 mr-2"></i>
                                            <span :class="permission.can_approve ? 'text-gray-700' : 'text-gray-400'">Approve</span>
                                        </div>
                                        <div class="flex items-center text-xs">
                                            <i :class="permission.can_delete ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-300'" class="w-4 mr-2"></i>
                                            <span :class="permission.can_delete ? 'text-gray-700' : 'text-gray-400'">Delete</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div> -->
            </div>

            <!-- Password Change (Optional) -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Change Password (Optional)</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Leave password fields empty to keep the current password
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <input type="password" x-model="form.password" minlength="8"
                               placeholder="••••••••"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password" x-model="form.password_confirmation" minlength="8"
                               placeholder="••••••••"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Re-enter new password</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active User</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Deactivate instead of delete</p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/users' : '/org/' . $organization->org_slug . '/users') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Update User</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function userEditForm() {
    return {
        loading: false,
        initialLoading: true,
        departments: [],
        roles: [],
        rolePermissions: [],
        userId: {{ $userId }},
        form: {
            employee_code: '',
            email: '',
            full_name: '',
            phone: '',
            dept_id: '',
            role_id: '',
            password: '',
            password_confirmation: '',
            is_active: true
        },
        
        async loadUser() {
            try {
                // TODO: Replace with actual API call
                // const response = await fetch(`/api/v1/users/${this.userId}`);
                // const data = await response.json();
                // this.form = data.data;
                
                // Mock data for now
                setTimeout(async () => {
                    this.form = {
                        employee_code: 'EMP-001',
                        email: 'user@example.com',
                        full_name: 'John Doe',
                        phone: '+91 9876543210',
                        dept_id: '',
                        role_id: '1', // Mock role ID
                        password: '',
                        password_confirmation: '',
                        is_active: true
                    };
                    this.initialLoading = false;
                    
                    // Load permissions for the current role
                    if (this.form.role_id) {
                        await this.loadRolePermissions();
                    }
                }, 500);
            } catch (error) {
                console.error('Failed to load user:', error);
                alert('Failed to load user data');
                this.initialLoading = false;
            }
        },
        
        async loadDepartments() {
            try {
                // TODO: Replace with actual API call
                // const response = await fetch('/api/v1/departments');
                // const data = await response.json();
                // this.departments = data.data;
                this.departments = [];
            } catch (error) {
                console.error('Failed to load departments:', error);
            }
        },
        
        async loadRoles() {
            try {
                // TODO: Replace with actual API call
                // const response = await fetch('/api/v1/roles');
                // const data = await response.json();
                // this.roles = data.data;
                this.roles = [];
            } catch (error) {
                console.error('Failed to load roles:', error);
            }
        },
        
        async loadRolePermissions() {
            if (!this.form.role_id) {
                this.rolePermissions = [];
                return;
            }
            
            try {
                // TODO: Replace with actual API call
                // const response = await fetch(`/api/v1/roles/${this.form.role_id}/permissions`);
                // const data = await response.json();
                // this.rolePermissions = data.data.permissions;
                
                // Mock data for demonstration
                this.rolePermissions = [
                    { module_code: 'PR', can_view: true, can_create: true, can_edit: true, can_approve: false, can_delete: false },
                    { module_code: 'PO', can_view: true, can_create: true, can_edit: true, can_approve: true, can_delete: false },
                    { module_code: 'GRN', can_view: true, can_create: false, can_edit: false, can_approve: false, can_delete: false },
                    { module_code: 'INVENTORY', can_view: true, can_create: true, can_edit: true, can_approve: false, can_delete: true },
                    { module_code: 'USER_MGMT', can_view: true, can_create: false, can_edit: false, can_approve: false, can_delete: false },
                ];
            } catch (error) {
                console.error('Failed to load role permissions:', error);
                this.rolePermissions = [];
            }
        },
        
        async submitForm() {
            // Validate password confirmation if password is provided
            if (this.form.password && this.form.password !== this.form.password_confirmation) {
                alert('Passwords do not match!');
                return;
            }
            
            this.loading = true;
            try {
                // Prepare form data
                const formData = { ...this.form };
                
                // Remove password fields if not changing password
                if (!formData.password) {
                    delete formData.password;
                    delete formData.password_confirmation;
                }
                
                // TODO: Replace with actual API call
                // const response = await fetch(`/api/v1/users/${this.userId}`, {
                //     method: 'PUT',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify(formData)
                // });
                
                alert('User update - Coming soon\n\nData to be submitted:\n' + JSON.stringify(formData, null, 2));
                // window.location.href = '/users';
            } catch (error) {
                console.error('Failed to update user:', error);
                alert('Failed to update user. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
