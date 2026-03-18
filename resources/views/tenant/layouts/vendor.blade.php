<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor') - {{ $organization->org_name }}</title>
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
                        category: '#F59E0B'
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
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-40">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 bg-category rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-white text-xl">handshake</span>
                    </div>
                    <div class="overflow-hidden">
                        <h2 class="text-sm font-semibold text-gray-900 truncate">Vendor</h2>
                        <p class="text-xs text-gray-500 truncate">{{ $organization->org_name }}</p>
                    </div>
                </div>
                <div x-show="!sidebarOpen" class="w-10 h-10 bg-category rounded-lg flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-white text-xl">handshake</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <!-- Back to Main Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/dashboard' : "/org/{$organization->org_slug}/dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">arrow_back</span>
                            <span x-show="sidebarOpen" class="font-medium">Main Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Category Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-dashboard' : "/org/{$organization->org_slug}/vendor-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-dashboard') ? 'bg-amber-50 text-category' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">dashboard</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="pt-2 border-t border-gray-200"></li>
                    
                    <!-- Vendor Modules -->
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Modules</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendors' : "/org/{$organization->org_slug}/vendors") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendors.*') ? 'bg-amber-50 text-category' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">handshake</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendors</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-contacts' : "/org/{$organization->org_slug}/vendor-contacts") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-contacts.*') ? 'bg-amber-50 text-category' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">contacts</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor Contacts</span>
                        </a>
                    </li>
                    <!-- <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-material-map' : "/org/{$organization->org_slug}/vendor-material-map") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-material-map.*') ? 'bg-amber-50 text-category' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">link</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor Material Map</span>
                        </a>
                    </li> -->
                </ul>
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-gray-200 p-4">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 w-full px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 bg-category rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-semibold text-sm" x-text="user.first_name && user.last_name ? (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase() : 'U'"></span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 text-left overflow-hidden">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="user.first_name && user.last_name ? user.first_name + ' ' + user.last_name : 'User'"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="user.email || ''"></p>
                        </div>
                        <span x-show="sidebarOpen" class="material-symbols-outlined text-gray-400 text-sm transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                        <a href="{{ url($tenantType === 'subdomain' ? '/profile' : "/org/{$organization->org_slug}/profile") }}" 
                           class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="material-symbols-outlined w-4">person</span>
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
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-category">handshake</span>
                        <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Vendor')</h1>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-xs text-amber-600 px-3 py-1 bg-amber-50 rounded-full font-semibold">
                        Vendor & Procurement
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
