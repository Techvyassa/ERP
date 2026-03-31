@extends('layouts.warehouse')

@section('title', 'Material Issues - ' . $organization->org_name)
@section('page-title', 'Material Issue Requests')

@section('content')
<div x-data="mirListData()" x-init="init()">
    <!-- Filters & Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="relative flex-1 md:w-80">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                <input type="text" x-model="search" @input.debounce.500ms="loadMIRs()"
                    placeholder="Search MIR No, Order No..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-warehouse/20 focus:border-warehouse transition-all">
            </div>
            <select x-model="status" @change="loadMIRs()"
                class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-warehouse/20 focus:border-warehouse bg-white">
                <option value="">All Status</option>
                <option value="PENDING">Pending Approval</option>
                <option value="APPROVED">Approved / Ready to Scan</option>
                <option value="REJECTED">Rejected</option>
            </select>
        </div>
        <button @click="loadMIRs()" class="flex items-center gap-2 px-4 py-2 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <span class="material-symbols-outlined text-lg" :class="loading ? 'animate-spin' : ''">refresh</span>
            Refresh
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">MIR Details</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Production Order</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-8 h-8 border-4 border-warehouse border-t-transparent rounded-full animate-spin"></div>
                                    <p class="text-sm text-gray-500 font-medium">Loading MIRs...</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && mirs.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-gray-300">outbox</span>
                                    <p class="text-gray-500">No material issue requests found</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="mir in mirs" :key="mir.id">
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900" x-text="mir.mir_no"></p>
                                <p class="text-xs text-gray-500" x-text="mir.lines.length + ' line items'"></p>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-700" x-text="mir.order_no || '—'"></td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900" x-text="mir.product_name || '—'"></p>
                                <p class="text-xs text-gray-500" x-text="mir.product_code || ''"></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="statusClass(mir.status)" x-text="mir.status"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="mir.created_at"></td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/org/{{ $organization->org_slug }}/warehouse/mir/' + mir.id"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-warehouse text-white text-xs font-bold rounded-lg hover:bg-warehouse/90 transition-colors">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Review & Issue
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
            const h = {
                'Accept': 'application/json',
                'X-Org-Slug': orgSlug
            };
            const t = token();
            if (t && t !== 'null') {
                h['Authorization'] = `Bearer ${t}`;
            }
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

                    const res = await fetch(url, {
                        headers: headers()
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.mirs = data.data.mirs;
                    }
                } catch (e) {
                    console.error('Error loading MIRs:', e);
                } finally {
                    this.loading = false;
                }
            },

            statusClass(status) {
                switch(status) {
                    case 'PENDING': return 'bg-amber-100 text-amber-700 border border-amber-200';
                    case 'APPROVED': return 'bg-green-100 text-green-700 border border-green-200';
                    case 'REJECTED': return 'bg-red-100 text-red-700 border border-red-200';
                    case 'ISSUED': return 'bg-blue-100 text-blue-700 border border-blue-200';
                    default: return 'bg-gray-100 text-gray-700 border border-gray-200';
                }
            }
        }
    }
</script>
@endsection
