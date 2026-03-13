@extends('layouts.warehouse')

@section('title', 'Warehouse Operations - Nexus ERP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Logistics & Receiving</h2>
            <p class="text-slate-500">Monitor vehicle entries, material arrivals, and storage.</p>
        </div>
        <div class="flex gap-2">
             <button class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">qr_code_scanner</span>
                Scan Gate Pass
            </button>
            <button class="bg-warehouse text-slate-900 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span>
                New Gate Entry
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 border-l-4 border-warehouse p-6 rounded-xl shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Expected Today</p>
            <h3 class="text-3xl font-black">24 ASNs</h3>
            <p class="text-xs text-slate-400 mt-2">8 Already arrived at gate</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border-l-4 border-emerald-500 p-6 rounded-xl shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Gate Verification</p>
            <h3 class="text-3xl font-black text-emerald-500">12 Pending</h3>
            <p class="text-xs text-slate-400 mt-2">Average wait time: 14 mins</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border-l-4 border-blue-500 p-6 rounded-xl shadow-sm">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unloading Bays</p>
            <h3 class="text-3xl font-black text-blue-500">3 / 5 Active</h3>
            <p class="text-xs text-slate-400 mt-2">Dock 1, 3, 4 occupied</p>
        </div>
    </div>

    <!-- Live Inward Tracker -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
            <h4 class="font-bold">Live Receiving Queue</h4>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-4">
                        <div class="size-10 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center font-bold">01</div>
                        <div>
                            <p class="text-sm font-bold">Truck GJ-01-XX-1234</p>
                            <p class="text-xs text-slate-500">Vendor: Tata Steel • PO: #45021</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                         <span class="px-3 py-1 bg-emerald-500 text-white rounded-lg text-[10px] font-bold">UNLOADING</span>
                         <button class="text-slate-400 hover:text-slate-900"><span class="material-symbols-outlined">more_vert</span></button>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-4">
                        <div class="size-10 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center font-bold">02</div>
                        <div>
                            <p class="text-sm font-bold">Vehicle MH-12-AB-9876</p>
                            <p class="text-xs text-slate-500">Vendor: Reliance Poly • PO: #45025</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                         <span class="px-3 py-1 bg-amber-500 text-white rounded-lg text-[10px] font-bold">DOC VERIFICATION</span>
                         <button class="text-slate-400 hover:text-slate-900"><span class="material-symbols-outlined">more_vert</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
