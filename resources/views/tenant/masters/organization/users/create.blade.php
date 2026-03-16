@extends('tenant.layouts.organization')

@section('title', 'Create User')
@section('page-title', 'Create New User')

@section('content')
<div x-data="userForm()" x-init="loadDepartments(); loadRoles();">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New User</h2>
                    <p class="text-gray-600 mt-1">Add a new system user with department and role assignment</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/users' : '/org/' . $organization->org_slug . '/users') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Employee Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
                        </label>
                        <div class="space-y-3">
                            <!-- Auto-generate option -->
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" 
                                       x-model="form.auto_generate_code"
                                       @change="handleAutoGenerateChange()"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label class="text-sm text-gray-700 cursor-pointer">Auto-generate code</label>
                            </div>
                            
                            <!-- Manual code generation -->
                            <div x-show="!form.auto_generate_code" x-transition>
                                <div class="flex items-center space-x-2">
                                    <!-- Manual Prefix -->
                                    <div class="w-32">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
                                        <input type="text" 
                                               x-model="form.manual_prefix"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="EMP"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    
                                    <!-- Separator -->
                                    <div class="flex items-center pb-6">
                                        <span class="text-gray-500 font-medium">-</span>
                                    </div>
                                    
                                    <!-- Sequential Number -->
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                                        <input type="text" 
                                               x-model="form.manual_number"
                                               @input="updateManualCode()"
                                               maxlength="10"
                                               placeholder="0001"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                </div>
                                
                                <!-- Generated Code Display -->
                                <div class="mt-2">
                                    <input type="text" 
                                           x-model="form.employee_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="20"
                                           placeholder="EMP-0001"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Generated employee code (auto-updates from prefix and number)</p>
                                </div>
                            </div>
                            
                            <!-- Auto-generate info -->
                            <div x-show="form.auto_generate_code" x-transition>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-magic text-green-600 mr-2"></i>
                                        <div class="text-sm text-green-800">
                                            <p class="font-medium">Auto-generation enabled</p>
                                            <p class="text-xs mt-1">Code will be generated based on department: EMP-XXXX (sequential)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                :disabled="!form.dept_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">Select Role</option>
                            <template x-for="role in roles" :key="role.role_id || role.id">
                                <option :value="role.role_id || role.id" x-text="role.role_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <span x-show="!form.dept_id" class="text-orange-600">Select department first</span>
                            <span x-show="form.dept_id">→ role_master(role_id) - filtered by department</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Security</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" x-model="form.password" required minlength="8"
                               placeholder="••••••••"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters (bcrypt hash)</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" x-model="form.password_confirmation" required minlength="8"
                               placeholder="••••••••"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Re-enter password</p>
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
                    <span x-show="!loading">Create User</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function userForm() {
    return {
        loading: false,
        departments: [],
        roles: [],
        allRoles: [], // Store all roles for reference
        form: {
            employee_code: '',
            email: '',
            full_name: '',
            phone: '',
            dept_id: '',
            role_id: '',
            password: '',
            password_confirmation: '',
            is_active: true,
            auto_generate_code: false,
            manual_prefix: 'EMP',
            manual_number: '0001'
        },
        
        async loadDepartments() {
            try {
                const response = await fetch('/api/v1/departments', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.departments = data.data.departments;
                } else {
                    console.error('Failed to load departments:', data.message);
                }
            } catch (error) {
                console.error('Failed to load departments:', error);
            }
        },
        
        async loadRoles() {
            try {
                const response = await fetch('/api/v1/roles', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.allRoles = data.data.roles;
                    // Initially show all roles until department is selected
                    this.roles = data.data.roles;
                } else {
                    console.error('Failed to load roles:', data.message);
                }
            } catch (error) {
                console.error('Failed to load roles:', error);
            }
        },
        
        async loadDepartmentRoles(deptId) {
            if (!deptId) {
                // If no department selected, show all roles
                this.roles = this.allRoles;
                return;
            }
            
            try {
                const response = await fetch(`/api/v1/departments/${deptId}/roles`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.roles = data.data.roles;
                    // Reset role selection if current role is not in the new list
                    if (this.form.role_id && !this.roles.find(r => r.role_id == this.form.role_id)) {
                        this.form.role_id = '';
                    }
                } else {
                    console.error('Failed to load department roles:', data.message);
                    // Fallback to all roles
                    this.roles = this.allRoles;
                }
            } catch (error) {
                console.error('Failed to load department roles:', error);
                // Fallback to all roles
                this.roles = this.allRoles;
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.employee_code = ''; // Clear field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = 'EMP';
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.employee_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.employee_code = '';
            }
        },
        
        async submitForm() {
            if (this.form.password !== this.form.password_confirmation) {
                alert('Passwords do not match!');
                return;
            }
            
            // Validate employee code if not auto-generated
            if (!this.form.auto_generate_code && !this.form.employee_code) {
                alert('Employee Code is required when auto-generation is disabled!');
                return;
            }
            
            // Validate required fields
            if (!this.form.dept_id) {
                alert('Please select a department!');
                return;
            }
            
            if (!this.form.role_id) {
                alert('Please select a role!');
                return;
            }
            
            this.loading = true;
            try {
                // Split full name into first and last name
                const nameParts = this.form.full_name.trim().split(' ');
                const firstName = nameParts[0] || this.form.full_name;
                const lastName = nameParts.slice(1).join(' ') || firstName;
                
                // Prepare form data
                const formData = {
                    email: this.form.email,
                    first_name: firstName,
                    last_name: lastName,
                    phone: this.form.phone || null,
                    dept_id: parseInt(this.form.dept_id),
                    role_id: parseInt(this.form.role_id),
                    password: this.form.password,
                    is_active: this.form.is_active
                };
                
                // Add employee code - if auto-generate is enabled, generate a temporary one
                if (this.form.auto_generate_code) {
                    // Generate a temporary code - backend should handle this properly
                    formData.employee_code = 'AUTO-' + Date.now();
                } else {
                    formData.employee_code = this.form.employee_code;
                }
                
                const response = await fetch('/api/v1/users', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.success) {
                    alert(data.message || 'User created successfully!');
                    window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/users" : "/org/" . $organization->org_slug . "/users") }}';
                } else {
                    // Show detailed validation errors if available
                    if (data.error && data.error.details) {
                        const errors = Object.entries(data.error.details)
                            .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                            .join('\n');
                        alert('Validation failed:\n\n' + errors);
                    } else {
                        alert(data.message || 'Failed to create user. Please try again.');
                    }
                }
            } catch (error) {
                console.error('Failed to create user:', error);
                alert('Failed to create user. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
