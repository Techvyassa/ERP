<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Procurement Portal - Nexus ERP')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#193261",
                        "background-light": "#f6f7f8",
                        "background-dark": "#13171f",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <div class="size-8 bg-primary rounded flex items-center justify-center text-white">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <h1 class="text-xl font-bold tracking-tight">Nexus ERP</h1>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('procurement.dashboard') ? 'bg-primary/10 text-primary dark:text-slate-100 dark:bg-primary/40' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-primary/20' }} transition-colors" href="/procurement/dashboard">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="text-sm font-medium">Overview</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-primary/20 transition-colors" href="/procurement/purchase-orders">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span class="text-sm font-medium">Purchase Orders</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-primary/20 transition-colors" href="/procurement/vendors">
                <span class="material-symbols-outlined">group</span>
                <span class="text-sm font-medium">Vendors</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-primary/20 transition-colors" href="/procurement/asn">
                <span class="material-symbols-outlined">lan</span>
                <span class="text-sm font-medium">Inward Tracking</span>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3 p-2">
                <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold truncate">Procurement Exec</p>
                    <p class="text-xs text-slate-500 truncate">Procurement Dept.</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navbar -->
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex items-center justify-between px-8">
            <div class="flex items-center gap-4 flex-1 max-w-xl">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 border-none rounded-lg focus:ring-2 focus:ring-primary text-sm transition-all" placeholder="Search PO, ASN, or Vendor..." type="text"/>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg relative">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <button class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                    <span class="material-symbols-outlined">settings</span>
                </button>
                <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 mx-2"></div>
                <form action="/logout" method="POST">
                    @csrf
                    <button class="bg-primary/10 text-primary hover:bg-primary/20 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="flex-1 overflow-auto p-8">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
