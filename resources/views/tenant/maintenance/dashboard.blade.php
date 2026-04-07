@extends('layouts.maintenance')
@section('title', 'Dashboard - ' . $organization->org_name)
@section('page-title', 'Dashboard')

@section('content')
@php $slug = $organization->org_slug; @endphp

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('maintDash', () => ({
        panel: null,       // active panel key
        panelTitle: '',
        panelIcon: '',
        loading: false,
        items: [],

        panels: {
            workOrders:       { title: 'Work Orders',         icon: 'handyman',              url: '{{ route('tenant.maintenance.dashboard.work-orders-json',       $organization->org_slug) }}' },
            assets:           { title: 'Assets',              icon: 'precision_manufacturing',url: '{{ route('tenant.maintenance.dashboard.assets-json',            $organization->org_slug) }}' },
            pm:               { title: 'PM Schedule',         icon: 'calendar_month',        url: '{{ route('tenant.maintenance.dashboard.pm-json',                $organization->org_slug) }}' },
            lowStock:         { title: 'Low Stock Parts',     icon: 'settings',              url: '{{ route('tenant.maintenance.dashboard.low-stock-json',         $organization->org_slug) }}' },
            materialRequests: { title: 'Material Requests',   icon: 'inventory_2',           url: '{{ route('tenant.maintenance.dashboard.material-requests-json', $organization->org_slug) }}' },
            requests:         { title: 'Maintenance Requests',icon: 'description',           url: '{{ route('tenant.maintenance.dashboard.requests-json',          $organization->org_slug) }}' },
            procurement:      { title: 'Procurement Orders',  icon: 'shopping_cart',         url: '{{ route('tenant.maintenance.procurement.orders-json',          $organization->org_slug) }}' },
        },

        async open(key) {
            const cfg = this.panels[key];
            if (!cfg) return;
            this.panel = key;
            this.panelTitle = cfg.title;
            this.panelIcon  = cfg.icon;
            this.loading = true;
            this.items = [];
            try {
                const res = await fetch(cfg.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.items = await res.json();
            } catch(e) { this.items = []; }
            this.loading = false;
        },

        close() { this.panel = null; this.items = []; },

        statusClass(s) {
            const m = {
                'Assigned':'bg-blue-100 text-blue-700','In Progress':'bg-amber-100 text-amber-700',
                'Completed':'bg-green-100 text-green-700','Closed':'bg-gray-100 text-gray-500',
                'Active':'bg-green-100 text-green-700','Inactive':'bg-gray-100 text-gray-500',
                'Under Maintenance':'bg-orange-100 text-orange-700',
                'Scheduled':'bg-blue-100 text-blue-700','Overdue':'bg-red-100 text-red-700','Done':'bg-green-100 text-green-700',
                'Pending Approval':'bg-amber-100 text-amber-700','Approved':'bg-green-100 text-green-700',
                'Procurement Required':'bg-red-100 text-red-700','Pending Issue':'bg-amber-100 text-amber-700',
                'Pending':'bg-amber-100 text-amber-700','Ordered':'bg-blue-100 text-blue-700','Received':'bg-green-100 text-green-700',
            };
            return m[s] || 'bg-gray-100 text-gray-600';
        }
    }));
});
</script>

