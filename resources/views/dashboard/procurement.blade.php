@extends('layouts.procurement')

@section('title', 'Procurement Overview - Nexus ERP')

@section('content')
<div class="space-y-6">
    <!-- Title & Actions -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Procurement Dashboard</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Monitor purchase requests, vendor status, and inbound supply chain.</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-sm">add</span>
                Create New PO
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active POs</span>
                <span class="material-symbols-outlined text-primary">shopping_bag</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold">142</span>
                <span class="text-xs font-medium text-emerald-500">+5%</span>
            </div>
        </div>
        <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Approval</span>
                <span class="material-symbols-outlined text-amber-500">pending_actions</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold">18</span>
                <span class="text-xs font-medium text-amber-500">Urgent</span>
            </div>
        </div>
        <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vendors</span>
                <span class="material-symbols-outlined text-blue-500">store</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold">2,450</span>
                <span class="text-xs font-medium text-slate-400">Total</span>
            </div>
        </div>
        <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inward Today</span>
                <span class="material-symbols-outlined text-emerald-500">local_shipping</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold">12</span>
                <span class="text-xs font-medium text-emerald-500">Trucks</span>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-bold">Recent Purchase Orders</h3>
            <button class="text-sm font-bold text-primary hover:underline">View All</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">PO Number</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap"><span class="font-semibold text-primary dark:text-blue-400">#PO-2024-001</span></td>
                        <td class="px-6 py-4 whitespace-nowrap">Global Steel Dynamics</td>
                        <td class="px-6 py-4 whitespace-nowrap">Oct 24, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold">$12,450.00</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Approved</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap"><span class="font-semibold text-primary dark:text-blue-400">#PO-2024-002</span></td>
                        <td class="px-6 py-4 whitespace-nowrap">Precision Machining</td>
                        <td class="px-6 py-4 whitespace-nowrap">Oct 25, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold">$8,200.00</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Pending Approval</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
