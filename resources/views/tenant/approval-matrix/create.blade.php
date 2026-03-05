@extends('tenant.layouts.app')

@section('title', 'Create Approval Rule')
@section('page-title', 'Create Approval Matrix Rule')

@section('content')
<div x-data="approvalForm()" x-init="loadRoles()">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create Approval Rule</h2>
                    <p class="text-gray-600 mt-1">Configure approval thresholds per document type</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/approval-matrix' : '/org/' . $organization->org_slug . '/approval-matrix') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
            <!-- Document Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Document Configuration</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Document Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Document Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.document_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Document Type</option>
                            <option value="PR">PR - Purchase Requisition</option>
                            <option value="PO">PO - Purchase Order</option>
                            <option value="PAYMENT">PAYMENT - Payment</option>
                            <option value="DN">DN - Delivery Note</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">PR / PO / PAYMENT / DN</p>
                    </div>

                    <!-- Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Approval Level <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.level" required min="1" max="10"
                               placeholder="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">1 = first approver, 2 = second...</p>
                    </div>
                </div>
            </div>

            <!-- Amount Thresholds -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Amount Thresholds (INR)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Minimum Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Amount <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.min_amount" required min="0" step="0.01"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Threshold lower bound (INR)</p>
                    </div>

                    <!-- Maximum Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Amount
                        </label>
                        <input type="number" x-model="form.max_amount" min="0" step="0.01"
                               placeholder="Leave empty for no upper limit"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">NULL means no upper limit</p>
                    </div>
                </div>
            </div>

            <!-- Approver Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Approver Configuration</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Approver Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Approver Role <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.approver_role_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Role</option>
                            <template x-for="role in roles" :key="role.id">
                                <option :value="role.id" x-text="role.role_name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">→ role_master(role_id)</p>
                    </div>

                    <!-- SLA Hours -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SLA Hours <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.sla_hours" required min="1" max="720"
                               placeholder="24"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Escalation SLA in hours (e.g., 24)</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active Rule</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Active flag</p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">About Approval Matrix</p>
                        <p>Configurable approval thresholds per document type. Eliminates hardcoded approval logic.</p>
                        <p class="mt-2 text-xs">Used in: PR Approval, PO Approval, Payment Approval workflow engine</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/approval-matrix' : '/org/' . $organization->org_slug . '/approval-matrix') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Create Rule</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function approvalForm() {
    return {
        loading: false,
        roles: [],
        form: {
            document_type: '',
            level: 1,
            min_amount: 0,
            max_amount: '',
            approver_role_id: '',
            sla_hours: 24,
            is_active: true
        },
        
        async loadRoles() {
            try {
                // TODO: Replace with actual API call
                this.roles = [];
            } catch (error) {
                console.error('Failed to load roles:', error);
            }
        },
        
        async submitForm() {
            // Validate amount range
            if (this.form.max_amount && parseFloat(this.form.max_amount) <= parseFloat(this.form.min_amount)) {
                alert('Maximum amount must be greater than minimum amount!');
                return;
            }
            
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                alert('Approval rule creation - Coming soon\n\nData to be submitted:\n' + JSON.stringify(this.form, null, 2));
                // window.location.href = '/approval-matrix';
            } catch (error) {
                console.error('Failed to create approval rule:', error);
                alert('Failed to create approval rule. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
