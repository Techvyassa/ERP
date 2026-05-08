@extends('layouts.security')

@section('title', 'Dispatch — ' . $organization->org_name)
@section('page-title', 'Dispatch')

@section('content')
<!-- JsBarcode for client-side barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

{{-- dispatchApp must be defined BEFORE Alpine processes x-data --}}
<script>
(function () {
    const factory = () => ({
        tab: 'packed',
        loading: false,
        packed: [],
        dispatched: [],
        stats: { packed: 0, dispatched_today: 0 },

        // Dispatch modal
        showModal: false,
        dispatchSO: null,
        packingLoading: false,
        checkedBoxes: {},
        form: { vehicle_number: '', driver_name: '', logistics_partner: '', expected_delivery_date: '' },
        error: '',
        submitting: false,

        // Labels modal
        showLabels: false,
        labelSO: null,
        labels: [],
        labelsLoading: false,

        token()   { return localStorage.getItem('access_token') || localStorage.getItem('auth_token') || ''; },
        headers() { return { 'Authorization': 'Bearer ' + this.token(), 'Accept': 'application/json', 'Content-Type': 'application/json' }; },

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            try {
                const h = this.headers();
                const [pkRes, dRes] = await Promise.all([
                    fetch('/api/v1/security/outward/packed',     { headers: h }),
                    fetch('/api/v1/security/outward/dispatched', { headers: h }),
                ]);
                const [pkJson, dJson] = await Promise.all([pkRes.json(), dRes.json()]);
                this.packed     = pkJson.success ? (pkJson.data ?? []) : [];
                this.dispatched = dJson.success  ? (dJson.data  ?? []) : [];
                const today = new Date().toDateString();
                this.stats = {
                    packed: this.packed.length,
                    dispatched_today: this.dispatched.filter(o => o.dispatched_at && new Date(o.dispatched_at).toDateString() === today).length,
                };
            } catch(e) { console.error('Dispatch load error', e); }
            this.loading = false;
        },

        async openDispatch(so) {
            this.dispatchSO    = { ...so, packing_data: null };
            this.packingLoading = true;
            this.checkedBoxes  = {};
            this.form = {
                vehicle_number: '',
                driver_name: '',
                logistics_partner: '',
                expected_delivery_date: so.required_delivery_date ? so.required_delivery_date.split('T')[0] : ''
            };
            this.error     = '';
            this.submitting = false;
            this.showModal  = true;

            try {
                const res  = await fetch('/api/v1/security/outward/' + so.id, { headers: this.headers() });
                const json = await res.json();
                if (json.success) {
                    this.dispatchSO = json.data;
                    this.checkedBoxes = {};
                    (json.data.packing_data ?? []).forEach((_, i) => { this.checkedBoxes[i] = false; });
                } else {
                    this.dispatchSO = so;
                }
            } catch(e) {
                console.error('SO detail fetch error', e);
                this.dispatchSO = so;
            }
            this.packingLoading = false;
        },

        allBoxesChecked() {
            if (!this.dispatchSO) return true;
            const data = this.dispatchSO.packing_data ?? [];
            if (data.length === 0) return true;
            return data.every((_, i) => !!this.checkedBoxes[i]);
        },

        toggleAllBoxes() {
            if (!this.dispatchSO) return;
            const data       = this.dispatchSO.packing_data ?? [];
            const allChecked = this.allBoxesChecked();
            const updated    = {};
            data.forEach((_, i) => { updated[i] = !allChecked; });
            this.checkedBoxes = updated;
        },

        async submitDispatch() {
            this.error = '';
            if (!this.form.vehicle_number || !this.form.driver_name || !this.form.expected_delivery_date) {
                this.error = 'Vehicle number, driver name and delivery date are required.'; return;
            }
            if (!this.allBoxesChecked()) {
                this.error = 'Please verify all boxes are checked before dispatching.'; return;
            }
            this.submitting = true;
            try {
                const res  = await fetch('/api/v1/security/outward/' + this.dispatchSO.id + '/dispatch', {
                    method: 'PATCH', headers: this.headers(), body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.success) {
                    this.showModal = false;
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
            this.labelSO       = so;
            this.labels        = [];
            this.labelsLoading = true;
            this.showLabels    = true;

            try {
                const res      = await fetch('/api/v1/security/outward/' + so.id, { headers: this.headers() });
                const json     = await res.json();
                const soDetail = json.success ? json.data : so;

                const dispatchDate = (soDetail.dispatched_at || so.dispatched_at)
                    ? new Date(soDetail.dispatched_at || so.dispatched_at)
                        .toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})
                    : new Date().toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});

                const packingData = soDetail.packing_data ?? [];
                const soLines     = soDetail.line_items ?? soDetail.lineItems ?? [];

                const sourceLines = packingData.length > 0
                    ? packingData.map(bl => ({
                        product_name: bl.item_name  ?? '—',
                        product_code: bl.item_code  ?? '',
                        qty:          parseFloat(bl.qty ?? 0),
                        uom_code:     '',
                        box_no:       bl.box_no ?? '',
                    }))
                    : soLines.map(li => ({
                        product_name: li.product?.product_name ?? '—',
                        product_code: li.product?.product_code ?? '',
                        qty:          parseFloat(li.qty ?? 0),
                        uom_code:     li.uom?.uom_code ?? '',
                        box_no:       '',
                    }));

                const soNum = soDetail.so_number ?? so.so_number ?? 'SO';

                this.labels = sourceLines.map((line, idx) => {
                    const safeBox  = String(line.box_no  || '').replace(/[^A-Za-z0-9]/g, '');
                    const safeProd = String(line.product_code || '').replace(/[^A-Za-z0-9\-]/g, '');
                    const barcodeRaw = soNum
                        + (safeBox  ? '-B' + safeBox  : '-L' + String(idx + 1).padStart(2, '0'))
                        + (safeProd ? '-'  + safeProd : '');

                    return {
                        so_number:     soNum,
                        product_name:  line.product_name,
                        product_code:  line.product_code,
                        qty:           line.qty.toFixed(3),
                        uom:           line.uom_code,
                        box_no:        line.box_no,
                        customer:      soDetail.customer?.customer_name ?? so.customer?.customer_name ?? '—',
                        vehicle:       soDetail.vehicle_number ?? so.vehicle_number ?? '—',
                        driver:        soDetail.driver_name    ?? so.driver_name    ?? '—',
                        dispatch_date: dispatchDate,
                        barcode_value: barcodeRaw,
                    };
                });
            } catch(e) {
                console.error('Label load error', e);
            }

            this.labelsLoading = false;
            // x-init on each SVG element handles barcode rendering automatically
        },

        printLabels() { window.print(); },

        isOverdue(date) {
            return date && new Date(date) < new Date(new Date().toDateString());
        },
    });

    window.dispatchApp = factory;

    const register = () => {
        if (window.Alpine) {
            window.Alpine.data('dispatchApp', factory);
        }
    };

    document.addEventListener('alpine:init', register);
    register();
})();
</script>

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
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Confirm Dispatch</h3>
                    <p class="text-xs text-gray-500 mt-0.5"
                       x-text="dispatchSO?.so_number + ' · ' + (dispatchSO?.customer?.customer_name ?? '')"></p>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">

                <!-- Packing loading indicator -->
                <div x-show="packingLoading" class="flex items-center gap-2 text-sm text-gray-400 py-1">
                    <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                    Loading packing details…
                </div>

                <!-- Packing Summary with verification checkboxes -->
                <div x-show="!packingLoading && dispatchSO?.packing_data?.length > 0">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Verify Packed Boxes</p>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold"
                                  :class="allBoxesChecked() ? 'text-teal-600' : 'text-amber-600'"
                                  x-text="Object.values(checkedBoxes).filter(Boolean).length + ' / ' + (dispatchSO?.packing_data?.length ?? 0) + ' verified'">
                            </span>
                            <button @click="toggleAllBoxes()"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold underline"
                                    x-text="allBoxesChecked() ? 'Uncheck All' : 'Check All'">
                            </button>
                        </div>
                    </div>
                    <div class="rounded-lg border border-indigo-100 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-indigo-50 text-indigo-700 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-center w-8">
                                        <input type="checkbox"
                                               :checked="allBoxesChecked()"
                                               @change="toggleAllBoxes()"
                                               class="w-3.5 h-3.5 rounded accent-indigo-600 cursor-pointer" />
                                    </th>
                                    <th class="px-3 py-2 text-left">Box</th>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(bl, i) in (dispatchSO?.packing_data ?? [])" :key="i">
                                    <tr class="cursor-pointer transition-colors"
                                        :class="checkedBoxes[i] ? 'bg-teal-50 hover:bg-teal-100' : 'hover:bg-gray-50'"
                                        @click="checkedBoxes[i] = !checkedBoxes[i]; checkedBoxes = {...checkedBoxes}">
                                        <td class="px-3 py-2 text-center" @click.stop>
                                            <input type="checkbox"
                                                   :checked="checkedBoxes[i]"
                                                   @change="checkedBoxes[i] = $event.target.checked; checkedBoxes = {...checkedBoxes}"
                                                   class="w-3.5 h-3.5 rounded accent-indigo-600 cursor-pointer" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="font-semibold"
                                                  :class="checkedBoxes[i] ? 'text-teal-700' : 'text-indigo-700'"
                                                  x-text="bl.box_no"></span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-700">
                                            <div x-text="bl.item_name"></div>
                                            <div class="text-gray-400 font-mono" x-text="bl.item_code"></div>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800" x-text="bl.qty"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <!-- Not all checked warning -->
                    <p x-show="!allBoxesChecked()"
                       class="mt-1.5 text-xs text-amber-600 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">info</span>
                        Check each box after physical verification to enable dispatch.
                    </p>
                </div>
                <div x-show="!packingLoading && (!dispatchSO?.packing_data || dispatchSO.packing_data.length === 0)"
                     class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">warning</span>
                    No packing data recorded for this order.
                </div>

                <!-- Dispatch Form -->
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
            </div>
            <!-- Footer -->
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 flex-shrink-0">
                <button @click="showModal = false"
                    class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button @click="submitDispatch()"
                        :disabled="submitting || !allBoxesChecked()"
                        class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-40 font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">local_shipping</span>
                    <span x-text="submitting ? 'Dispatching...' : 'Confirm Dispatch'"></span>
                </button>
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
                                <p class="text-xs text-gray-400 font-mono" x-text="label.product_code"></p>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                    <span class="text-xs bg-teal-50 text-teal-700 px-2 py-0.5 rounded font-semibold"
                                          x-text="'Qty: ' + label.qty + (label.uom ? ' ' + label.uom : '')"></span>
                                    <span x-show="label.box_no"
                                          class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-semibold"
                                          x-text="'Box: ' + label.box_no"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-text="'To: ' + label.customer"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="'Driver: ' + label.driver"></p>
                            </div>
                            <!-- Barcode: x-init fires after Alpine inserts this element into DOM -->
                            <div class="flex flex-col items-center mt-2">
                                <svg x-init="$nextTick(() => {
                                        try {
                                            JsBarcode($el, label.barcode_value, {
                                                format: 'CODE128', width: 1.8, height: 50,
                                                displayValue: false, margin: 4
                                            });
                                        } catch(e) { console.warn('Barcode err', label.barcode_value, e.message); }
                                     })"
                                     class="barcode-svg max-w-full"></svg>
                                <p class="text-center text-xs text-gray-400 mt-1 font-mono" x-text="label.barcode_value"></p>
                            </div>
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
@endsection