<div x-data="maintDash()">

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

    <button x-on:click="open('workOrders')"
        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md hover:border-amber-300 transition-all text-left w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="bg-amber-100 p-2 rounded-lg"><span class="material-symbols-outlined text-amber-600 text-xl">handyman</span></div>
            <span class="text-xs font-semibold bg-amber-50 text-amber-600 px-2 py-0.5 rounded">Active</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['openWorkOrders'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Open Work Orders</p>
        @if($stats['overdueOrders'] > 0)
            <p class="text-xs text-red-600 font-semibold mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">warning</span>{{ $stats['overdueOrders'] }} overdue
            </p>
        @endif
    </button>

    <button x-on:click="open('assets')"
        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md hover:border-blue-300 transition-all text-left w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="bg-blue-100 p-2 rounded-lg"><span class="material-symbols-outlined text-blue-600 text-xl">precision_manufacturing</span></div>
            <span class="text-xs font-semibold bg-blue-50 text-blue-600 px-2 py-0.5 rounded">Tracked</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['totalAssets'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Total Assets</p>
        <p class="text-xs text-gray-400 mt-1">{{ $stats['activeAssets'] }} active · {{ $stats['underMaintenance'] }} under maint.</p>
    </button>

    <button x-on:click="open('pm')"
        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md hover:border-green-300 transition-all text-left w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="bg-green-100 p-2 rounded-lg"><span class="material-symbols-outlined text-green-600 text-xl">calendar_month</span></div>
            <span class="text-xs font-semibold bg-green-50 text-green-600 px-2 py-0.5 rounded">This Week</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['scheduledPM'] }}</p>
        <p class="text-xs text-gray-500 mt-1">PM Tasks Due</p>
        @if($stats['overduePM'] > 0)
            <p class="text-xs text-red-600 font-semibold mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">warning</span>{{ $stats['overduePM'] }} overdue PM
            </p>
        @endif
    </button>

    <button x-on:click="open('lowStock')"
        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md hover:border-purple-300 transition-all text-left w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="bg-purple-100 p-2 rounded-lg"><span class="material-symbols-outlined text-purple-600 text-xl">settings</span></div>
            <span class="text-xs font-semibold bg-purple-50 text-purple-600 px-2 py-0.5 rounded">Parts</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['totalParts'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Spare Parts</p>
        @if($stats['lowStockCount'] > 0)
            <p class="text-xs text-orange-600 font-semibold mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">warning</span>{{ $stats['lowStockCount'] }} low stock
            </p>
        @endif
    </button>
</div>

<!-- Alerts -->
@php
    $alerts = [];
    if ($stats['overdueOrders'] > 0)     $alerts[] = ['red',    'warning',        "{$stats['overdueOrders']} work order(s) overdue.",                   'workOrders'];
    if ($stats['overduePM'] > 0)         $alerts[] = ['red',    'event_busy',     "{$stats['overduePM']} PM task(s) overdue.",                          'pm'];
    if ($stats['lowStockCount'] > 0)     $alerts[] = ['orange', 'inventory_2',    "{$stats['lowStockCount']} spare part(s) at or below reorder level.", 'lowStock'];
    if ($stats['procurementNeeded'] > 0) $alerts[] = ['red',    'shopping_cart',  "{$stats['procurementNeeded']} material request(s) need procurement.",'materialRequests'];
    if ($stats['pendingRequests'] > 0)   $alerts[] = ['amber',  'pending_actions',"{$stats['pendingRequests']} request(s) awaiting approval.",          'requests'];
    if ($stats['pendingIssue'] > 0)      $alerts[] = ['amber',  'output',         "{$stats['pendingIssue']} material(s) ready to issue from stock.",    'materialRequests'];
@endphp
@if(count($alerts) > 0)
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
    <p class="text-xs font-semibold text-gray-500 uppercase mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">notifications_active</span> Alerts
    </p>
    <div class="space-y-2">
        @foreach($alerts as [$color, $icon, $msg, $panelKey])
        <button x-on:click="open('{{ $panelKey }}')"
            class="flex items-center gap-3 p-2.5 rounded-lg w-full text-left transition-colors
            {{ $color === 'red' ? 'bg-red-50 hover:bg-red-100' : ($color === 'orange' ? 'bg-orange-50 hover:bg-orange-100' : 'bg-amber-50 hover:bg-amber-100') }}">
            <span class="material-symbols-outlined text-base {{ $color === 'red' ? 'text-red-600' : ($color === 'orange' ? 'text-orange-600' : 'text-amber-600') }}">{{ $icon }}</span>
            <span class="text-sm font-medium {{ $color === 'red' ? 'text-red-800' : ($color === 'orange' ? 'text-orange-800' : 'text-amber-800') }}">{{ $msg }}</span>
            <span class="material-symbols-outlined text-sm ml-auto {{ $color === 'red' ? 'text-red-400' : ($color === 'orange' ? 'text-orange-400' : 'text-amber-400') }}">chevron_right</span>
        </button>
        @endforeach
    </div>
</div>
@endif

<!-- Middle Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <!-- WO Status -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Work Order Status</h3>
            <button x-on:click="open('workOrders')" class="text-xs text-amber-600 hover:underline">View all</button>
        </div>
        @php
            $woStatuses = [
                'Assigned'    => ['bg-blue-400',  $woByStatus['Assigned']    ?? 0],
                'In Progress' => ['bg-amber-400', $woByStatus['In Progress'] ?? 0],
                'Completed'   => ['bg-green-400', $woByStatus['Completed']   ?? 0],
                'Closed'      => ['bg-gray-400',  $woByStatus['Closed']      ?? 0],
            ];
            $woTotal = array_sum(array_column($woStatuses, 1));
        @endphp
        <div class="space-y-2.5">
            @foreach($woStatuses as $label => [$barCls, $count])
            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-gray-600 w-24 flex-shrink-0">{{ $label }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $barCls }}" style="width: {{ $woTotal > 0 ? round($count / $woTotal * 100) : 0 }}%"></div>
                </div>
                <span class="text-xs font-bold text-gray-700 w-5 text-right">{{ $count }}</span>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between text-xs text-gray-500">
            <span>Total: <span class="font-bold text-gray-700">{{ $woTotal }}</span></span>
            <span>Overdue: <span class="font-bold text-red-600">{{ $stats['overdueOrders'] }}</span></span>
        </div>
    </div>

    <!-- PM Status -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">PM Schedule</h3>
            <button x-on:click="open('pm')" class="text-xs text-green-600 hover:underline">View all</button>
        </div>
        <div class="space-y-3">
            <button x-on:click="open('pm')" class="flex items-center justify-between p-2.5 bg-green-50 hover:bg-green-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-green-600 text-base">event_available</span><span class="text-xs font-medium text-green-800">Due This Week</span></div>
                <span class="text-lg font-bold text-green-700">{{ $stats['scheduledPM'] }}</span>
            </button>
            <button x-on:click="open('pm')" class="flex items-center justify-between p-2.5 bg-red-50 hover:bg-red-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-red-600 text-base">event_busy</span><span class="text-xs font-medium text-red-800">Overdue</span></div>
                <span class="text-lg font-bold text-red-700">{{ $stats['overduePM'] }}</span>
            </button>
            <button x-on:click="open('pm')" class="flex items-center justify-between p-2.5 bg-gray-50 hover:bg-gray-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-gray-500 text-base">task_alt</span><span class="text-xs font-medium text-gray-600">Completed</span></div>
                <span class="text-lg font-bold text-gray-700">{{ $stats['donePM'] }}</span>
            </button>
        </div>
    </div>

    <!-- Procurement & Materials -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Procurement & Materials</h3>
            <button x-on:click="open('procurement')" class="text-xs text-amber-600 hover:underline">View all</button>
        </div>
        <div class="space-y-3">
            <button x-on:click="open('materialRequests')" class="flex items-center justify-between p-2.5 bg-red-50 hover:bg-red-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-red-600 text-base">shopping_cart</span><span class="text-xs font-medium text-red-800">Needs Procurement</span></div>
                <span class="text-lg font-bold text-red-700">{{ $stats['procurementNeeded'] }}</span>
            </button>
            <button x-on:click="open('materialRequests')" class="flex items-center justify-between p-2.5 bg-amber-50 hover:bg-amber-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-600 text-base">output</span><span class="text-xs font-medium text-amber-800">Pending Issue</span></div>
                <span class="text-lg font-bold text-amber-700">{{ $stats['pendingIssue'] }}</span>
            </button>
            <button x-on:click="open('procurement')" class="flex items-center justify-between p-2.5 bg-blue-50 hover:bg-blue-100 rounded-lg w-full transition-colors">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-blue-600 text-base">local_shipping</span><span class="text-xs font-medium text-blue-800">POs In Progress</span></div>
                <span class="text-lg font-bold text-blue-700">{{ $stats['pendingProcurement'] }}</span>
            </button>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <!-- Recent Work Orders -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Recent Work Orders</h3>
            <button x-on:click="open('workOrders')" class="text-xs text-amber-600 hover:underline">View all</button>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentWOs as $wo)
            @php
                $woCls  = match($wo['status']) { 'Assigned'=>'bg-blue-100 text-blue-700','In Progress'=>'bg-amber-100 text-amber-700','Completed'=>'bg-green-100 text-green-700',default=>'bg-gray-100 text-gray-500' };
                $priCls = match($wo['priority'] ?? 'Medium') { 'High'=>'text-red-600','Low'=>'text-gray-400',default=>'text-amber-500' };
                $isOverdue = $wo['due_date'] && $wo['due_date'] < date('Y-m-d') && $wo['status'] !== 'Closed';
            @endphp
            <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-amber-700">{{ $wo['wo_no'] }}</span>
                        @if($isOverdue)<span class="text-xs font-semibold text-red-600 flex items-center gap-0.5"><span class="material-symbols-outlined text-xs">warning</span>Overdue</span>@endif
                    </div>
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $wo['asset'] }}</p>
                    <p class="text-xs text-gray-400">{{ $wo['technician'] }} · Due {{ $wo['due_date'] ?? '-' }}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $woCls }}">{{ $wo['status'] }}</span>
                    <span class="text-xs font-semibold {{ $priCls }}">{{ $wo['priority'] ?? 'Medium' }}</span>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">No work orders yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Low Stock Parts -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Low Stock Parts</h3>
            <button x-on:click="open('lowStock')" class="text-xs text-purple-600 hover:underline">View all</button>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse(array_slice($lowStockParts, 0, 6) as $part)
            <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
                <div class="bg-orange-100 p-1.5 rounded-lg flex-shrink-0"><span class="material-symbols-outlined text-orange-600 text-base">settings</span></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $part['name'] }}</p>
                    <p class="text-xs text-gray-400">{{ $part['code'] }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold {{ $part['stock'] == 0 ? 'text-red-600' : 'text-orange-600' }}">{{ $part['stock'] }} {{ $part['unit'] }}</p>
                    <p class="text-xs text-gray-400">min {{ $part['reorder_level'] }}</p>
                </div>
                <span class="px-1.5 py-0.5 rounded text-xs font-semibold flex-shrink-0 {{ $part['stock'] == 0 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">{{ $part['stock'] == 0 ? 'Out' : 'Low' }}</span>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">
                <span class="material-symbols-outlined text-2xl block mb-1 text-green-400">check_circle</span>
                All parts are well stocked.
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Slide-over Panel -->
<!-- ============================================================ -->
<div x-show="panel !== null" x-cloak>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/40 z-40" x-on:click="close()"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <!-- Panel -->
    <div class="fixed right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col"
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-xl" x-text="panelIcon"></span>
                </div>
                <h2 class="text-base font-semibold text-gray-900" x-text="panelTitle"></h2>
            </div>
            <button x-on:click="close()" class="text-gray-400 hover:text-gray-700">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-5">

            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-400">
                    <span class="material-symbols-outlined text-3xl animate-spin mr-2">progress_activity</span> Loading...
                </div>
            </template>

            <template x-if="!loading && items.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                    No records found.
                </div>
            </template>

            <!-- Work Orders -->
            <template x-if="!loading && panel === 'workOrders' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="w in items" :key="w.wo_no">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div>
                                    <span class="text-xs font-bold text-amber-700" x-text="w.wo_no"></span>
                                    <span x-show="w.overdue" class="ml-2 text-xs font-semibold text-red-600">⚠ Overdue</span>
                                    <p class="font-medium text-gray-900 text-sm" x-text="w.asset"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0" :class="statusClass(w.status)" x-text="w.status"></span>
                            </div>
                            <div class="flex gap-4 text-xs text-gray-500 mt-1">
                                <span x-text="'Tech: ' + w.technician"></span>
                                <span x-text="'Due: ' + (w.due_date || '-')"></span>
                                <span class="font-semibold" :class="w.priority === 'High' ? 'text-red-600' : (w.priority === 'Low' ? 'text-gray-400' : 'text-amber-500')" x-text="w.priority"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Assets -->
            <template x-if="!loading && panel === 'assets' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="a in items" :key="a.code">
                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
                            <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0"><span class="material-symbols-outlined text-blue-600 text-base">precision_manufacturing</span></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-sm" x-text="a.name"></p>
                                <p class="text-xs text-gray-400" x-text="a.code + ' · ' + a.category + (a.location ? ' · ' + a.location : '')"></p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0" :class="statusClass(a.status)" x-text="a.status"></span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- PM Schedule -->
            <template x-if="!loading && panel === 'pm' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="p in items" :key="p.pm_no">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div>
                                    <span class="text-xs font-bold text-green-700" x-text="p.pm_no"></span>
                                    <span x-show="p.overdue" class="ml-2 text-xs font-semibold text-red-600">⚠ Overdue</span>
                                    <p class="font-medium text-gray-900 text-sm" x-text="p.task"></p>
                                    <p class="text-xs text-gray-400" x-text="p.asset + ' · ' + p.frequency"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0" :class="statusClass(p.status)" x-text="p.status"></span>
                            </div>
                            <div class="flex gap-4 text-xs text-gray-500 mt-1">
                                <span x-text="'Due: ' + (p.next_due || '-')"></span>
                                <span x-show="p.assigned_to" x-text="'Assigned: ' + p.assigned_to"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Low Stock -->
            <template x-if="!loading && panel === 'lowStock' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="p in items" :key="p.code">
                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
                            <div class="bg-orange-100 p-2 rounded-lg flex-shrink-0"><span class="material-symbols-outlined text-orange-600 text-base">settings</span></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-sm" x-text="p.name"></p>
                                <p class="text-xs text-gray-400" x-text="p.code + (p.asset ? ' · ' + p.asset : '')"></p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold" :class="p.stock === 0 ? 'text-red-600' : 'text-orange-600'" x-text="p.stock + ' ' + p.unit"></p>
                                <p class="text-xs text-gray-400" x-text="'min ' + p.reorder_level"></p>
                            </div>
                            <span class="px-1.5 py-0.5 rounded text-xs font-semibold flex-shrink-0"
                                :class="p.stock === 0 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'"
                                x-text="p.stock === 0 ? 'Out' : 'Low'"></span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Material Requests -->
            <template x-if="!loading && panel === 'materialRequests' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="m in items" :key="m.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div>
                                    <span class="text-xs font-bold text-gray-600" x-text="m.id"></span>
                                    <span class="text-xs text-amber-700 ml-2 font-medium" x-text="m.wo_no"></span>
                                    <p class="font-medium text-gray-900 text-sm" x-text="m.part_name || m.part_code"></p>
                                    <p class="text-xs text-gray-400" x-text="m.qty + ' ' + m.unit + ' · Raised: ' + (m.raised_on || '-')"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0" :class="statusClass(m.status)" x-text="m.status"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Maintenance Requests -->
            <template x-if="!loading && panel === 'requests' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="r in items" :key="r.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div>
                                    <span class="text-xs font-bold text-gray-600" x-text="r.id"></span>
                                    <p class="font-medium text-gray-900 text-sm" x-text="r.asset"></p>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="r.issue"></p>
                                    <p class="text-xs text-gray-400 mt-0.5" x-text="'By: ' + (r.raised_by || '-') + ' · ' + (r.date || '')"></p>
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="statusClass(r.status)" x-text="r.status"></span>
                                    <span class="text-xs font-semibold" :class="r.priority === 'High' ? 'text-red-600' : (r.priority === 'Low' ? 'text-gray-400' : 'text-amber-500')" x-text="r.priority"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Procurement Orders -->
            <template x-if="!loading && panel === 'procurement' && items.length > 0">
                <div class="space-y-3">
                    <template x-for="o in items" :key="o.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <p class="font-semibold text-amber-700 text-sm" x-text="o.po_no"></p>
                                    <p class="font-medium text-gray-900 text-sm" x-text="o.part_name || o.part_code"></p>
                                    <p class="text-xs text-gray-400" x-text="o.part_code"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0" :class="statusClass(o.status)" x-text="o.status"></span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-gray-500">
                                <div><p class="text-gray-400">Qty</p><p class="font-semibold text-gray-800" x-text="o.qty + ' ' + o.unit"></p></div>
                                <div><p class="text-gray-400">Vendor</p><p class="font-semibold text-gray-800" x-text="o.vendor || '-'"></p></div>
                                <div><p class="text-gray-400">Expected</p><p class="font-semibold text-gray-800" x-text="o.expected_date || '-'"></p></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

        </div>
    </div>
</div>

</div>
@endsection
