@extends('layouts.warehouse')

@section('title', 'Outward — ' . $organization->org_name)
@section('page-title', 'Outward')

@section('content')
<div x-data="outwardApp()" x-init="init()">

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-xl">send_to_mobile</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">HHT</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.picking">0</p>
            <p class="text-sm text-gray-500 mt-1">In Picking</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-xl">inventory_2</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Ready</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.packed">0</p>
            <p class="text-sm text-gray-500 mt-1">Packed</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-teal-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-teal-600 text-xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-teal-600 bg-teal-50 px-2 py-1 rounded">Today</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.dispatched_today">0</p>
            <p class="text-sm text-gray-500 mt-1">Dispatched Today</p>
        </div>
    </div>

    <!-- Tab Panel -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 border-b border-gray-200">
            <nav class="flex gap-6 -mb-px">
                <button @click="tab = 'picking'"
                    :class="tab === 'picking' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">send_to_mobile</span>
                    Picking
                    <span class="bg-amber-100 text-amber-700 text-xs font-bold px-1.5 py-0.5 rounded-full" x-text="stats.picking"></span>
                </button>
                <button @click="tab = 'packed'"
                    :class="tab === 'packed' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">inventory_2</span>
                    Packed
                    <span class="bg-purple-100 text-purple-700 text-xs font-bold px-1.5 py-0.5 rounded-full" x-text="stats.packed"></span>
                </button>
                <button @click="tab = 'dispatched'"
                    :class="tab === 'dispatched' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">local_shipping</span>
                    Dispatched
                </button>
            </nav>
            <button @click="load()" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined text-xl">refresh</span>
            </button>
        </div>

        <div class="p-6">
            <div x-show="loading" class="flex justify-center py-12">
                <span class="material-symbols-outlined animate-spin text-3xl text-teal-500">progress_activity</span>
            </div>

            <div x-show="!loading">
                <!-- Picking -->
                <template x-if="tab === 'picking'">
                    <div>
                        <div x-show="picking.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">send_to_mobile</span>
                            No orders currently in picking.
                        </div>
                        <div x-show="picking.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Delivery Date</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-left">Items</th>
                                        <th class="px-4 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in picking" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3">
                                                <span :class="isOverdue(so.required_delivery_date) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                                      x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-gray-500 text-xs" x-text="(so.line_items?.length ?? so.items_count ?? '—') + ' item(s)'"></td>
                                            <td class="px-4 py-3">
                                                <button @click="markPacked(so.id)"
                                                    class="text-xs bg-purple-600 text-white hover:bg-purple-700 px-3 py-1.5 rounded font-semibold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">inventory_2</span> Mark Packed
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Packed -->
                <template x-if="tab === 'packed'">
                    <div>
                        <div x-show="packed.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">inventory_2</span>
                            No packed orders awaiting dispatch.
                        </div>
                        <div x-show="packed.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Delivery Date</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in packed" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3">
                                                <span :class="isOverdue(so.required_delivery_date) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                                      x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded font-semibold flex items-center justify-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">security</span> Awaiting Security Dispatch
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Dispatched -->
                <template x-if="tab === 'dispatched'">
                    <div>
                        <div x-show="dispatched.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">local_shipping</span>
                            No dispatched orders yet.
                        </div>
                        <div x-show="dispatched.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Dispatched On</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-left">Vehicle / Driver</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in dispatched" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3 text-gray-600"
                                                x-text="so.dispatched_at ? new Date(so.dispatched_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true}) : '—'"></td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-gray-600 text-xs"
                                                x-text="(so.vehicle_number ?? '—') + ' / ' + (so.driver_name ?? '—')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Dispatch Modal removed — dispatch is handled by Security portal -->

</div>

<script>
function outwardApp() {
    return {
        tab: 'picking',
        loading: false,
        picking: [], packed: [], dispatched: [],
        stats: { picking: 0, packed: 0, dispatched_today: 0 },

        token() { return localStorage.getItem('access_token') || localStorage.getItem('auth_token') || ''; },
        headers() { return { 'Authorization': 'Bearer ' + this.token(), 'Accept': 'application/json', 'Content-Type': 'application/json' }; },

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            try {
                // Fetch all three statuses in parallel
                const h = this.headers();
                const [pRes, pkRes, dRes] = await Promise.all([
                    fetch('/api/v1/sales-orders?per_page=100&status=PICKING', { headers: h }),
                    fetch('/api/v1/sales-orders?per_page=100&status=PACKED', { headers: h }),
                    fetch('/api/v1/sales-orders?per_page=100&status=DISPATCHED', { headers: h }),
                ]);
                const [pJson, pkJson, dJson] = await Promise.all([pRes.json(), pkRes.json(), dRes.json()]);

                this.picking   = pJson.success  ? (pJson.data.data  ?? pJson.data  ?? []) : [];
                this.packed    = pkJson.success ? (pkJson.data.data ?? pkJson.data ?? []) : [];
                this.dispatched = dJson.success ? (dJson.data.data  ?? dJson.data  ?? []) : [];

                const today = new Date().toDateString();
                this.stats = {
                    picking: this.picking.length,
                    packed:  this.packed.length,
                    dispatched_today: this.dispatched.filter(o => o.dispatched_at && new Date(o.dispatched_at).toDateString() === today).length,
                };
            } catch(e) { console.error('Outward load error', e); }
            this.loading = false;
        },

        async markPacked(id) {
            if (!confirm('Mark this order as PACKED?')) return;
            const res = await fetch('/api/v1/sales-orders/' + id + '/mark-packed', {
                method: 'PATCH', headers: this.headers()
            });
            const json = await res.json();
            if (json.success) { this.tab = 'packed'; await this.load(); }
            else alert(json.message || 'Failed to mark as packed.');
        },

        isOverdue(date) {
            return date && new Date(date) < new Date(new Date().toDateString());
        },
    }
}
</script>
@endsection
