@extends('tenant.layouts.app')

@section('title', $organization->org_name . ' - Dashboard')
@section('page-title', 'Organization Dashboard')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: #1e3a8a;
        --secondary: #3b82f6;
        --accent: #6366f1;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #0f172a;
    }

    .dashboard-container {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .glass-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        margin-bottom: 16px;
    }

    .gradient-text {
        background: linear-gradient(135deg, var(--primary), var(--accent));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    [x-cloak] {
        display: none !important;
    }

    .shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }
</style>

<div class="dashboard-container" x-data="dashboardController()" x-init="init()">

    <!-- Welcome & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">
                Welcome back, <span class="gradient-text">{{ explode(' ', Auth::user()->name ?? 'User')[0] }}</span>!
            </h1>
            <p class="text-slate-500 font-medium">Viewing real-time operations for <span class="text-blue-600 font-bold tracking-wide">{{ $organization->org_name }}</span></p>
        </div>

        <div class="flex items-center gap-3">
            <div class="glass-card px-4 py-2 rounded-2xl flex items-center gap-3">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <span class="text-sm font-bold text-slate-600">Live Services</span>
            </div>
            <button @click="loadStats()" class="glass-card p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                <span class="material-symbols-outlined" :class="{ 'animate-spin': loading }">refresh</span>
            </button>
        </div>
    </div>

    <!-- Onboarding Progress Banner (Dynamic) -->
    <div x-show="onboarding.percentage < 100" x-cloak
        class="mb-8 overflow-hidden rounded-3xl relative">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-700 via-indigo-700 to-primary opacity-90"></div>
        <div class="relative p-8 text-white z-10">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                            <span class="material-symbols-outlined text-white">rocket_launch</span>
                        </span>
                        <h2 class="text-2xl font-bold">Getting Started</h2>
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold tracking-wider" x-text="onboarding.percentage + '% Complete'">0% Complete</span>
                    </div>
                    <p class="text-white/80 max-w-2xl text-lg font-medium leading-relaxed">
                        You're on your way! Complete your organization setup to unlock advanced manufacturing features and analytics.
                    </p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-4">
                    <div class="w-48 bg-white/10 rounded-full h-3 overflow-hidden border border-white/20 p-0.5">
                        <div class="bg-white h-2 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(255,255,255,0.5)]"
                            :style="`width: ${onboarding.percentage}%` transition: 'width 1s cubic-bezier(0.4, 0, 0.2, 1)'"></div>
                    </div>
                    <a href="{{ url("/org/{$organization->org_slug}/profile-completion") }}"
                        class="px-8 py-3 bg-white text-primary font-bold rounded-2xl hover:bg-slate-50 hover:shadow-xl hover:scale-105 transition-all text-sm tracking-wide">
                        Finish Setup
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Primary Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Revenue Card -->
        <div class="glass-card p-6 rounded-3xl relative overflow-hidden group">
            <div class="stat-icon bg-emerald-50 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-emerald-600 text-3xl">payments</span>
            </div>
            <h3 class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Total Revenue</h3>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-black text-slate-800" x-text="formatCurrency(stats.overview.revenue)">₹0</p>
                <span class="text-emerald-500 font-bold text-xs mb-1 mb-1.5 flex items-center">
                    <span class="material-symbols-outlined text-xs">trending_up</span> Live
                </span>
            </div>
            <!-- Decorative Wave -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500/20">
                <div class="h-full bg-emerald-500 transition-all duration-1000" :style="`width: ${Math.min(100, stats.overview.revenue / 10000)}%`"></div>
            </div>
        </div>

        <!-- Production Card -->
        <div class="glass-card p-6 rounded-3xl relative overflow-hidden group">
            <div class="stat-icon bg-blue-50 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-blue-600 text-3xl">factory</span>
            </div>
            <h3 class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Active Orders</h3>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-black text-slate-800" x-text="stats.overview.active_production">0</p>
                <span class="text-blue-500 font-bold text-xs mb-1.5">Production</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500/20 text-[10px] items-center flex px-2 text-blue-600 font-bold uppercase tracking-tight">On Floor</div>
        </div>

        <!-- Purchases Card -->
        <div class="glass-card p-6 rounded-3xl relative overflow-hidden group">
            <div class="stat-icon bg-amber-50 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-amber-600 text-3xl">shopping_cart</span>
            </div>
            <h3 class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Pending Purchases</h3>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-black text-slate-800" x-text="stats.overview.pending_purchases">0</p>
                <span class="text-amber-500 font-bold text-xs mb-1.5">Awaiting GRN</span>
            </div>
        </div>

        <!-- Stock Alerts Card -->
        <div class="glass-card p-6 rounded-3xl relative overflow-hidden group" :class="stats.overview.low_stock > 0 ? 'bg-red-50/50' : ''">
            <div class="stat-icon" :class="stats.overview.low_stock > 0 ? 'bg-red-100' : 'bg-slate-100'">
                <span class="material-symbols-outlined" :class="stats.overview.low_stock > 0 ? 'text-red-600' : 'text-slate-600'">warning</span>
            </div>
            <h3 class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Critical Stock</h3>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-black" :class="stats.overview.low_stock > 0 ? 'text-red-700' : 'text-slate-800'" x-text="stats.overview.low_stock">0</p>
                <span class="font-bold text-xs mb-1.5" :class="stats.overview.low_stock > 0 ? 'text-red-600' : 'text-slate-500'">Low Items</span>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

        <!-- Left Panel: Trends & Stats -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Quick Link Hub -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button @click="window.location.href='/org/{{ $organization->org_slug }}/production/orders'"
                    class="glass-card p-4 rounded-3xl flex flex-col items-center justify-center gap-3 hover:bg-blue-50/50 transition-all group">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-blue-600">precision_manufacturing</span>
                    </div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">Production</span>
                </button>
                <button @click="window.location.href='/org/{{ $organization->org_slug }}/inventory-dashboard'"
                    class="glass-card p-4 rounded-3xl flex flex-col items-center justify-center gap-3 hover:bg-emerald-50/50 transition-all group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-emerald-600">inventory_2</span>
                    </div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">Inventory</span>
                </button>
                <button @click="window.location.href='/org/{{ $organization->org_slug }}/sales/orders'"
                    class="glass-card p-4 rounded-3xl flex flex-col items-center justify-center gap-3 hover:bg-purple-50/50 transition-all group">
                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-purple-600">orders</span>
                    </div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">Sales</span>
                </button>
                <button @click="window.location.href='/org/{{ $organization->org_slug }}/users'"
                    class="glass-card p-4 rounded-3xl flex flex-col items-center justify-center gap-3 hover:bg-slate-100 transition-all group">
                    <div class="w-10 h-10 bg-slate-200 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-slate-600">group</span>
                    </div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">Users</span>
                </button>
            </div>

            <!-- Operational Breakdown -->
            <div class="glass-card rounded-[2rem] p-8 overflow-hidden relative">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-3">
                        Operational Status
                        <span class="text-[10px] bg-blue-100 text-blue-600 px-3 py-1 rounded-full uppercase tracking-widest font-bold">Today</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Production Stats -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h4 class="font-bold text-slate-700">Production Velocity</h4>
                        </div>
                        <div class="space-y-4">
                            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="font-bold text-slate-600">Pending Execution</span>
                                    <span class="font-black text-blue-600" x-text="stats.production.pending">0</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" :style="`width: ${Math.min(100, stats.production.pending * 5)}%`"></div>
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="font-bold text-slate-600">In Progress</span>
                                    <span class="font-black text-emerald-600" x-text="stats.production.in_progress">0</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" :style="`width: ${Math.min(100, stats.production.in_progress * 5)}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Stats -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                            <h4 class="font-bold text-slate-700">Sales Pipeline</h4>
                        </div>
                        <div class="space-y-4">
                            <template x-for="(count, status) in stats.sales.by_status" :key="status">
                                <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-100 hover:bg-white transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 bg-slate-400 rounded-full" :class="{'bg-purple-500': status === 'CONFIRMED', 'bg-blue-500': status === 'DRAFT'}"></div>
                                        <span class="text-xs font-black text-slate-600 uppercase tracking-wider" x-text="status.replace('_', ' ')">Status</span>
                                    </div>
                                    <span class="font-black text-slate-800" x-text="count">0</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Activity & Alerts -->
        <div class="space-y-8">

            <!-- Activity Feed -->
            <div class="glass-card rounded-[2rem] p-8 h-full">
                <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-3">
                    Recent Activity
                    <span class="w-2 h-2 bg-blue-500 rounded-full animate-ping"></span>
                </h3>

                <div class="space-y-6 relative">
                    <!-- Timeline Line -->
                    <div class="absolute left-[19px] top-0 bottom-0 w-0.5 bg-slate-100"></div>

                    <!-- Loading States -->
                    <template x-if="loading">
                        <div class="space-y-6">
                            <template x-for="i in 5">
                                <div class="flex gap-4 animate-pulse">
                                    <div class="w-10 h-10 bg-slate-200 rounded-full z-10 border-4 border-white"></div>
                                    <div class="flex-1 space-y-2 py-1">
                                        <div class="h-3 bg-slate-200 rounded-full w-3/4"></div>
                                        <div class="h-2 bg-slate-100 rounded-full w-1/2"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Real Activity -->
                    <template x-if="!loading && stats.recent_activity.length > 0">
                        <template x-for="(activity, index) in stats.recent_activity" :key="index">
                            <div class="flex gap-4 group cursor-default">
                                <div class="w-10 h-10 rounded-full z-10 border-4 border-white flex items-center justify-center flex-shrink-0 transition-all group-hover:scale-110"
                                    :class="{
                                         'bg-blue-100 text-blue-600': activity.type === 'Sales Order',
                                         'bg-purple-100 text-purple-600': activity.type === 'Production Order',
                                         'bg-emerald-100 text-emerald-600': activity.type === 'Purchase Order'
                                     }">
                                    <span class="material-symbols-outlined text-lg" x-text="activity.type === 'Sales Order' ? 'shopping_bag' : 'factory'">history</span>
                                </div>
                                <div class="flex-1 py-1">
                                    <p class="text-sm font-black text-slate-800 mb-0.5 leading-tight group-hover:text-blue-600 transition-colors" x-text="activity.event"></p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest" x-text="activity.time">Time</p>
                                </div>
                            </div>
                        </template>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loading && stats.recent_activity.length === 0">
                        <div class="text-center py-20 text-slate-400">
                            <span class="material-symbols-outlined text-4xl mb-4 opacity-20">hourglass_empty</span>
                            <p class="text-xs font-bold uppercase tracking-widest">No activities recorded</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function dashboardController() {
        return {
            loading: false,
            onboarding: {
                percentage: 0
            },
            stats: {
                overview: {
                    revenue: 0,
                    active_production: 0,
                    pending_purchases: 0,
                    low_stock: 0
                },
                sales: {
                    by_status: {}
                },
                production: {
                    pending: 0,
                    in_progress: 0,
                    completed: 0
                },
                recent_activity: []
            },

            async init() {
                console.log('Initializing Dashboard Controller...');
                await this.loadAll();

                // Auto-refresh every 5 minutes
                setInterval(() => this.loadStats(), 300000);
            },

            async loadAll() {
                this.loading = true;
                try {
                    await Promise.all([
                        this.loadOnboardingStatus(),
                        this.loadStats()
                    ]);
                } catch (error) {
                    console.error('Core data load failed:', error);
                } finally {
                    this.loading = false;
                }
            },

            async loadOnboardingStatus() {
                try {
                    const slug = '{{ $organization->org_slug }}';
                    const response = await fetch(`/api/v1/profile-completion/status`, {
                        headers: {
                            'X-Org-Slug': slug,
                            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.onboarding.percentage = data.data.percentage || 0;
                    }
                } catch (err) {
                    console.warn('Onboarding load aborted');
                }
            },

            async loadStats() {
                this.loading = true;
                try {
                    const slug = '{{ $organization->org_slug }}';
                    const response = await fetch(`/api/v1/dashboard/master-stats`, {
                        headers: {
                            'X-Org-Slug': slug,
                            'Authorization': `Bearer ${localStorage.getItem('access_token')}`
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.stats = data.data;
                    }
                } catch (error) {
                    console.error('Stats fetch failed:', error);
                } finally {
                    this.loading = false;
                }
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    maximumFractionDigits: 0
                }).format(value || 0);
            }
        }
    }
</script>
@endsection