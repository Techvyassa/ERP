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
            <a href="{{ url("/org/{$organization->org_slug}/procurement/quotation-comparison") }}"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span>Back to List
            </a>
        </div>
    </div>

    <!-- Best Vendor Recommendation Summary -->
    <div x-show="!loading && comparison.length > 0" class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl shadow-lg p-6 mb-6 border-2 border-green-200">
        <template x-if="isAlreadySelected">
            <div class="bg-green-600 text-white rounded-lg p-4 mb-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-3xl">check_circle</span>
                <div>
                    <p class="font-bold text-lg">Quotation Already Selected</p>
                    <p class="text-sm opacity-90">Selected Vendor: <span class="font-semibold" x-text="selectedVendorName"></span></p>
                </div>
            </div>
        </template>
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-green-600 text-2xl">verified</span>
                    <h3 class="text-xl font-bold text-gray-900">Recommended Vendor</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Best Price Vendor</p>
                        <p class="text-lg font-bold text-green-600" x-text="getBestPriceVendor()"></p>
                        <p class="text-sm text-gray-600 mt-1">Total: <span class="font-semibold" x-text="'₹' + getLowestTotalPrice().toFixed(2)"></span></p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Fastest Delivery</p>
                        <p class="text-lg font-bold text-blue-600" x-text="getFastestDeliveryVendor()"></p>
                        <p class="text-sm text-gray-600 mt-1">Date: <span class="font-semibold" x-text="getEarliestDeliveryDate()"></span></p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Overall Best</p>
                        <p class="text-lg font-bold text-primary" x-text="getOverallBestVendor()"></p>
                        <p class="text-xs text-gray-500 mt-1">Based on price & delivery</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <template x-if="loading">
            <div class="p-12 text-center text-gray-400">Loading comparison...</div>
        </template>

        <template x-if="!loading && comparison.length === 0">
            <div class="p-12 text-center text-gray-400">No quotations found for comparison</div>
        </template>

        <template x-if="!loading && comparison.length > 0">
            <div class="overflow-x-auto">
                <template x-for="(item, itemIndex) in comparison" :key="itemIndex">
                    <div class="border-b border-gray-200 last:border-b-0">
                        <!-- Item Header -->
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900" x-text="'Item: ' + item.item_name"></h3>
                                <span class="text-xs text-gray-500" x-text="item.quotations.length + ' vendor(s)'"></span>
                            </div>
                        </div>

                        <!-- Vendor Quotations Comparison -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Quantity</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Delivery Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Remarks</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(quote, quoteIndex) in item.quotations" :key="quote.id">
                                        <tr class="hover:bg-gray-50 transition-colors"
                                            :class="{
                                                'bg-green-100 border-l-4 border-green-500': getBestPrice(item.quotations) === quote.total_price,
                                                'bg-blue-50': getEarliestDelivery(item.quotations) === quote.delivery_date && quote.delivery_date && getBestPrice(item.quotations) !== quote.total_price
                                            }">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-gray-900" x-text="quote.vendor_name"></span>
                                                    <template x-if="getBestPrice(item.quotations) === quote.total_price">
                                                        <span class="px-2 py-0.5 bg-green-600 text-white rounded-full text-xs font-bold flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-xs">check_circle</span>
                                                            BEST PRICE
                                                        </span>
                                                    </template>
                                                    <template x-if="getEarliestDelivery(item.quotations) === quote.delivery_date && quote.delivery_date">
                                                        <span class="px-2 py-0.5 bg-blue-600 text-white rounded-full text-xs font-bold flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-xs">schedule</span>
                                                            FASTEST
                                                        </span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-700" x-text="quote.quantity"></td>
                                            <td class="px-4 py-3 text-right text-gray-700" x-text="'₹' + parseFloat(quote.unit_price).toFixed(2)"></td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="font-semibold text-gray-900" x-text="'₹' + parseFloat(quote.total_price).toFixed(2)"></span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700" x-text="quote.delivery_date || '—'"></td>
                                            <td class="px-4 py-3 text-gray-600 text-xs" x-text="quote.remarks || '—'"></td>
                                            <td class="px-6 py-4 text-center">
                                                <template x-if="!isAlreadySelected">
                                                    <button @click="selectQuotation(quote)" 
                                                            class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-xs">
                                                        Select
                                                    </button>
                                                </template>
                                                <template x-if="isAlreadySelected && quote.vendor_name === selectedVendorName">
                                                    <span class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                                                        ✓ Selected
                                                    </span>
                                                </template>
                                                <template x-if="isAlreadySelected && quote.vendor_name !== selectedVendorName">
                                                    <span class="px-3 py-1.5 bg-gray-100 text-gray-400 rounded-lg text-xs">
                                                        —
                                                    </span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Selection Modal -->
    <div x-show="showSelectionModal" x-cloak 
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showSelectionModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Confirm Quotation Selection</h2>
                <p class="text-sm text-gray-500 mt-1">Select this vendor's quotation?</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">Vendor</p>
                            <p class="font-semibold text-gray-900" x-text="selectedQuote?.vendor_name"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Total Price</p>
                            <p class="font-semibold text-gray-900" x-text="'₹' + parseFloat(selectedQuote?.total_price || 0).toFixed(2)"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Delivery Date</p>
                            <p class="font-semibold text-gray-900" x-text="selectedQuote?.delivery_date || '—'"></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Selection Reason (Optional)</label>
                    <textarea x-model="selectionReason" rows="3" placeholder="Why are you selecting this vendor?"
                              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"></textarea>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <span class="font-semibold">Note:</span> At least 2 vendor quotations are required for comparison. This selection will be recorded for audit purposes.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button @click="showSelectionModal = false" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-white transition-colors">
                    Cancel
                </button>
                <button @click="confirmSelection()" :disabled="selecting" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50">
                    <span x-show="!selecting">Confirm Selection</span>
                    <span x-show="selecting">Selecting...</span>
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
        selecting: false,
        showSelectionModal: false,
        selectedQuote: null,
        selectionReason: '',
        isAlreadySelected: false,
        selectedVendorName: '',
        toast: { show: false, message: '', type: 'success' },

        async init() {
            if (!this.prNumber) {
                this.showToast('PR Number is required', 'error');
                return;
            }
            await this.loadComparison();
        },

        async loadComparison() {
            this.loading = true;
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch(`/api/v1/quotation-comparison/${this.prNumber}`, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.comparison = data.data.comparison;
                    this.isAlreadySelected = data.data.is_selected || false;
                    this.selectedVendorName = data.data.selected_vendor || '';
                } else {
                    this.showToast(data.message || 'Failed to load comparison', 'error');
                }
            } catch (error) {
                console.error('Error loading comparison:', error);
                this.showToast('Failed to load comparison', 'error');
            } finally {
                this.loading = false;
            }
        },

        getBestPrice(quotations) {
            if (!quotations || quotations.length === 0) return null;
            return Math.min(...quotations.map(q => parseFloat(q.total_price)));
        },

        getEarliestDelivery(quotations) {
            if (!quotations || quotations.length === 0) return null;
            const dates = quotations.filter(q => q.delivery_date).map(q => q.delivery_date);
            if (dates.length === 0) return null;
            return dates.sort()[0];
        },

        // Get overall lowest total price across all items
        getLowestTotalPrice() {
            if (!this.comparison || this.comparison.length === 0) return 0;
            const vendorTotals = {};
            
            this.comparison.forEach(item => {
                item.quotations.forEach(quote => {
                    if (!vendorTotals[quote.vendor_name]) {
                        vendorTotals[quote.vendor_name] = 0;
                    }
                    vendorTotals[quote.vendor_name] += parseFloat(quote.total_price);
                });
            });
            
            return Math.min(...Object.values(vendorTotals));
        },

        // Get vendor with best price
        getBestPriceVendor() {
            if (!this.comparison || this.comparison.length === 0) return '—';
            const vendorTotals = {};
            
            this.comparison.forEach(item => {
                item.quotations.forEach(quote => {
                    if (!vendorTotals[quote.vendor_name]) {
                        vendorTotals[quote.vendor_name] = 0;
                    }
                    vendorTotals[quote.vendor_name] += parseFloat(quote.total_price);
                });
            });
            
            let bestVendor = '';
            let lowestPrice = Infinity;
            
            for (const [vendor, total] of Object.entries(vendorTotals)) {
                if (total < lowestPrice) {
                    lowestPrice = total;
                    bestVendor = vendor;
                }
            }
            
            return bestVendor || '—';
        },

        // Get earliest delivery date across all items
        getEarliestDeliveryDate() {
            if (!this.comparison || this.comparison.length === 0) return '—';
            const allDates = [];
            
            this.comparison.forEach(item => {
                item.quotations.forEach(quote => {
                    if (quote.delivery_date) {
                        allDates.push(quote.delivery_date);
                    }
                });
            });
            
            if (allDates.length === 0) return '—';
            return allDates.sort()[0];
        },

        // Get vendor with fastest delivery
        getFastestDeliveryVendor() {
            if (!this.comparison || this.comparison.length === 0) return '—';
            const earliestDate = this.getEarliestDeliveryDate();
            if (earliestDate === '—') return '—';
            
            for (const item of this.comparison) {
                for (const quote of item.quotations) {
                    if (quote.delivery_date === earliestDate) {
                        return quote.vendor_name;
                    }
                }
            }
            
            return '—';
        },

        // Get overall best vendor (considering both price and delivery)
        getOverallBestVendor() {
            const bestPrice = this.getBestPriceVendor();
            const fastestDelivery = this.getFastestDeliveryVendor();
            
            // If same vendor has both best price and fastest delivery
            if (bestPrice === fastestDelivery && bestPrice !== '—') {
                return bestPrice + ' ⭐';
            }
            
            // Otherwise, prioritize best price
            return bestPrice;
        },

        selectQuotation(quote) {
            this.selectedQuote = quote;
            this.selectionReason = '';
            this.showSelectionModal = true;
        },

        async confirmSelection() {
            if (!this.selectedQuote) return;

            this.selecting = true;
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/quotation-comparison/select', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        pr_number: this.prNumber,
                        quotation_id: this.selectedQuote.id,
                        selection_reason: this.selectionReason
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('Quotation selected successfully', 'success');
                    this.showSelectionModal = false;
                    setTimeout(() => {
                        window.location.href = '{{ url("/org/{$organization->org_slug}/procurement/quotation-comparison") }}';
                    }, 1500);
                } else {
                    this.showToast(data.message || 'Selection failed', 'error');
                }
            } catch (error) {
                console.error('Error selecting quotation:', error);
                this.showToast('Selection failed', 'error');
            } finally {
                this.selecting = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        }
    }
}
</script>
@endsection
