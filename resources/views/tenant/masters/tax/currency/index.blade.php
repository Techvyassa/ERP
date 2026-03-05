@extends('tenant.layouts.tax')

@section('title', 'Currency')
@section('page-title', 'Currency Master')

@section('content')
<div x-data="currencyData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Currency Master</h2>
                <p class="text-gray-600 mt-1">Manage currencies and exchange rates</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Currency
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.search" @input="loadData" placeholder="Search by code or name..." 
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

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exchange Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Currency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading currencies...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-dollar-sign text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No currencies found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.currency_code"></span></td>
                            <td class="px-6 py-4"><span class="text-sm text-gray-900" x-text="item.currency_name"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.currency_symbol"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.exchange_rate"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" :class="item.is_base_currency ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'" x-text="item.is_base_currency ? 'Base' : 'Foreign'"></span>
                            </td>
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

    <!-- Create/Edit Modal -->
    <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900" x-text="editMode ? 'Edit Currency' : 'Add Currency'"></h3>
            </div>
            <form @submit.prevent="saveItem">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Code *</label>
                        <input type="text" x-model="formData.currency_code" required maxlength="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., USD, EUR, INR">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Name *</label>
                        <input type="text" x-model="formData.currency_name" required maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., US Dollar">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol *</label>
                        <input type="text" x-model="formData.currency_symbol" required maxlength="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., $, €, ₹">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Exchange Rate *</label>
                        <input type="number" x-model="formData.exchange_rate" required step="0.0001" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., 1.0000">
                        <p class="text-xs text-gray-500 mt-1">Rate relative to base currency</p>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="formData.is_base_currency" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Set as Base Currency</span>
                        </label>
                    </div>
                    <div x-show="editMode">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="formData.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <span x-text="editMode ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function currencyData() {
    return {
        items: [], loading: false, showModal: false, editMode: false,
        filters: { search: '', is_active: '' },
        formData: { currency_code: '', currency_name: '', currency_symbol: '', exchange_rate: 1, is_base_currency: false, is_active: true },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.is_active !== '') params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/currencies?${params}`, {
                    headers: { 
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    }
                });
                const data = await response.json();
                if (data.success) this.items = data.data.currencies;
            } catch (e) {
                alert('Failed to load currencies.');
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { search: '', is_active: '' };
            this.loadData();
        },
        
        openCreateModal() {
            this.editMode = false;
            this.formData = { currency_code: '', currency_name: '', currency_symbol: '', exchange_rate: 1, is_base_currency: false, is_active: true };
            this.showModal = true;
        },
        
        edit(item) {
            this.editMode = true;
            this.formData = { ...item };
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
        },
        
        async saveItem() {
            try {
                const url = this.editMode ? `/api/v1/currencies/${this.formData.id}` : '/api/v1/currencies';
                const method = this.editMode ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    this.closeModal();
                    this.loadData();
                } else {
                    alert(data.message || 'Operation failed');
                }
            } catch (e) {
                alert('Failed to save currency.');
            }
        },
        
        async deleteItem(item) {
            if (!confirm(`Deactivate currency: ${item.currency_code}?`)) return;
            
            try {
                const response = await fetch(`/api/v1/currencies/${item.id}`, {
                    method: 'DELETE',
                    headers: { 
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    this.loadData();
                } else {
                    alert(data.message || 'Delete failed');
                }
            } catch (e) {
                alert('Failed to delete currency.');
            }
        },
        
        getToken() {
            return localStorage.getItem('access_token') || '';
        },
        
        getOrgSlug() {
            return localStorage.getItem('org_slug') || '';
        }
    }
}
</script>
@endsection
