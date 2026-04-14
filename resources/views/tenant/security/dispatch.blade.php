@extends('layouts.security')

@section('title', 'Dispatch — ' . $organization->org_name)
@section('page-title', 'Dispatch')

@section('content')
<!-- JsBarcode for client-side barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<div x-data="dispatchApp()" x-init="init()">

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-xl">inventory_2</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Pending</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.packed">0</p>
            <p class="text-sm text-gray-500 mt-1">Packed — Awaiting Dispatch</p>
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

    <!-- Tabs -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 border-b border-gray-200">
            <nav class="flex gap-6 -mb-px">
                <button @click="tab = 'packed'"
                    :class="tab === 'packed' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">inventory_2</span>
                    Ready to Dispatch
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
                <span class="material-symbols-outlined animate-spin text-3xl text-indigo-500">progress_activity</span>
            </div>

            <div x-show="!loading">
                <!-- Ready to Dispatch (PACKED) -->
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
                                        <th class="px-4 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in packed" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-indigo-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3">
                                                <span :class="isOverdue(so.required_delivery_date) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                                      x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">PACKED</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button @click="openDispatch(so)"
                                                    class="text-xs bg-teal-600 text-white hover:bg-teal-700 px-3 py-1.5 rounded font-semibold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                                                    Dispatch
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Dispatched History -->
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
                                        <th class="px-4 py-3 text-left">Vehicle</th>
                                        <th class="px-4 py-3 text-left">Driver</th>
                                        <th class="px-4 py-3 text-center">Labels</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in dispatched" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-indigo-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3 text-gray-600"
                                                x-text="so.dispatched_at ? new Date(so.dispatched_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true}) : '—'"></td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-gray-600 text-xs" x-text="so.vehicle_number ?? '—'"></td>
                                            <td class="px-4 py-3 text-gray-600 text-xs" x-text="so.driver_name ?? '—'"></td>
                                            <td class="px-4 py-3 text-center">
                                                <button @click="viewLabels(so)"
                                                    class="text-xs bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-2.5 py-1 rounded font-semibold flex items-center gap-1 mx-auto">
                                                    <span class="material-symbols-outlined text-sm">barcode</span> View Labels
                                                </button>
                                            </td>
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

    <!-- Dispatch Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Confirm Dispatch</h3>
                    <p class="text-xs text-gray-500 mt-0.5"
                       x-text="dispatchSO?.so_number + ' · ' + (dispatchSO?.customer?.customer_name ?? '')"></p>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Vehicle Number <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.vehicle_number" placeholder="GJ-01-XX-1234"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Driver Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.driver_name" placeholder="Driver name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Logistics Partner</label>
                        <input type="text" x-model="form.logistics_partner" placeholder="e.g. Delhivery"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Expected Delivery Date <span class="text-red-500">*</span></label>
                        <input type="date" x-model="form.expected_delivery_date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                </div>
                <div x-show="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2" x-text="error"></div>
                <div class="flex justify-end gap-3 pt-1">
                    <button @click="showModal = false"
                        class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button @click="submitDispatch()" :disabled="submitting"
                        class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-semibold flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">local_shipping</span>
                        <span x-text="submitting ? 'Dispatching...' : 'Confirm Dispatch'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barcode Labels Modal -->
    <div x-show="showLabels" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Dispatch Labels</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="labelSO?.so_number + ' · ' + (labelSO?.customer?.customer_name ?? '')"></p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="printLabels()"
                        class="flex items-center gap-1.5 text-sm bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-1.5 rounded-lg font-semibold">
                        <span class="material-symbols-outlined text-base">print</span> Print Labels
                    </button>
                    <button @click="showLabels = false" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto p-6" id="labels-print-area">
                <div x-show="labelsLoading" class="flex justify-center py-10">
                    <span class="material-symbols-outlined animate-spin text-3xl text-indigo-500">progress_activity</span>
                </div>
                <div x-show="!labelsLoading" class="grid grid-cols-2 gap-4" id="labels-grid">
                    <template x-for="(label, idx) in labels" :key="idx">
                        <div class="border-2 border-gray-300 rounded-lg p-4 bg-white label-card">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Dispatch Label</p>
                                    <p class="text-sm font-bold text-indigo-700" x-text="label.so_number"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500" x-text="label.dispatch_date"></p>
                                    <p class="text-xs font-semibold text-gray-700" x-text="label.vehicle"></p>
                                </div>
                            </div>
                            <!-- Product Info -->
                            <div class="mb-3">
                                <p class="text-sm font-bold text-gray-900" x-text="label.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="label.product_code"></p>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs bg-teal-50 text-teal-700 px-2 py-0.5 rounded font-semibold"
                                          x-text="'Qty: ' + label.qty + ' ' + label.uom"></span>
                                    <span class="text-xs text-gray-500" x-text="'To: ' + label.customer"></span>
                                </div>
                            </div>
                            <!-- Barcode -->
                            <div class="flex justify-center mt-2">
                                <svg :id="'barcode-' + idx" class="barcode-svg"></svg>
                            </div>
                            <p class="text-center text-xs text-gray-400 mt-1" x-text="label.barcode_value"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
