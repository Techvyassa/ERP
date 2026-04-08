<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quality Portal') - {{ $organization->org_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#193261',
                        qc: '#0ea5e9'
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
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, user: {} }" x-init="user = JSON.parse(localStorage.getItem('user') || '{}');">
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-40">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 bg-qc rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-white text-xl">biotech</span>
                    </div>
                    <div class="overflow-hidden">
                        <h2 class="text-sm font-semibold text-gray-900 truncate">{{ $organization->org_name }}</h2>
                        <p class="text-xs text-gray-500 truncate">{{ $organization->org_slug }}</p>
                    </div>
                </div>
                <div x-show="!sidebarOpen" class="w-10 h-10 bg-qc rounded-lg flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-white text-xl">biotech</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ url("/org/{$organization->org_slug}/quality/dashboard") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.quality.dashboard') ? 'bg-sky-50 text-qc font-semibold' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">home</span>
                            <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="pt-2 border-t border-gray-200"></li>
                    
                    <li x-show="sidebarOpen" class="px-3 py-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Quality</span>
                    </li>
                    
                    <li>
                        <a href="{{ url("/org/{$organization->org_slug}/quality/inspections") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.quality.inspections') || request()->routeIs('tenant.quality.inspections.show') ? 'bg-sky-50 text-qc font-semibold' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">fact_check</span>
                            <span x-show="sidebarOpen" class="font-medium">Inspections</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ url("/org/{$organization->org_slug}/quality/decisions") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.quality.decisions') ? 'bg-sky-50 text-qc font-semibold' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">rule</span>
                            <span x-show="sidebarOpen" class="font-medium">Usage Decisions</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ url("/org/{$organization->org_slug}/quality/reports") }}" 
                           class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('tenant.quality.reports') ? 'bg-sky-50 text-qc font-semibold' : 'text-gray-700 hover:bg-gray-100' }} transition-colors">
                            <span class="material-symbols-outlined text-lg w-5">lab_profile</span>
                            <span x-show="sidebarOpen" class="font-medium">Quality Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-gray-200 p-4">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 w-full px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 bg-qc rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-semibold text-sm" x-text="user.first_name && user.last_name ? (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase() : 'U'"></span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 text-left overflow-hidden">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="user.first_name && user.last_name ? user.first_name + ' ' + user.last_name : 'User'"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="user.email || ''"></p>
                        </div>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                        <form action="{{ route('tenant.logout', $organization->org_slug) }}" method="POST">
                            @csrf
                            <input type="hidden" name="redirect_to" value="/org/{{ $organization->org_slug }}/quality/login">
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
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Quality Portal')</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="text-gray-600 hover:text-gray-900">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </button>
                    <button class="text-gray-600 hover:text-gray-900">
                        <span class="material-symbols-outlined text-xl">notifications</span>
                    </button>
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
