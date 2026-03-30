@extends('layouts.production')

@section('title', 'Packing Orders - ' . $organization->org_name)
@section('page-title', 'Packing Orders')

@section('content')
<div x-data="packingOrders('{{ $organization->org_slug }}')" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Packing Workspace</h2>
            <p class="text-sm text-gray-500">Create packing orders for completed FG, open cartons, scan units, and seal cartons.</p>
        </div>
        <button @click="createPackingOrder()" :disabled="creatingOrder || !newPackingOrder.production_order_id"
                class="px-4 py-2 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600 disabled:opacity-50">
            Create Packing Order
        </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[360px_1fr] gap-6">
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900 mb-3">New Packing Order</h3>
                <select x-model="newPackingOrder.production_order_id"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option value="">Select completed production order</option>
                    <template x-for="order in completedOrders" :key="order.id">
                        <option :value="order.id" x-text="order.order_no + ' • ' + order.product_name"></option>
                    </template>
                </select>
                <p class="text-xs text-gray-500 mt-2">Only completed orders are shown. FG QC must be decided before packing if QC was required.</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Packing Orders</h3>
                    <button @click="loadPackingOrders()" class="text-sm text-orange-600 font-semibold">Refresh</button>
                </div>
                <div class="divide-y divide-gray-100 max-h-[70vh] overflow-y-auto">
                    <template x-if="packingOrders.length === 0">
                        <div class="p-6 text-sm text-gray-400 text-center">No packing orders yet.</div>
                    </template>
                    <template x-for="order in packingOrders" :key="order.id">
                        <button @click="selectOrder(order.id)"
                                class="w-full text-left px-4 py-4 hover:bg-orange-50 transition"
                                :class="selectedOrder?.id === order.id ? 'bg-orange-50' : ''">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900" x-text="order.packing_order_no"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="order.production_order?.order_no + ' • ' + (order.production_order?.product?.product_name || '')"></p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-[11px] font-bold"
                                      :class="order.status === 'COMPLETED' ? 'bg-green-100 text-green-700' : (order.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')"
                                      x-text="order.status"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <template x-if="selectedOrder">
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900" x-text="selectedOrder.packing_order_no"></h3>
                                <p class="text-sm text-gray-500 mt-1" x-text="selectedOrder.production_order?.order_no + ' • ' + (selectedOrder.production_order?.product?.product_name || '')"></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                      :class="selectedOrder.status === 'COMPLETED' ? 'bg-green-100 text-green-700' : (selectedOrder.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')"
                                      x-text="selectedOrder.status"></span>
                                <button @click="completePackingOrder()" :disabled="selectedOrder.status === 'COMPLETED'"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
                                    Complete
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">FG Batch</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.production_order?.fg_batch_number || '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Target Qty</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.production_order?.actual_qty || '—'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Cartons</p>
                                <p class="font-semibold text-gray-900" x-text="selectedOrder.cartons?.length || 0"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Packed Qty</p>
                                <p class="font-semibold text-gray-900" x-text="packedQty(selectedOrder)"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900">Cartons</h4>
                                <button @click="createCarton()" class="text-sm text-orange-600 font-semibold">Open Carton</button>
                            </div>
                            <div class="space-y-3 max-h-[28rem] overflow-y-auto">
                                <template x-if="!selectedOrder.cartons || selectedOrder.cartons.length === 0">
                                    <div class="text-sm text-gray-400">No cartons yet.</div>
                                </template>
                                <template x-for="carton in selectedOrder.cartons" :key="carton.id">
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
                                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                                            Seal Carton
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                        <input type="text" x-model="scanForm.product_barcode"
                                               placeholder="Scan / enter FG barcode"
                                               class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                        <input type="number" min="0.001" step="0.001" x-model="scanForm.qty"
                                               class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                        <button @click="scanIntoCarton()" :disabled="selectedCarton.status !== 'OPEN'"
                                                class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50">
                                            Scan Into Carton
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
                                                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No items scanned yet.</td></tr>
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
                    Select a packing order to manage cartons and scans.
                </div>
            </template>
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
        creatingOrder: false,
        newPackingOrder: { production_order_id: '' },
        scanForm: { product_barcode: '', qty: 1 },

        async init() {
            await Promise.all([this.loadCompletedOrders(), this.loadPackingOrders()]);
        },

        get selectedCarton() {
            return this.selectedOrder?.cartons?.find(c => c.id === this.selectedCartonId) || null;
        },

        packedQty(order) {
            return (order.cartons || []).reduce((sum, carton) => {
                return sum + (carton.items || []).reduce((inner, item) => inner + parseFloat(item.qty || 0), 0);
            }, 0).toFixed(3);
        },

        async loadCompletedOrders() {
            const res = await fetch('/api/v1/production-orders?status=COMPLETED', { headers: headers() });
            const data = await res.json();
            this.completedOrders = data.data?.orders || [];
        },

        async loadPackingOrders() {
            const res = await fetch('/api/v1/packing-orders', { headers: headers() });
            const data = await res.json();
            this.packingOrders = data.data?.packing_orders || [];
        },

        async selectOrder(id) {
            const res = await fetch(`/api/v1/packing-orders/${id}`, { headers: headers() });
            const data = await res.json();
            this.selectedOrder = data.data?.packing_order || null;
            this.selectedCartonId = this.selectedOrder?.cartons?.[0]?.id || null;
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
            } catch (error) {
                alert(error.message);
            } finally {
                this.creatingOrder = false;
            }
        },

        async createCarton() {
            const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ carton_type: 'OUTER' })
            });
            const data = await res.json();
            if (!res.ok || !data.success) return alert(data.message || 'Failed to create carton');
            await this.selectOrder(this.selectedOrder.id);
            this.selectedCartonId = data.data.carton.id;
        },

        async scanIntoCarton() {
            const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons/${this.selectedCartonId}/scan`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(this.scanForm)
            });
            const data = await res.json();
            if (!res.ok || !data.success) return alert(data.message || 'Failed to scan FG into carton');
            this.scanForm = { product_barcode: '', qty: 1 };
            await this.selectOrder(this.selectedOrder.id);
        },

        async sealCarton() {
            const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/cartons/${this.selectedCartonId}/seal`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ labelled: true })
            });
            const data = await res.json();
            if (!res.ok || !data.success) return alert(data.message || 'Failed to seal carton');
            await this.selectOrder(this.selectedOrder.id);
        },

        async completePackingOrder() {
            const res = await fetch(`/api/v1/packing-orders/${this.selectedOrder.id}/complete`, {
                method: 'POST',
                headers: headers()
            });
            const data = await res.json();
            if (!res.ok || !data.success) return alert(data.message || 'Failed to complete packing order');
            await this.selectOrder(this.selectedOrder.id);
            await this.loadPackingOrders();
        }
    }
}
</script>
@endsection
