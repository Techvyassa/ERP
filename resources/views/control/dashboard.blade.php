@extends('control.layouts.app')

@section('title', 'Super Admin Dashboard')
@section('page-title', 'Super Admin Dashboard')

@section('content')
<div x-data="controlDashboard()" x-init="init()">
    <!-- Welcome Section -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome, <span x-text="userName">Super Admin</span>!</h2>
        <p class="text-gray-600">Manage all organizations, subscriptions, and system settings.</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-admin">business</span>
                </div>
                <span class="text-xs font-semibold text-admin bg-purple-50 px-2 py-1 rounded">Total</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.organizations">0</h3>
            <p class="text-sm text-gray-600">Organizations</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.activeSubscriptions">0</h3>
            <p class="text-sm text-gray-600">Active Subscriptions</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600">payments</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">This Month</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.revenue">$0</h3>
            <p class="text-sm text-gray-600">Revenue</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600">price_check</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Plans</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.plans">0</h3>
            <p class="text-sm text-gray-600">Subscription Plans</p>
        </div>
    </div>

    <!-- Main Control Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Organizations Card -->
        <a href="{{ route('control.organizations.index') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-purple-500 to-admin p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">business</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Organizations</h3>
                <p class="text-sm text-gray-600 mb-4">Manage all tenant organizations and their settings</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>

        <!-- Subscriptions Card -->
        <a href="{{ route('control.subscriptions.index') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">card_membership</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Subscriptions</h3>
                <p class="text-sm text-gray-600 mb-4">View and manage organization subscriptions</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>

        <!-- Subscription Plans Card -->
        <a href="{{ route('control.plans.index') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">price_check</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Subscription Plans</h3>
                <p class="text-sm text-gray-600 mb-4">Configure pricing plans and features</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>

        <!-- Payments Card -->
        <a href="{{ route('control.payments.index') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">payments</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Payments</h3>
                <p class="text-sm text-gray-600 mb-4">View payment history and transactions</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>

        <!-- Feature Control Card -->
        <a href="{{ route('control.features.index') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">toggle_on</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Feature Control</h3>
                <p class="text-sm text-gray-600 mb-4">Enable/disable features for organizations</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>

        <!-- Settings Card -->
        <a href="{{ route('control.settings') }}" 
           class="bg-white rounded-xl border-2 border-gray-200 hover:border-admin hover:shadow-xl transition-all cursor-pointer group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-gray-500 to-slate-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">settings</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-admin transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">System Settings</h3>
                <p class="text-sm text-gray-600 mb-4">Configure global system settings</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </a>
    </div>
</div>

<script>
function controlDashboard() {
    return {
        userName: 'Super Admin',
        stats: {
            organizations: 0,
            activeSubscriptions: 0,
            revenue: '$0',
            plans: 0
        },

        async init() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            this.userName = user.first_name || 'Super Admin';
            
            // TODO: Load stats from API
            // await this.loadStats();
        },

        async loadStats() {
            // Placeholder for API call
            try {
                const token = localStorage.getItem('access_token');
                // const response = await fetch('/api/v1/control/stats', {
                //     headers: {
                //         'Authorization': `Bearer ${token}`,
                //         'Accept': 'application/json'
                //     }
                // });
                // if (response.ok) {
                //     const data = await response.json();
                //     this.stats = data.data;
                // }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }
    }
}
</script>
@endsection
