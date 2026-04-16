@extends('layouts.production')

@section('title', 'Receiving')
@section('page-title', 'Receiving')

@section('content')
<div x-data="receivingList()" x-init="init()">

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                    <span class="material-symbols-outlined text-2xl">pending</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Awaiting Confirmation</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="orders.filter(o => o.mir_status === 'FULLY_ISSUED').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <span class="material-symbols-outlined text-2xl">check_circle</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Confirmed Today</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="orders.filter(o => o.mir_status === 'CLOSED').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <span class="material-symbols-outlined text-2xl">sync</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">In Progress</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="orders.filter(o => o.status === 'IN_PROGRESS').length"></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex items-center gap-2 mb-6">
        <button @click="filter = 'FULLY_ISSUED'" :class="filter === 'FULLY_ISSUED' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
            Pending Receipt
        </button>
        <button @click="filter = 'CLOSED'" :class="filter === 'CLOSED' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
            Confirmed
        </button>
        <button @click="filter = ''" :class="filter === '' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-gray-200'"
            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
            All
        </button>
        <button @click="loadOrders()" class="ml-auto p-2 text-slate-400 hover:text-slate-700 transition-colors">
            <span class="material-symbols-outlined text-lg" :class="loading ? 'animate-spin' : ''">refresh</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Order</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Product</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Target Qty</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">MIR Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Order Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <span class="material-symbols-outlined text-4xl animate-spin text-orange-400">progress_activity</span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filteredOrders().length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-300">
                                    <span class="material-symbols-outlined text-5xl">move_to_inbox</span>
                                    <p class="text-sm font-black text-gray-400 uppercase tracking-widest">No orders found</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="order in filteredOrders()" :key="order.id">
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-black text-slate-900 font-mono" x-text="order.order_no"></span>
                                    <span class="text-[10px] text-slate-400" x-text="formatDate(order.planned_date)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-bold text-slate-800" x-text="order.product_name || '—'"></span>
                                    <span class="text-[10px] font-black text-slate-400 uppercase" x-text="order.product_code || ''"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center leading-none">
                                <span class="text-sm font-black text-slate-700" x-text="order.target_qty"></span>
                            </td>
                            <td class="px-6 py-4 text-center leading-none">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                    :class="{
                                        'bg-amber-50 text-amber-700 ring-1 ring-amber-100': order.mir_status === 'FULLY_ISSUED',
                                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100': order.mir_status === 'CLOSED',
                                        'bg-orange-50 text-orange-700 ring-1 ring-orange-100': order.mir_status === 'PARTIALLY_ISSUED',
                                        'bg-blue-50 text-blue-700 ring-1 ring-blue-100': order.mir_status === 'APPROVED',
                                        'bg-slate-50 text-slate-500 ring-1 ring-slate-100': !order.mir_status,
                                    }"
                                    x-text="order.mir_status || 'No MIR'"></span>
                            </td>
                            <td class="px-6 py-4 text-center leading-none">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                    :class="{
                                        'bg-slate-50 text-slate-600 ring-1 ring-slate-200': order.status === 'DRAFT',
                                        'bg-blue-50 text-blue-700 ring-1 ring-blue-100': order.status === 'IN_PROGRESS',
                                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100': order.status === 'COMPLETED',
                                    }"
                                    x-text="order.status"></span>
                            </td>
                            <td class="px-6 py-4 text-right leading-none">
                                <!-- FULLY_ISSUED: needs floor confirmation -->
                                <template x-if="order.mir_status === 'FULLY_ISSUED'">
                                    <a :href="`/org/{{ $organization->org_slug }}/production/orders/${order.id}/receiving`"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-amber-600 transition-all shadow-sm active:scale-95">
                                        <span class="material-symbols-outlined text-sm">move_to_inbox</span>
                                        Confirm Receipt
                                    </a>
                                </template>
                                <!-- CLOSED: already confirmed, can start -->
                                <template x-if="order.mir_status === 'CLOSED' && order.status === 'DRAFT'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase tracking-widest rounded-xl ring-1 ring-emerald-100">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        Ready to Start
                                    </span>
                                </template>
                                <!-- IN_PROGRESS -->
                                <template x-if="order.status === 'IN_PROGRESS'">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-xl ring-1 ring-blue-100">
                                        <span class="material-symbols-outlined text-sm">sync</span>
                                        Running
                                    </span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function receivingList() {
        const orgSlug = '{{ $organization->org_slug }}';
        const token = () => localStorage.getItem('access_token');
        const headers = () => {
            const h = {
                'Accept': 'application/json',
                'X-Org-Slug': orgSlug
            };
            const t = token();
            if (t && t !== 'null') h['Authorization'] = `Bearer ${t}`;
            return h;
        };

        return {
            orders: [],
            loading: false,
            filter: 'FULLY_ISSUED',

            async init() {
                await this.loadOrders();
            },

            async loadOrders() {
                this.loading = true;
                try {
                    // Load orders that have MIR in FULLY_ISSUED or CLOSED state
                    const res = await fetch(`/api/v1/production-orders?per_page=100`, {
                        headers: headers()
                    });
                    const data = await res.json();
                    const all = data?.data?.orders || data?.data || [];
                    // Only show orders relevant to receiving flow
                    this.orders = all.filter(o => ['FULLY_ISSUED', 'CLOSED', 'PARTIALLY_ISSUED'].includes(o.mir_status) ||
                        o.status === 'IN_PROGRESS'
                    );
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            filteredOrders() {
                if (!this.filter) return this.orders;
                if (this.filter === 'FULLY_ISSUED') return this.orders.filter(o => o.mir_status === 'FULLY_ISSUED');
                if (this.filter === 'CLOSED') return this.orders.filter(o => o.mir_status === 'CLOSED' || o.status === 'IN_PROGRESS');
                return this.orders;
            },

            formatDate(d) {
                if (!d) return '—';
                return new Date(d).toLocaleDateString('en-IN', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
        };
    }
</script>
@endsection