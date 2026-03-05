@extends('tenant.layouts.organization')

@section('title', 'Create Role')
@section('page-title', 'Create New Role')

@section('content')
<div x-data="roleForm()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Role</h2>
                    <p class="text-gray-600 mt-1">Define a named system role for user permissions</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/roles' : '/org/' . $organization->org_slug . '/roles') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Role Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Role Information</h3>
                <div class="space-y-6">
                    <!-- Role Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.role_code" required maxlength="30"
                               placeholder="ADMIN, BUYER, QC_INSP"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">e.g. ADMIN, BUYER, QC_INSP (max 30 chars)</p>
                    </div>

                    <!-- Role Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.role_name" required maxlength="100"
                               placeholder="Administrator"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Human-readable label (max 100 chars)</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea x-model="form.description" rows="4"
                                  placeholder="Describe the role's purpose and responsibilities..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Role description (optional)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Role</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Roles</p>
                        <p>Roles replace hardcoded admin/user strings in the users table. After creating a role, you can assign permissions to it and then assign users to this role.</p>
                        <p class="mt-2 text-xs">Used in: Users, Permissions, Approval Matrix</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/roles' : '/org/' . $organization->org_slug . '/roles') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Role</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function roleForm() {
    return {
        loading: false,
        form: {
            role_code: '',
            role_name: '',
            description: '',
            is_active: true
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Role creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
                // window.location.href = '/roles';
            } catch (error) {
                console.error('Failed to create role:', error);
                alert('Failed to create role. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
