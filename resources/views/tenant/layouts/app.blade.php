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
                    
                    <!-- Organization Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Organization</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/users' : "/org/{$organization->org_slug}/users") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.users.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">groups</span>
                            <span x-show="sidebarOpen" class="font-medium">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/departments' : "/org/{$organization->org_slug}/departments") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.departments.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">apartment</span>
                            <span x-show="sidebarOpen" class="font-medium">Departments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/roles' : "/org/{$organization->org_slug}/roles") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.roles.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">shield_person</span>
                            <span x-show="sidebarOpen" class="font-medium">Roles</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/approval-matrix' : "/org/{$organization->org_slug}/approval-matrix") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.approval-matrix.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">account_tree</span>
                            <span x-show="sidebarOpen" class="font-medium">Approval Matrix</span>
                        </a>
                    </li>
                    
                    <!-- Inventory Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Inventory</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/materials' : "/org/{$organization->org_slug}/materials") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.materials.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">inventory_2</span>
                            <span x-show="sidebarOpen" class="font-medium">Materials</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/products' : "/org/{$organization->org_slug}/products") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.products.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">package_2</span>
                            <span x-show="sidebarOpen" class="font-medium">Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/warehouses' : "/org/{$organization->org_slug}/warehouses") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.warehouses.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">warehouse</span>
                            <span x-show="sidebarOpen" class="font-medium">Warehouses</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/bin-locations' : "/org/{$organization->org_slug}/bin-locations") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.bin-locations.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">grid_view</span>
                            <span x-show="sidebarOpen" class="font-medium">Bin Locations</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/uom' : "/org/{$organization->org_slug}/uom") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.uom.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">straighten</span>
                            <span x-show="sidebarOpen" class="font-medium">UOM</span>
                        </a>
                    </li>
                    
                    <!-- Vendor Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Vendor</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendors' : "/org/{$organization->org_slug}/vendors") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendors.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">handshake</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendors</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-contacts' : "/org/{$organization->org_slug}/vendor-contacts") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-contacts.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">contacts</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor Contacts</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/vendor-material-map' : "/org/{$organization->org_slug}/vendor-material-map") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.vendor-material-map.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">link</span>
                            <span x-show="sidebarOpen" class="font-medium">Vendor Material Map</span>
                        </a>
                    </li>
                    
                    <!-- Tax Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Tax & Finance</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/hsn-codes' : "/org/{$organization->org_slug}/hsn-codes") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.hsn-codes.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">qr_code</span>
                            <span x-show="sidebarOpen" class="font-medium">HSN Codes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/gst-taxes' : "/org/{$organization->org_slug}/gst-taxes") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.gst-taxes.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">percent</span>
                            <span x-show="sidebarOpen" class="font-medium">GST Taxes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/currency' : "/org/{$organization->org_slug}/currency") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.currency.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">payments</span>
                            <span x-show="sidebarOpen" class="font-medium">Currency</span>
                        </a>
                    </li>
                    
                    <!-- BOM Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
                        <span class="text-xs font-semibold text-gray-400 uppercase">BOM</span>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/bom-header' : "/org/{$organization->org_slug}/bom-header") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.bom-header.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">list_alt</span>
                            <span x-show="sidebarOpen" class="font-medium">BOM Header</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/bom-detail' : "/org/{$organization->org_slug}/bom-detail") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.bom-detail.*') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">format_list_numbered</span>
                            <span x-show="sidebarOpen" class="font-medium">BOM Detail</span>
                        </a>
                    </li>
                    
                    <!-- Other Section -->
                    <li x-show="sidebarOpen" class="px-3 py-2 pt-4">
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
