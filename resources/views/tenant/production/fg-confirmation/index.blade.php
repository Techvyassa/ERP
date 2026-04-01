@extends('layouts.production')

@section('title', 'FG Confirmation')
@section('page-title', 'FG Confirmation')

@section('content')
<div x-data="fgConfirmation('{{ $organization->org_slug }}')" x-init="init()">

    {{-- ── Confirmation Modal ─────────────────────────────────────────── --}}
    <div x-show="modal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl z-10" @click.stop>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Finished Goods</h3>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="modal.order?.order_no + ' — ' + (modal.order?.product_name || '')"></p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Progress bar --}}
                <div class="px-6 pt-5">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Confirmed so far</span>
                        <span x-text="modal.order?.confirmed_qty_total + ' / ' + modal.order?.target_qty + ' ' + (modal.order?.uom || '')"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="bg-green-500 h-2.5 rounded-full transition-all"
                             :style="'width:' + Math.min(100, ((modal.order?.confirmed_qty_total / modal.order?.target_qty) * 100) || 0) + '%'"></div>
                    </div>
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-green-600 font-semibold" x-text="'Confirmed: ' + (modal.order?.confirmed_qty_total || 0)"></span>
                        <span class="text-orange-600 font-semibold" x-text="'Remaining: ' + (modal.order?.remaining_qty ?? modal.order?.target_qty ?? 0)"></span>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Confirmed Qty (this session) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" min="0.001" step="0.001"
                                   x-model="form.confirmed_qty"
                                   :max="modal.order?.remaining_qty"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-400 focus:border-transparent text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rejected Qty</label>
                            <input type="number" min="0" step="0.001"
                                   x-model="form.rejected_qty"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-400 focus:border-transparent text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rejection Reason Code</label>
                            <select x-model="form.rejection_reason_code"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
                                <option value="">— None —</option>
                                <option value="DEFECT_VISUAL">Visual Defect</option>
                                <option value="DEFECT_DIMENSIONAL">Dimensional Defect</option>
                                <option value="DEFECT_FUNCTIONAL">Functional Defect</option>
                                <option value="CONTAMINATION">Contamination</option>
                                <option value="PACKAGING_DAMAGE">Packaging Damage</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">FG Batch Number</label>
                            <input type="text" x-model="form.fg_batch_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
                        </div>
                    </div>

                    {{-- Completion status --}}
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <button @click="form.completion_status = 'PARTIALLY_COMPLETED'"
                                :class="form.completion_status === 'PARTIALLY_COMPLETED'
                                    ? 'border-yellow-500 bg-yellow-50 text-yellow-800 ring-2 ring-yellow-300'
                                    : 'border-gray-200 text-gray-600 hover:border-yellow-300'"
                                class="flex items-center gap-3 px-4 py-3 border-2 rounded-xl transition-all text-left">
                            <span class="material-symbols-outlined text-yellow-500 text-2xl">pending</span>
                            <div>
                                <p class="font-semibold text-sm">Partially Completed</p>
                                <p class="text-xs opacity-70">Batch stays open for more sessions</p>
                            </div>
                        </button>
                        <button @click="form.completion_status = 'COMPLETED'"
                                :class="form.completion_status === 'COMPLETED'
                                    ? 'border-green-500 bg-green-50 text-green-800 ring-2 ring-green-300'
                                    : 'border-gray-200 text-gray-600 hover:border-green-300'"
                                class="flex items-center gap-3 px-4 py-3 border-2 rounded-xl transition-all text-left">
                            <span class="material-symbols-outlined text-green-500 text-2xl">check_circle</span>
                            <div>
                                <p class="font-semibold text-sm">Completed</p>
                                <p class="text-xs opacity-70">Batch closed, no more sessions</p>
                            </div>
                        </button>
                    </div>

                    <label class="flex items-center gap-3 rounded-lg bg-orange-50 px-4 py-3 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="form.qc_required" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        Hold FG in QC after confirmation
                    </label>

                    <div x-show="modal.error" class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="modal.error"></div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm">Cancel</button>
                    <button @click="submitConfirmation()"
                            :disabled="modal.submitting || !form.confirmed_qty"
                            :class="(!modal.submitting && form.confirmed_qty) ? 'hover:bg-green-600' : 'opacity-50 cursor-not-allowed'"
                            class="px-5 py-2 bg-green-500 text-white font-semibold rounded-lg transition-colors flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-sm"
                              :class="modal.submitting ? 'animate-spin' : ''"
                              x-text="modal.submitting ? 'progress_activity' : 'task_alt'"></span>
                        <span x-text="modal.submitting ? 'Posting...' : (form.completion_status === 'COMPLETED' ? 'Complete Batch' : 'Save & Keep Open')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sessions History Drawer ────────────────────────────────────── --}}
    <div x-show="drawer.show" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display:none;">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-40" @click="drawer.show = false"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmation Sessions</h3>
                    <p class="text-xs text-gray-500" x-text="drawer.order?.order_no + ' — ' + (drawer.order?.product_name || '')"></p>
                </div>
                <button @click="drawer.show = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            {{-- Summary --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-xs text-gray-500">Target</p>
                    <p class="font-bold text-gray-900 text-lg" x-text="drawer.summary?.target_qty ?? '—'"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Confirmed</p>
                    <p class="font-bold text-green-600 text-lg" x-text="drawer.summary?.confirmed_qty_total ?? '—'"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Remaining</p>
                    <p class="font-bold text-orange-600 text-lg" x-text="drawer.summary?.remaining_qty ?? '—'"></p>
                </div>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-xs text-gray-500">Rejected</p>
                    <p class="font-bold text-red-600" x-text="drawer.summary?.rejected_qty_total ?? '—'"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Yield %</p>
                    <p class="font-bold text-blue-600" x-text="(drawer.summary?.yield_percent ?? '—') + '%'"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Variance</p>
                    <p class="font-bold"
                       :class="(drawer.summary?.variance ?? 0) < 0 ? 'text-red-600' : 'text-green-600'"
                       x-text="drawer.summary?.variance ?? '—'"></p>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                <div x-show="drawer.loading" class="text-center py-8 text-gray-400">
                    <span class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>
                    Loading sessions...
                </div>
                <template x-if="!drawer.loading && drawer.sessions.length === 0">
                    <div class="text-center py-8 text-gray-400">
                        <span class="material-symbols-outlined text-4xl block mx-auto mb-2">history</span>
                        No sessions recorded yet.
                    </div>
                </template>
                <template x-for="(s, idx) in drawer.sessions" :key="s.id">
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500" x-text="'Session #' + (idx + 1)"></span>
                            <span class="px-2 py-0.5 text-xs rounded-full font-semibold"
                                  :class="s.completion_status === 'COMPLETED' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                  x-text="s.completion_status === 'COMPLETED' ? 'Completed' : 'Partial'"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-500">Confirmed:</span> <span class="font-semibold text-green-700" x-text="s.confirmed_qty"></span></div>
                            <div><span class="text-gray-500">Rejected:</span> <span class="font-semibold text-red-600" x-text="s.rejected_qty"></span></div>
                            <div x-show="s.rejection_reason_code" class="col-span-2">
                                <span class="text-gray-500">Reason:</span>
                                <span class="font-medium text-gray-700" x-text="s.rejection_reason_code"></span>
                            </div>
                            <div x-show="s.fg_batch_number" class="col-span-2">
                                <span class="text-gray-500">Batch:</span>
                                <span class="font-mono text-xs text-gray-700" x-text="s.fg_batch_number"></span>
                            </div>
                            <div class="col-span-2 text-xs text-gray-400" x-text="s.created_at ? new Date(s.created_at).toLocaleString() : ''"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Page Header ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">FG Confirmation</h2>
            <p class="text-sm text-gray-500 mt-1">Confirm finished goods output against open production batches. Record confirmed qty, rejected qty, and reason codes.</p>
        </div>
    </div>

    {{-- ── Filters ────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.400ms="loadOrders()"
                   placeholder="Search order no, product..."
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-400 focus:border-transparent text-sm">
            <select x-model="filters.status" @change="loadOrders()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-400 focus:border-transparent text-sm">
                <option value="IN_PROGRESS">In Progress (Open Batches)</option>
                <option value="">All Status</option>
                <option value="COMPLETED">Completed</option>
            </select>
            <button @click="filters.search=''; filters.status='IN_PROGRESS'; loadOrders()"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm transition-colors">
                Reset
            </button>
        </div>
    </div>

    {{-- ── Orders Table ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Target Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Confirmed</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Remaining</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-2">progress_activity</span>
                            Loading...
                        </td></tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-5xl block mx-auto mb-2">task_alt</span>
                            No open batches found. Start a production order first.
                        </td></tr>
                    </template>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-sm font-semibold text-gray-900" x-text="order.order_no"></td>
                            <td class="px-5 py-3 text-sm text-gray-700">
                                <div x-text="order.product_name"></div>
                                <div class="text-xs text-gray-400" x-text="order.product_code"></div>
                            </td>
                            <td class="px-5 py-3 text-sm text-right font-medium text-gray-700"
                                x-text="order.target_qty + ' ' + (order.uom || '')"></td>
                            <td class="px-5 py-3 text-sm text-right">
                                <span class="font-semibold text-green-600"
                                      x-text="(order.confirmed_qty_total ?? 0) + ' ' + (order.uom || '')"></span>
                            </td>
                            <td class="px-5 py-3 text-sm text-right">
                                <span class="font-semibold text-orange-600"
                                      x-text="remainingQty(order) + ' ' + (order.uom || '')"></span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                      :class="{
                                          'bg-blue-100 text-blue-700': order.status === 'IN_PROGRESS',
                                          'bg-green-100 text-green-800': order.status === 'COMPLETED',
                                          'bg-gray-100 text-gray-700': order.status === 'DRAFT',
                                          'bg-red-100 text-red-800': order.status === 'CANCELLED'
                                      }"
                                      x-text="order.status"></span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button @click="openSessions(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">history</span> Sessions
                                    </button>
                                    <button x-show="order.status === 'IN_PROGRESS'"
                                            @click="openModal(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500 text-white hover:bg-green-600 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">task_alt</span> Confirm FG
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
        filters: { search: '', status: 'IN_PROGRESS' },

        modal: { show: false, submitting: false, order: null, error: '' },
        form: {
            confirmed_qty: '',
            rejected_qty: 0,
            rejection_reason_code: '',
            fg_batch_number: '',
            completion_status: 'PARTIALLY_COMPLETED',
            qc_required: false,
        },

        drawer: { show: false, loading: false, order: null, sessions: [], summary: null },

        async init() {
            await this.loadOrders();
        },

        remainingQty(order) {
            const confirmed = parseFloat(order.confirmed_qty_total ?? 0);
            const target    = parseFloat(order.target_qty ?? 0);
            return Math.max(0, target - confirmed).toFixed(3).replace(/\.?0+$/, '') || '0';
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                const res  = await this._fetch(`/api/v1/production-orders?${params}`);
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
                const res  = await this._fetch(`/api/v1/production-orders/${order.id}/fg-sessions`);
                const data = await res.json();
                if (data.success) {
                    this.modal.order = { ...order, ...data.data.order };
                }
            } catch (e) { /* use cached order data */ }

            const remaining = parseFloat(this.modal.order?.remaining_qty ?? this.modal.order?.target_qty ?? 0);
            this.form = {
                confirmed_qty: remaining > 0 ? remaining : '',
                rejected_qty: 0,
                rejection_reason_code: '',
                fg_batch_number: '',
                completion_status: 'PARTIALLY_COMPLETED',
                qc_required: false,
            };
            this.modal.show = true;
        },

        closeModal() {
            this.modal = { show: false, submitting: false, order: null, error: '' };
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
                    confirmed_qty:         parseFloat(this.form.confirmed_qty),
                    rejected_qty:          parseFloat(this.form.rejected_qty || 0),
                    rejection_reason_code: this.form.rejection_reason_code || null,
                    fg_batch_number:       this.form.fg_batch_number || null,
                    completion_status:     this.form.completion_status,
                    qc_required:           !!this.form.qc_required,
                };
                const res  = await this._fetch(`/api/v1/production-orders/${this.modal.order.id}/confirm-fg`, {
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
            this.drawer.show    = true;
            this.drawer.loading = true;
            this.drawer.order   = order;
            this.drawer.sessions = [];
            this.drawer.summary  = null;
            try {
                const res  = await this._fetch(`/api/v1/production-orders/${order.id}/fg-sessions`);
                const data = await res.json();
                if (data.success) {
                    this.drawer.sessions = data.data.sessions || [];
                    this.drawer.summary  = data.data.order;
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
