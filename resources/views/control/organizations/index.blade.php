@extends('control.layouts.app')

@section('title', 'Organizations')
@section('page-title', 'Organizations Management')

@section('content')
<div x-data="organizationsData()" x-init="init()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Organizations</h2>
                <p class="text-gray-600 mt-1">Manage all tenant organizations</p>
            </div>
            <button @click="openCreateModal" 
                    class="px-4 py-2 bg-admin text-white rounded-lg hover:bg-purple-800 transition-colors">
                <span class="material-symbols-outlined mr-2 align-middle">add</span>Add Organization
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search organizations..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin focus:border-transparent">
            <select x-model="filters.status" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin focus:border-transparent">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="terminated">Terminated</option>
            </select>
            <select x-model="filters.subscription_status" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin focus:border-transparent">
                <option value="">All Subscriptions</option>
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="expired">Expired</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined mr-2 align-middle">refresh</span>Reset
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="material-symbols-outlined text-3xl text-gray-400 animate-spin">progress_activity</span>
                                <p class="text-gray-600 mt-2">Loading organizations...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">business</span>
                                <p class="text-gray-600">No organizations found.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-admin rounded-lg flex items-center justify-center mr-3">
                                        <span class="material-symbols-outlined text-white">business</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="item.org_name"></div>
                                        <div class="text-sm text-gray-500" x-text="item.primary_email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="item.org_slug"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="item.subscription_plan || 'None'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full" 
                                      :class="{
                                          'bg-green-100 text-green-800': item.status === 'active',
                                          'bg-yellow-100 text-yellow-800': item.status === 'suspended',
                                          'bg-red-100 text-red-800': item.status === 'terminated'
                                      }"
                                      x-text="item.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="item.created_at"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="view(item)" class="text-admin hover:text-purple-900 mr-3" title="View">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                                <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900" title="Delete">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function organizationsData() {
    return {
        items: [],
        loading: false,
        filters: {
            search: '',
            status: '',
            subscription_status: ''
        },
        
        async init() {
            await this.loadData();
        },
        
        async loadData() {
            this.loading = true;
            try {
                // TODO: Replace with actual API call
                // const token = localStorage.getItem('access_token');
                // const response = await fetch('/api/v1/control/organizations', {
                //     headers: {
                //         'Authorization': `Bearer ${token}`,
                //         'Accept': 'application/json'
                //     }
                // });
                // if (response.ok) {
                //     const data = await response.json();
                //     this.items = data.data.organizations;
                // }
                
                // Placeholder data
                this.items = [];
            } catch (error) {
                console.error('Failed to load organizations:', error);
                alert('Failed to load organizations. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        
        resetFilters() {
            this.filters = { search: '', status: '', subscription_status: '' };
            this.loadData();
        },
        
        openCreateModal() {
            alert('Create organization form - Coming soon');
        },
        
        view(item) {
            alert('View organization: ' + item.org_name + ' - Coming soon');
        },
        
        edit(item) {
            alert('Edit organization: ' + item.org_name + ' - Coming soon');
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure you want to delete organization: ' + item.org_name + '?')) {
                alert('Delete functionality - Coming soon');
            }
        }
    }
}
</script>
@endsection
