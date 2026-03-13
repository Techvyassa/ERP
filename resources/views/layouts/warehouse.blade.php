<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Warehouse Portal - Nexus ERP')</title>
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
                        "warehouse": "#f59e0b",
                        "background-light": "#f6f7f8",
                        "background-dark": "#0f172a",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-700 bg-slate-900 flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <div class="size-8 bg-warehouse rounded-lg flex items-center justify-center text-slate-900">
                <span class="material-symbols-outlined font-bold">warehouse</span>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white">Nexus Store</h1>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('warehouse.dashboard') ? 'bg-warehouse/20 text-warehouse' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} transition-colors" href="/warehouse/dashboard">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="text-sm font-medium">Store Overview</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors" href="/warehouse/gate-entry">
                <span class="material-symbols-outlined">gate</span>
                <span class="text-sm font-medium">Gate Entry</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors" href="/warehouse/receipts">
                <span class="material-symbols-outlined">inventory_2</span>
                <span class="text-sm font-medium">Material Receipts</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors" href="/warehouse/putaway">
                <span class="material-symbols-outlined">shelves</span>
                <span class="text-sm font-medium">Stock Putaway</span>
            </a>
        </nav>
        <div class="p-4 bg-slate-800/50 mt-auto">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-warehouse flex items-center justify-center text-slate-900 font-bold">
                    SK
                </div>
                <div class="overflow-hidden text-white">
                    <p class="text-sm font-semibold truncate text-warehouse">Store Keeper</p>
                    <p class="text-[10px] uppercase font-bold text-slate-400">Main Warehouse</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between px-8">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400">terminal</span>
                <span class="text-xs font-mono text-slate-400">NODE_WH_01</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right mr-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Current Shift</p>
                    <p class="text-sm font-bold text-warehouse">06:00 - 14:00 (A)</p>
                </div>
                <form action="/logout" method="POST">
                    @csrf
                    <button class="bg-warehouse text-slate-900 px-4 py-2 rounded-lg text-sm font-bold hover:bg-warehouse/90 transition-all">
                        End Shift
                    </button>
                </form>
            </div>
        </header>
        <div class="flex-1 overflow-auto p-8">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
