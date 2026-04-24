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
                    


                     <!-- Tax Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/tax-dashboard' : "/org/{$organization->org_slug}/tax-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.tax-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">receipt_long</span>
                            <span x-show="sidebarOpen" class="font-medium">GST & Tax </span>
                        </a>
                    </li>


                    <!-- Inventory Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/inventory-dashboard' : "/org/{$organization->org_slug}/inventory-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.inventory-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">inventory</span>
                            <span x-show="sidebarOpen" class="font-medium">Material Management </span>
                        </a>
                    </li>

                    <!-- Organization Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/organization-dashboard' : "/org/{$organization->org_slug}/organization-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.organization-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">corporate_fare</span>
                            <span x-show="sidebarOpen" class="font-medium">Organization</span>
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

                    <!-- Quality Dashboard -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/quality-dashboard' : "/org/{$organization->org_slug}/quality-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.quality-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">biotech</span>
                            <span x-show="sidebarOpen" class="font-medium">Quality</span>
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
                    
                   
                    
                    

                    <!-- Customer Master -->
                    <li>
                        <a href="{{ url($tenantType === 'subdomain' ? '/customer-dashboard' : "/org/{$organization->org_slug}/customer-dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.customer-dashboard') ? 'bg-blue-50 text-primary' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">groups</span>
                            <span x-show="sidebarOpen" class="font-medium">Customer Master</span>
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
                    <!-- Global Search -->
                    <div class="relative" x-data="{ 
                        searchOpen: false, 
                        searchQuery: '',
                        searchResults: [],
                        allPages: [
                            { name: 'Dashboard', url: '{{ url($tenantType === 'subdomain' ? '/dashboard' : "/org/{$organization->org_slug}/dashboard") }}', icon: 'home', category: 'Main' },
                            { name: 'Profile Setup', url: '{{ url($tenantType === 'subdomain' ? '/profile-completion' : "/org/{$organization->org_slug}/profile-completion") }}', icon: 'task', category: 'Main' },
                            { name: 'Master Setup', url: '{{ url($tenantType === 'subdomain' ? '/master-setup' : "/org/{$organization->org_slug}/master-setup") }}', icon: 'database', category: 'Main' },
                            { name: 'Users', url: '{{ url($tenantType === 'subdomain' ? '/users' : "/org/{$organization->org_slug}/users") }}', icon: 'group', category: 'Organization', keywords: ['user', 'employee', 'staff'] },
                            { name: 'Departments', url: '{{ url($tenantType === 'subdomain' ? '/departments' : "/org/{$organization->org_slug}/departments") }}', icon: 'corporate_fare', category: 'Organization', keywords: ['dept', 'department', 'division'] },
                            { name: 'Roles', url: '{{ url($tenantType === 'subdomain' ? '/roles' : "/org/{$organization->org_slug}/roles") }}', icon: 'badge', category: 'Organization', keywords: ['role', 'permission', 'access'] },
                            { name: 'Approval Matrix', url: '{{ url($tenantType === 'subdomain' ? '/approval-matrix' : "/org/{$organization->org_slug}/approval-matrix") }}', icon: 'approval', category: 'Organization', keywords: ['approval', 'workflow', 'authorization'] },
                            { name: 'Materials', url: '{{ url($tenantType === 'subdomain' ? '/materials' : "/org/{$organization->org_slug}/materials") }}', icon: 'inventory_2', category: 'Inventory', keywords: ['material', 'raw material', 'rm', 'item'] },
                            { name: 'Products', url: '{{ url($tenantType === 'subdomain' ? '/products' : "/org/{$organization->org_slug}/products") }}', icon: 'shopping_bag', category: 'Inventory', keywords: ['product', 'finished goods', 'fg', 'item'] },
                            { name: 'Warehouses', url: '{{ url($tenantType === 'subdomain' ? '/warehouses' : "/org/{$organization->org_slug}/warehouses") }}', icon: 'warehouse', category: 'Inventory', keywords: ['warehouse', 'storage', 'location'] },
                            { name: 'UOM', url: '{{ url($tenantType === 'subdomain' ? '/uom' : "/org/{$organization->org_slug}/uom") }}', icon: 'straighten', category: 'Inventory', keywords: ['uom', 'unit', 'measurement'] },
                            { name: 'Bin Locations', url: '{{ url($tenantType === 'subdomain' ? '/bin-locations' : "/org/{$organization->org_slug}/bin-locations") }}', icon: 'shelves', category: 'Inventory', keywords: ['bin', 'rack', 'location', 'shelf'] },
                            { name: 'Vendors', url: '{{ url($tenantType === 'subdomain' ? '/vendors' : "/org/{$organization->org_slug}/vendors") }}', icon: 'store', category: 'Vendor', keywords: ['vendor', 'supplier', 'partner'] },
                            // { name: 'Vendor Material Map', url: '{{ url($tenantType === 'subdomain' ? '/vendor-material-map' : "/org/{$organization->org_slug}/vendor-material-map") }}', icon: 'link', category: 'Vendor', keywords: ['mapping', 'vendor material', 'avl'] },
                            { name: 'HSN Codes', url: '{{ url($tenantType === 'subdomain' ? '/hsn-codes' : "/org/{$organization->org_slug}/hsn-codes") }}', icon: 'qr_code', category: 'Tax', keywords: ['hsn', 'code', 'tax code'] },
                            { name: 'GST Taxes', url: '{{ url($tenantType === 'subdomain' ? '/gst-taxes' : "/org/{$organization->org_slug}/gst-taxes") }}', icon: 'receipt', category: 'Tax', keywords: ['gst', 'tax', 'cgst', 'sgst', 'igst'] },
                            { name: 'Currency', url: '{{ url($tenantType === 'subdomain' ? '/currency' : "/org/{$organization->org_slug}/currency") }}', icon: 'currency_exchange', category: 'Tax', keywords: ['currency', 'exchange', 'forex'] },
                            { name: 'BOM Header', url: '{{ url($tenantType === 'subdomain' ? '/bom-header' : "/org/{$organization->org_slug}/bom-header") }}', icon: 'description', category: 'BOM', keywords: ['bom', 'bill of materials', 'recipe'] },
                            { name: 'BOM Detail', url: '{{ url($tenantType === 'subdomain' ? '/bom-detail' : "/org/{$organization->org_slug}/bom-detail") }}', icon: 'list_alt', category: 'BOM', keywords: ['bom detail', 'component', 'material list'] },
                            { name: 'QC Dashboard', url: '{{ url($tenantType === 'subdomain' ? '/quality-dashboard' : "/org/{$organization->org_slug}/quality-dashboard") }}', icon: 'biotech', category: 'Quality', keywords: ['quality', 'qc', 'inspection'] },
                            { name: 'QC Test Types', url: '{{ url($tenantType === 'subdomain' ? '/qc-test-types' : "/org/{$organization->org_slug}/qc-test-types") }}', icon: 'science', category: 'Quality', keywords: ['qc', 'test type', 'quality master'] },
                            { name: 'QC Parameters', url: '{{ url($tenantType === 'subdomain' ? '/qc-parameters' : "/org/{$organization->org_slug}/qc-parameters") }}', icon: 'biotech', category: 'Quality', keywords: ['qc parameter', 'specification', 'quality parameter'] },
                            { name: 'Customer Master', url: '{{ url($tenantType === 'subdomain' ? '/customer-dashboard' : "/org/{$organization->org_slug}/customer-dashboard") }}', icon: 'groups', category: 'Customer', keywords: ['customer', 'client', 'buyer', 'account'] },
                            { name: 'Reports', url: '{{ url($tenantType === 'subdomain' ? '/reports' : "/org/{$organization->org_slug}/reports") }}', icon: 'bar_chart', category: 'Other', keywords: ['report', 'analytics', 'dashboard'] },
                            { name: 'Settings', url: '{{ url($tenantType === 'subdomain' ? '/settings' : "/org/{$organization->org_slug}/settings") }}', icon: 'settings', category: 'Other', keywords: ['setting', 'configuration', 'preferences'] }
                        ],
                        search() {
                            if (this.searchQuery.length < 2) {
                                this.searchResults = [];
                                return;
                            }
                            const query = this.searchQuery.toLowerCase();
                            this.searchResults = this.allPages.filter(page => {
                                const nameMatch = page.name.toLowerCase().includes(query);
                                const categoryMatch = page.category.toLowerCase().includes(query);
                                const keywordMatch = page.keywords && page.keywords.some(k => k.includes(query));
                                return nameMatch || categoryMatch || keywordMatch;
                            }).slice(0, 8);
                        },
                        navigate(url) {
                            window.location.href = url;
                        },
                        handleKeydown(event) {
                            if (event.key === 'Escape') {
                                this.searchOpen = false;
                                this.searchQuery = '';
                                this.searchResults = [];
                            }
                        }
                    }">
                        <button @click="searchOpen = true" class="flex items-center space-x-2 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                            <span class="material-symbols-outlined text-xl">search</span>
                            <span class="text-sm text-gray-500">Search...</span>
                            <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded">Ctrl-K</kbd>
                        </button>
                        
                        <!-- Search Modal -->
                        <div x-show="searchOpen" 
                             @click.away="searchOpen = false"
                             @keydown.window="handleKeydown"
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             style="display: none;">
                            <div class="flex items-start justify-center min-h-screen pt-20 px-4">
                                <!-- Backdrop -->
                                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"></div>
                                
                                <!-- Modal Content -->
                                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl">
                                    <!-- Search Input -->
                                    <div class="flex items-center px-4 py-3 border-b border-gray-200">
                                        <span class="material-symbols-outlined text-gray-400 mr-3">search</span>
                                        <input 
                                            type="text" 
                                            x-model="searchQuery"
                                            @input="search()"
                                            placeholder="Search for pages, features, or modules..."
                                            class="flex-1 outline-none text-gray-900 placeholder-gray-400"
                                            autofocus>
                                        <button @click="searchOpen = false; searchQuery = ''; searchResults = [];" class="ml-2 text-gray-400 hover:text-gray-600">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Search Results -->
                                    <div class="max-h-96 overflow-y-auto">
                                        <template x-if="searchQuery.length >= 2 && searchResults.length > 0">
                                            <div class="py-2">
                                                <template x-for="(result, index) in searchResults" :key="index">
                                                    <button @click="navigate(result.url)" 
                                                            class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left">
                                                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                            <span class="material-symbols-outlined text-blue-600 text-lg" x-text="result.icon"></span>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900" x-text="result.name"></p>
                                                            <p class="text-xs text-gray-500" x-text="result.category"></p>
                                                        </div>
                                                        <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        
                                        <template x-if="searchQuery.length >= 2 && searchResults.length === 0">
                                            <div class="py-12 text-center">
                                                <span class="material-symbols-outlined text-gray-300 text-5xl">search_off</span>
                                                <p class="text-sm text-gray-500 mt-2">No results found</p>
                                                <p class="text-xs text-gray-400 mt-1">Try searching for materials, vendors, or other modules</p>
                                            </div>
                                        </template>
                                        
                                        <template x-if="searchQuery.length < 2">
                                            <div class="py-8">
                                                <p class="text-xs text-gray-400 text-center mb-4">Quick Access</p>
                                                <div class="grid grid-cols-2 gap-2 px-4">
                                                    <button @click="navigate('{{ url($tenantType === 'subdomain' ? '/materials' : "/org/{$organization->org_slug}/materials") }}')" 
                                                            class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors text-left">
                                                        <span class="material-symbols-outlined text-blue-600 text-sm">inventory_2</span>
                                                        <span class="text-sm text-gray-700">Materials</span>
                                                    </button>
                                                    <button @click="navigate('{{ url($tenantType === 'subdomain' ? '/products' : "/org/{$organization->org_slug}/products") }}')" 
                                                            class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors text-left">
                                                        <span class="material-symbols-outlined text-green-600 text-sm">shopping_bag</span>
                                                        <span class="text-sm text-gray-700">Products</span>
                                                    </button>
                                                    <button @click="navigate('{{ url($tenantType === 'subdomain' ? '/vendors' : "/org/{$organization->org_slug}/vendors") }}')" 
                                                            class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors text-left">
                                                        <span class="material-symbols-outlined text-purple-600 text-sm">store</span>
                                                        <span class="text-sm text-gray-700">Vendors</span>
                                                    </button>
                                                    <button @click="navigate('{{ url($tenantType === 'subdomain' ? '/users' : "/org/{$organization->org_slug}/users") }}')" 
                                                            class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors text-left">
                                                        <span class="material-symbols-outlined text-orange-600 text-sm">group</span>
                                                        <span class="text-sm text-gray-700">Users</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <!-- Footer -->
                                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>Type to search across all modules</span>
                                            <div class="flex items-center space-x-2">
                                                <kbd class="px-2 py-1 bg-white border border-gray-200 rounded">ESC</kbd>
                                                <span>to close</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="relative" x-data="{ notificationOpen: false }">
                        <button @click="notificationOpen = !notificationOpen" class="text-gray-600 hover:text-gray-900 relative">
                            <span class="material-symbols-outlined text-xl">notifications</span>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div x-show="notificationOpen" 
                             @click.away="notificationOpen = false"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            
                            <!-- Header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                <button class="text-xs text-blue-600 hover:text-blue-700">Mark all as read</button>
                            </div>
                            
                            <!-- Notification List -->
                            <div class="max-h-96 overflow-y-auto">
                                <!-- Notification Item 1 -->
                                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined text-blue-600 text-sm">task_alt</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 font-medium">Profile setup completed</p>
                                            <p class="text-xs text-gray-500 mt-1">Your organization profile has been successfully set up</p>
                                            <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                                        </div>
                                        <div class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-2"></div>
                                    </div>
                                </div>
                                
                                <!-- Notification Item 2 -->
                                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined text-green-600 text-sm">person_add</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 font-medium">New user added</p>
                                            <p class="text-xs text-gray-500 mt-1">John Doe has been added to your organization</p>
                                            <p class="text-xs text-gray-400 mt-1">5 hours ago</p>
                                        </div>
                                        <div class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-2"></div>
                                    </div>
                                </div>
                                
                                <!-- Notification Item 3 -->
                                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined text-yellow-600 text-sm">inventory</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 font-medium">Low stock alert</p>
                                            <p class="text-xs text-gray-500 mt-1">Material RM-001 is running low on stock</p>
                                            <p class="text-xs text-gray-400 mt-1">1 day ago</p>
                                        </div>
                                        <div class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-2"></div>
                                    </div>
                                </div>
                                
                                <!-- Empty State (hidden when there are notifications) -->
                                <div class="hidden px-4 py-12 text-center">
                                    <span class="material-symbols-outlined text-gray-300 text-5xl">notifications_off</span>
                                    <p class="text-sm text-gray-500 mt-2">No notifications</p>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="px-4 py-3 border-t border-gray-200 text-center">
                                <a href="#" class="text-xs text-blue-600 hover:text-blue-700 font-medium">View all notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            @yield('content')
        </main>
    </div>

    <!-- Global Toast Notification System -->
    @include('components.toast')

    <!-- Global Confirmation Modal -->
    @include('components.confirm-modal')
</body>
</html>
