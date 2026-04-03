{{-- Procurement slide-over panel --}}
{{-- Include this partial in any maintenance view that needs "View PO" without redirecting --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('procurementPanel', () => ({
        open: false,
        loading: false,
        orders: [],
        filterCode: null,   // set to a part_code to filter, null = show all

        async load(partCode = null) {
            this.filterCode = partCode || null;
            this.open = true;
            this.loading = true;
            try {
                const res = await fetch('{{ route('tenant.maintenance.procurement.orders-json', $organization->org_slug) }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.orders = this.filterCode
                    ? data.filter(o => o.part_code === this.filterCode)
                    : data;
            } catch(e) {
                this.orders = [];
            }
            this.loading = false;
        },

        statusClass(s) {
            return {
                'Pending':  'bg-amber-100 text-amber-700',
                'Ordered':  'bg-blue-100 text-blue-700',
                'Received': 'bg-green-100 text-green-700',
                'Cancelled':'bg-gray-100 text-gray-500',
            }[s] || 'bg-gray-100 text-gray-600';
        }
    }));
});
</script>

<div x-data="procurementPanel()">
    {{-- Trigger: any element can call $dispatch('open-po-panel', { code: 'SP-001' }) or just open-po-panel --}}
    <div x-on:open-po-panel.window="load($event.detail?.code)"></div>

    {{-- Slide-over backdrop --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 bg-black/40 z-40 transition-opacity"
         x-on:click="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Slide-over panel --}}
    <div x-show="open" x-cloak
         class="fixed right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-xl">shopping_cart</span>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Procurement Orders</h2>
                    <p class="text-xs text-gray-500" x-text="filterCode ? 'Filtered: ' + filterCode : 'All orders'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button x-on:click="open = false" class="text-gray-400 hover:text-gray-700 ml-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-5">

            {{-- Loading --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-400">
                    <span class="material-symbols-outlined text-3xl animate-spin mr-2">progress_activity</span>
                    Loading...
                </div>
            </template>

            {{-- Empty --}}
            <template x-if="!loading && orders.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <span class="material-symbols-outlined text-4xl block mb-2">shopping_cart</span>
                    No procurement orders found.
                </div>
            </template>

            {{-- Orders list --}}
            <template x-if="!loading && orders.length > 0">
                <div class="space-y-3">
                    <template x-for="o in orders" :key="o.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div>
                                    <p class="font-semibold text-amber-700 text-sm" x-text="o.po_no"></p>
                                    <p class="font-medium text-gray-900 text-sm" x-text="o.part_name || o.part_code"></p>
                                    <p class="text-xs text-gray-400" x-text="o.part_code"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold flex-shrink-0"
                                    :class="statusClass(o.status)" x-text="o.status"></span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-gray-600 mt-2">
                                <div>
                                    <p class="text-gray-400 font-medium">Qty</p>
                                    <p class="font-semibold text-gray-800" x-text="o.qty + ' ' + o.unit"></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 font-medium">Vendor</p>
                                    <p class="font-semibold text-gray-800" x-text="o.vendor || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 font-medium">Expected</p>
                                    <p class="font-semibold text-gray-800" x-text="o.expected_date || '-'"></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2" x-text="'Raised: ' + (o.raised_on || '-')"></p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
