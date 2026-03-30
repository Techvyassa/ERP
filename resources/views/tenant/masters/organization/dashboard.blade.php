@extends('tenant.layouts.organization')

@section('title', $organization->org_name . ' - Organization & Access Control')
@section('page-title', 'Organization & Access Control')

@section('content')
<div x-data="organizationDashboard()" x-init="init()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('tenant.dashboard', ['org_slug' => $organization->org_slug]) }}" 
               class="text-gray-600 hover:text-primary">Dashboard</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-semibold">Organization & Access Control</span>
        </nav>
    </div>

    <!-- Category Header -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 mb-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <span class="material-symbols-outlined text-5xl">apartment</span>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-1">Organization & Access Control</h2>
                <p class="text-white/90">Manage users, roles, departments, and approval workflows</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">apartment</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.departments">0</h3>
            <p class="text-sm text-gray-600">Departments</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">badge</span>
                </div>
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Defined</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.roles">0</h3>
            <p class="text-sm text-gray-600">Roles</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-pink-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-pink-600 text-2xl">groups</span>
                </div>
                <span class="text-xs font-semibold text-pink-600 bg-pink-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.users">0</h3>
            <p class="text-sm text-gray-600">Users</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">approval</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Configured</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.approvalMatrix">0</h3>
            <p class="text-sm text-gray-600">Approval Rules</p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Departments -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-purple-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('departments')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-purple-600 text-3xl">apartment</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Departments</h4>
                    <p class="text-xs text-gray-600">Organization hierarchy</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage organizational departments and cost centers</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-purple-600" x-text="stats.departments + ' Active'">0 Active</span>
                <span class="material-symbols-outlined text-purple-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Roles -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-indigo-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('roles')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-indigo-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-indigo-600 text-3xl">badge</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Roles</h4>
                    <p class="text-xs text-gray-600">System roles</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Define roles and assign permissions</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-indigo-600" x-text="stats.roles + ' Defined'">0 Defined</span>
                <span class="material-symbols-outlined text-indigo-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Users -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-pink-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('users')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-pink-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-pink-600 text-3xl">groups</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Users</h4>
                    <p class="text-xs text-gray-600">System user accounts</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Manage team members and their access</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-pink-600" x-text="stats.users + ' Members'">0 Members</span>
                <span class="material-symbols-outlined text-pink-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>

        <!-- Approval Matrix -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-xl transition-all cursor-pointer group p-6" 
             @click="navigateTo('approval-matrix')">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-xl group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">approval</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">Approval Matrix</h4>
                    <p class="text-xs text-gray-600">Workflow configuration</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Configure approval workflows and rules</p>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-blue-600" x-text="stats.approvalMatrix + ' Rules'">0 Rules</span>
                <span class="material-symbols-outlined text-blue-600 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </div>
    </div>
</div>

<script>
function organizationDashboard() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const tenantType = '{{ $tenantType ?? 'path' }}';
    const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;
    const headers = () => ({
        'Authorization': `Bearer ${token()}`,
        'Accept': 'application/json',
        'X-Org-Slug': orgSlug
    });

    return {
        stats: {
            departments: 0,
            roles: 0,
            users: 0,
            approvalMatrix: 0
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const response = await fetch('/api/v1/dashboard/master-stats', { headers: headers() });
                const data = await response.json();

                if (data.success && data.data?.organization) {
                    this.stats = data.data.organization;
                }
            } catch (error) {
                console.error('Failed to load organization dashboard stats:', error);
            };
        },

        navigateTo(page) {
            const routes = {
                'departments': `${baseUrl}/departments`,
                'roles': `${baseUrl}/roles`,
                'users': `${baseUrl}/users`,
                'approval-matrix': `${baseUrl}/approval-matrix`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            }
        }
    }
}
</script>
@endsection
