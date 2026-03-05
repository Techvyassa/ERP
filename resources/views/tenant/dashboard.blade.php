@extends('tenant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData()" x-init="init()">
    <!-- Subscription Status Banner -->
    <div x-show="subscription.status === 'trial'" x-cloak
         class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl p-5 mb-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4 flex-1">
                <div class="bg-amber-100 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-amber-600 text-3xl">schedule</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-lg font-bold text-gray-900">Trial Period Active</h3>
                        <span class="bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full">TRIAL</span>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">
                        <span class="font-bold text-amber-600" x-text="subscription.daysRemaining"></span> days remaining in your trial period
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-3 rounded-full transition-all duration-500" 
                                     :style="`width: ${subscription.trialProgress}%`"></div>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-600" x-text="subscription.trialProgress + '%'">0%</span>
                    </div>
                </div>
            </div>
            <a href="/pricing" class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-lg hover:shadow-lg transition-all">
                Upgrade Now
            </a>
        </div>
    </div>

    <!-- Active Subscription Banner -->
    <div x-show="subscription.status === 'active'" x-cloak
         class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-5 mb-6 shadow-sm"></div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-xl">
                    <span class="material-symbols-outlined text-green-600 text-3xl">verified</span>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-lg font-bold text-gray-900" x-text="subscription.planName">Premium Plan</h3>
                        <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">ACTIVE</span>
                    </div>
                    <p class="text-sm text-gray-700">
                        Next billing: <span class="font-bold" x-text="subscription.nextBilling"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>


    <!-- Welcome Section -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, <span x-text="userName">User</span>!</h2>
        <p class="text-gray-600">Here's your complete manufacturing operations overview</p>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Materials Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">inventory_2</span>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Live</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.materials">0</h3>
            <p class="text-sm text-gray-600 mb-2">Active Materials</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold" x-text="'+' + stats.materialsChange">+0</span>
                <span class="text-gray-500">this month</span>
            </div>
        </div>

        <!-- Products Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">category</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Products</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.products">0</h3>
            <p class="text-sm text-gray-600 mb-2">Finished Products</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold" x-text="'+' + stats.productsChange">+0</span>
                <span class="text-gray-500">this month</span>
            </div>
        </div>

        <!-- Production Orders Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">factory</span>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.production">0</h3>
            <p class="text-sm text-gray-600 mb-2">Production Orders</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-blue-600 font-semibold" x-text="stats.productionPending">0</span>
                <span class="text-gray-500">pending</span>
            </div>
        </div>

        <!-- Vendors Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">handshake</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Approved</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.vendors">0</h3>
            <p class="text-sm text-gray-600 mb-2">Active Vendors</p>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-green-600 font-semibold" x-text="'+' + stats.vendorsChange">+0</span>
                <span class="text-gray-500">this month</span>
            </div>
        </div>
    </div>


    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Left Column - Organization & Master Data (2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Organization Setup Progress -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Organization Setup</h3>
                    <span class="text-xs font-semibold text-primary bg-blue-50 px-3 py-1 rounded-full" x-text="overallPercentage + '% Complete'">0%</span>
                </div>
                
                <!-- Profile Completion -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors" @click="navigateTo('profile-completion')">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">business</span>
                            <span class="font-semibold text-gray-900">Organization Profile</span>
                        </div>
                        <span class="text-sm font-bold text-gray-600" x-text="profilePercentage + '%'">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all" :style="`width: ${profilePercentage}%`"></div>
                    </div>
                </div>

                <!-- Master Data Progress -->
                <div class="p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors" @click="navigateTo('master-setup')">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-emerald-600">database</span>
                            <span class="font-semibold text-gray-900">Master Data Setup</span>
                        </div>
                        <span class="text-sm font-bold text-gray-600" x-text="masterPercentage + '%'">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all" :style="`width: ${masterPercentage}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Master Data Breakdown -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Master Data Overview</h3>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Organization Masters -->
                    <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-purple-600">apartment</span>
                            <h4 class="font-bold text-gray-900">Organization</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Departments</span>
                                <span class="font-bold text-gray-900" x-text="masterData.departments">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Roles</span>
                                <span class="font-bold text-gray-900" x-text="masterData.roles">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Users</span>
                                <span class="font-bold text-gray-900" x-text="masterData.users">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Masters -->
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-blue-600">inventory</span>
                            <h4 class="font-bold text-gray-900">Inventory</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Materials</span>
                                <span class="font-bold text-gray-900" x-text="masterData.materials">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Products</span>
                                <span class="font-bold text-gray-900" x-text="masterData.products">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Warehouses</span>
                                <span class="font-bold text-gray-900" x-text="masterData.warehouses">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vendor Masters -->
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-amber-600">handshake</span>
                            <h4 class="font-bold text-gray-900">Vendors</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Vendors</span>
                                <span class="font-bold text-gray-900" x-text="masterData.vendors">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Contacts</span>
                                <span class="font-bold text-gray-900" x-text="masterData.vendorContacts">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Mappings</span>
                                <span class="font-bold text-gray-900" x-text="masterData.vendorMappings">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & BOM Masters -->
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-green-600">receipt_long</span>
                            <h4 class="font-bold text-gray-900">Tax & BOM</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">HSN Codes</span>
                                <span class="font-bold text-gray-900" x-text="masterData.hsnCodes">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">GST Taxes</span>
                                <span class="font-bold text-gray-900" x-text="masterData.gstTaxes">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">BOMs</span>
                                <span class="font-bold text-gray-900" x-text="masterData.boms">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

div>

        <!-- Right Column - Quick Actions & Recent Activity -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button @click="navigateTo('materials')" class="w-full flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors text-left">
                        <span class="material-symbols-outlined text-blue-600">add_circle</span>
                        <span class="font-semibold text-gray-900">Add Material</span>
                    </button>
                    <button @click="navigateTo('products')" class="w-full flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors text-left">
                        <span class="material-symbols-outlined text-purple-600">add_circle</span>
                        <span class="font-semibold text-gray-900">Add Product</span>
                    </button>
                    <button @click="navigateTo('vendors')" class="w-full flex items-center gap-3 p-3 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors text-left">
                        <span class="material-symbols-outlined text-amber-600">add_circle</span>
                        <span class="font-semibold text-gray-900">Add Vendor</span>
                    </button>
                    <button @click="navigateTo('users')" class="w-full flex items-center gap-3 p-3 bg-pink-50 hover:bg-pink-100 rounded-lg transition-colors text-left">
                        <span class="material-symbols-outlined text-pink-600">person_add</span>
                        <span class="font-semibold text-gray-900">Add User</span>
                    </button>
                    <button @click="navigateTo('bom-header')" class="w-full flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors text-left">
                        <span class="material-symbols-outlined text-green-600">add_circle</span>
                        <span class="font-semibold text-gray-900">Create BOM</span>
                    </button>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">System Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Database</span>
                        </div>
                        <span class="text-xs text-green-600 font-bold">Online</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">API Services</span>
                        </div>
                        <span class="text-xs text-green-600 font-bold">Online</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                            <span class="text-sm font-semibold text-gray-900">Last Sync</span>
                        </div>
                        <span class="text-xs text-blue-600 font-bold" x-text="lastSync">Just now</span>
                    </div>
                </div>
            </div>

            <!-- Storage Usage -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Storage Usage</h3>
                <div class="mb-3">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">Used Storage</span>
                        <span class="font-bold text-gray-900" x-text="storage.used + ' / ' + storage.total">0 GB / 10 GB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-primary h-3 rounded-full transition-all" 
                             :style="`width: ${storage.percentage}%`"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500">
                    <span class="font-semibold" x-text="storage.remaining">10 GB</span> remaining
                </p>
            </div>
        </div>
    </div>


    <!-- Module Access Cards -->
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Module Access</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Departments -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('departments')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-purple-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-purple-600">apartment</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Departments</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Manage departments</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.departments + ' Active'">0 Active</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Roles -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('roles')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-indigo-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-indigo-600">badge</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Roles</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Manage user roles</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.roles + ' Defined'">0 Defined</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Users -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('users')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-pink-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-pink-600">groups</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Users</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Team management</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.users + ' Members'">0 Members</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Materials -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('materials')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-blue-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-blue-600">inventory_2</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Materials</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Material master</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.materials + ' Items'">0 Items</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Products -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('products')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-purple-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-purple-600">category</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Products</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Product catalog</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.products + ' Items'">0 Items</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Vendors -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('vendors')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-amber-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-amber-600">handshake</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Vendors</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Vendor management</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.vendors + ' Active'">0 Active</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- Warehouses -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('warehouses')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-cyan-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-cyan-600">warehouse</span>
                    </div>
                    <h4 class="font-bold text-gray-900">Warehouses</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Storage locations</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.warehouses + ' Locations'">0 Locations</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
            </div>

            <!-- BOMs -->
            <div class="bg-white rounded-xl border border-gray-200 hover:border-primary hover:shadow-lg transition-all cursor-pointer group p-5" 
                 @click="navigateTo('bom-header')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-green-100 p-2 rounded-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-green-600">account_tree</span>
                    </div>
                    <h4 class="font-bold text-gray-900">BOMs</h4>
                </div>
                <p class="text-xs text-gray-600 mb-2">Bill of materials</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-primary" x-text="masterData.boms + ' Defined'">0 Defined</span>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-primary text-sm">arrow_forward</span>
                </div>
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
        lastSync: 'Just now',
        
        subscription: {
            status: 'trial', // 'trial', 'active', 'expired'
            planName: 'Free Trial',
            daysRemaining: 14,
            totalDays: 14,
            trialProgress: 100,
            nextBilling: 'N/A'
        },
        
        stats: {
            materials: 0,
            materialsChange: 0,
            products: 0,
            productsChange: 0,
            production: 0,
            productionPending: 0,
            vendors: 0,
            vendorsChange: 0,
            users: 0
        },
        
        masterData: {
            departments: 0,
            roles: 0,
            users: 0,
            materials: 0,
            products: 0,
            warehouses: 0,
            vendors: 0,
            vendorContacts: 0,
            vendorMappings: 0,
            hsnCodes: 0,
            gstTaxes: 0,
            boms: 0
        },
        
        storage: {
            used: '0.5 GB',
            total: '10 GB',
            remaining: '9.5 GB',
            percentage: 5
        },

        async init() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            this.userName = user.first_name || 'User';
            
            await this.loadAllData();
            this.updateLastSync();
            
            // Update last sync every minute
            setInterval(() => this.updateLastSync(), 60000);
        },

        async loadAllData() {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = localStorage.getItem('org_slug');
                
                if (!token || !orgSlug) {
                    console.error('Missing authentication data');
                    return;
                }
                
                const headers = {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'X-Org-Slug': orgSlug
                };

                // Load profile completion
                const profileResponse = await fetch('/api/v1/profile-completion/status', { headers });
                if (profileResponse.ok) {
                    const data = await profileResponse.json();
                    this.profilePercentage = data.data.percentage;
                }

                // Load master data status
                const masterResponse = await fetch('/api/v1/profile-completion/master-data-status', { headers });
                if (masterResponse.ok) {
                    const data = await profileResponse.json();
                    this.masterPercentage = data.data.percentage;
                }

                this.overallPercentage = Math.round((this.profilePercentage + this.masterPercentage) / 2);

                // Load subscription data (mock for now - replace with actual API)
                await this.loadSubscriptionData();
                
                // Load statistics (mock for now - replace with actual API)
                await this.loadStatistics();
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        },

        async loadSubscriptionData() {
            // TODO: Replace with actual API call
            // Mock data for demonstration
            this.subscription = {
                status: 'trial',
                planName: 'Free Trial',
                daysRemaining: 12,
                totalDays: 14,
                trialProgress: Math.round((12 / 14) * 100),
                nextBilling: 'N/A'
            };
        },

        async loadStatistics() {
            // TODO: Replace with actual API calls
            // Mock data for demonstration
            this.stats = {
                materials: 45,
                materialsChange: 5,
                products: 23,
                productsChange: 3,
                production: 8,
                productionPending: 3,
                vendors: 12,
                vendorsChange: 2,
                users: 8
            };
            
            this.masterData = {
                departments: 5,
                roles: 8,
                users: 8,
                materials: 45,
                products: 23,
                warehouses: 3,
                vendors: 12,
                vendorContacts: 18,
                vendorMappings: 34,
                hsnCodes: 15,
                gstTaxes: 6,
                boms: 12
            };
            
            this.storage = {
                used: '2.3 GB',
                total: '10 GB',
                remaining: '7.7 GB',
                percentage: 23
            };
        },

        updateLastSync() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            this.lastSync = `${hours}:${minutes}`;
        },

        navigateTo(page) {
            const orgSlug = '{{ $organization->org_slug }}';
            const tenantType = '{{ $tenantType }}';
            const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;
            
            const routes = {
                'profile-completion': `${baseUrl}/profile-completion`,
                'master-setup': `${baseUrl}/master-setup`,
                'departments': `${baseUrl}/departments`,
                'roles': `${baseUrl}/roles`,
                'users': `${baseUrl}/users`,
                'materials': `${baseUrl}/materials`,
                'products': `${baseUrl}/products`,
                'warehouses': `${baseUrl}/warehouses`,
                'vendors': `${baseUrl}/vendors`,
                'bom-header': `${baseUrl}/bom-header`,
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