@media print {
    body * { visibility: hidden; }
    #labels-print-area, #labels-print-area * { visibility: visible; }
    #labels-print-area { position: fixed; top: 0; left: 0; width: 100%; }
    .label-card { break-inside: avoid; page-break-inside: avoid; border: 2px solid #000 !important; }
}
</style>

<script>
function dispatchApp() {
    return {
        tab: 'packed',
        loading: false,
        packed: [],
        dispatched: [],
        stats: { packed: 0, dispatched_today: 0 },

        // Dispatch modal
        showModal: false,
        dispatchSO: null,
        form: { vehicle_number: '', driver_name: '', logistics_partner: '', expected_delivery_date: '' },
        error: '',
        submitting: false,

        // Labels modal
        showLabels: false,
        labelSO: null,
        labels: [],
        labelsLoading: false,

        token() { return localStorage.getItem('access_token') || localStorage.getItem('auth_token') || ''; },
        headers() { return { 'Authorization': 'Bearer ' + this.token(), 'Accept': 'application/json', 'Content-Type': 'application/json' }; },

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            try {
                const h = this.headers();
                const [pkRes, dRes] = await Promise.all([
                    fetch('/api/v1/sales-orders?per_page=100&status=PACKED', { headers: h }),
                    fetch('/api/v1/sales-orders?per_page=100&status=DISPATCHED', { headers: h }),
                ]);
                const [pkJson, dJson] = await Promise.all([pkRes.json(), dRes.json()]);
                this.packed     = pkJson.success ? (pkJson.data.data ?? pkJson.data ?? []) : [];
                this.dispatched = dJson.success  ? (dJson.data.data  ?? dJson.data  ?? []) : [];
                const today = new Date().toDateString();
                this.stats = {
                    packed: this.packed.length,
                    dispatched_today: this.dispatched.filter(o => o.dispatched_at && new Date(o.dispatched_at).toDateString() === today).length,
                };
            } catch(e) { console.error('Dispatch load error', e); }
            this.loading = false;
        },

        openDispatch(so) {
            this.dispatchSO = so;
            this.form = {
                vehicle_number: '',
                driver_name: '',
                logistics_partner: '',
                expected_delivery_date: so.required_delivery_date ? so.required_delivery_date.split('T')[0] : ''
            };
            this.error = '';
            this.submitting = false;
            this.showModal = true;
        },

        async submitDispatch() {
            this.error = '';
            if (!this.form.vehicle_number || !this.form.driver_name || !this.form.expected_delivery_date) {
                this.error = 'Vehicle number, driver name and delivery date are required.'; return;
            }
            this.submitting = true;
            try {
                const res = await fetch('/api/v1/sales-orders/' + this.dispatchSO.id + '/dispatch', {
                    method: 'PATCH', headers: this.headers(), body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.success) {
                    this.showModal = false;
                    // Generate labels immediately after dispatch
                    const dispatchedSO = { ...this.dispatchSO, ...this.form, dispatched_at: new Date().toISOString() };
                    await this.load();
                    this.tab = 'dispatched';
                    await this.viewLabels(dispatchedSO);
                } else {
                    this.error = json.message || 'Dispatch failed.';
                }
            } catch(e) { this.error = 'Network error. Please try again.'; }
            this.submitting = false;
        },

        async viewLabels(so) {
            this.labelSO = so;
            this.labels = [];
            this.labelsLoading = true;
            this.showLabels = true;

            try {
                // Fetch full SO details with line items
                const res = await fetch('/api/v1/sales-orders/' + so.id, { headers: this.headers() });
                const json = await res.json();
                const soDetail = json.success ? json.data : so;
                const lines = soDetail.line_items ?? soDetail.lineItems ?? [];
                const dispatchDate = so.dispatched_at
                    ? new Date(so.dispatched_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})
                    : new Date().toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});

                this.labels = lines.map((line, idx) => ({
                    so_number:    soDetail.so_number ?? so.so_number,
                    product_name: line.product?.product_name ?? line.item_name ?? 'Product',
                    product_code: line.product?.product_code ?? '',
                    qty:          parseFloat(line.qty).toFixed(3),
                    uom:          line.uom?.uom_code ?? '',
                    customer:     soDetail.customer?.customer_name ?? so.customer?.customer_name ?? '—',
                    vehicle:      so.vehicle_number ?? soDetail.vehicle_number ?? '—',
                    dispatch_date: dispatchDate,
                    // Barcode value: SO-LINEIDX-PRODUCTCODE
                    barcode_value: (soDetail.so_number ?? so.so_number) + '-L' + String(idx + 1).padStart(2, '0') + (line.product?.product_code ? '-' + line.product.product_code : ''),
                }));
            } catch(e) {
                console.error('Label load error', e);
            }

            this.labelsLoading = false;

            // Render barcodes after DOM update
            this.$nextTick(() => {
                this.labels.forEach((label, idx) => {
                    try {
                        JsBarcode('#barcode-' + idx, label.barcode_value, {
                            format: 'CODE128',
                            width: 1.8,
                            height: 50,
                            displayValue: false,
                            margin: 4,
                        });
                    } catch(e) { console.warn('Barcode render failed for idx ' + idx, e); }
                });
            });
        },

        printLabels() {
            window.print();
        },

        isOverdue(date) {
            return date && new Date(date) < new Date(new Date().toDateString());
        },
    }
}
</script>
@endsection
