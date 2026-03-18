@extends('tenant.layouts.organization')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="departmentEditForm()" x-init="loadDepartment(); loadParentDepartments()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Department</h2>
                    <p class="text-gray-600 mt-1">Update department information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="initialLoading" class="bg-white rounded-xl shadow p-12 text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
            <p class="text-gray-600">Loading department data...</p>
        </div>

        <!-- Form -->
        <form x-show="!initialLoading" @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Department Information</h3>
                <div class="space-y-6">
                    <!-- Department Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.dept_code" required maxlength="20"
                            placeholder="DEPT-0001"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                            readonly>
                        <p class="text-xs text-gray-500 mt-1">Department code cannot be changed</p>
                    </div>

                    <!-- Department Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.dept_name" required maxlength="100"
                            placeholder="Production Department"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Full display name (max 100 chars)</p>
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
                    <span x-show="!loading">Update Department</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function departmentEditForm() {
        return {
            loading: false,
            initialLoading: true,
            parentDepartments: [],
            departmentId: null,
            form: {
                dept_code: '',
                dept_name: '',
                parent_dept_id: '',
                cost_center_code: '',
                is_active: true
            },

            async loadDepartment() {
                // Get department ID from URL
                const urlParts = window.location.pathname.split('/');
                this.departmentId = urlParts[urlParts.length - 2]; // Get ID before /edit
                
                if (!this.departmentId || isNaN(this.departmentId)) {
                    console.error('Invalid department ID:', this.departmentId);
                    alert('Invalid department ID');
                    this.initialLoading = false;
                    return;
                }
                
                try {
                    const response = await fetch(`/api/v1/departments/${this.departmentId}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to load department');
                    }
                    
                    const data = await response.json();
                    const dept = data.data?.department || data.data;
                    
                    this.form = {
                        dept_code: dept.dept_code || '',
                        dept_name: dept.dept_name || '',
                        parent_dept_id: dept.parent_dept_id || '',
                        cost_center_code: dept.cost_center_code || '',
                        is_active: dept.is_active !== undefined ? dept.is_active : true
                    };
                    
                    this.initialLoading = false;
                } catch (error) {
                    console.error('Failed to load department:', error);
                    alert('Failed to load department data');
                    this.initialLoading = false;
                }
            },

            async loadParentDepartments() {
                try {
                    const response = await fetch('/api/v1/departments', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        // API returns data directly as array, not nested
                        const allDepts = Array.isArray(data.data) ? data.data : (data.data?.departments || []);
                        // Filter out current department to prevent self-reference
                        this.parentDepartments = allDepts.filter(dept => dept.id != this.departmentId);
                    }
                } catch (error) {
                    console.error('Failed to load parent departments:', error);
                }
            },

            async submitForm() {
                this.loading = true;
                try {
                    const response = await fetch(`/api/v1/departments/${this.departmentId}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.form)
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        alert(data.message || 'Failed to update department');
                        return;
                    }
                    
                    alert('Department updated successfully!');
                    window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/departments" : "/org/" . $organization->org_slug . "/departments") }}';
                } catch (error) {
                    console.error('Failed to update department:', error);
                    alert('Failed to update department. Please try again.');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection