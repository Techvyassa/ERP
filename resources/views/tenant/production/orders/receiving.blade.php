@extends('layouts.production')

@section('title', 'Confirm Material Receipt')
@section('page-title', 'Production Floor Receiving')

@section('content')
<div x-data="receivingData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="/org/{{ $organization->org_slug }}/production/orders"
                class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-slate-900 hover:border-slate-400 transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black text-orange-600 bg-orange-50 px-2 py-0.5 rounded uppercase tracking-widest">Production Floor</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-[10px] font-bold text-slate-400 font-mono" x-text="order?.order_no || '...'"></span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Confirm Material Receipt</h2>
            </div>
        </div>

        <!-- Confirm button in header -->
        <template x-if="order && order.mir_status !== 'CLOSED'">
            <div class="flex items-center gap-3">
                <template x-if="confirmError">
                    <span class="text-xs text-red-600 font-bold" x-text="confirmError"></span>
                </template>
                <button @click="openConfirmModal()" :disabled="!allQtysEntered()"
                    class="px-6 py-2.5 bg-orange-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-orange-600 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-sm">move_to_inbox</span>
                    Confirm Receipt
                </button>
            </div>
        </template>
        <template x-if="order && order.mir_status === 'CLOSED'">
            <span class="px-4 py-2.5 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                Receipt Confirmed
            </span>
        </template>
    </div>

    <!-- Loading -->
    <template x-if="loading">
        <div class="flex items-center justify-center py-24">
            <span class="material-symbols-outlined text-4xl animate-spin text-orange-500">progress_activity</span>
        </div>
    </template>

    <template x-if="!loading && order">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: MIR Lines to verify -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Production Order Info -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400 text-xl">precision_manufacturing</span>
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Production Order</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Order No</p>
                            <p class="text-sm font-black text-slate-900 font-mono" x-text="order.order_no"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Product</p>
                            <p class="text-sm font-bold text-slate-800" x-text="order.product_name || '—'"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Target Qty</p>
                            <p class="text-sm font-black text-slate-900" x-text="order.target_qty"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">MIR Status</p>
                            <span class="px-2 py-1 text-[10px] font-black rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"
                                x-text="order.mir_status"></span>
                        </div>
                    </div>
                </div>

                <!-- Materials to Verify -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400 text-xl">inventory_2</span>
                            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Materials Issued by Store</h3>
                        </div>
                        <span class="px-2 py-1 bg-slate-200 text-slate-600 rounded text-[10px] font-black uppercase tracking-widest"
                            x-text="mirLines.length + ' items'"></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/20 border-b border-gray-50">
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Material</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Required</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Issued by Store</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Received at Floor</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Discrepancy</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="line in mirLines" :key="line.id">
                                    <tr class="hover:bg-slate-50/30 transition-all">
                                        <td class="px-6 py-4 leading-none">
                                            <span class="text-sm font-bold text-slate-800" x-text="line.material?.name || line.material_name"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center leading-none">
                                            <span class="text-sm font-black text-slate-700" x-text="line.required_qty"></span>
                                            <span class="text-[10px] text-slate-400 ml-1" x-text="line.uom_name || line.uom"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center leading-none">
                                            <span class="text-sm font-black text-emerald-600" x-text="line.issued_qty"></span>
                                            <span class="text-[10px] text-slate-400 ml-1" x-text="line.uom_name || line.uom"></span>
                                        </td>
                                        <td class="px-6 py-4 text-center leading-none">
                                            <div class="flex items-center justify-center gap-1">
                                                <input type="number"
                                                    :value="receivedQtys[line.id]"
                                                    readonly
                                                    class="w-24 px-2 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-sm font-bold text-center text-slate-500 cursor-not-allowed">
                                                <span class="text-[10px] text-slate-400" x-text="line.uom_name || line.uom"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center leading-none">
                                            <template x-if="receivedQtys[line.id] != null">
                                                <span class="text-xs font-black"
                                                    :class="parseFloat(receivedQtys[line.id]) < parseFloat(line.issued_qty) ? 'text-red-600' : 'text-emerald-600'"
                                                    x-text="parseFloat(receivedQtys[line.id]) < parseFloat(line.issued_qty)
                                                        ? '−' + (parseFloat(line.issued_qty) - parseFloat(receivedQtys[line.id])).toFixed(3)
                                                        : 'OK'">
                                                </span>
                                            </template>
                                            <template x-if="receivedQtys[line.id] == null">
                                                <span class="text-[10px] text-slate-300">—</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Guidelines -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-black text-slate-800 flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-orange-500">info</span>
                        How This Works
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-[10px] font-black shrink-0">1</span>
                            <p class="text-xs text-gray-600 leading-relaxed">Store has issued all materials. They are physically en route to your workstation.</p>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-[10px] font-black shrink-0">2</span>
                            <p class="text-xs text-gray-600 leading-relaxed">Physically verify each material against the issued quantity. Enter the actual quantity you received.</p>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-[10px] font-black shrink-0">3</span>
                            <p class="text-xs text-gray-600 leading-relaxed">If there's a discrepancy (e.g. 9.8 kg received vs 10 kg issued), enter the actual received qty and note the difference.</p>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-[10px] font-black shrink-0">4</span>
                            <p class="text-xs text-gray-600 leading-relaxed">Once confirmed, production can start. Discrepancies are logged for investigation but do not block production.</p>
                        </li>
                    </ul>
                </div>

                <!-- MIR Summary -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-black text-slate-800 flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-slate-400">summarize</span>
                        MIR Summary
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">MIR No</span>
                            <span class="text-xs font-black text-slate-900 font-mono" x-text="order.mir_no || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Total Lines</span>
                            <span class="text-xs font-black text-slate-900" x-text="mirLines.length"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">All Fully Picked</span>
                            <span class="text-xs font-black flex items-center gap-1"
                                :class="mirLines.length > 0 && mirLines.every(l => l.status === 'FULLY_PICKED') ? 'text-emerald-600' : 'text-amber-600'">
                                <span class="material-symbols-outlined text-sm"
                                    x-text="mirLines.length > 0 && mirLines.every(l => l.status === 'FULLY_PICKED') ? 'check_circle' : 'pending'">
                                </span>
                                <span x-text="mirLines.length > 0 && mirLines.every(l => l.status === 'FULLY_PICKED') ? 'Yes' : 'Partial'"></span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">MIR Status</span>
                            <span class="text-xs font-black text-slate-900" x-text="order.mir_status || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Issued At</span>
                            <span class="text-xs font-semibold text-slate-700"
                                x-text="order.mir_fully_issued_at ? new Date(order.mir_fully_issued_at).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Confirm Receipt Modal -->
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-orange-500 to-orange-600 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-xl">
                            <span class="material-symbols-outlined text-white">move_to_inbox</span>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-white">Confirm Material Receipt</h3>
                            <p class="text-[10px] text-orange-100 font-semibold" x-text="order?.order_no"></p>
                        </div>
                    </div>
                    <button @click="showConfirmModal = false" class="text-white/60 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Summary -->
                    <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                        <p class="text-xs font-black text-orange-700 mb-2 uppercase tracking-widest">Receipt Summary</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500">Total materials</span>
                                <span class="font-black text-slate-900" x-text="mirLines.length + ' items'"></span>
                            </div>
                            <template x-for="line in mirLines" :key="line.id">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500" x-text="line.material?.name || line.material_name"></span>
                                    <span class="font-bold"
                                        :class="parseFloat(receivedQtys[line.id]) < parseFloat(line.issued_qty) ? 'text-red-600' : 'text-emerald-600'"
                                        x-text="receivedQtys[line.id] + ' ' + (line.uom_name || line.uom)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Notes (optional)
                        </label>
                        <textarea x-model="receivingNotes" rows="3"
                            placeholder="Note any discrepancies, damaged materials, or observations..."
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-orange-400 transition-all resize-none"></textarea>
                    </div>

                    <template x-if="confirmError">
                        <div class="px-4 py-3 bg-red-50 text-red-600 text-xs rounded-xl font-bold flex items-center gap-2 border border-red-100">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <span x-text="confirmError"></span>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-1">
                        <button @click="showConfirmModal = false"
                            class="flex-1 py-3 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button @click="confirmReceipt()" :disabled="processing"
                            class="flex-[2] py-3 bg-orange-500 text-white text-sm font-black uppercase tracking-widest rounded-xl hover:bg-orange-600 transition-all flex items-center justify-center gap-2 disabled:opacity-50 shadow-md active:scale-95">
                            <template x-if="!processing">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                    Confirm Receipt
                                </span>
                            </template>
                            <template x-if="processing">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                                    Confirming...
                                </span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function receivingData() {
    const orgSlug = '{{ $organization->org_slug }}';
    const orderId = '{{ $orderId }}';
    const token = () => localStorage.getItem('access_token');
    const headers = () => {
        const h = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug };
        const t = token();
        if (t && t !== 'null') h['Authorization'] = `Bearer ${t}`;
        return h;
    };

    return {
        loading: true,
        processing: false,
        showConfirmModal: false,
        order: null,
        mirLines: [],
        receivedQtys: {},
        receivingNotes: '',
        confirmError: '',

        async init() {
            await this.loadOrder();
        },

        async loadOrder() {
            this.loading = true;
            try {
                const res = await fetch(`/api/v1/production-orders/${orderId}`, { headers: headers() });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load order');

                const raw = data.data?.order || data.data;
                this.order = {
                    ...raw,
                    product_name:         raw.product?.product_name || raw.product_name,
                    mir_no:               raw.mir?.mir_no,
                    mir_status:           raw.mir?.status || raw.mir_status,
                    mir_fully_issued_at:  raw.mir?.fully_issued_at,
                    mir_id:               raw.mir?.id || raw.mir_id,
                };

                // Load MIR lines
                const mirId = raw.mir?.id || raw.mir_id;
                if (mirId) {
                    const mirRes = await fetch(`/api/v1/material-issue-requests/${mirId}`, { headers: headers() });
                    const mirData = await mirRes.json();
                    if (mirData.success) {
                        this.mirLines = mirData.data.lines || [];
                        // Pre-fill received qty with issued qty (default: all received)
                        this.mirLines.forEach(line => {
                            this.receivedQtys[line.id] = parseFloat(line.issued_qty);
                        });
                    }
                }

                // Guard: redirect if MIR not FULLY_ISSUED
                if (!['FULLY_ISSUED', 'CLOSED'].includes(this.order.mir_status)) {
                    window.location.href = `/org/${orgSlug}/production/orders`;
                }
            } catch (e) {
                console.error(e);
                window.location.href = `/org/${orgSlug}/production/orders`;
            } finally {
                this.loading = false;
            }
        },

        allQtysEntered() {
            return this.mirLines.length > 0 && this.mirLines.every(line => {
                const v = this.receivedQtys[line.id];
                return v !== null && v !== undefined && v !== '' && parseFloat(v) >= 0;
            });
        },

        openConfirmModal() {
            this.confirmError = '';
            this.receivingNotes = '';
            this.showConfirmModal = true;
        },

        async confirmReceipt() {
            this.confirmError = '';
            this.processing = true;
            try {
                // Build discrepancy notes from any qty differences
                const discrepancies = this.mirLines
                    .filter(line => parseFloat(this.receivedQtys[line.id]) < parseFloat(line.issued_qty))
                    .map(line => {
                        const diff = (parseFloat(line.issued_qty) - parseFloat(this.receivedQtys[line.id])).toFixed(3);
                        return `${line.material?.name || line.material_name}: short by ${diff} ${line.uom}`;
                    });

                const notes = [
                    this.receivingNotes,
                    discrepancies.length ? 'Discrepancies: ' + discrepancies.join('; ') : ''
                ].filter(Boolean).join(' | ');

                // Call the receiving confirm endpoint
                const res = await fetch(`/api/v1/production-orders/${orderId}/confirm-receipt`, {
                    method: 'PATCH',
                    headers: headers(),
                    body: JSON.stringify({
                        receiving_notes: notes || null,
                        received_lines: this.mirLines.map(line => ({
                            mir_line_id: line.id,
                            received_qty: parseFloat(this.receivedQtys[line.id])
                        }))
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to confirm receipt');

                this.showConfirmModal = false;
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: 'Materials confirmed. Production can now start!', type: 'success' }
                }));
                setTimeout(() => {
                    window.location.href = `/org/${orgSlug}/production/orders`;
                }, 1200);
            } catch (e) {
                this.confirmError = e.message || 'An error occurred. Please try again.';
            } finally {
                this.processing = false;
            }
        }
    };
}
</script>
@endsection
