@extends('layouts.procurement')

@section('title', 'Compare Quotations')
@section('page-title', 'Compare Quotations')

@section('content')
<div x-data="compareQuotationsData()" x-init="init()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Quotation Comparison</h2>
                <p class="text-gray-600 mt-1">PR Number: <span class="font-semibold text-primary" x-text="prNumber"></span></p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Save Selections - always show if selections exist and PO not created -->
                <button @click="saveSelections()"
                        x-show="!poExists && Object.keys(itemSelections).length > 0"
                        :disabled="saving"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span x-show="!saving">Save Selections</span>
                    <span x-show="saving">Saving...</span>
                </button>
                <!-- Create POs - show after selections are saved -->
                <button @click="showCreatePOModal = true"
                        x-show="selectionsFinalized && !poExists && Object.keys(itemSelections).length > 0"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                    Create Purchase Orders
                </button>
                <a href="{{ url("/org/{$organization->org_slug}/procurement/quotation-comparison") }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>Back
                </a>
            </div>
        </div>
    </div>

    <!-- Selection Summary Banner -->
    <div x-show="selectionsFinalized && !loading && !poExists" class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
            <div>
                <p class="font-semibold text-green-800">Vendor selections saved</p>
                <p class="text-sm text-green-700">
                    <span x-text="Object.keys(itemSelections).length"></span> item(s) assigned to
                    <span x-text="getUniqueVendorCount()"></span> vendor(s).
                    You can still modify selections or proceed to create Purchase Orders.
                </p>
            </div>
        </div>
    </div>

    <!-- Vendor Summary Cards -->
    <div x-show="!loading && comparison.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Best Price Vendor</p>
            <p class="text-lg font-bold text-green-600" x-text="getBestPriceVendor()"></p>
            <p class="text-sm text-gray-600 mt-1">Total: <span class="font-semibold" x-text="'₹' + getLowestTotalPrice().toFixed(2)"></span></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Fastest Delivery</p>
            <p class="text-lg font-bold text-blue-600" x-text="getFastestDeliveryVendor()"></p>
            <p class="text-sm text-gray-600 mt-1">Date: <span class="font-semibold" x-text="getEarliestDeliveryDate()"></span></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Items Selected</p>
            <p class="text-lg font-bold text-primary" x-text="Object.keys(itemSelections).length + ' / ' + comparison.length"></p>
            <p class="text-xs text-gray-500 mt-1">Across <span x-text="getUniqueVendorCount()"></span> vendor(s)</p>
        </div>
    </div>

    <!-- Comparison Table (per item) -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-12 text-center text-gray-400">Loading comparison...</div>
        </template>
        <template x-if="!loading && comparison.length === 0">
            <div class="p-12 text-center text-gray-400">No quotations found for this PR</div>
        </template>

        <template x-if="!loading && comparison.length > 0">
            <div>
                <!-- Table header hint -->
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-600">Select the best vendor for each item. You can split items across multiple vendors.</p>
                    <button @click="autoSelectBestPrice()" class="text-xs px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                        Auto-select Best Price
                    </button>
                </div>

                <template x-for="(item, itemIndex) in comparison" :key="itemIndex">
                    <div class="border-b border-gray-200 last:border-b-0">
                        <!-- Item Header -->
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center font-bold" x-text="itemIndex + 1"></span>
                                <h3 class="font-semibold text-gray-900" x-text="item.item_name"></h3>
                                <span class="text-xs text-gray-500" x-text="item.quotations.length + ' vendor(s)'"></span>
                            </div>
                            <!-- Selected vendor badge for this item -->
                            <template x-if="itemSelections[item.item_name]">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Selected:</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">check_circle</span>
                                        <span x-text="getSelectedVendorName(item.item_name)"></span>
                                    </span>
                                    <!-- Only allow deletion if PO not created yet -->
                                    <button x-show="!poExists" @click="delete itemSelections[item.item_name]; delete itemSelectionDetails[item.item_name]" class="text-gray-400 hover:text-red-500">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Vendor rows for this item -->
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Delivery</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Remarks</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Select</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="quote in item.quotations" :key="quote.id">
                                    <tr class="hover:bg-gray-50 transition-colors"
                                        :class="{
                                            'bg-green-50 border-l-4 border-green-500': itemSelections[item.item_name] === quote.id
                                        }">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-medium text-gray-900" x-text="quote.vendor_name"></span>
                                                <template x-if="getBestPrice(item.quotations) === parseFloat(quote.total_price)">
                                                    <span class="px-1.5 py-0.5 bg-green-600 text-white rounded text-xs font-bold">BEST PRICE</span>
                                                </template>
                                                <template x-if="getEarliestDelivery(item.quotations) === quote.delivery_date && quote.delivery_date">
                                                    <span class="px-1.5 py-0.5 bg-blue-600 text-white rounded text-xs font-bold">FASTEST</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-700" x-text="quote.quantity"></td>
                                        <td class="px-4 py-3 text-right text-gray-700" x-text="'₹' + parseFloat(quote.unit_price).toFixed(2)"></td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="'₹' + parseFloat(quote.total_price).toFixed(2)"></td>
                                        <td class="px-4 py-3 text-gray-700 text-xs" x-text="quote.delivery_date || '—'"></td>
                                        <td class="px-4 py-3 text-gray-500 text-xs" x-text="quote.remarks || '—'"></td>
                                        <td class="px-4 py-3 text-center">
                                            <!-- Always show select button unless PO already exists -->
                                            <template x-if="!poExists">
                                                <button @click="selectItem(item.item_name, quote)"
                                                        :class="itemSelections[item.item_name] === quote.id
                                                            ? 'bg-green-600 text-white'
                                                            : 'bg-gray-100 text-gray-700 hover:bg-primary hover:text-white'"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                                    <span x-text="itemSelections[item.item_name] === quote.id ? '✓ Selected' : 'Select'"></span>
                                                </button>
                                            </template>
                                            <!-- Only lock after PO is created -->
                                            <template x-if="poExists">
                                                <span x-show="itemSelections[item.item_name] === quote.id"
                                                      class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">✓ Selected</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- Footer: selection summary -->
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span x-text="Object.keys(itemSelections).length"></span> of
                            <span x-text="comparison.length"></span> items selected
                            <template x-if="getUniqueVendorCount() > 0">
                                <span> &mdash; will create <strong x-text="getUniqueVendorCount()"></strong> PO(s)</span>
                            </template>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Save button - always available if PO not created -->
                            <template x-if="!poExists">
                                <button @click="saveSelections()"
                                        :disabled="saving || Object.keys(itemSelections).length === 0"
                                        class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 text-sm font-medium">
                                    <span x-show="!saving">Save Selections</span>
                                    <span x-show="saving">Saving...</span>
                                </button>
                            </template>
                            <!-- Create PO button - show after selections saved -->
                            <template x-if="selectionsFinalized && !poExists">
                                <button @click="showCreatePOModal = true"
                                        class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                                    Create Purchase Orders
                                </button>
                            </template>
                            <template x-if="poExists">
                                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-semibold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    PO Already Created
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Create PO Confirmation Modal -->
    <div x-show="showCreatePOModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showCreatePOModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Create Purchase Orders</h2>
                <p class="text-sm text-gray-500 mt-1">The following POs will be created from your selections:</p>
            </div>
            <div class="p-6 space-y-3 max-h-80 overflow-y-auto">
                <template x-for="(items, vendorName) in getVendorItemGroups()" :key="vendorName">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-gray-900" x-text="vendorName"></p>
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full" x-text="items.length + ' item(s)'"></span>
                        </div>
                        <ul class="space-y-1">
                            <template x-for="item in items" :key="item.item_name">
                                <li class="text-sm text-gray-600 flex justify-between">
                                    <span x-text="item.item_name"></span>
                                    <span class="font-medium" x-text="'₹' + parseFloat(item.total_price).toFixed(2)"></span>
                                </li>
                            </template>
                        </ul>
                        <div class="mt-2 pt-2 border-t border-gray-100 flex justify-between text-sm font-semibold">
                            <span>Total</span>
                            <span x-text="'₹' + items.reduce((s, i) => s + parseFloat(i.total_price), 0).toFixed(2)"></span>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showCreatePOModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-white transition-colors">
                    Cancel
                </button>
                <button @click="createPOs()" :disabled="creatingPOs"
                        class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 font-medium">
                    <span x-show="!creatingPOs">Confirm & Create POs</span>
                    <span x-show="creatingPOs">Creating...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-cloak
         class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function compareQuotationsData() {
    return {
        prNumber: '{{ $prNumber ?? "" }}',
        comparison: [],
        loading: false,
        saving: false,
        creatingPOs: false,
        showCreatePOModal: false,
        poExists: false,
        // { item_name: quotation_id }
        itemSelections: {},
        // { item_name: { vendor_name, total_price, ... } }
        itemSelectionDetails: {},
        selectionsFinalized: false,
        toast: { show: false, message: '', type: 'success' },

        async init() {
            if (!this.prNumber) { this.showToast('PR Number is required', 'error'); return; }
            await this.loadComparison();
            await this.loadExistingSelections();
        },

        async loadComparison() {
            this.loading = true;
            try {
                const token = localStorage.getItem('access_token');
                const res = await fetch(`/api/v1/quotation-comparison/${this.prNumber}`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.comparison = data.data.comparison;
                    this.poExists = data.data.po_exists ?? false;
                }
            } catch (e) {
                this.showToast('Failed to load comparison', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadExistingSelections() {
            try {
                const token = localStorage.getItem('access_token');
                const res = await fetch(`/api/v1/quotation-comparison/item-selections/${this.prNumber}`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.data.selections.length > 0) {
                    data.data.selections.forEach(s => {
                        this.itemSelections[s.item_name] = s.quotation_id;
                        // Find full quote details from comparison
                        const item = this.comparison.find(c => c.item_name === s.item_name);
                        if (item) {
                            const quote = item.quotations.find(q => q.id === s.quotation_id);
                            if (quote) this.itemSelectionDetails[s.item_name] = quote;
                        }
                    });
                    this.selectionsFinalized = true;
                }
            } catch (e) {
                // no existing selections, that's fine
            }
        },

        selectItem(itemName, quote) {
            // Allow selection changes until PO is created
            if (this.poExists) return;
            this.itemSelections[itemName] = quote.id;
            this.itemSelectionDetails[itemName] = quote;
            // Trigger Alpine reactivity
            this.itemSelections = { ...this.itemSelections };
            this.itemSelectionDetails = { ...this.itemSelectionDetails };
        },

        getSelectedVendorName(itemName) {
            return this.itemSelectionDetails[itemName]?.vendor_name ?? '';
        },

        autoSelectBestPrice() {
            this.comparison.forEach(item => {
                const best = item.quotations.reduce((min, q) =>
                    parseFloat(q.total_price) < parseFloat(min.total_price) ? q : min
                );
                this.itemSelections[item.item_name] = best.id;
                this.itemSelectionDetails[item.item_name] = best;
            });
            this.itemSelections = { ...this.itemSelections };
            this.itemSelectionDetails = { ...this.itemSelectionDetails };
        },

        async saveSelections() {
            if (Object.keys(this.itemSelections).length === 0) {
                this.showToast('Please select a vendor for at least one item', 'error');
                return;
            }
            this.saving = true;
            try {
                const token = localStorage.getItem('access_token');
                const selections = Object.entries(this.itemSelections).map(([item_name, quotation_id]) => ({
                    item_name,
                    quotation_id
                }));
                const res = await fetch('/api/v1/quotation-comparison/select-items', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ pr_number: this.prNumber, selections })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectionsFinalized = true;
                    this.showToast('Selections saved successfully', 'success');
                } else {
                    this.showToast(data.message || 'Failed to save selections', 'error');
                }
            } catch (e) {
                this.showToast('Failed to save selections', 'error');
            } finally {
                this.saving = false;
            }
        },

        async createPOs() {
            this.creatingPOs = true;
            try {
                const token = localStorage.getItem('access_token');
                const res = await fetch('/api/v1/quotation-comparison/create-pos', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ pr_number: this.prNumber })
                });
                const data = await res.json();
                if (data.success) {
                    const pos = data.data.purchase_orders;
                    this.showCreatePOModal = false;
                    this.showToast(`${pos.length} PO(s) created: ${pos.map(p => p.po_number).join(', ')}`, 'success');
                    setTimeout(() => {
                        window.location.href = '{{ url("/org/{$organization->org_slug}/procurement/purchase-orders") }}';
                    }, 2500);
                } else if (data.error?.code === 'PO_ALREADY_EXISTS') {
                    this.showCreatePOModal = false;
                    this.showToast(data.message, 'error');
                } else {
                    this.showToast(data.message || 'Failed to create POs', 'error');
                }
            } catch (e) {
                this.showToast('Failed to create POs', 'error');
            } finally {
                this.creatingPOs = false;
            }
        },

        getUniqueVendorCount() {
            const vendors = new Set(Object.values(this.itemSelectionDetails).map(d => d?.vendor_name).filter(Boolean));
            return vendors.size;
        },

        // Returns { vendorName: [{ item_name, total_price, ... }] }
        getVendorItemGroups() {
            const groups = {};
            Object.entries(this.itemSelectionDetails).forEach(([itemName, quote]) => {
                if (!quote) return;
                if (!groups[quote.vendor_name]) groups[quote.vendor_name] = [];
                groups[quote.vendor_name].push({ item_name: itemName, ...quote });
            });
            return groups;
        },

        getBestPrice(quotations) {
            return Math.min(...quotations.map(q => parseFloat(q.total_price)));
        },

        getEarliestDelivery(quotations) {
            const dates = quotations.filter(q => q.delivery_date).map(q => q.delivery_date);
            return dates.length ? dates.sort()[0] : null;
        },

        getLowestTotalPrice() {
            const totals = {};
            this.comparison.forEach(item => {
                item.quotations.forEach(q => {
                    totals[q.vendor_name] = (totals[q.vendor_name] || 0) + parseFloat(q.total_price);
                });
            });
            return totals ? Math.min(...Object.values(totals)) : 0;
        },

        getBestPriceVendor() {
            const totals = {};
            this.comparison.forEach(item => {
                item.quotations.forEach(q => {
                    totals[q.vendor_name] = (totals[q.vendor_name] || 0) + parseFloat(q.total_price);
                });
            });
            return Object.entries(totals).sort((a, b) => a[1] - b[1])[0]?.[0] ?? '—';
        },

        getEarliestDeliveryDate() {
            const dates = [];
            this.comparison.forEach(item => item.quotations.forEach(q => { if (q.delivery_date) dates.push(q.delivery_date); }));
            return dates.length ? dates.sort()[0] : '—';
        },

        getFastestDeliveryVendor() {
            const earliest = this.getEarliestDeliveryDate();
            if (earliest === '—') return '—';
            for (const item of this.comparison) {
                for (const q of item.quotations) {
                    if (q.delivery_date === earliest) return q.vendor_name;
                }
            }
            return '—';
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        }
    }
}
</script>
@endsection
