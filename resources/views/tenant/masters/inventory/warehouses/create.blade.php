@extends('tenant.layouts.inventory')

@section('title', 'Create Warehouse')
@section('page-title', 'Create New Warehouse')

@section('content')
<div x-data="warehouseForm()" x-init="loadUsers()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Warehouse</h2>
                    <p class="text-gray-600 mt-1">Physical storage location master</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Warehouse Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Warehouse Information</h3>
                <div class="space-y-6">
                    <!-- Warehouse Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.warehouse_code" required maxlength="20"
                               placeholder="WH-001, WH-002"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Warehouse Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.warehouse_name" required maxlength="100"
                               placeholder="Masala RM Store"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Warehouse Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.warehouse_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="RM">RM - Raw Material</option>
                            <option value="FG">FG - Finished Goods</option>
                            <option value="PKG">PKG - Packaging</option>
                            <option value="REJECTION">REJECTION - Rejection</option>
                            <option value="WIP">WIP - Work in Progress</option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Physical Address
                        </label>
                        <textarea x-model="form.address" rows="3"
                                  placeholder="Enter warehouse physical address..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Physical address</p>
                    </div>

                    <!-- Incharge User -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Incharge User
                        </label>
                        <select x-model="form.incharge_user_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select User</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.full_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ users(user_id)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Warehouse</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Warehouse Master</p>
                        <p>Physical storage location master. Supports multiple warehouse types (RM, FG, Packaging, Rejection).</p>
                        <p class="mt-2 text-xs">Used in: Material Master, GRN, Stock Ledger, Inventory Transfer</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Warehouse</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function warehouseForm() {
    return {
        loading: false,
        users: [],
        form: {
            warehouse_code: '',
            warehouse_name: '',
            warehouse_type: '',
            address: '',
            incharge_user_id: '',
            is_active: true
        },
        
        async loadUsers() {
            try {
                // TODO: Replace with actual API call
                this.users = [];
            } catch (error) {
                console.error('Failed to load users:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Warehouse creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create warehouse:', error);
                alert('Failed to create warehouse. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
