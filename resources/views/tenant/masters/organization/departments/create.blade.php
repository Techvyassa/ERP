@extends('tenant.layouts.organization')

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
                            Department Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
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
                                               placeholder="DEPT"
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
                                           x-model="form.dept_code"
                                           :required="!form.auto_generate_code"
                                           maxlength="20"
                                           placeholder="DEPT-0001"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Generated department code (auto-updates from prefix and number)</p>
                                </div>
                            </div>
                            
                            <!-- Auto-generate info -->
                            <div x-show="form.auto_generate_code" x-transition>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-magic text-green-600 mr-2"></i>
                                        <div class="text-sm text-green-800">
                                            <p class="font-medium">Auto-generation enabled</p>
                                            <p class="text-xs mt-1">Code will be generated based on department type: DEPT-XXXX (sequential)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            is_active: true,
            auto_generate_code: false,
            manual_prefix: 'DEPT',
            manual_number: '0001'
        },
        
        async loadParentDepartments() {
            try {
                const response = await fetch('/api/v1/departments', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.parentDepartments = data.data.departments;
                } else {
                    console.error('Failed to load parent departments:', data.message);
                }
            } catch (error) {
                console.error('Failed to load parent departments:', error);
            }
        },
        
        handleAutoGenerateChange() {
            if (this.form.auto_generate_code) {
                this.form.dept_code = ''; // Clear field when auto-generate is checked
                this.form.manual_prefix = ''; // Clear manual fields
                this.form.manual_number = '';
            } else {
                // Set default manual values when switching to manual
                this.form.manual_prefix = 'DEPT';
                this.form.manual_number = '0001';
                this.updateManualCode();
            }
        },
        
        updateManualCode() {
            if (this.form.manual_prefix && this.form.manual_number) {
                this.form.dept_code = `${this.form.manual_prefix}-${this.form.manual_number}`;
            } else {
                this.form.dept_code = '';
            }
        },
        
        async submitForm() {
            // Validate department code if not auto-generated
            if (!this.form.auto_generate_code && !this.form.dept_code) {
                alert('Department Code is required when auto-generation is disabled!');
                return;
            }
            
            this.loading = true;
            try {
                // Prepare form data
                const formData = {
                    dept_name: this.form.dept_name,
                    parent_dept_id: this.form.parent_dept_id || null,
                    cost_center_code: this.form.cost_center_code,
                    is_active: this.form.is_active
                };
                
                // Add dept_code if not auto-generated
                if (!this.form.auto_generate_code) {
                    formData.dept_code = this.form.dept_code;
                }
                
                const response = await fetch('/api/v1/departments', {
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
                    alert(data.message || 'Department created successfully!');
                    window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/departments" : "/org/" . $organization->org_slug . "/departments") }}';
                } else {
                    alert(data.message || 'Failed to create department. Please try again.');
                }
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
