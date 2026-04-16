@extends('layouts.warehouse')

@section('title', 'Material Issues - ' . $organization->org_name)
@section('page-title', 'Material Issue Requests')

@section('content')
<div x-data="mirListData()" x-init="init()">
    <!-- Statistics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                    <span class="material-symbols-outlined text-2xl">pending</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider leading-none mb-1">Pending</p>
                    <h3 class="text-2xl font-black text-gray-900 leading-none" x-text="mirs.filter(m => m.status === 'PENDING').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <span class="material-symbols-outlined text-2xl">outbox</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider leading-none mb-1">Partially Issued</p>
                    <h3 class="text-2xl font-black text-gray-900 leading-none" x-text="mirs.filter(m => m.status === 'PARTIALLY_ISSUED').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-50 rounded-xl text-green-600">
                    <span class="material-symbols-outlined text-2xl">check_circle</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider leading-none mb-1">Fully Issued</p>
                    <h3 class="text-2xl font-black text-gray-900 leading-none" x-text="mirs.filter(m => m.status === 'FULLY_ISSUED').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-50 rounded-xl text-red-600">
                    <span class="material-symbols-outlined text-2xl">cancel</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider leading-none mb-1">Rejected</p>
                    <h3 class="text-2xl font-black text-gray-900 leading-none" x-text="mirs.filter(m => m.status === 'REJECTED').length"></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white/60 backdrop-blur-md rounded-2xl border border-gray-200 p-2 mb-8 flex flex-wrap items-center gap-3 shadow-sm">
        <div class="flex-1 min-w-[200px] relative text-slate-400">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg">search</span>
            <input type="text" x-model="search" @input.debounce.500ms="loadMIRs()"
                placeholder="Search MIR No, Order No..."
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-amber-500 text-sm font-medium text-slate-900 transition-all placeholder:text-slate-400">
        </div>
        <div class="w-48 relative text-slate-400">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg">filter_alt</span>
            <select x-model="status" @change="loadMIRs()"
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-amber-500 text-sm font-bold text-slate-700 appearance-none cursor-pointer transition-all">
                <option value="">All Statuses</option>
                <option value="PENDING">Pending</option>
                <option value="APPROVED">Approved</option>
                <option value="PARTIALLY_ISSUED">Partially Issued</option>
                <option value="FULLY_ISSUED">Fully Issued</option>
                <option value="REJECTED">Rejected</option>
                <option value="CLOSED">Closed</option>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-lg">expand_more</span>
        </div>
        <button @click="loadMIRs()"
            class="px-4 py-2 text-slate-400 hover:text-amber-600 font-black uppercase tracking-widest text-[10px] transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm" :class="loading ? 'animate-spin' : ''">refresh</span>
            Refresh
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">MIR Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Production Request</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Main Product</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Issue Status</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Request Date</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <span class="material-symbols-outlined text-4xl animate-spin text-amber-500">progress_activity</span>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest leading-none">Syncing logistics data...</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && mirs.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4 text-gray-300">
                                    <div class="p-4 bg-gray-50 rounded-full">
                                        <span class="material-symbols-outlined text-5xl">outbox</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">No requests found</p>
                                        <p class="text-xs text-gray-400 mt-1">Pending MIRs from production will appear here.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="mir in mirs" :key="mir.id">
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-black text-slate-900 font-mono tracking-tight" x-text="mir.mir_no"></span>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter" x-text="mir.lines_count + ' Items'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-extrabold text-slate-700" x-text="mir.request_no || mir.order_no || '—'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-bold text-slate-800" x-text="mir.product_name || '—'"></span>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter" x-text="mir.product_code || ''"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center leading-none">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="px-3 py-1 text-[10px] rounded-full font-black uppercase tracking-widest"
                                        :class="statusClass(mir.status)" x-text="statusLabel(mir.status)"></span>
                                    <span class="text-[10px] text-slate-400 font-semibold"
                                        x-text="mir.fully_picked_count + '/' + mir.lines_count + ' picked'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-500 leading-none" x-text="formatDate(mir.created_at)"></td>
                            <td class="px-6 py-4 text-right leading-none">
                                <a :href="'/org/{{ $organization->org_slug }}/warehouse/mir/' + mir.id"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all shadow-md active:scale-95">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Process
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function mirListData() {
        const token = () => localStorage.getItem('access_token');
        const orgSlug = '{{ $organization->org_slug }}';
        const headers = () => {
            const h = { 'Accept': 'application/json', 'X-Org-Slug': orgSlug };
            const t = token();
            if (t && t !== 'null') h['Authorization'] = `Bearer ${t}`;
            return h;
        };

        return {
            mirs: [],
            loading: false,
            search: '',
            status: '',

            async init() {
                await this.loadMIRs();
            },

            async loadMIRs() {
                this.loading = true;
                try {
                    const url = new URL(`${window.location.origin}/api/v1/material-issue-requests`);
                    if (this.search) url.searchParams.append('search', this.search);
                    if (this.status) url.searchParams.append('status', this.status);

                    const res = await fetch(url, { headers: headers() });
                    const data = await res.json();
                    if (data.success) {
                        // API returns data.data as a flat array (paginated map result)
                        this.mirs = Array.isArray(data.data) ? data.data : [];
                    }
                } catch (e) {
                    console.error('Error loading MIRs:', e);
                } finally {
                    this.loading = false;
                }
            },

            statusLabel(status) {
                const labels = {
                    'PENDING': 'Pending',
                    'APPROVED': 'Approved',
                    'PARTIALLY_ISSUED': 'Partial',
                    'FULLY_ISSUED': 'Fully Issued',
                    'REJECTED': 'Rejected',
                    'CLOSED': 'Closed',
                };
                return labels[status] || status;
            },

            statusClass(status) {
                switch (status) {
                    case 'PENDING':          return 'bg-amber-50 text-amber-700 ring-1 ring-amber-100';
                    case 'APPROVED':         return 'bg-blue-50 text-blue-700 ring-1 ring-blue-100';
                    case 'PARTIALLY_ISSUED': return 'bg-orange-50 text-orange-700 ring-1 ring-orange-100';
                    case 'FULLY_ISSUED':     return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100';
                    case 'REJECTED':         return 'bg-red-50 text-red-700 ring-1 ring-red-100';
                    case 'CLOSED':           return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
                    default:                 return 'bg-slate-50 text-slate-700 ring-1 ring-slate-100';
                }
            },

            formatDate(iso) {
                if (!iso) return '—';
                return new Date(iso).toLocaleDateString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
            }
        }
    }
</script>
@endsection