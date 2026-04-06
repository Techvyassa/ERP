@extends('tenant.layouts.organization')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

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
                               placeholder="Full Name"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Display name</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone
                        </label>
                        <input type="text" x-model="form.phone"
                               placeholder="+91 0000000000"
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
                        <select x-model="form.dept_id" 
                                @change="loadDepartmentRoles(form.dept_id)"
                                required
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
                                :disabled="!form.dept_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="" x-text="form.dept_id ? 'Select Role' : 'Select a department first'"></option>
                            <template x-for="role in roles" :key="role.role_id || role.id">
                                <option :value="role.role_id || role.id" x-text="role.role_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Roles filtered by selected department</p>
                    </div>
                </div>

                <!-- Role Permissions Preview -->
                <div x-show="form.role_id && rolePermissions.length > 0" x-transition class="mt-6">
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
                </div>
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
        userId: null,
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
            // Get user ID from URL
            const urlParts = window.location.pathname.split('/');
            this.userId = urlParts[urlParts.length - 2]; // Get ID before /edit
            
            if (!this.userId || isNaN(this.userId)) {
                console.error('Invalid user ID:', this.userId);
                alert('Invalid user ID');
                this.initialLoading = false;
                return;
            }
            
            try {
                const response = await fetch(`{{ url('api/v1/users') }}/${this.userId}`, {
                    credentials: 'same-origin',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load user');
                }
                
                const data = await response.json();
                console.log('User API Response:', data); // Debug log
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load user');
                }
                
                // Extract user from nested response structure
                const user = data.data.user;
                console.log('Parsed user data:', user); // Debug log
                
                // Build full name from first_name and last_name
                let fullName = '';
                if (user.first_name && user.last_name) {
                    fullName = `${user.first_name} ${user.last_name}`.trim();
                } else if (user.first_name) {
                    fullName = user.first_name;
                } else if (user.full_name) {
                    fullName = user.full_name;
                } else if (user.name) {
                    fullName = user.name;
                }
                
                this.form = {
                    employee_code: user.employee_code || user.emp_code || '',
                    email: user.email || '',
                    full_name: fullName || '',
                    phone: user.phone || user.mobile || '',
                    dept_id: user.dept_id || user.department_id || (user.department ? user.department.id : ''),
                    role_id: user.role_id || (user.role ? user.role.id : ''),
                    password: '',
                    password_confirmation: '',
                    is_active: user.is_active !== undefined ? user.is_active : true
                };
                
                console.log('Form data after loading:', this.form); // Debug log
                
                this.initialLoading = false;
                
                // Load department roles if department is selected
                if (this.form.dept_id) {
                    await this.loadDepartmentRoles(this.form.dept_id);
                }
                
                // Load permissions for the current role
                if (this.form.role_id) {
                    await this.loadRolePermissions();
                }
            } catch (error) {
                console.error('Failed to load user:', error);
                alert('Failed to load user data: ' + error.message);
                this.initialLoading = false;
            }
        },
        
        async loadDepartments() {
            try {
                const response = await fetch('{{ url("api/v1/departments") }}', {
                    credentials: 'same-origin',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Departments API Response:', data); // Debug log
                    
                    if (data.success && data.data) {
                        this.departments = Array.isArray(data.data) ? data.data : (data.data.departments || []);
                    } else {
                        this.departments = [];
                    }
                    console.log('Loaded departments:', this.departments); // Debug log
                }
            } catch (error) {
                console.error('Failed to load departments:', error);
                this.departments = [];
            }
        },
        
        async loadRoles() {
            // Roles are now loaded per-department via loadDepartmentRoles
            // This is kept for compatibility but does nothing on init
        },

        async loadDepartmentRoles(deptId) {
            this.roles = [];
            if (!deptId) {
                this.form.role_id = '';
                this.rolePermissions = [];
                return;
            }
            
            try {
                const response = await fetch(`{{ url('api/v1/departments') }}/${deptId}/roles`, {
                    credentials: 'same-origin',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Department roles API Response:', data); // Debug log
                    
                    if (data.success && data.data && data.data.roles) {
                        this.roles = data.data.roles;
                    } else {
                        this.roles = [];
                    }
                    console.log('Loaded roles for department:', this.roles); // Debug log
                    
                    // If current role_id is not in the new roles list, clear it
                    if (this.form.role_id && !this.roles.find(r => (r.role_id || r.id) == this.form.role_id)) {
                        this.form.role_id = '';
                        this.rolePermissions = [];
                    }
                }
            } catch (error) {
                console.error('Failed to load department roles:', error);
                this.roles = [];
            }
        },
        
        async loadRolePermissions() {
            if (!this.form.role_id) {
                this.rolePermissions = [];
                return;
            }
            
            try {
                const response = await fetch(`{{ url('api/v1/roles') }}/${this.form.role_id}/permissions`, {
                    credentials: 'same-origin',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Role permissions API Response:', data); // Debug log
                    
                    if (data.success && data.data) {
                        this.rolePermissions = data.data.permissions || [];
                    } else {
                        this.rolePermissions = [];
                    }
                }
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
                // Split full name into first and last name
                const nameParts = this.form.full_name.trim().split(' ');
                const firstName = nameParts[0] || this.form.full_name;
                const lastName = nameParts.slice(1).join(' ') || '';
                
                // Prepare form data
                const formData = {
                    employee_code: this.form.employee_code,
                    email: this.form.email,
                    first_name: firstName,
                    last_name: lastName,
                    phone: this.form.phone || null,
                    dept_id: parseInt(this.form.dept_id),
                    role_id: parseInt(this.form.role_id),
                    is_active: this.form.is_active
                };
                
                // Add password fields if changing password
                if (this.form.password) {
                    formData.password = this.form.password;
                    formData.password_confirmation = this.form.password_confirmation;
                }
                
                console.log('Submitting form data:', formData); // Debug log
                
                const response = await fetch(`{{ url('api/v1/users') }}/${this.userId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                console.log('Update response:', data); // Debug log
                
                if (!response.ok) {
                    if (data.error && data.error.details) {
                        const errors = Object.entries(data.error.details)
                            .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                            .join('\n');
                        alert('Validation failed:\n\n' + errors);
                    } else {
                        alert(data.message || 'Failed to update user');
                    }
                    return;
                }
                
                if (data.success) {
                    alert(data.message || 'User updated successfully!');
                    window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/users" : "/org/" . $organization->org_slug . "/users") }}';
                } else {
                    alert(data.message || 'Failed to update user');
                }
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
