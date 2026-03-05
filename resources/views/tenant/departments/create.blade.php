@extends('tenant.layouts.app')

@section('title', 'Create Department')
@section('page-title', 'Create New Department')

@section('content')
<div x-data="departmentForm()" x-init="loadParentDepartments()">
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
                    <!-- Department Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Department Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.dept_code" required maxlength="20"
                               placeholder="PROD, PURCH, QC"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">e.g. PROD, PURCH, QC (max 20 chars)</p>
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
        form: {
            dept_code: '',
            dept_name: '',
            parent_dept_id: '',
            cost_center_code: '',
            is_active: true
        },
        
        async loadParentDepartments() {
            try {
                // TODO: Replace with actual API call
                this.parentDepartments = [];
            } catch (error) {
                console.error('Failed to load parent departments:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Department creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
                // window.location.href = '/departments';
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
