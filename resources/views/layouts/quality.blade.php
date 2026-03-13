<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Quality Portal - Nexus ERP')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#193261",
                        "qc": "#0ea5e9",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 font-display">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <div class="size-8 bg-qc rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined font-bold">biotech</span>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-primary">Nexus Quality</h1>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('quality.dashboard') ? 'bg-qc text-white' : 'text-slate-600 hover:bg-slate-100' }} transition-colors" href="/quality/dashboard">
                <span class="material-symbols-outlined">analytics</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" href="/quality/inspections">
                <span class="material-symbols-outlined">fact_check</span>
                <span class="text-sm font-medium">Pending Inspections</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" href="/quality/decisions">
                <span class="material-symbols-outlined">rule</span>
                <span class="text-sm font-medium">Usage Decisions</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" href="/quality/reports">
                <span class="material-symbols-outlined">description</span>
                <span class="text-sm font-medium">Quality Reports</span>
            </a>
        </nav>
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-qc/10 flex items-center justify-center text-qc font-bold">
                    QA
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-bold text-slate-900 truncate">Analyst 042</p>
                    <p class="text-[10px] uppercase font-bold text-qc">QC Department</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    <span class="size-1.5 bg-green-500 rounded-full animate-pulse"></span>
                    Analyzer Online
                </div>
            </div>
            <div class="flex items-center gap-6">
                 <button class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <form action="/logout" method="POST">
                    @csrf
                    <button class="text-sm font-bold text-slate-600 hover:text-red-500 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </header>
        <div class="flex-1 overflow-auto p-8 bg-slate-50/30">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
