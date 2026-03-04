<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $organization->org_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/api-client.js"></script>
    <style>
        [x-cloak] { display: none !important; }
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
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-industry text-white text-xl"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h2 class="text-sm font-semibold text-gray-900 truncate">{{ $organization->org_name }}</h2>
                        <p class="text-xs text-gray-500 truncate">{{ $organization->org_slug }}</p>
                    </div>
                </div>
                <div x-show="!sidebarOpen" class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mx-auto">
                    <i class="fas fa-industry text-white text-xl"></i>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/dashboard' : '/org/' . $organization->org_slug . '/dashboard') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-home text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/profile-completion' : '/org/' . $organization->org_slug . '/profile-completion') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.profile-completion') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-tasks text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Profile Setup</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/master-setup' : '/org/' . $organization->org_slug . '/master-setup') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.master-setup') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-database text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Master Setup</span>
                        </a>
                    </li>
                    <li class="pt-2 border-t border-gray-200"></li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/users' : '/org/' . $organization->org_slug . '/users') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.users.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-users text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments' : '/org/' . $organization->org_slug . '/departments') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.departments.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-building text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Departments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/roles' : '/org/' . $organization->org_slug . '/roles') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.roles.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-user-shield text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Roles</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/reports' : '/org/' . $organization->org_slug . '/reports') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.reports.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-chart-bar text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/settings' : '/org/' . $organization->org_slug . '/settings') }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.settings') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <i class="fas fa-cog text-lg w-5"></i>
                            <span x-show="sidebarOpen" class="font-medium">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-gray-200 p-4">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 w-full px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-semibold text-sm" x-text="user.first_name && user.last_name ? (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase() : 'U'"></span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 text-left overflow-hidden">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="user.first_name && user.last_name ? user.first_name + ' ' + user.last_name : 'User'"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="user.email || ''"></p>
                        </div>
                        <i x-show="sidebarOpen" class="fas fa-chevron-up text-gray-400 text-sm" :class="{ 'rotate-180': !open }"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/profile' : '/org/' . $organization->org_slug . '/profile') }}" 
                           class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user w-4"></i>
                            <span>Profile</span>
                        </a>
                        <form action="{{ url(request()->get('tenant_type') === 'subdomain' ? '/logout' : '/org/' . $organization->org_slug . '/logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center space-x-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left">
                                <i class="fas fa-sign-out-alt w-4"></i>
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
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="text-gray-600 hover:text-gray-900 relative">
                        <i class="far fa-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                    </button>
                    
                    @if($tenantType === 'subdomain')
                        <span class="text-xs text-gray-500 px-3 py-1 bg-gray-100 rounded-full">
                            <i class="fas fa-globe mr-1"></i>Subdomain
                        </span>
                    @else
                        <span class="text-xs text-gray-500 px-3 py-1 bg-gray-100 rounded-full">
                            <i class="fas fa-link mr-1"></i>Path-based
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
