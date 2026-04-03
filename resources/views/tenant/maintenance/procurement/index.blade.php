@extends('layouts.maintenance')

@section('title', 'Procurement - ' . $organization->org_name)
@section('page-title', 'Procurement')

@section('content')
<div x-data="{ showForm: false, receiveModal: null, receiveQty: 1, receiveNote: '' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Procurement Orders</h2>
            <p class="text-sm text-gray-500">Raise purchase orders for spare parts and track deliveries</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            New Order
        </button>
    </div>

    <!-- Summary Cards -->
    @php
        $pending  = count(array_filter($orders, fn($o) => $o['status'] === 'Pending'));
        $ordered  = count(array_filter($orders, fn($o) => $o['status'] === 'Ordered'));
        $received = count(array_filter($orders, fn($o) => $o['status'] === 'Received'));
    @endphp
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-700">{{ $pending }}</p>
            <p class="text-xs text-amber-600 font-semibold mt-1">Pending</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700">{{ $ordered }}</p>
            <p class="text-xs text-blue-600 font-semibold mt-1">Ordered</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $received }}</p>
            <p class="text-xs text-green-600 font-semibold mt-1">Received</p>
        </div>
    </div>

    <!-- New Order Form -->
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl border border-amber-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">shopping_cart</span>
            Raise Procurement Order
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.procurement.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Spare Part <span class="text-red-500">*</span></label>
                    @if(count($parts) > 0)
                        <select name="part_code" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                            <option value="">Select part</option>
                            @foreach($parts as $p)
                                <option value="{{ $p['code'] }}">{{ $p['name'] }} ({{ $p['code'] }}) — {{ $p['stock'] }} {{ $p['unit'] }} in stock</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="part_code" required placeholder="Part code e.g. SP-001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                        <p class="text-xs text-gray-400 mt-1">No parts found. <a href="{{ route('tenant.maintenance.spare-parts', $organization->org_slug) }}" class="text-purple-600 underline">Add spare parts first</a>.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="qty" required min="1" placeholder="e.g. 10"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor / Supplier</label>
                    <input type="text" name="vendor" placeholder="e.g. ABC Supplies"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery</label>
                    <input type="date" name="expected_date"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="Any additional notes"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Raise Order</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">PO No.</th>
                        <th class="py-3 px-4 font-semibold">Part</th>
                        <th class="py-3 px-4 font-semibold">Qty</th>
                        <th class="py-3 px-4 font-semibold">Vendor</th>
                        <th class="py-3 px-4 font-semibold">Expected</th>
                        <th class="py-3 px-4 font-semibold">Raised On</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $sc = [
                                'Pending'  => 'bg-amber-100 text-amber-700',
                                'Ordered'  => 'bg-blue-100 text-blue-700',
                                'Received' => 'bg-green-100 text-green-700',
                                'Cancelled'=> 'bg-gray-100 text-gray-500',
                            ][$order['status']] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-amber-700">{{ $order['po_no'] }}</td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-900">{{ $order['part_name'] ?: $order['part_code'] }}</p>
                                <p class="text-xs text-gray-400">{{ $order['part_code'] }}</p>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $order['qty'] }} {{ $order['unit'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $order['vendor'] ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $order['expected_date'] ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $order['raised_on'] }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $order['status'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    @if($order['status'] === 'Pending')
                                        <form method="POST" action="{{ route('tenant.maintenance.procurement.mark-ordered', [$organization->org_slug, $order['id']]) }}">
                                            @csrf
                                            <button type="submit" class="flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                                <span class="material-symbols-outlined text-sm">local_shipping</span> Mark Ordered
                                            </button>
                                        </form>
                                    @endif
                                    @if(in_array($order['status'], ['Pending', 'Ordered']))
                                        <button @click="receiveModal = {{ $order['id'] }}; receiveQty = {{ $order['qty'] }}; receiveNote = ''"
                                            class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">input</span> Receive
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="8">
                                <span class="material-symbols-outlined text-4xl block mb-2">shopping_cart</span>
                                No procurement orders yet. Click "New Order" to raise one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Receive Modal -->
    <div x-show="receiveModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="receiveModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-2 rounded-lg"><span class="material-symbols-outlined text-green-600 text-xl">input</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Receive Delivery</h3>
                    <p class="text-sm text-gray-500">Update stock on receipt</p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/procurement/' + receiveModal + '/receive'" method="POST">
                @csrf
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Received <span class="text-red-500">*</span></label>
                        <input type="number" name="qty" x-model="receiveQty" required min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                        <input type="text" name="note" x-model="receiveNote" placeholder="e.g. Partial delivery"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">Confirm Receipt</button>
                    <button type="button" @click="receiveModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
