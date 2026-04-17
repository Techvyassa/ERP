@extends('layouts.production')

@section('title', 'Packing Orders - ' . $organization->org_name)
@section('page-title', 'Packing Orders')

@section('content')
<div x-data="packingOrders('{{ $organization->org_slug }}')" x-init="init()">
    <div class="flex items-center justify-between mb-6" x-show="!selectedOrder">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Packing Workspace</h2>
            <p class="text-sm text-gray-500">Create and manage packing orders for completed production.</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="loadPackingOrders()" class="p-2 text-gray-400 hover:text-orange-600 transition-colors">
                <span class="material-symbols-outlined">refresh</span>
            </button>
        </div>
    </div>

    <div x-show="!selectedOrder" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs mb-4">Start New Packing</h3>
                    <select x-model="newPackingOrder.production_order_id"
                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-orange-500 mb-4 appearance-none shadow-inner">
                        <option value="">Select completed production order</option>
                        <template x-for="order in completedOrders" :key="order.id">
                            <option :value="order.id" x-text="order.order_no + ' • ' + order.product_name"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-gray-400 font-medium leading-relaxed mb-6">Only completed orders are shown. FG QC must be decided before packing if QC was required.</p>
                </div>
                <button @click="createPackingOrder()" :disabled="creatingOrder || !newPackingOrder.production_order_id"
                    class="w-full py-3 bg-orange-600 text-white rounded-xl font-black uppercase tracking-widest text-xs hover:bg-orange-700 disabled:opacity-50 transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Create Order
                </button>
            </div>

            <div class="md:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="font-black text-xs text-gray-400 uppercase tracking-widest">Active Packing Orders</h3>
                </div>
                <div class="divide-y divide-gray-100 min-h-[300px]">
                    <template x-if="packingOrders.length === 0">
                        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                            <p class="text-xs font-bold uppercase tracking-widest">No orders found</p>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-gray-100">
                        <template x-for="order in packingOrders" :key="order.id">
                            <button @click="selectOrder(order.id)"
                                class="bg-white text-left px-6 py-6 hover:bg-orange-50/50 transition-all group">
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-black text-gray-900 text-sm" x-text="order.packing_order_no"></p>
                                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-tighter"
                                                :class="order.status === 'COMPLETED' ? 'bg-green-100 text-green-700' : (order.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')"
                                                x-text="order.status"></span>
                                        </div>
                                        <p class="text-xs font-bold text-gray-500" x-text="order.production_order?.order_no"></p>
                                        <p class="text-xs text-gray-400 font-medium truncate max-w-[200px]" x-text="order.production_order?.product?.product_name || ''"></p>
                                    </div>
                                    <span class="material-symbols-outlined text-gray-300 group-hover:text-orange-500 transition-colors">arrow_forward_ios</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="selectedOrder" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-12" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-6">
        <div class="flex items-center gap-4 mb-2">
            <button @click="selectedOrder = null" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-gray-600">arrow_back</span>
            </button>
            <div>
                <h3 class="text-xl font-bold text-gray-900" x-text="selectedOrder?.packing_order_no"></h3>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-0.5" x-text="selectedOrder?.production_order?.order_no + ' • ' + (selectedOrder?.production_order?.product?.product_name || '')"></p>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                    :class="selectedOrder?.status === 'COMPLETED' ? 'bg-green-100 text-green-700' : (selectedOrder?.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')"
                    x-text="selectedOrder?.status"></span>
                <button @click="completePackingOrder()" :disabled="selectedOrder?.status === 'COMPLETED'"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-indigo-700 disabled:opacity-50 shadow-lg shadow-indigo-100 transition-all active:scale-95">
                    Finish Workspace
                </button>
            </div>
        </div>

        <div>
            <template x-if="selectedOrder">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">FG Batch</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.production_order?.fg_batch_number || '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Produced Qty</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.production_order?.actual_qty || '0.000'"></p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg p-3">
                                <p class="text-xs text-emerald-600 font-bold">QC Passed Qty</p>
                                <p class="font-bold text-emerald-700" x-text="qcPassedQty(selectedOrder)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Packed Qty</p>
                                <p class="font-semibold text-gray-900" x-text="packedQty(selectedOrder)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Packages</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.cartons?.length || 0"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900">Packages</h4>
                                <div class="flex items-center gap-2">
                                    <button @click="showAllCartons = !showAllCartons" class="text-xs text-gray-600 hover:text-orange-600 font-medium">
                                        <span x-text="showAllCartons ? 'Show Open Only' : 'Show All'"></span>
                                    </button>
                                    <button @click="createCarton()" :disabled="hasOpenCarton" class="text-sm text-orange-600 font-semibold" :class="hasOpenCarton ? 'opacity-50 cursor-not-allowed' : ''">Open Package</button>
                                </div>
                            </div>
                            <div class="space-y-3 max-h-[28rem] overflow-y-auto">
                                <template x-if="!selectedOrder.cartons || selectedOrder.cartons.length === 0">
                                    <div class="text-sm text-gray-400">No packages yet.</div>
                                </template>
                                <template x-for="carton in filteredCartons" :key="carton.id">
                                    <button @click="selectedCartonId = carton.id"
                                        class="w-full text-left rounded-lg border px-3 py-3 transition"
                                        :class="selectedCartonId === carton.id ? 'border-orange-300 bg-orange-50' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-gray-900" x-text="carton.carton_barcode"></p>
                                                <p class="text-xs text-gray-500 mt-1" x-text="carton.carton_type + ' • ' + (carton.items?.length || 0) + ' scans'"></p>
                                            </div>
                                            <span class="px-2 py-1 rounded-full text-[11px] font-bold"
                                                :class="carton.status === 'OPEN' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                                x-text="carton.status"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <template x-if="selectedCarton">
                                <div class="bg-white rounded-xl border border-gray-200 p-5">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-900" x-text="selectedCarton.carton_barcode"></h4>
                                            <p class="text-sm text-gray-500" x-text="selectedCarton.carton_type + ' carton'"></p>
                                        </div>
                                        <button @click="sealCarton()" :disabled="selectedCarton.status !== 'OPEN'"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50 transition">
                                            Seal Package
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                        <input type="text" x-model="scanForm.product_barcode"
                                            placeholder="Scan / enter FG barcode"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                        <input type="number" min="0.001" step="0.001" x-model="scanForm.qty"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                        <button @click="openScanModal()" :disabled="selectedCarton.status !== 'OPEN'"
                                            class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                            Scan Into Package
                                        </button>
                                    </div>

                                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Barcode</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Batch</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-if="!selectedCarton.items || selectedCarton.items.length === 0">
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">No items scanned yet.</td>
                                                    </tr>
                                                </template>
                                                <template x-for="item in selectedCarton.items" :key="item.id">
                                                    <tr>
                                                        <td class="px-4 py-2 text-gray-700" x-text="item.product_barcode"></td>
                                                        <td class="px-4 py-2 text-gray-900" x-text="item.product?.product_name || '—'"></td>
                                                        <td class="px-4 py-2 text-right font-semibold text-gray-900" x-text="item.qty"></td>
                                                        <td class="px-4 py-2 text-gray-600" x-text="item.batch_number || '—'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!selectedCarton">
                                <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-400">
                                    Select a carton to scan FG and seal packs.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!selectedOrder">
                <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center text-gray-400">
                    Select a packing order to manage packages and scans.
                </div>
            </template>
        </div>
    </div>

    <!-- Scan Modal -->
    <div x-show="scanModal.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeScanModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                @click.stop>
                <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-5 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Scan FG into Package</h3>
                        <p class="text-xs text-white/70" x-text="scanModal.carton?.carton_barcode"></p>
                    </div>
                    <button @click="closeScanModal()" class="text-white/60 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-5">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Product Barcode</label>
                        <input type="text" x-model="scanForm.product_barcode"
                            placeholder="Scan / enter FG barcode"
                            class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-500 font-bold text-gray-900 shadow-inner">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Quantity</label>
                        <input type="number" min="0.001" step="0.001" x-model="scanForm.qty"
                            class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-500 font-bold text-gray-900 shadow-inner">
                    </div>
                    <div x-show="scanModal.error"
                        class="p-3 bg-red-50 border border-red-100 text-red-700 text-xs font-bold rounded-xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">error</span>
                        <span x-text="scanModal.error"></span>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/30">
                    <button @click="closeScanModal()" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-600 font-bold text-sm hover:bg-gray-50 transition-all shadow-sm">Cancel</button>
                    <button @click="submitScan()"
                        :disabled="scanModal.submitting || !scanForm.product_barcode"
                        :class="(!scanModal.submitting && scanForm.product_barcode) ? 'bg-orange-600 shadow-orange-200 cursor-pointer' : 'bg-gray-300 cursor-not-allowed'"
                        class="px-6 py-2.5 text-white font-black uppercase tracking-widest text-xs rounded-xl transition-all shadow-lg flex items-center gap-2 active:scale-95">
                        <span class="material-symbols-outlined text-lg"
                            :class="scanModal.submitting ? 'animate-spin' : ''"
                            x-text="scanModal.submitting ? 'progress_activity' : 'check'"></span>
                        <span x-text="scanModal.submitting ? 'Scanning...' : 'Scan Into Carton'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function packingOrders(orgSlug) {
        const headers = () => ({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + (localStorage.getItem('access_token') || ''),
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Org-Slug': orgSlug
        });

        return {
            packingOrders: [],
            completedOrders: [],
            selectedOrder: null,
            selectedCartonId: null,
            showAllCartons: false,
            creatingOrder: false,
            newPackingOrder: {
                production_order_id: ''
            },
            scanForm: {
                product_barcode: '',
                qty: 1
            },
            scanModal: {
                show: false,
                carton: null,
                submitting: false,
                error: ''
            },

            async init() {
                await Promise.all([this.loadCompletedOrders(), this.loadPackingOrders()]);
            },

            openScanModal() {
                if (!this.selectedCarton) return;
                this.scanModal = {
                    show: true,
                    carton: this.selectedCarton
                };
            },

            closeScanModal() {
                this.scanModal = {
                    show: false,
                    carton: null
                };
                this.scanForm = {
                    product_barcode: '',
                    qty: 1
                };
            },

            async submitScan() {
                this.scanModal.error = '';
                if (!this.scanForm.product_barcode || this.scanForm.product_barcode.trim() === '') {
                    this.scanModal.error = 'Please enter or scan the product barcode first.';
                    return;
                }
                if (!this.scanForm.qty || parseFloat(this.scanForm.qty) <= 0) {
                    this.scanModal.error = 'Please enter a valid quantity.';
                    return;
                }
                this.scanModal.submitting = true;
                try {
                    const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons/${this.selectedCartonId}/scan`, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify(this.scanForm)
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to scan FG into Package');
                    this.closeScanModal();
                    await this.selectOrder(this.selectedOrder.id);
                } catch (error) {
                    this.scanModal.error = error.message || 'An error occurred. Please try again.';
                } finally {
                    this.scanModal.submitting = false;
                }
            },

            get selectedCarton() {
                return this.selectedOrder?.cartons?.find(c => c.id === this.selectedCartonId) || null;
            },

            get filteredCartons() {
                if (this.showAllCartons) {
                    return this.selectedOrder?.cartons || [];
                }
                return (this.selectedOrder?.cartons || []).filter(c => c.status === 'OPEN');
            },

            get hasOpenCarton() {
                return (this.selectedOrder?.cartons || []).some(c => c.status === 'OPEN');
            },

            packedQty(order) {
                return (order.cartons || []).reduce((sum, carton) => {
                    return sum + (carton.items || []).reduce((inner, item) => inner + parseFloat(item.qty || 0), 0);
                }, 0).toFixed(3);
            },

            qcPassedQty(order) {
                if (!order || !order.production_order) return '0.000';
                const lots = order.production_order.inspection_lots || [];
                return lots.reduce((sum, lot) => {
                    return sum + parseFloat(lot.usage_decision?.accepted_qty || 0);
                }, 0).toFixed(3);
            },

            async loadCompletedOrders() {
                const res = await fetch('/api/v1/production-orders/for-packing', {
                    headers: headers()
                });
                const data = await res.json();
                this.completedOrders = data.data?.orders || [];
            },

            async loadPackingOrders() {
                const res = await fetch('/api/v1/packing-orders', {
                    headers: headers()
                });
                const data = await res.json();
                this.packingOrders = data.data?.packing_orders || [];
            },

            async selectOrder(id) {
                const res = await fetch(`/api/v1/packing-orders/${id}`, {
                    headers: headers()
                });
                const data = await res.json();
                this.selectedOrder = data.data?.packing_order || null;

                // Preserve selected carton if it still exists in the updated order
                const existingCarton = this.selectedOrder?.cartons?.find(c => c.id === this.selectedCartonId);
                if (existingCarton) {
                    this.selectedCartonId = existingCarton.id;
                } else if (this.selectedOrder?.cartons?.length > 0) {
                    // If no existing carton, select the first open one, or first carton if none open
                    const openCarton = this.selectedOrder.cartons.find(c => c.status === 'OPEN');
                    this.selectedCartonId = openCarton ? openCarton.id : this.selectedOrder.cartons[0].id;
                } else {
                    this.selectedCartonId = null;
                }
            },

            async createPackingOrder() {
                this.creatingOrder = true;
                try {
                    const res = await fetch('/api/v1/packing-orders', {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify(this.newPackingOrder)
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to create packing order');
                    this.newPackingOrder.production_order_id = '';
                    await this.loadPackingOrders();
                    await this.selectOrder(data.data.packing_order.id);
                    this.notify('Packing order created');
                } catch (error) {
                    this.notify(error.message, 'error');
                } finally {
                    this.creatingOrder = false;
                }
            },

            async createCarton() {
                try {
                    const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons`, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({
                            carton_type: 'OUTER'
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        return this.notify(data.message || 'Failed to create carton', 'error');
                    }
                    await this.selectOrder(this.selectedOrder.id);
                    this.selectedCartonId = data.data.carton.id;
                    this.notify('New carton opened');
                } catch (error) {
                    this.notify(error.message || 'An error occurred while creating the carton', 'error');
                }
            },

            async scanIntoCarton() {
                if (!this.scanForm.product_barcode || this.scanForm.product_barcode.trim() === '') {
                    return this.notify('Please enter or scan the product barcode first.', 'error');
                }
                if (!this.scanForm.qty || parseFloat(this.scanForm.qty) <= 0) {
                    return this.notify('Please enter a valid quantity.', 'error');
                }
                const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons/${this.selectedCartonId}/scan`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(this.scanForm)
                });
                const data = await res.json();
                if (!res.ok || !data.success) return this.notify(data.message || 'Failed to scan FG into Package', 'error');
                this.scanForm = {
                    product_barcode: '',
                    qty: 1
                };
                await this.selectOrder(this.selectedOrder.id);
                this.notify('FG scanned into carton');
            },

            async sealCarton() {
                const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons/${this.selectedCartonId}/seal`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({
                        labelled: true
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) return this.notify(data.message || 'Failed to seal carton', 'error');
                await this.selectOrder(this.selectedOrder.id);
                this.notify('Carton sealed');
            },

            async completePackingOrder() {
                const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/complete`, {
                    method: 'POST',
                    headers: headers()
                });
                const data = await res.json();
                if (!res.ok || !data.success) return this.notify(data.message || 'Failed to complete packing order', 'error');
                await this.selectOrder(this.selectedOrder.id);
                await this.loadPackingOrders();
                this.notify('Packing order completed');
            },

            notify(message, type = 'success') {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message,
                        type
                    }
                }));
            },

            confirm(title, message, onConfirm, confirmText = 'Confirm', confirmColor = 'red') {
                window.dispatchEvent(new CustomEvent('open-confirm', {
                    detail: {
                        title,
                        message,
                        onConfirm,
                        confirmText,
                        confirmColor
                    }
                }));
            }
        }
    }
</script>
@endsection