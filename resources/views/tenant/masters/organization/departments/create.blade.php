@extends('tenant.layouts.organization')

@section('title', 'Create Department')
@section('page-title', 'Create New Department')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="departmentForm()" x-init="loadDropdowns()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Department</h2>
                    <p class="text-gray-600 mt-1">Define business department with cost centre mapping</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Department Information</h3>
                <div class="space-y-6">
                    <!-- Department Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.dept_name" @input="generateDeptCode" required maxlength="100"
                               placeholder="Production Department"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Full display name (max 100 chars)</p>
                    </div>

                    <!-- Department Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department Code <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Auto-generate checkbox -->
                        <div class="mb-3">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="autoGenerate" @change="toggleAutoGenerate"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">Auto-generate code from department name</span>
                            </label>
                        </div>

                        <input type="text" x-model="form.dept_code" required maxlength="20"
                               :readonly="autoGenerate"
                               :class="autoGenerate ? 'bg-gray-50 text-gray-700' : ''"
                               placeholder="PROD, HR, FINANCE"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1" x-show="!autoGenerate">e.g. PROD, HR, FINANCE (max 20 chars)</p>
                        <p class="text-xs text-gray-500 mt-1" x-show="autoGenerate">Auto-generated from department name (uncheck to edit manually)</p>
                    </div>

                    <!-- Parent Department -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Parent Department
                        </label>
                        <select x-model="form.parent_dept_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">None (Top Level)</option>
                            <template x-for="dept in parentDepartments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.dept_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Self-ref → department_master(dept_id) for hierarchical support</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.role_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Role</option>
                            <template x-for="role in roles" :key="role.id">
                                <option :value="role.id" x-text="role.role_code + ' - ' + role.role_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ role_master(role_id) for department role assignment</p>
                    </div>

                    <!-- Cost Center Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cost Center Code
                        </label>
                        <input type="text" x-model="form.cost_center_code" maxlength="20"
                               placeholder="CC-001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Link to finance cost centre (max 20 chars)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Department</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Soft delete flag</p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Department</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function departmentForm() {
    return {
        loading: false,
        parentDepartments: [],
        roles: [],
        autoGenerate: true, // Default to auto-generate enabled
        form: {
            dept_code: '',
            dept_name: '',
            parent_dept_id: '',
            role_id: '',
            cost_center_code: '',
            is_active: true
        },
        
        toggleAutoGenerate() {
            if (this.autoGenerate) {
                this.generateDeptCode();
            }
        },
        
        generateDeptCode() {
            if (this.autoGenerate && this.form.dept_name) {
                // Convert department name to abbreviated uppercase code format
                let words = this.form.dept_name
                    .trim()
                    .toUpperCase()
                    .replace(/[^A-Z0-9\s]/g, '') // Remove special characters
                    .split(/\s+/); // Split by spaces
                
                let code = '';
                
                if (words.length === 1) {
                    // Single word: take first 6 characters
                    code = words[0].substring(0, 6);
                } else if (words.length === 2) {
                    // Two words: take first 3-4 chars from each based on length
                    let firstWord = words[0];
                    let secondWord = words[1];
                    
                    if (firstWord.length <= 3) {
                        // First word is short, take all + first 3 from second
                        code = firstWord + secondWord.substring(0, 3);
                    } else {
                        // Both words longer, take 3 from each
                        code = firstWord.substring(0, 3) + secondWord.substring(0, 3);
                    }
                } else {
                    // Multiple words: take 2 chars from each word
                    code = words.map(word => {
                        return word.length <= 2 ? word : word.substring(0, 2);
                    }).join('').substring(0, 8);
                }
                
                this.form.dept_code = code;
            }
        },
        
        async loadDropdowns() {
            try {
                // Load parent departments
                const deptResponse = await fetch('/api/v1/departments', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const deptData = await deptResponse.json();
                if (deptData.success) {
                    this.parentDepartments = deptData.data.departments;
                } else {
                    console.error('Failed to load parent departments:', deptData.message);
                }

                // Load roles
                const roleResponse = await fetch('/api/v1/roles', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const roleData = await roleResponse.json();
                if (roleData.success) {
                    this.roles = roleData.data.roles || [];
                } else {
                    console.error('Failed to load roles:', roleData.message);
                }
            } catch (error) {
                console.error('Failed to load dropdowns:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // Prepare form data
                const formData = {
                    dept_code: this.form.dept_code,
                    dept_name: this.form.dept_name,
                    parent_dept_id: this.form.parent_dept_id || null,
                    role_id: parseInt(this.form.role_id),
                    cost_center_code: this.form.cost_center_code,
                    is_active: this.form.is_active
                };
                
                const response = await fetch('/api/v1/departments', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (!response.ok) {
                    alert(data.message || 'Failed to create department. Please try again.');
                    return;
                }
                
                alert(data.message || 'Department created successfully!');
                window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/departments" : "/org/" . $organization->org_slug . "/departments") }}';
            } catch (error) {
                console.error('Failed to create department:', error);
                alert('Failed to create department. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
