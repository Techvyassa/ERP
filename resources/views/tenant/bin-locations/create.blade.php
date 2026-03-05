@extends('tenant.layouts.app')

@section('title', 'Create Bin Location')
@section('page-title', 'Create New Bin Location')

@section('content')
<div x-data="binForm()" x-init="loadWarehouses()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Bin Location</h2>
                    <p class="text-gray-600 mt-1">Rack / Bin structural master defining physical slot locations</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Bin Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Bin Location Information</h3>
                <div class="space-y-6">
                    <!-- Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Warehouse <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.warehouse_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Warehouse</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="wh.id" x-text="wh.warehouse_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ warehouse_master(warehouse_id)</p>
                    </div>

                    <!-- Bin Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bin Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.bin_code" required maxlength="30"
                               placeholder="R01-S02-B03 (Rack-Shelf-Bin)"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Aisle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Aisle
                        </label>
                        <input type="text" x-model="form.aisle" maxlength="10"
                               placeholder="Aisle identifier"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Rack -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Rack
                        </label>
                        <input type="text" x-model="form.rack" maxlength="10"
                               placeholder="Rack identifier"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Shelf -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Shelf
                        </label>
                        <input type="text" x-model="form.shelf" maxlength="10"
                               placeholder="Shelf level"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Max Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Max Weight (kg)
                        </label>
                        <input type="number" x-model="form.max_weight_kg" min="0" step="0.01"
                               placeholder="Capacity limit in kg"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Capacity limit in kg</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Bin Location</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Bin Locations</p>
                        <p>Rack / Bin structural master defining physical slot locations within warehouses. Pure structural — no stock quantities stored here.</p>
                        <p class="mt-2 text-xs">Used in: GRN Put-Away, Material Issuance, Physical Count</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bin-locations' : '/org/' . $organization->org_slug . '/bin-locations') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Bin Location</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function binForm() {
    return {
        loading: false,
        warehouses: [],
        form: {
            warehouse_id: '',
            bin_code: '',
            aisle: '',
            rack: '',
            shelf: '',
            max_weight_kg: '',
            is_active: true
        },
        
        async loadWarehouses() {
            try {
                // TODO: Replace with actual API call
                this.warehouses = [];
            } catch (error) {
                console.error('Failed to load warehouses:', error);
            }
        },
        
        async submitForm() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Bin location creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
            } catch (error) {
                console.error('Failed to create bin location:', error);
                alert('Failed to create bin location. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
