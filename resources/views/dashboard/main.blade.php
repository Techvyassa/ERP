<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard | Zap ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#4F46E5",
                        "secondary": "#10B981",
                        "accent": "#F59E0B",
                        "dark": "#0F172A",
                    },
                    fontFamily: {
                        "sans": ["Plus Jakarta Sans", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }

        body {
            background: radial-gradient(circle at top right, #f8fafc, #f1f5f9);
            min-height: 100vh;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }

        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-item:hover {
            transform: translateX(4px);
        }

        .sidebar-active {
            background: linear-gradient(to right, rgba(79, 70, 229, 0.1), transparent);
            border-left: 4px solid #4F46E5;
            color: #4F46E5;
        }

        .stat-card {
            transition: all 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased font-sans">
    <!-- Layout Wrapper -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 glass-card border-r border-slate-200 lg:static lg:translate-x-0 transition-transform duration-300 -translate-x-full">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="p-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-white">precision_manufacturing</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-dark tracking-tight">Zap<span class="text-primary">ERP</span></h1>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Enterprise Edition</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                    <p class="text-[10px] font-bold text-slate-400 uppercase px-4 mb-2 tracking-widest">Main Menu</p>
                    
                    <a href="#" class="sidebar-item sidebar-active flex items-center gap-3 px-4 py-3 rounded-xl font-semibold">
                        <span class="material-symbols-outlined">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="#" onclick="navigateTo('production')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">factory</span>
                        <span>Production</span>
                    </a>

                    <a href="#" onclick="navigateTo('inventory')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">inventory_2</span>
                        <span>Inventory</span>
                    </a>

                    <a href="#" onclick="navigateTo('masters')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">database</span>
                        <span>Master Data</span>
                    </a>

                    <p class="text-[10px] font-bold text-slate-400 uppercase px-4 mt-8 mb-2 tracking-widest">Organization</p>
                    
                    <a href="#" onclick="navigateTo('departments')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">apartment</span>
                        <span>Departments</span>
                    </a>

                    <a href="#" onclick="navigateTo('users')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">group</span>
                        <span>Teams & Roles</span>
                    </a>

                    <a href="#" onclick="navigateTo('profile')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined">settings</span>
                        <span>Settings</span>
                    </a>
                </nav>

                <!-- User Footer -->
                <div class="p-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 p-2 rounded-xl border border-slate-100 glass-card">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-primary font-bold overflow-hidden" id="userAvatar">
                            U
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-dark truncate" id="userName">User Name</p>
                            <p class="text-[10px] text-slate-500 truncate" id="userEmail">user@example.com</p>
                        </div>
                        <button onclick="logout()" class="text-slate-400 hover:text-rose-500 transition-colors">
                            <span class="material-symbols-outlined text-sm">logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="h-16 glass-card border-b border-slate-200 px-8 flex items-center justify-between sticky top-0 z-40">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="lg:hidden text-slate-600">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="hidden sm:block">
                        <h2 class="text-sm font-bold text-dark" id="orgName">Loading Organization...</h2>
                        <p class="text-xs text-slate-500" id="currentDate">--</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="relative hidden md:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                        <input type="text" placeholder="Global search..." class="pl-10 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all w-64 shadow-sm" />
                    </div>
                    
                    <button class="relative text-slate-600 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-rose-500 border-2 border-white rounded-full"></span>
                    </button>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                
                <!-- Welcome Banner -->
                <div class="mb-10 animate-fade-in">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h1 class="text-3xl font-extrabold text-dark mb-2">Welcome back, <span id="welcomeName" class="text-primary text-slate-900">User</span>! ✨</h1>
                            <p class="text-slate-500">Here's a comprehensive overview of your manufacturing operations today.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="refreshData()" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold glass-card text-slate-600 hover:text-primary transition-all">
                                <span class="material-symbols-outlined text-sm">refresh</span>
                                Refresh
                            </button>
                            <button onclick="navigateTo('dashboard')" class="flex items-center gap-2 px-6 py-2 rounded-xl bg-primary text-white text-sm font-bold shadow-lg shadow-primary/30 hover:shadow-primary/40 transition-all hover:scale-[1.02]">
                                <span class="material-symbols-outlined text-sm">rocket_launch</span>
                                Full Workspace
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Setup Progress -->
                <div id="setupBanner" class="hidden mb-10 p-6 rounded-2xl glass-card border-none bg-gradient-to-r from-primary/5 to-indigo-500/5 relative overflow-hidden animate-fade-in" style="animation-delay: 0.1s">
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="px-3 py-1 bg-primary/10 text-primary text-[10px] font-bold rounded-full uppercase tracking-widest mb-3 inline-block">Onboarding Progress</span>
                                <h3 class="text-lg font-bold text-dark mb-1">Complete Your Organization Setup</h3>
                                <p class="text-sm text-slate-500">You're almost there! Finish these steps to unlock the full potential of Zap ERP.</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black text-primary" id="overallPercentage">0%</span>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Completed</p>
                            </div>
                        </div>
                        <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden mb-6">
                            <div id="overallProgress" class="bg-primary h-full rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="flex items-center gap-3">
                                <div id="profCheck" class="w-6 h-6 rounded-full flex items-center justify-center text-white bg-slate-300">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                </div>
                                <span class="text-xs font-semibold text-slate-600">Company Profile</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div id="masterCheck" class="w-6 h-6 rounded-full flex items-center justify-center text-white bg-slate-300">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                </div>
                                <span class="text-xs font-semibold text-slate-600">Master Data Setup</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div id="teamCheck" class="w-6 h-6 rounded-full flex items-center justify-center text-white bg-slate-300">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                </div>
                                <span class="text-xs font-semibold text-slate-600">Team Configuration</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
                    <!-- Revenue -->
                    <div class="stat-card glass-card p-6 rounded-2xl animate-fade-in" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                                <span class="material-symbols-outlined text-2xl">payments</span>
                            </div>
                            <span class="text-[10px] font-black text-blue-600 py-1 px-3 bg-blue-100 rounded-full uppercase tracking-widest">Revenue</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Confirmed</p>
                        <h3 class="text-2xl font-black text-dark tracking-tight" id="statRevenue">₹ --</h3>
                    </div>

                    <!-- Active Production -->
                    <div class="stat-card glass-card p-6 rounded-2xl animate-fade-in" style="animation-delay: 0.3s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                                <span class="material-symbols-outlined text-2xl">settings_applications</span>
                            </div>
                            <span class="text-[10px] font-black text-emerald-600 py-1 px-3 bg-emerald-100 rounded-full uppercase tracking-widest">Live</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Active Production</p>
                        <h3 class="text-2xl font-black text-dark tracking-tight" id="statProduction">--</h3>
                    </div>

                    <!-- Pending Purchases -->
                    <div class="stat-card glass-card p-6 rounded-2xl animate-fade-in" style="animation-delay: 0.4s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600">
                                <span class="material-symbols-outlined text-2xl">shopping_cart_checkout</span>
                            </div>
                            <span class="text-[10px] font-black text-amber-600 py-1 px-3 bg-amber-100 rounded-full uppercase tracking-widest">Pending</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Purchase Orders</p>
                        <h3 class="text-2xl font-black text-dark tracking-tight" id="statPurchase">--</h3>
                    </div>

                    <!-- Low Stock -->
                    <div class="stat-card glass-card p-6 rounded-2xl animate-fade-in" style="animation-delay: 0.5s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600">
                                <span class="material-symbols-outlined text-2xl">warning</span>
                            </div>
                            <span class="text-[10px] font-black text-rose-600 py-1 px-3 bg-rose-100 rounded-full uppercase tracking-widest">Critical</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Low Stock Items</p>
                        <h3 class="text-2xl font-black text-dark tracking-tight" id="statLowStock">--</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Recent Activity Section -->
                    <div class="lg:col-span-2 glass-card rounded-2xl animate-fade-in" style="animation-delay: 0.6s">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-dark flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">history</span>
                                Recent Activity
                            </h3>
                            <button class="text-xs font-bold text-primary hover:underline">View All</button>
                        </div>
                        <div class="p-6">
                            <div class="space-y-6" id="activityList">
                                <!-- Activity Item Skeleton -->
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl skeleton flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <div class="h-4 w-48 rounded skeleton mb-2"></div>
                                        <div class="h-3 w-32 rounded skeleton"></div>
                                    </div>
                                    <div class="h-3 w-12 rounded skeleton"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Navigation Cards -->
                    <div class="space-y-6 animate-fade-in" style="animation-delay: 0.7s">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Workspace Hub</p>
                        
                        <div onclick="navigateTo('production')" class="group p-6 rounded-2xl bg-white border border-slate-100 hover:border-primary/50 shadow-sm hover:shadow-xl hover:shadow-primary/5 transition-all cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-primary group-hover:text-white transition-all transform group-hover:rotate-6">
                                        <span class="material-symbols-outlined">precision_manufacturing</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark">Production Line</h4>
                                        <p class="text-xs text-slate-500">Manage orders & confirm FG</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 group-hover:translate-x-1 group-hover:text-primary transition-all">chevron_right</span>
                            </div>
                        </div>

                        <div onclick="navigateTo('inventory')" class="group p-6 rounded-2xl bg-white border border-slate-100 hover:border-secondary/50 shadow-sm hover:shadow-xl hover:shadow-secondary/5 transition-all cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-secondary group-hover:text-white transition-all transform group-hover:-rotate-6">
                                        <span class="material-symbols-outlined">inventory_2</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark">Smart Inventory</h4>
                                        <p class="text-xs text-slate-500">Stock levels & warehouse status</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 group-hover:translate-x-1 group-hover:text-secondary transition-all">chevron_right</span>
                            </div>
                        </div>

                        <div onclick="navigateTo('users')" class="group p-6 rounded-2xl bg-white border border-slate-100 hover:border-accent/50 shadow-sm hover:shadow-xl hover:shadow-accent/5 transition-all cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-accent group-hover:text-white transition-all transform group-hover:scale-110">
                                        <span class="material-symbols-outlined">person_pin</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark">User Control</h4>
                                        <p class="text-xs text-slate-500">Roles, access & departments</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 group-hover:translate-x-1 group-hover:text-accent transition-all">chevron_right</span>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // State management
        const state = {
            user: JSON.parse(localStorage.getItem('user') || '{}'),
            orgSlug: localStorage.getItem('org_slug'),
            accessToken: localStorage.getItem('access_token'),
            profileCompletion: null,
            masterDataStatus: null,
            dashboardStats: null
        };

        // Initialize UI
        function initUI() {
            if (!state.accessToken || !state.orgSlug) {
                window.location.href = '/login';
                return;
            }

            // Set user info
            document.getElementById('userName').textContent = `${state.user.first_name || 'User'} ${state.user.last_name || ''}`.trim();
            document.getElementById('welcomeName').textContent = state.user.first_name || 'User';
            document.getElementById('userEmail').textContent = state.user.email || '';
            document.getElementById('userAvatar').textContent = (state.user.first_name || 'U').charAt(0).toUpperCase();

            // Date
            document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-GB', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });

            // Sidebar toggle for mobile
            document.getElementById('sidebarToggle').addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('-translate-x-full');
            });

            loadData();
        }

        // Data Fetching
        async function loadData() {
            try {
                // Parallel requests
                const [profileRes, masterRes, statsRes] = await Promise.all([
                    fetchAPI('/api/v1/profile-completion/status'),
                    fetchAPI('/api/v1/profile-completion/master-data-status'),
                    fetchAPI('/api/v1/dashboard/master-stats')
                ]);

                if (profileRes.success) state.profileCompletion = profileRes.data;
                if (masterRes.success) state.masterDataStatus = masterRes.data;
                if (statsRes.success) state.dashboardStats = statsRes.data;

                updateUI();
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        async function fetchAPI(url) {
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${state.accessToken}`,
                    'Accept': 'application/json',
                    'X-Org-Slug': state.orgSlug
                }
            });
            return await response.json();
        }

        function updateUI() {
            // Update Stats
            if (state.dashboardStats) {
                const s = state.dashboardStats;
                document.getElementById('statRevenue').textContent = '₹ ' + (s.overview?.total_revenue?.toLocaleString() || '0');
                document.getElementById('statProduction').textContent = s.overview?.active_production || '0';
                document.getElementById('statPurchase').textContent = s.overview?.pending_purchases || '0';
                document.getElementById('statLowStock').textContent = s.overview?.low_stock_count || '0';

                // Org Name
                const orgData = JSON.parse(localStorage.getItem('org_data') || '{}');
                document.getElementById('orgName').textContent = orgData.organization?.org_name || orgData.org_name || 'My Organization';

                // Recent Activity
                renderActivity(s.recent_activity || []);
            }

            // Update Progress Banner
            if (state.profileCompletion && state.masterDataStatus) {
                const profPerc = state.profileCompletion.percentage || 0;
                const mastPerc = state.masterDataStatus.percentage || 0;
                const overall = Math.round((profPerc + mastPerc) / 2);

                if (overall < 100) {
                    document.getElementById('setupBanner').classList.remove('hidden');
                    document.getElementById('overallProgress').style.width = overall + '%';
                    document.getElementById('overallPercentage').textContent = overall + '%';
                    
                    // Checkmarks
                    toggleCheck('profCheck', profPerc >= 100);
                    toggleCheck('masterCheck', mastPerc >= 100);
                    toggleCheck('teamCheck', state.dashboardStats?.organization?.departments > 0);
                }
            }
        }

        function toggleCheck(id, isActive) {
            const el = document.getElementById(id);
            if (isActive) {
                el.classList.remove('bg-slate-300');
                el.classList.add('bg-secondary');
            } else {
                el.classList.remove('bg-secondary');
                el.classList.add('bg-slate-300');
            }
        }

        function renderActivity(activities) {
            const list = document.getElementById('activityList');
            if (activities.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-10">
                        <span class="material-symbols-outlined text-slate-200 text-6xl">cloud_off</span>
                        <p class="text-slate-400 mt-4 text-sm font-semibold tracking-tight">No recent activity detected</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = activities.map(act => `
                <div class="flex items-start gap-4 p-3 hover:bg-slate-50 rounded-xl transition-all">
                    <div class="w-10 h-10 rounded-xl bg-${act.color}-50 flex items-center justify-center text-${act.color}-600 flex-shrink-0">
                        <span class="material-symbols-outlined text-xl">${act.icon}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-dark truncate">${act.title}</h4>
                        <p class="text-xs text-slate-500 truncate">${act.description}</p>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 whitespace-nowrap">${act.time}</span>
                </div>
            `).join('');
        }

        function refreshData() {
            const btn = event.currentTarget;
            btn.classList.add('animate-spin');
            loadData().finally(() => setTimeout(() => btn.classList.remove('animate-spin'), 600));
        }

        function navigateTo(section) {
            const routes = {
                'dashboard': `/org/${state.orgSlug}/dashboard`,
                'profile': `/org/${state.orgSlug}/profile-completion`,
                'masters': `/org/${state.orgSlug}/master-setup`,
                'departments': `/org/${state.orgSlug}/departments`,
                'users': `/org/${state.orgSlug}/users`,
                'production': `/org/${state.orgSlug}/production`,
                'inventory': `/org/${state.orgSlug}/inventory`
            };
            
            if (routes[section]) {
                window.location.href = routes[section];
            } else {
                alert(`${section} section coming soon!`);
            }
        }

        async function logout() {
            if (!confirm('Are you sure you want to sign out?')) return;
            
            try {
                await fetch('/logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
            } catch (e) {}

            localStorage.clear();
            window.location.href = '/login';
        }

        // Run on load
        document.addEventListener('DOMContentLoaded', initUI);
    </script>
</body>
</html>
