@extends('layouts.maintenance')

@section('title', 'Spare Parts - ' . $organization->org_name)
@section('page-title', 'Spare Parts')

@section('content')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sparePartsPage', () => ({
        showForm: false,
        issueModal: null, issueQty: 1, issueWo: '',
        receiveModal: null, receiveQty: 1,
        orderModal: null, orderModalName: '', orderModalUnit: 'Nos', orderQty: 1, orderVendor: '', orderDate: '',
        orderLoading: false,
        toast: null,
        parts: @json(array_values($parts)),

        async submitOrder() {
            if (!this.orderModal) return;
            this.orderLoading = true;
            const body = new FormData();
            body.append('part_code', this.orderModal);
            body.append('qty', this.orderQty);
            body.append('vendor', this.orderVendor);
            body.append('expected_date', this.orderDate);
            body.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const res = await fetch('{{ route('tenant.maintenance.procurement.store', $organization->org_slug) }}', {
                    method: 'POST', body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.ok) {
                    const idx = this.parts.findIndex(p => p.code === data.part_code);
                    if (idx !== -1) {
                        this.parts[idx].po_no     = data.po_no;
                        this.parts[idx].po_status = 'Pending';
                    }
                    this.toast = 'Procurement order ' + data.po_no + ' raised successfully.';
                    this.orderModal = null;
                } else {
                    this.toast = 'Failed to raise order.';
                }
            } catch(e) {
                this.toast = 'Network error. Please try again.';
            }
            this.orderLoading = false;
            setTimeout(() => this.toast = null, 4000);
        }
    }));
});
</script>
<div x-data="sparePartsPage()">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <!-- Alpine toast -->
    <div x-show="toast" x-cloak x-transition
         class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
        <span class="material-symbols-outlined text-base">check_circle</span>
        <span x-text="toast"></span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Spare Parts Inventory</h2>
            <p class="text-sm text-gray-500">Track stock, issue against work orders, and receive procurement deliveries</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-purple-500 hover:bg-purple-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            Add Part
        </button>
    </div>

    <!-- Add Part Form -->
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl border border-purple-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-500">settings</span>
            Add Spare Part
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.spare-parts.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Part Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Oil Filter"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Part Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required placeholder="e.g. SP-001"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Compatible Asset</label>
                    @if(isset($assets) && count($assets) > 0)
                        <select name="asset" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                            <option value="">Select asset (optional)</option>
                            @foreach($assets as $a)
                                <option value="{{ $a['name'] }}">{{ $a['name'] }} ({{ $a['code'] }})</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="asset" placeholder="e.g. Air Compressor"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Stock <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" required min="0" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level</label>
                    <input type="number" name="reorder_level" min="0" placeholder="e.g. 5"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <input type="text" name="unit" placeholder="Nos, Litres, Kg"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Save Part</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Low Stock Alert with quick Order buttons -->
    @php $lowStock = array_values(array_filter($parts, fn($p) => isset($p['reorder_level']) && $p['reorder_level'] !== null && $p['stock'] <= $p['reorder_level'])); @endphp
    @if(count($lowStock) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-red-800 mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-base">warning</span>
            {{ count($lowStock) }} part(s) at or below reorder level — order now
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($lowStock as $p)
                @if(empty($p['po_no']))
                <button
                    @click="orderModal = '{{ $p['code'] }}'; orderModalName = @js($p['name']); orderModalUnit = '{{ $p['unit'] ?? 'Nos' }}'; orderQty = {{ max(1, ($p['reorder_level'] ?? 1) - $p['stock'] + 1) }}; orderVendor = ''; orderDate = ''"
                    class="flex items-center gap-1.5 bg-white border border-red-300 hover:bg-red-100 text-red-700 text-xs font-semibold px-3 py-1.5 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-sm">shopping_cart</span>
                    Order {{ $p['name'] }} ({{ $p['stock'] }} left)
                </button>
                @else
                <span class="flex items-center gap-1.5 bg-white border border-blue-300 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                    {{ $p['name'] }} — PO {{ $p['po_status'] }} ({{ $p['po_no'] }})
                </span>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Pending Material Requests -->
    @php $pendingMR = array_values(array_filter($matReqs, fn($m) => $m['status'] === 'Pending Issue')); @endphp
    @if(count($pendingMR) > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-amber-800 mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-base">pending_actions</span>
            {{ count($pendingMR) }} material request(s) pending issue from stock
        </p>
        <a href="{{ route('tenant.maintenance.material-requests', $organization->org_slug) }}"
            class="text-xs font-semibold text-amber-700 underline">View Material Requests →</a>
    </div>
    @endif

    <!-- Parts Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Part Code</th>
                        <th class="py-3 px-4 font-semibold">Part Name</th>
                        <th class="py-3 px-4 font-semibold">Compatible Asset</th>
                        <th class="py-3 px-4 font-semibold">Stock</th>
                        <th class="py-3 px-4 font-semibold">Reorder Level</th>
                        <th class="py-3 px-4 font-semibold">Unit</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="parts.length === 0">
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="8">
                                <span class="material-symbols-outlined text-4xl block mb-2">settings</span>
                                No spare parts added yet. Click "Add Part" to build your inventory.
                            </td>
                        </tr>
                    </template>
                    <template x-for="part in parts" :key="part.code">
                        <tr class="border-b last:border-b-0 hover:bg-gray-50"
                            :class="(part.reorder_level !== null && part.stock <= part.reorder_level && !part.po_no) ? 'bg-red-50/30' : ''">
                            <td class="py-3 px-4 font-semibold text-purple-600" x-text="part.code"></td>
                            <td class="py-3 px-4 font-medium text-gray-900" x-text="part.name"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="part.asset || '-'"></td>
                            <td class="py-3 px-4 font-bold"
                                :class="part.stock == 0 ? 'text-red-600' : (part.reorder_level !== null && part.stock <= part.reorder_level ? 'text-orange-600' : 'text-gray-900')"
                                x-text="part.stock"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="part.reorder_level ?? '-'"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="part.unit || 'Nos'"></td>
                            <td class="py-3 px-4">
                                <template x-if="part.po_no">
                                    <div>
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">local_shipping</span>
                                            PO <span x-text="part.po_status"></span>
                                        </span>
                                        <p class="text-xs text-blue-500 mt-0.5 font-medium" x-text="part.po_no"></p>
                                    </div>
                                </template>
                                <template x-if="!part.po_no && part.stock == 0">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Out of Stock</span>
                                </template>
                                <template x-if="!part.po_no && part.stock > 0 && part.reorder_level !== null && part.stock <= part.reorder_level">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-700">Low Stock</span>
                                </template>
                                <template x-if="!part.po_no && part.stock > 0 && (part.reorder_level === null || part.stock > part.reorder_level)">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">In Stock</span>
                                </template>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-1.5 flex-wrap">
                                    <template x-if="part.stock > 0">
                                        <button x-on:click="issueModal = part.code; issueQty = 1; issueWo = ''"
                                            class="flex items-center gap-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">output</span> Issue
                                        </button>
                                    </template>
                                    <button x-on:click="receiveModal = part.code; receiveQty = 1"
                                        class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">input</span> Receive
                                    </button>
                                    <template x-if="part.reorder_level !== null && part.stock <= part.reorder_level && !part.po_no">
                                        <button
                                            x-on:click="orderModal = part.code; orderModalName = part.name; orderModalUnit = part.unit || 'Nos'; orderQty = Math.max(1, (part.reorder_level || 1) - part.stock + 1); orderVendor = ''; orderDate = ''"
                                            class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">shopping_cart</span> Order
                                        </button>
                                    </template>
                                    <template x-if="part.po_no">
                                        <button
                                            x-on:click="$dispatch('open-po-panel', { code: part.code })"
                                            class="flex items-center gap-1 border border-blue-300 text-blue-600 hover:bg-blue-50 text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">local_shipping</span> View PO
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Issue Modal -->
    <div x-show="issueModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="issueModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-2 rounded-lg"><span class="material-symbols-outlined text-amber-600 text-xl">output</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Issue Spare Part</h3>
                    <p class="text-sm text-gray-500" x-text="'Part: ' + issueModal"></p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/spare-parts/' + issueModal + '/issue'" method="POST">
                @csrf
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Order</label>
                        <input type="text" name="work_order" x-model="issueWo" placeholder="e.g. WO-0001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" name="qty" x-model="issueQty" required min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">Issue</button>
                    <button type="button" @click="issueModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receive Modal -->
    <div x-show="receiveModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="receiveModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-2 rounded-lg"><span class="material-symbols-outlined text-green-600 text-xl">input</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Receive Stock</h3>
                    <p class="text-sm text-gray-500" x-text="'Part: ' + receiveModal"></p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/spare-parts/' + receiveModal + '/receive'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Received <span class="text-red-500">*</span></label>
                    <input type="number" name="qty" x-model="receiveQty" required min="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">Receive</button>
                    <button type="button" @click="receiveModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Order (Procurement) Modal -->
    <div x-show="orderModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" x-on:click.self="orderModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" x-on:click.stop>
            <div class="flex items-center gap-3 mb-1">
                <div class="bg-red-100 p-2 rounded-lg"><span class="material-symbols-outlined text-red-600 text-xl">shopping_cart</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Raise Procurement Order</h3>
                    <p class="text-sm text-gray-500" x-text="orderModalName + ' (' + orderModal + ')'"></p>
                </div>
            </div>
            <div class="space-y-3 my-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Order <span class="text-red-500">*</span></label>
                    <input type="number" x-model="orderQty" required min="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor / Supplier</label>
                    <input type="text" x-model="orderVendor" placeholder="e.g. ABC Supplies (optional)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery</label>
                    <input type="date" x-model="orderDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                </div>
            </div>
            <div class="flex gap-3">
                <button x-on:click="submitOrder()" :disabled="orderLoading"
                    class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white font-semibold py-2 rounded-lg text-sm transition-colors flex items-center justify-center gap-2">
                    <span x-show="orderLoading" class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                    <span x-text="orderLoading ? 'Raising...' : 'Raise Order'"></span>
                </button>
                <button type="button" x-on:click="orderModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </div>
    </div>
</div>

@include('tenant.maintenance.partials.procurement-panel')

@endsection
