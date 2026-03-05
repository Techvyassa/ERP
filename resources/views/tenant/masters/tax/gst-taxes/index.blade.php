@extends('tenant.layouts.tax')

@section('title', 'GST Taxes')
@section('page-title', 'GST Tax Master')

@section('content')
<div x-data="gstData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">GST Tax Master</h2>
                <p class="text-gray-600 mt-1">Manage GST tax rates and configurations</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add GST Tax
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData" placeholder="Search by tax name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.tax_type" @change="loadData" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="CGST_SGST">CGST/SGST</option>
                <option value="IGST">IGST</option>
                <option value="CESS">CESS</option>
            </select>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGST %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SGST %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IGST %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CESS %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="text-gray-600 mt-2">Loading GST taxes...</p></td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-percentage text-6xl text-gray-300 mb-4"></i><p class="text-gray-600">No GST taxes found.</p></td></tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900" x-text="item.tax_name"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800" x-text="item.tax_type"></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.cgst_rate + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.sgst_rate + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.igst_rate + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.cess_rate + '%'"></td>
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
                <h3 class="text-xl font-bold text-gray-900" x-text="editMode ? 'Edit GST Tax' : 'Add GST Tax'"></h3>
            </div>
            <form @submit.prevent="saveItem">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tax Name *</label>
                        <input type="text" x-model="formData.tax_name" required maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tax Type *</label>
                        <select x-model="formData.tax_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="CGST_SGST">CGST/SGST</option>
                            <option value="IGST">IGST</option>
                            <option value="CESS">CESS</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CGST Rate (%)</label>
                            <input type="number" x-model="formData.cgst_rate" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SGST Rate (%)</label>
                            <input type="number" x-model="formData.sgst_rate" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">IGST Rate (%)</label>
                            <input type="number" x-model="formData.igst_rate" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CESS Rate (%)</label>
                            <input type="number" x-model="formData.cess_rate" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
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
function gstData() {
    return {
        items: [], loading: false, showModal: false, editMode: false,
        filters: { search: '', tax_type: '', is_active: '' },
        formData: { tax_name: '', tax_type: '', cgst_rate: 0, sgst_rate: 0, igst_rate: 0, cess_rate: 0, is_active: true },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.tax_type) params.append('tax_type', this.filters.tax_type);
                if (this.filters.is_active !== '') params.append('is_active', this.filters.is_active);
                
                const response = await fetch(`/api/v1/gst-taxes?${params}`, {
                    headers: { 
                        'Authorization': `Bearer ${this.getToken()}`,
                        'X-Org-Slug': this.getOrgSlug()
                    }
                });
                const data = await response.json();
                if (data.success) this.items = data.data.gst_taxes;
            } catch (e) {
                alert('Failed to load GST taxes.');
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { search: '', tax_type: '', is_active: '' };
            this.loadData();
        },
        
        openCreateModal() {
            this.editMode = false;
            this.formData = { tax_name: '', tax_type: '', cgst_rate: 0, sgst_rate: 0, igst_rate: 0, cess_rate: 0, is_active: true };
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
                const url = this.editMode ? `/api/v1/gst-taxes/${this.formData.id}` : '/api/v1/gst-taxes';
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
                alert('Failed to save GST tax.');
            }
        },
        
        async deleteItem(item) {
            if (!confirm(`Deactivate GST tax: ${item.tax_name}?`)) return;
            
            try {
                const response = await fetch(`/api/v1/gst-taxes/${item.id}`, {
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
                alert('Failed to delete GST tax.');
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
