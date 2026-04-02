@extends('layouts.maintenance')

@section('title', 'Spare Parts - ' . $organization->org_name)
@section('page-title', 'Spare Parts')

@section('content')
<div x-data="{ showForm: false, issueModal: null, issueQty: 1, issueWo: '', receiveModal: null, receiveQty: 1 }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

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
                    @if(count($workOrders) > 0)
                        <input type="text" name="asset" placeholder="e.g. Air Compressor"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
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

    <!-- Low Stock Alert -->
    @php $lowStock = array_values(array_filter($parts, fn($p) => isset($p['reorder_level']) && $p['reorder_level'] !== null && $p['stock'] <= $p['reorder_level'])); @endphp
    @if(count($lowStock) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-red-800 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-base">warning</span>
            {{ count($lowStock) }} part(s) at or below reorder level — raise a procurement request
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($lowStock as $p)
                <span class="bg-white border border-red-300 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
                    {{ $p['name'] }} — {{ $p['stock'] }} {{ $p['unit'] ?? '' }} left
                </span>
            @endforeach
        </div>
        <a href="{{ route('tenant.maintenance.material-requests', $organization->org_slug) }}"
            class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-red-700 underline">
            <span class="material-symbols-outlined text-sm">open_in_new</span>
            Go to Material Requests to raise procurement
        </a>
    </div>
    @endif

    <!-- Pending Material Requests from WOs -->
    @php $pendingMR = array_values(array_filter($matReqs, fn($m) => $m['status'] === 'Pending Issue')); @endphp
    @if(count($pendingMR) > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-amber-800 mb-2 flex items-center gap-2">
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
                    @forelse($parts as $part)
                        @php $isLow = isset($part['reorder_level']) && $part['reorder_level'] !== null && $part['stock'] <= $part['reorder_level']; @endphp
                        <tr class="border-b last:border-b-0 hover:bg-gray-50 {{ $isLow ? 'bg-red-50' : '' }}">
                            <td class="py-3 px-4 font-semibold text-purple-600">{{ $part['code'] }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $part['name'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $part['asset'] ?: '-' }}</td>
                            <td class="py-3 px-4 font-semibold {{ $isLow ? 'text-red-600' : 'text-gray-900' }}">{{ $part['stock'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $part['reorder_level'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $part['unit'] ?? 'Nos' }}</td>
                            <td class="py-3 px-4">
                                @if($part['stock'] == 0)
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Out of Stock</span>
                                @elseif($isLow)
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-700">Low Stock</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">In Stock</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    @if($part['stock'] > 0)
                                        <button @click="issueModal = '{{ $part['code'] }}'; issueQty = 1; issueWo = ''"
                                            class="flex items-center gap-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">output</span> Issue
                                        </button>
                                    @endif
                                    <button @click="receiveModal = '{{ $part['code'] }}'; receiveQty = 1"
                                        class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">input</span> Receive
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="8">
                                <span class="material-symbols-outlined text-4xl block mb-2">settings</span>
                                No spare parts added yet. Click "Add Part" to build your inventory.
                            </td>
                        </tr>
                    @endforelse
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
</div>
@endsection
