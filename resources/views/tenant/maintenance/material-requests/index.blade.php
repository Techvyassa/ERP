@extends('layouts.maintenance')

@section('title', 'Material Requests - ' . $organization->org_name)
@section('page-title', 'Material Requests')

@section('content')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mmrPage', () => ({
        showForm: false,
        woId: '',
        items: [{ part_code: '', part_name: '', unit: 'Nos', qty: 1, stock: 0 }],
        raisePOModal: null,
        raisePOVendor: '',
        raisePODate: '',
        raisePOLoading: false,
        toast: null,
        parts: @json(array_values($parts)),
        rows: @json(array_values($matRequests)),

        async submitRaisePO() {
            if (!this.raisePOModal) return;
            this.raisePOLoading = true;
            const body = new FormData();
            body.append('mmr_no', this.raisePOModal);
            body.append('vendor', this.raisePOVendor);
            body.append('expected_date', this.raisePODate);
            body.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const res = await fetch('{{ route('tenant.maintenance.material-requests.raise-po-direct', $organization->org_slug) }}', {
                    method: 'POST', body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.ok) {
                    const idx = this.rows.findIndex(r => r.id === data.mmr_no);
                    if (idx !== -1) {
                        this.rows[idx].status = 'PO Raised';
                        this.rows[idx].po_no  = data.po_no;
                    }
                    this.toast = 'Procurement order ' + data.po_no + ' raised for ' + data.part_name;
                    this.raisePOModal = null;
                } else {
                    this.toast = data.message || 'Failed to raise PO.';
                }
            } catch(e) {
                this.toast = 'Network error. Please try again.';
            }
            this.raisePOLoading = false;
            setTimeout(() => this.toast = null, 4000);
        }
    }));
});
</script>
<div x-data="mmrPage()">

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
            <h2 class="text-lg font-semibold text-gray-900">Material Requests</h2>
            <p class="text-sm text-gray-500">Identify materials needed per work order — system checks stock and flags procurement if unavailable</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            Request Material
        </button>
    </div>

    <!-- Process Flow Banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">info</span>
            Material Flow
        </p>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="bg-white border border-blue-200 text-blue-700 px-2 py-1 rounded font-medium">1. Request Material</span>
            <span class="material-symbols-outlined text-sm text-blue-400">arrow_forward</span>
            <span class="bg-green-100 border border-green-300 text-green-700 px-2 py-1 rounded font-medium">In Stock → Issue to WO</span>
            <span class="text-gray-400 text-xs font-medium">or</span>
            <span class="bg-red-100 border border-red-300 text-red-700 px-2 py-1 rounded font-medium">Not in Stock → Raise PO</span>
            <span class="material-symbols-outlined text-sm text-blue-400">arrow_forward</span>
            <span class="bg-blue-100 border border-blue-300 text-blue-700 px-2 py-1 rounded font-medium">PO Raised → Receive Stock</span>
            <span class="material-symbols-outlined text-sm text-blue-400">arrow_forward</span>
            <span class="bg-white border border-blue-200 text-blue-700 px-2 py-1 rounded font-medium">Issue → Repair</span>
        </div>
    </div>

    <!-- Request Form -->
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl border border-amber-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">inventory_2</span>
            New Material Request
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.material-requests.store', $organization->org_slug) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Work Order <span class="text-red-500">*</span></label>
                @php $activeWOs = array_values(array_filter($workOrders, fn($w) => !in_array($w['status'], ['Closed']))); @endphp
                <select name="wo_id" x-model="woId" required class="w-full md:w-80 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                    <option value="">Select work order</option>
                    @forelse($activeWOs as $wo)
                        <option value="{{ $wo['wo'] }}">{{ $wo['wo'] }} — {{ $wo['asset'] }} ({{ $wo['status'] }})</option>
                    @empty
                        <option value="" disabled>No active work orders</option>
                    @endforelse
                </select>
                @if(count($activeWOs) === 0)
                    <p class="text-xs text-gray-400 mt-1">
                        <a href="{{ route('tenant.maintenance.assignments', $organization->org_slug) }}" class="text-amber-600 underline">Go to Assignments</a> to create a work order first.
                    </p>
                @endif
            </div>

            <div class="space-y-3">
                <template x-for="(it, idx) in items" :key="idx">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg">
                        <div class="md:col-span-5">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Spare Part <span class="text-red-500">*</span></label>
                            @if(count($parts) > 0)
                                <select :name="`items[${idx}][part_code]`" x-model="it.part_code" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none"
                                    @change="
                                        const opt = $event.target.options[$event.target.selectedIndex];
                                        it.part_name = opt?.dataset?.name || '';
                                        it.unit = opt?.dataset?.unit || 'Nos';
                                        it.stock = parseInt(opt?.dataset?.stock || '0');
                                    ">
                                    <option value="">Select part</option>
                                    @foreach($parts as $p)
                                        <option value="{{ $p['code'] }}"
                                            data-name="{{ $p['name'] }}"
                                            data-unit="{{ $p['unit'] ?? 'Nos' }}"
                                            data-stock="{{ $p['stock'] }}">
                                            {{ $p['name'] }} ({{ $p['code'] }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" :name="`items[${idx}][part_code]`" x-model="it.part_code" required placeholder="Part code"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                            @endif
                            <input type="hidden" :name="`items[${idx}][part_name]`" :value="it.part_name">
                            <input type="hidden" :name="`items[${idx}][unit]`" :value="it.unit">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Qty <span class="text-red-500">*</span></label>
                            <input type="number" :name="`items[${idx}][qty]`" x-model.number="it.qty" required min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                        </div>
                        <div class="md:col-span-3">
                            <!-- Stock indicator -->
                            <div x-show="it.part_code !== ''" class="text-xs mt-1">
                                <span x-show="it.stock >= it.qty" class="flex items-center gap-1 text-green-600 font-semibold">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    <span x-text="it.stock + ' in stock'"></span>
                                </span>
                                <span x-show="it.stock > 0 && it.stock < it.qty" class="flex items-center gap-1 text-orange-600 font-semibold">
                                    <span class="material-symbols-outlined text-sm">warning</span>
                                    <span x-text="'Only ' + it.stock + ' in stock'"></span>
                                </span>
                                <span x-show="it.stock === 0" class="flex items-center gap-1 text-red-600 font-semibold">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                    Out of stock — PO will be raised
                                </span>
                            </div>
                        </div>
                        <div class="md:col-span-2 flex gap-2">
                            <button type="button" @click="items.push({ part_code: '', part_name: '', unit: 'Nos', qty: 1, stock: 0 })"
                                class="flex-1 flex items-center justify-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-2 py-2 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </button>
                            <button type="button" @click="items.length > 1 ? items.splice(idx, 1) : null" :disabled="items.length === 1"
                                class="flex-1 flex items-center justify-center gap-1 border border-gray-300 hover:bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-2 rounded-lg transition-colors disabled:opacity-40">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Submit Request</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards (reactive) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-700" x-text="rows.filter(r=>r.status==='Pending Issue').length"></p>
            <p class="text-xs text-amber-600 font-semibold mt-1">Pending Issue</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700" x-text="rows.filter(r=>r.status==='Procurement Required').length"></p>
            <p class="text-xs text-red-600 font-semibold mt-1">Needs Procurement</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700" x-text="rows.filter(r=>r.status==='PO Raised').length"></p>
            <p class="text-xs text-blue-600 font-semibold mt-1">PO Raised</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700" x-text="rows.filter(r=>r.status==='Issued').length"></p>
            <p class="text-xs text-green-600 font-semibold mt-1">Issued</p>
        </div>
    </div>

    <!-- Material Requests Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">MMR ID</th>
                        <th class="py-3 px-4 font-semibold">Work Order</th>
                        <th class="py-3 px-4 font-semibold">Part</th>
                        <th class="py-3 px-4 font-semibold">Qty</th>
                        <th class="py-3 px-4 font-semibold">Raised On</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="rows.length === 0">
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">inventory_2</span>
                                No material requests yet. Click "Request Material" to get started.
                            </td>
                        </tr>
                    </template>
                    <template x-for="mr in rows" :key="mr.id">
                        <tr class="border-b last:border-b-0 hover:bg-gray-50"
                            :class="mr.status === 'Procurement Required' ? 'bg-red-50/40' : ''">
                            <td class="py-3 px-4 font-semibold text-gray-700 text-xs" x-text="mr.id"></td>
                            <td class="py-3 px-4 font-medium text-amber-700 text-xs" x-text="mr.wo_id"></td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-900" x-text="mr.part_name || mr.part_code"></p>
                                <p class="text-xs text-gray-400" x-text="mr.part_code"></p>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900" x-text="mr.qty + ' ' + mr.unit"></td>
                            <td class="py-3 px-4 text-gray-500 text-xs" x-text="mr.raised_on"></td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold"
                                    :class="{
                                        'bg-amber-100 text-amber-700': mr.status === 'Pending Issue',
                                        'bg-red-100 text-red-700':    mr.status === 'Procurement Required',
                                        'bg-blue-100 text-blue-700':  mr.status === 'PO Raised',
                                        'bg-green-100 text-green-700':mr.status === 'Issued'
                                    }">
                                    <span class="material-symbols-outlined text-xs"
                                        x-text="({'Pending Issue':'pending','Procurement Required':'warning','PO Raised':'local_shipping','Issued':'check_circle'})[mr.status] || 'info'"></span>
                                    <span x-text="mr.status"></span>
                                </span>
                                <p x-show="mr.po_no" class="text-xs text-blue-600 mt-0.5 font-medium" x-text="mr.po_no"></p>
                            </td>
                            <td class="py-3 px-4">
                                <template x-if="mr.status === 'Pending Issue'">
                                    <form method="POST"
                                        :action="'{{ url('/org/'.$organization->org_slug.'/maintenance/material-requests') }}/' + mr.id + '/issue'">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button type="submit"
                                            class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">output</span> Issue from Stock
                                        </button>
                                    </form>
                                </template>
                                <template x-if="mr.status === 'Procurement Required'">
                                    <button
                                        x-on:click="raisePOModal = mr.id; raisePOVendor = ''; raisePODate = ''"
                                        class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">shopping_cart</span> Raise PO
                                    </button>
                                </template>
                                <template x-if="mr.status === 'PO Raised'">
                                    <button
                                        x-on:click="$dispatch('open-po-panel', { code: mr.part_code })"
                                        class="flex items-center gap-1 text-blue-600 hover:text-blue-800 border border-blue-200 hover:bg-blue-50 text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">local_shipping</span> View PO
                                    </button>
                                </template>
                                <template x-if="mr.status === 'Issued'">
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        <span x-text="'Issued ' + (mr.issued_on || '')"></span>
                                    </span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Raise PO Modal (fetch-based, no redirect) -->
    <div x-show="raisePOModal !== null" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         x-on:click.self="raisePOModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" x-on:click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-red-100 p-2 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-xl">shopping_cart</span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Raise Procurement Order</h3>
                    <p class="text-sm text-gray-500" x-text="'MMR: ' + raisePOModal"></p>
                </div>
            </div>
            <div class="space-y-3 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor / Supplier</label>
                    <input type="text" x-model="raisePOVendor" placeholder="e.g. ABC Supplies (optional)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery</label>
                    <input type="date" x-model="raisePODate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 outline-none">
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                    <span class="material-symbols-outlined text-sm align-middle">info</span>
                    A procurement order will be created and this request will be marked as "PO Raised".
                </div>
            </div>
            <div class="flex gap-3">
                <button x-on:click="submitRaisePO()" :disabled="raisePOLoading"
                    class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white font-semibold py-2 rounded-lg text-sm transition-colors flex items-center justify-center gap-2">
                    <span x-show="raisePOLoading" class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                    <span x-text="raisePOLoading ? 'Raising...' : 'Confirm & Raise PO'"></span>
                </button>
                <button type="button" x-on:click="raisePOModal = null"
                    class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@include('tenant.maintenance.partials.procurement-panel')

@endsection
