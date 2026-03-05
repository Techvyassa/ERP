<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $organization->org_name }}</title>
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
                        primary: '#193261'
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
    if (!user.first_name) {
        console.warn('User data not found in localStorage');
    }
">
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-40">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-white text-xl">precision_manufacturing</span>
                    </div>
                    <div class="overflow-hidden">
                        <h2 class="text-sm font-semibold text-gray-900 truncate">{{ $organization->org_name }}</h2>
                        <p class="text-xs text-gray-500 truncate">{{ $organization->org_slug }}</p>
                    </div>
                </div>
                <div x-show="!sidebarOpen" class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-white text-xl">precision_manufacturing</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/dashboard' : "/org/{$organization->org_slug}/dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">home</span>
                            <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/profile-completion' : "/org/{$organization->org_slug}/profile-completion") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.profile-completion') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">task</span>
                            <span x-show="sidebarOpen" class="font-medium">Profile Setup</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/master-setup' : "/org/{$organization->org_slug}/master-setup") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.master-setup') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">database</span>
                            <span x-show="sidebarOpen" class="font-medium">Master Setup</span>
                        </a>
                    </li>
                    
                    <li class="pt-2 border-t border-gray-200"></li>
                    
                    <!-- Master Data Categories -->
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Master Data</span>
                    </li>
                    
                    <!-- Organization Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/organization-dashboard' : "/org/{$organization->org_slug}/organization-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.organization-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">corporate_fare</span>
                            <span x-show="sidebarOpen" class="font-medium">Organization</span>
                        </a>
                    </li>
                    
                    <!-- Inventory Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/inventory-dashboard' : "/org/{$organization->org_slug}/inventory-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.inventory-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">inventory</span>
                            <span x-show="sidebarOpen" class="font-medium">Inventory</span>
                        </a>
                    </li>
                    
                    <!-- Vendor Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-dashboard' : "/org/{$organization->org_slug}/vendor-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">handshake</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor</span>
                        </a>
                    </li>
                    
                    <!-- Tax Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/tax-dashboard' : "/org/{$organization->org_slug}/tax-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.tax-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">receipt_long</span>
                            <span x-show="sidebarOpen" class="font-medium">Tax & Finance</span>
                        </a>
                    </li>
                    
                    <!-- BOM Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/production-dashboard' : "/org/{$organization->org_slug}/production-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.production-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">precision_manufacturing</span>
                            <span x-show="sidebarOpen" class="font-medium">Production & BOM</span>
                        </a>
                    </li>
                    
                    <li class="pt-2 border-t border-gray-200"></li>
                    
                    <!-- Other Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Other</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/reports' : "/org/{$organization->org_slug}/reports") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.reports.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">bar_chart</span>
                            <span x-show="sidebarOpen" class="font-medium">Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/settings' : "/org/{$organization->org_slug}/settings") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.settings') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">settings</span>
                            <span x-show="sidebarOpen" class="font-medium">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-gray-200 p-4">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 w-full px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-semibold text-sm" x-text="user.first_name && user.last_name ? (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase() : 'U'"></span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 text-left overflow-hidden">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="user.first_name && user.last_name ? user.first_name + ' ' + user.last_name : 'User'"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="user.email || ''"></p>
                        </div>
                        <span x-show="sidebarOpen" class="material-symbols-outlined text-gray-400 text-sm transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                        <a href="{{ url($tenantType === 'subdomain' ? '/profile' : "/org/{$organization->org_slug}/profile") }}" 
                           class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="material-symbols-outlined w-4">person</span>
                            <span>Profile</span>
                        </a>
                        <form action="{{ url($tenantType === 'subdomain' ? '/logout' : "/org/{$organization->org_slug}/logout") }}" method="POST">
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
                    
                    @if($tenantType === 'subdomain')
                        <span class="text-xs text-gray-500 px-3 py-1 bg-gray-100 rounded-full">
                            <span class="material-symbols-outlined text-sm mr-1 align-middle">language</span>Subdomain
                        </span>
                    @else
                        <span class="text-xs text-gray-500 px-3 py-1 bg-gray-100 rounded-full">
                            <span class="material-symbols-outlined text-sm mr-1 align-middle">link</span>Path-based
                        </span>
                    @endif
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
