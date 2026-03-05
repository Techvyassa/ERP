@extends('tenant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData()" x-init="init()">
    <!-- Profile Completion Banner -->
    <div x-show="showBanner && overallPercentage < 100" x-cloak
         class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4 flex-1">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">info</span>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Complete Your Profile Setup</h3>
                    <p class="text-sm text-gray-600 mb-3">Finish setting up your organization profile and master data to unlock all features</p>
                    <div class="flex items-center gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>Overall Progress</span>
                                <span x-text="overallPercentage + '%'" class="font-bold">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full transition-all duration-500" 
                                     :style="`width: ${overallPercentage}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button @click="dismissBanner" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, <span x-text="userName">User</span>!</h2>
        <p class="text-gray-600">Here's what's happening with your manufacturing operations today.</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600">inventory_2</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Live</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.materials">0</h3>
            <p class="text-sm text-gray-600">Active Materials</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600">factory</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.production">0</h3>
            <p class="text-sm text-gray-600">Production Orders</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600">handshake</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Approved</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.vendors">0</h3>
            <p class="text-sm text-gray-600">Active Vendors</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600">groups</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Team</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1" x-text="stats.users">0</h3>
            <p class="text-sm text-gray-600">Team Members</p>
        </div>
    </div>

    <!-- Main Dashboard Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Organization Profile Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('profile-completion')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-blue-500 to-primary p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">business</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Organization Profile</h3>
                <p class="text-sm text-gray-600 mb-4">Manage company details, address, and settings</p>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all" 
                             :style="`width: ${profilePercentage}%`"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-600" x-text="profilePercentage + '%'">0%</span>
                </div>
            </div>
        </div>

        <!-- Master Data Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('master-setup')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">database</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Master Data Setup</h3>
                <p class="text-sm text-gray-600 mb-4">Configure materials, vendors, BOMs, and more</p>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all" 
                             :style="`width: ${masterPercentage}%`"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-600" x-text="masterPercentage + '%'">0%</span>
                </div>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('departments')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">apartment</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Departments</h3>
                <p class="text-sm text-gray-600 mb-4">Manage organizational departments and cost centers</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </div>

        <!-- Users & Roles Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('users')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">groups</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Users & Roles</h3>
                <p class="text-sm text-gray-600 mb-4">Manage team members and access permissions</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </div>

        <!-- Production Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('production')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">precision_manufacturing</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Production</h3>
                <p class="text-sm text-gray-600 mb-4">Work orders, shop floor, and production tracking</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </div>

        <!-- Inventory Card -->
        <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" 
             @click="navigateTo('inventory')">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-br from-cyan-500 to-blue-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-2xl">inventory</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Inventory</h3>
                <p class="text-sm text-gray-600 mb-4">Stock management, warehouses, and transfers</p>
                <div class="text-xs text-gray-500">Click to manage</div>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardData() {
    return {
        userName: 'User',
        profilePercentage: 0,
        masterPercentage: 0,
        overallPercentage: 0,
        showBanner: true,
        stats: {
            materials: 0,
            production: 0,
            vendors: 0,
            users: 0
        },

        async init() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            this.userName = user.first_name || 'User';
            
            const dismissed = localStorage.getItem('completion_banner_dismissed');
            this.showBanner = !dismissed;

            await this.loadProgress();
        },

        async loadProgress() {
            try {
                const token = localStorage.getItem('access_token');
                
                // Load profile completion
                const profileResponse = await fetch('/api/v1/profile-completion/status', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (profileResponse.ok) {
                    const data = await profileResponse.json();
                    this.profilePercentage = data.data.percentage;
                }

                // Load master data status
                const masterResponse = await fetch('/api/v1/profile-completion/master-data-status', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (masterResponse.ok) {
                    const data = await masterResponse.json();
                    this.masterPercentage = data.data.percentage;
                }

                this.overallPercentage = Math.round((this.profilePercentage + this.masterPercentage) / 2);
            } catch (error) {
                console.error('Failed to load progress:', error);
            }
        },

        dismissBanner() {
            this.showBanner = false;
            localStorage.setItem('completion_banner_dismissed', 'true');
        },

        navigateTo(page) {
            const orgSlug = '{{ $organization->org_slug }}';
            const tenantType = '{{ $tenantType }}';
            const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;
            
            const routes = {
                'profile-completion': `${baseUrl}/profile-completion`,
                'master-setup': `${baseUrl}/master-setup`,
                'departments': `${baseUrl}/departments`,
                'users': `${baseUrl}/users`,
                'production': `${baseUrl}/production`,
                'inventory': `${baseUrl}/inventory`
            };
            
            if (routes[page]) {
                window.location.href = routes[page];
            } else {
                alert(`${page} page coming soon!`);
            }
        }
    }
}
</script>
@endsection
