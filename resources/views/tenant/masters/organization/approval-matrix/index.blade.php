@extends('tenant.layouts.organization')

@section('title', 'Approval Matrix')
@section('page-title', 'Approval Matrix Master')

@section('content')
<div x-data="approvalData()" x-init="loadData()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Approval Matrix</h2>
                <p class="text-gray-600 mt-1">Manage approval workflows and hierarchies</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/approval-matrix/create' : '/org/' . $organization->org_slug . '/approval-matrix/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Rule
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select x-model="filters.document_type" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Document Types</option>
                <option value="PR">Purchase Requisition</option>
                <option value="PO">Purchase Order</option>
                <option value="PAYMENT">Payment</option>
            </select>
            <input type="number" x-model="filters.level" @input="loadData" placeholder="Filter by level..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.is_active" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Range</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approver Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA (Hours)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading rules...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-sitemap text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No approval rules found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="{'bg-blue-100 text-blue-800': item.document_type === 'PR', 'bg-green-100 text-green-800': item.document_type === 'PO', 'bg-yellow-100 text-yellow-800': item.document_type === 'PAYMENT'}" x-text="item.document_type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.level"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">₹<span x-text="item.min_amount"></span> - <span x-text="item.max_amount || '∞'"></span></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="item.approver_role_name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.sla_hours || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function approvalData() {
    return {
        items: [], loading: false, filters: { document_type: '', level: '', is_active: '' },
        async loadData() { this.loading = true; try { this.items = []; } catch (e) { alert('Failed to load approval rules.'); } finally { this.loading = false; } },
        resetFilters() { this.filters = { document_type: '', level: '', is_active: '' }; this.loadData(); },
        openCreateModal() { alert('Create approval rule - Coming soon'); },
        edit(item) { alert('Edit approval rule - Coming soon'); },
        async deleteItem(item) { if (confirm('Delete this approval rule?')) { alert('Delete functionality - Coming soon'); } }
    }
}
</script>
@endsection
