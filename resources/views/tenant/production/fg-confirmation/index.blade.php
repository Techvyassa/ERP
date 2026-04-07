@extends('layouts.production')

@section('title', 'FG Confirmation')
@section('page-title', 'FG Confirmation')

@section('content')
<div x-data="fgConfirmation('{{ $organization->org_slug }}')" x-init="init()">

    {{-- ── Confirmation Modal ─────────────────────────────────────────── --}}
    <div x-show="modal.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl z-10 overflow-hidden"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                @click.stop>

                <div class="bg-gradient-to-r from-emerald-700 to-emerald-900 px-6 py-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/10 rounded-lg text-emerald-100">
                            <span class="material-symbols-outlined">inventory</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Record Finished Goods</h3>
                            <p class="text-xs text-emerald-100/70" x-text="modal.order?.order_no + ' — ' + (modal.order?.product_name || '')"></p>
                        </div>
                    </div>
                    <button @click="closeModal()" class="text-white/60 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Progress bar --}}
                <div class="px-6 pt-6">
                    <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-tighter text-gray-400 mb-2">
                        <span>Quantity Tracker</span>
                        <span x-text="modal.order?.confirmed_qty_total + ' / ' + modal.order?.target_qty + ' ' + (modal.order?.uom?.uom_name || modal.order?.uom || '')"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden shadow-inner">
                        <div class="bg-emerald-500 h-full transition-all duration-700"
                            :style="'width:' + Math.min(100, ((modal.order?.confirmed_qty_total / modal.order?.target_qty) * 100) || 0) + '%'"></div>
                    </div>
                    <div class="flex justify-between text-xs mt-2 font-semibold">
                        <span class="text-emerald-600" x-text="'Produced: ' + (modal.order?.confirmed_qty_total || 0)"></span>
                        <span class="text-orange-600" x-text="'Remaining: ' + (modal.order?.remaining_qty ?? modal.order?.target_qty ?? 0)"></span>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <!-- Auto-calculated fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Total Target</label>
                            <input type="text" readonly
                                :value="modal.order?.target_qty + ' ' + (modal.order?.uom?.uom_name || modal.order?.uom || '')"
                                class="w-full px-4 py-2.5 bg-gray-100 border-none rounded-xl text-gray-600 font-bold shadow-inner cursor-not-allowed">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Already Confirmed</label>
                            <input type="text" readonly
                                :value="(modal.order?.confirmed_qty_total ?? 0) + ' ' + (modal.order?.uom?.uom_name || modal.order?.uom || '')"
                                class="w-full px-4 py-2.5 bg-gray-100 border-none rounded-xl text-gray-600 font-bold shadow-inner cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Accept (Auto-filled)</label>
                            <input type="number" min="0.001" step="0.001"
                                x-model="form.confirmed_qty"
                                :max="modal.order?.remaining_qty"
                                readonly
                                class="w-full px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 font-bold shadow-inner cursor-not-allowed">
                            <p class="text-[10px] text-emerald-600">Auto-calculated from remaining quantity</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Reject (Optional)</label>
                            <input type="number" min="0" step="0.001"
                                x-model="form.rejected_qty"
                                @input="syncStatus()"
                                class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-red-500 font-bold text-gray-900 shadow-inner">
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg" :class="form.completion_status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                <span class="material-symbols-outlined text-sm" x-text="form.completion_status === 'COMPLETED' ? 'task_alt' : 'pending_actions'"></span>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter leading-none mb-1">Batch Close Logic</p>
                                <p class="text-xs font-black uppercase tracking-widest" :class="form.completion_status === 'COMPLETED' ? 'text-emerald-700' : 'text-blue-700'"
                                    x-text="form.completion_status.replace('_', ' ')"></p>
                            </div>
                        </div>
                        <p class="text-[10px] font-medium text-gray-500 italic max-w-[180px] text-right">
                            <span x-show="form.completion_status === 'COMPLETED'">Full batch target met. Order will be marked as Finished for Packing.</span>
                            <span x-show="form.completion_status !== 'COMPLETED'">Target not fully met. Order remains Open for more recordings.</span>
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Rejection Reason</label>
                        <select x-model="form.rejection_reason_code"
                            class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 font-medium text-gray-900 appearance-none shadow-inner">
                            <option value="">— None —</option>
                            <option value="DEFECT_VISUAL">Visual Defect</option>
                            <option value="DEFECT_DIMENSIONAL">Dimensional Defect</option>
                            <option value="DEFECT_FUNCTIONAL">Functional Defect</option>
                            <option value="CONTAMINATION">Contamination</option>
                            <option value="PACKAGING_DAMAGE">Packaging Damage</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>

                    <div x-show="form.rejection_reason_code === 'OTHER'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="space-y-1.5">
                        <label class="block text-xs font-bold text-orange-500 uppercase tracking-wider">Specify Manual Reason</label>
                        <input type="text" x-model="form.rejection_reason_note"
                            placeholder="Type details about the rejection reason..."
                            class="w-full px-4 py-2.5 bg-orange-50/50 border border-orange-100 rounded-xl focus:ring-2 focus:ring-orange-400 font-medium text-gray-900 shadow-inner">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Batch Identity</label>
                        <input type="text" x-model="form.fg_batch_number"
                            placeholder="Auto-generated"
                            class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-medium text-gray-900 shadow-inner">
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl bg-emerald-50/50 px-4 py-3 text-sm text-emerald-800 cursor-pointer border border-emerald-100 hover:bg-emerald-50 transition-colors">
                        <input type="checkbox" x-model="form.qc_required" class="rounded-lg border-emerald-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 shadow-inner">
                        <div class="flex-1">
                            <p class="font-bold">Hold for QC Inspection</p>
                            <p class="text-[10px] opacity-70 italic uppercase tracking-tighter font-black">Strict verification mandated</p>
                        </div>
                    </label>

                    <div x-show="modal.error"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        class="p-4 bg-red-50 border border-red-100 text-red-700 text-xs font-bold rounded-xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">error</span>
                        <span x-text="modal.error"></span>
                    </div>
                </div>

                <div class="px-6 py-5 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/30">
                    <button @click="closeModal()" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-600 font-bold text-sm hover:bg-gray-50 transition-all shadow-sm">Cancel</button>
                    <button @click="submitConfirmation()"
                        :disabled="modal.submitting || !form.confirmed_qty"
                        :class="(!modal.submitting && form.confirmed_qty) ? 'bg-emerald-600 shadow-emerald-200 cursor-pointer' : 'bg-gray-300 cursor-not-allowed'"
                        class="px-6 py-2.5 text-white font-black uppercase tracking-widest text-xs rounded-xl transition-all shadow-lg flex items-center gap-2 active:scale-95">
                        <span class="material-symbols-outlined text-lg"
                            :class="modal.submitting ? 'animate-spin' : ''"
                            x-text="modal.submitting ? 'progress_activity' : 'task_alt'"></span>
                        <span x-text="modal.submitting ? 'Processing...' : 'Confirm Output'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sessions History Drawer ────────────────────────────────────── --}}
    <div x-show="drawer.show" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
            x-show="drawer.show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="drawer.show = false"></div>

        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl flex flex-col"
            x-show="drawer.show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full">

            <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-slate-800 to-slate-900 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/10 rounded-lg text-slate-200">
                        <span class="material-symbols-outlined">analytics</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight">Batch Analysis</h3>
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-slate-300 font-mono" x-text="drawer.order?.order_no"></p>
                            <span class="text-slate-500 text-xs">•</span>
                            <p class="text-xs text-slate-300 font-medium" x-text="drawer.order?.product_name"></p>
                        </div>
                    </div>
                </div>
                <button @click="drawer.show = false" class="text-white/60 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            {{-- Summary Cards --}}
            <div class="p-6 bg-gray-50/50 space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">Target</p>
                        <p class="font-black text-gray-900 text-base" x-text="drawer.summary?.target_qty ?? '—'"></p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center border-l-4 border-l-emerald-500">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 font-black">Confirmed</p>
                        <p class="font-black text-emerald-600 text-base" x-text="drawer.summary?.confirmed_qty_total ?? '—'"></p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center border-l-4 border-l-orange-500">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 font-black">Open</p>
                        <p class="font-black text-orange-600 text-base" x-text="drawer.summary?.remaining_qty ?? '—'"></p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center border-l-4 border-l-red-500">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 font-black">Rejected</p>
                        <p class="font-black text-red-600 text-base" x-text="drawer.summary?.rejected_qty_total ?? '—'"></p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center border-l-4 border-l-blue-500">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 font-black">Yield</p>
                        <p class="font-black text-blue-600 text-base" x-text="(drawer.summary?.yield_percent ?? '—') + '%'"></p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm text-center">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">Variance</p>
                        <p class="font-black text-base"
                            :class="(drawer.summary?.variance ?? 0) < 0 ? 'text-red-700' : 'text-emerald-700'"
                            x-text="(drawer.summary?.variance ?? 0) > 0 ? '+' + drawer.summary?.variance : drawer.summary?.variance ?? '—'"></p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Transaction Log</h4>
                    <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold" x-text="drawer.sessions.length + ' Entries'"></span>
                </div>

                <div x-show="drawer.loading" class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <span class="material-symbols-outlined text-4xl animate-spin text-emerald-500 mb-4">progress_activity</span>
                    <p class="text-sm font-bold tracking-tight uppercase tracking-widest">Retrieving history...</p>
                </div>

                <template x-if="!drawer.loading && drawer.sessions.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                        <div class="p-4 bg-gray-50 rounded-full mb-4">
                            <span class="material-symbols-outlined text-4xl">history</span>
                        </div>
                        <p class="text-sm font-bold tracking-tight uppercase tracking-widest">No activity recorded</p>
                    </div>
                </template>

                <template x-for="(s, idx) in drawer.sessions" :key="s.id">
                    <div class="group relative bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all hover:border-emerald-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-black" x-text="idx + 1"></span>
                                <div>
                                    <p class="text-xs font-black text-gray-900 uppercase tracking-tighter" x-text="s.completion_status === 'COMPLETED' ? 'Final Confirmation' : 'Partial Record'"></p>
                                    <p class="text-[10px] text-gray-400 font-medium" x-text="s.created_at ? new Date(s.created_at).toLocaleString() : ''"></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-[10px] rounded-lg font-black uppercase tracking-wider"
                                :class="s.completion_status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                x-text="s.completion_status"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50/50 rounded-xl p-3 border border-transparent group-hover:border-emerald-50 transition-colors">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">Confirmed</p>
                                <p class="text-sm font-black text-emerald-700" x-text="s.confirmed_qty"></p>
                            </div>
                            <div class="bg-gray-50/50 rounded-xl p-3 border border-transparent group-hover:border-red-50 transition-colors">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">Rejected</p>
                                <p class="text-sm font-black text-red-700" x-text="s.rejected_qty"></p>
                            </div>

                            <template x-if="s.rejection_reason_code">
                                <div class="col-span-2 bg-red-50/30 rounded-xl p-3 border border-red-50">
                                    <p class="text-[10px] font-black text-red-400 uppercase tracking-tighter mb-1">Rejection Basis</p>
                                    <p class="text-xs font-bold text-red-800" x-text="s.rejection_reason_code"></p>
                                </div>
                            </template>

                            <template x-if="s.fg_batch_number">
                                <div class="col-span-2 bg-indigo-50/30 rounded-xl p-3 border border-indigo-50">
                                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-tighter mb-1">Assigned Batch Tag</p>
                                    <p class="text-xs font-mono font-black text-indigo-800" x-text="s.fg_batch_number"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <span class="material-symbols-outlined text-2xl">pending_actions</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Active Batches</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="orders.filter(o => o.status === 'IN_PROGRESS').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-50 rounded-xl text-green-600">
                    <span class="material-symbols-outlined text-2xl">check_circle</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Completed Today</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="orders.filter(o => o.status === 'COMPLETED').length"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-orange-50 rounded-xl text-orange-600">
                    <span class="material-symbols-outlined text-2xl">trending_up</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Overall Yield</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="'94.2%'"></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-50 rounded-xl text-red-600">
                    <span class="material-symbols-outlined text-2xl">report</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Rejections</p>
                    <h3 class="text-2xl font-black text-gray-900" x-text="'128'"></h3>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Page Header ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">FG Confirmation</h2>
            <p class="text-sm text-gray-500 mt-1">Record finished goods output and maintain batch integrity.</p>
        </div>
    </div>

    {{-- ── Filters ────────────────────────────────────────────────────── --}}
    <div class="bg-white/60 backdrop-blur-md rounded-2xl border border-gray-200 p-2 mb-8 flex flex-wrap items-center gap-3 shadow-sm">
        <div class="flex-1 min-w-[200px] relative text-slate-400">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg">search</span>
            <input type="text" x-model="filters.search" @input.debounce.400ms="loadOrders()"
                placeholder="Search by order no, product..."
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-medium text-slate-900 transition-all placeholder:text-slate-400">
        </div>
        <div class="w-48 relative text-slate-400">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg">filter_alt</span>
            <select x-model="filters.status" @change="loadOrders()"
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-bold text-slate-700 appearance-none cursor-pointer transition-all">
                <option value="IN_PROGRESS">Open Batches</option>
                <option value="">All Status</option>
                <option value="COMPLETED">Completed</option>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-lg">expand_more</span>
        </div>
        <button @click="filters.search=''; filters.status='IN_PROGRESS'; loadOrders()"
            class="px-4 py-2 text-slate-400 hover:text-emerald-600 font-black uppercase tracking-widest text-[10px] transition-colors">
            Reset Filters
        </button>
    </div>

    {{-- ── Orders Table ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Order Reference</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Product Identity</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Projected</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Confirmed</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Remaining</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Batch Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Operations</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <span class="material-symbols-outlined text-4xl animate-spin text-emerald-500">progress_activity</span>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest leading-none">Hydrating Data...</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4 text-gray-300">
                                    <div class="p-4 bg-gray-50 rounded-full">
                                        <span class="material-symbols-outlined text-5xl">task_alt</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">No active batches</p>
                                        <p class="text-xs text-gray-400 mt-1">Initialize production orders to begin confirmation.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-6 py-4">
                                <span class="text-xs font-black text-slate-900 font-mono tracking-tight leading-none" x-text="order.order_no"></span>
                            </td>
                            <td class="px-6 py-4 leading-none">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-bold text-slate-800" x-text="order.product_name"></span>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter" x-text="order.product_code"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right leading-none">
                                <span class="text-xs font-extrabold text-slate-600"
                                    x-text="order.target_qty + ' ' + (order.uom?.uom_name || order.uom || '')"></span>
                            </td>
                            <td class="px-6 py-4 text-right leading-none">
                                <div class="inline-flex flex-col items-end gap-1">
                                    <span class="text-xs font-black text-emerald-600"
                                        x-text="(order.confirmed_qty_total ?? 0) + ' ' + (order.uom?.uom_name || order.uom || '')"></span>
                                    <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="bg-emerald-500 h-full transition-all duration-1000" :style="'width: ' + ((order.confirmed_qty_total / order.target_qty) * 100) + '%'"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-black leading-none">
                                <span class="text-xs"
                                    :class="remainingQty(order) > 0 ? 'text-orange-600' : 'text-gray-400'"
                                    x-text="remainingQty(order) + ' ' + (order.uom?.uom_name || order.uom || '')"></span>
                            </td>
                            <td class="px-6 py-4 text-center leading-none">
                                <span class="px-3 py-1 text-[10px] rounded-full font-black uppercase tracking-widest"
                                    :class="{
                                          'bg-blue-50 text-blue-700 ring-1 ring-blue-100': order.status === 'IN_PROGRESS',
                                          'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100': order.status === 'COMPLETED',
                                          'bg-slate-50 text-slate-700 ring-1 ring-slate-100': order.status === 'DRAFT',
                                          'bg-red-50 text-red-700 ring-1 ring-red-100': order.status === 'CANCELLED'
                                      }"
                                    x-text="order.status.replace('_', ' ')"></span>
                            </td>
                            <td class="px-6 py-4 text-right leading-none">
                                <div class="inline-flex items-center gap-2">
                                    <button @click="openSessions(order)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-95">
                                        <span class="material-symbols-outlined text-sm">history</span> History
                                    </button>
                                    <button x-show="order.status === 'IN_PROGRESS'"
                                        @click="openModal(order)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md active:scale-95 shadow-emerald-100">
                                        <span class="material-symbols-outlined text-sm">task_alt</span> Confirm
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function fgConfirmation(orgSlug) {
        return {
            orgSlug,
            loading: false,
            orders: [],
            filters: {
                search: '',
                status: 'IN_PROGRESS'
            },

            modal: {
                show: false,
                submitting: false,
                order: null,
                error: ''
            },
            form: {
                confirmed_qty: '',
                rejected_qty: 0,
                rejection_reason_code: '',
                rejection_reason_note: '',
                fg_batch_number: '',
                completion_status: 'PARTIALLY_COMPLETED',
                qc_required: true,
            },

            drawer: {
                show: false,
                loading: false,
                order: null,
                sessions: [],
                summary: null
            },

            async init() {
                await this.loadOrders();
            },

            remainingQty(order) {
                const confirmed = parseFloat(order.confirmed_qty_total ?? 0);
                const target = parseFloat(order.target_qty ?? 0);
                return Math.max(0, target - confirmed).toFixed(3).replace(/\.?0+$/, '') || '0';
            },

            async loadOrders() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filters.search) params.append('search', this.filters.search);
                    if (this.filters.status) params.append('status', this.filters.status);
                    const res = await this._fetch(`/api/v1/production-orders?${params}`);
                    const data = await res.json();
                    this.orders = data?.data?.orders || data?.data || [];
                } catch (e) {
                    console.error('Failed to load orders', e);
                    this.orders = [];
                } finally {
                    this.loading = false;
                }
            },

            async openModal(order) {
                // Fetch latest session totals
                this.modal.error = '';
                this.modal.order = order;
                try {
                    const res = await this._fetch(`/api/v1/production-orders/${order.id}/fg-sessions`);
                    const data = await res.json();
                    if (data.success) {
                        this.modal.order = {
                            ...order,
                            ...data.data.order
                        };
                    }
                } catch (e) {
                    /* use cached order data */
                }

                const remaining = parseFloat(this.modal.order?.remaining_qty ?? this.modal.order?.target_qty ?? 0);
                this.form = {
                    confirmed_qty: remaining > 0 ? remaining : '',
                    rejected_qty: 0,
                    rejection_reason_code: '',
                    rejection_reason_note: '',
                    fg_batch_number: '',
                    completion_status: remaining > 0 ? 'COMPLETED' : 'PARTIALLY_COMPLETED',
                    qc_required: true,
                };
                this.modal.show = true;
            },

            syncStatus() {
                // Auto-adjust confirmed_qty when reject_qty changes
                const remaining = parseFloat(this.modal.order?.remaining_qty ?? this.modal.order?.target_qty ?? 0);
                const rejectQty = parseFloat(this.form.rejected_qty || 0);
                const maxAccept = Math.max(0, remaining - rejectQty);

                if (parseFloat(this.form.confirmed_qty || 0) > maxAccept) {
                    this.form.confirmed_qty = maxAccept;
                }

                const sessionTotal = parseFloat(this.form.confirmed_qty || 0) + rejectQty;
                if (sessionTotal >= remaining - 0.001) {
                    this.form.completion_status = 'COMPLETED';
                } else {
                    this.form.completion_status = 'PARTIALLY_COMPLETED';
                }
            },

            closeModal() {
                this.modal = {
                    show: false,
                    submitting: false,
                    order: null,
                    error: ''
                };
            },

            async submitConfirmation() {
                this.modal.error = '';
                if (!this.form.confirmed_qty || parseFloat(this.form.confirmed_qty) <= 0) {
                    this.modal.error = 'Confirmed qty must be greater than 0.';
                    return;
                }
                this.modal.submitting = true;
                try {
                    const payload = {
                        confirmed_qty: parseFloat(this.form.confirmed_qty),
                        rejected_qty: parseFloat(this.form.rejected_qty || 0),
                        rejection_reason_code: this.form.rejection_reason_code || null,
                        rejection_reason_note: this.form.rejection_reason_code === 'OTHER' ? this.form.rejection_reason_note : null,
                        fg_batch_number: this.form.fg_batch_number || null,
                        completion_status: this.form.completion_status,
                        qc_required: !!this.form.qc_required,
                    };
                    const res = await this._fetch(`/api/v1/production-orders/${this.modal.order.id}/confirm-fg`, {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to confirm FG');
                    this.closeModal();
                    await this.loadOrders();
                } catch (e) {
                    this.modal.error = e.message || 'An error occurred. Please try again.';
                } finally {
                    this.modal.submitting = false;
                }
            },

            async openSessions(order) {
                this.drawer.show = true;
                this.drawer.loading = true;
                this.drawer.order = order;
                this.drawer.sessions = [];
                this.drawer.summary = null;
                try {
                    const res = await this._fetch(`/api/v1/production-orders/${order.id}/fg-sessions`);
                    const data = await res.json();
                    if (data.success) {
                        this.drawer.sessions = data.data.sessions || [];
                        this.drawer.summary = data.data.order;
                    }
                } catch (e) {
                    console.error('Failed to load sessions', e);
                } finally {
                    this.drawer.loading = false;
                }
            },

            _fetch(url, options = {}) {
                return fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': 'Bearer ' + (localStorage.getItem('access_token') || ''),
                    },
                    ...options,
                });
            },
        }
    }
</script>
@endsection