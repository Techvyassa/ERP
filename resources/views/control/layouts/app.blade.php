<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') - Zap ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#193261',
                        admin: '#7c3aed'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, userMenuOpen: false, user: {} }" x-init="
    user = JSON.parse(localStorage.getItem('user') || '{}');
">
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-gradient-to-b from-admin to-purple-700 text-white transition-all duration-300 z-40">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="flex items-center justify-between p-4 border-b border-purple-600">
                <div class="flex items-center space-x-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-admin text-xl">admin_panel_settings</span>
                    </div>
                    <div class="overflow-hidden">
                        <h2 class="text-sm font-semibold text-white truncate">Super Admin</h2>
                        <p class="text-xs text-purple-200 truncate">Control Panel</p>
                    </div>
                </div>
                <div x-show="!sidebarOpen" class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-admin text-xl">admin_panel_settings</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('control.dashboard') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.dashboard') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">dashboard</span>
                            <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="pt-2 border-t border-purple-600"></li>
                    
                    <!-- Organizations Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-purple-200 uppercase">Organizations</span>
                    </li>
                    <li>
                        <a href="{{ route('control.organizations.index') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.organizations.*') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">business</span>
                            <span x-show="sidebarOpen" class="font-medium">Organizations</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('control.subscriptions.index') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.subscriptions.*') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">card_membership</span>
                            <span x-show="sidebarOpen" class="font-medium">Subscriptions</span>
                        </a>
                    </li>
                    
                    <!-- Plans Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-purple-200 uppercase">Plans & Billing</span>
                    </li>
                    <li>
                        <a href="{{ route('control.plans.index') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.plans.*') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">price_check</span>
                            <span x-show="sidebarOpen" class="font-medium">Subscription Plans</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('control.payments.index') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.payments.*') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">payments</span>
                            <span x-show="sidebarOpen" class="font-medium">Payments</span>
                        </a>
                    </li>
                    
                    <!-- System Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-purple-200 uppercase">System</span>
                    </li>
                    <li>
                        <a href="{{ route('control.features.index') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.features.*') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">toggle_on</span>
                            <span x-show="sidebarOpen" class="font-medium">Feature Control</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('control.settings') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('control.settings') ? 'bg-white/20' : 'hover:bg-white/10' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">settings</span>
                            <span x-show="sidebarOpen" class="font-medium">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-purple-600 p-4">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 w-full px-3 py-2 rounded-lg hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-admin font-semibold text-sm" x-text="user.first_name && user.last_name ? (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase() : 'SA'"></span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 text-left overflow-hidden">
                            <p class="text-sm font-medium text-white truncate" x-text="user.first_name && user.last_name ? user.first_name + ' ' + user.last_name : 'Super Admin'"></p>
                            <p class="text-xs text-purple-200 truncate" x-text="user.email || ''"></p>
                        </div>
                        <span x-show="sidebarOpen" class="material-symbols-outlined text-purple-200 text-sm transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                        <a href="{{ route('control.profile') }}" 
                           class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="material-symbols-outlined w-4 text-gray-600">person</span>
                            <span>Profile</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center space-x-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left">
                                <span class="material-symbols-outlined w-4">logout</span>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div :class="sidebarOpen ? 'ml-64' : 'ml-20'" class="transition-all duration-300">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-900">
                        <span class="material-symbols-outlined text-xl">menu</span>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="text-gray-600 hover:text-gray-900 relative">
                        <span class="material-symbols-outlined text-xl">notifications</span>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                    </button>
                    
                    <span class="text-xs text-admin font-semibold px-3 py-1 bg-purple-50 rounded-full">
                        <span class="material-symbols-outlined text-sm mr-1 align-middle">admin_panel_settings</span>Super Admin
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
