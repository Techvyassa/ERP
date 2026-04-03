@extends('layouts.maintenance')

@section('title', 'Stock Management - ' . $organization->org_name)
@section('page-title', 'Stock Management')

@section('content')
<div x-data="{ adjustModal: null, adjustQty: 0, adjustType: 'add', adjustNote: '' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Stock Management</h2>
            <p class="text-sm text-gray-500">View current stock levels, adjust quantities, and review movement history</p>
        </div>
        <a href="{{ route('tenant.maintenance.spare-parts', $organization->org_slug) }}"
            class="flex items-center gap-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">settings</span>
            Manage Parts
        </a>
    </div>

    <!-- Stock Summary -->
    @php
        $totalParts  = count($parts);
        $inStock     = count(array_filter($parts, fn($p) => $p['stock'] > ($p['reorder_level'] ?? 0)));
        $lowStock    = count(array_filter($parts, fn($p) => isset($p['reorder_level']) && $p['reorder_level'] !== null && $p['stock'] <= $p['reorder_level'] && $p['stock'] > 0));
        $outOfStock  = count(array_filter($parts, fn($p) => $p['stock'] == 0));
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totalParts }}</p>
            <p class="text-xs text-gray-500 font-semibold mt-1">Total Parts</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $inStock }}</p>
            <p class="text-xs text-green-600 font-semibold mt-1">In Stock</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-orange-700">{{ $lowStock }}</p>
            <p class="text-xs text-orange-600 font-semibold mt-1">Low Stock</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700">{{ $outOfStock }}</p>
            <p class="text-xs text-red-600 font-semibold mt-1">Out of Stock</p>
        </div>
    </div>

    <!-- Low Stock Alert -->
    @php $lowStockParts = array_values(array_filter($parts, fn($p) => isset($p['reorder_level']) && $p['reorder_level'] !== null && $p['stock'] <= $p['reorder_level'])); @endphp
    @if(count($lowStockParts) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-red-800 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-base">warning</span>
            {{ count($lowStockParts) }} part(s) need restocking
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($lowStockParts as $p)
                <span class="bg-white border border-red-300 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
                    {{ $p['name'] }} — {{ $p['stock'] }} {{ $p['unit'] ?? '' }} left
                </span>
            @endforeach
        </div>
        <a href="{{ route('tenant.maintenance.procurement', $organization->org_slug) }}"
            class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-red-700 underline">
            <span class="material-symbols-outlined text-sm">shopping_cart</span>
            Raise a Procurement Order
        </a>
    </div>
    @endif

    <!-- Stock Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Current Stock Levels</h3>
        </div>
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
                        <th class="py-3 px-4 font-semibold">Adjust</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parts as $part)
                        @php $isLow = isset($part['reorder_level']) && $part['reorder_level'] !== null && $part['stock'] <= $part['reorder_level']; @endphp
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-purple-600">{{ $part['code'] }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $part['name'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $part['asset'] ?: '-' }}</td>
                            <td class="py-3 px-4 font-bold {{ $part['stock'] == 0 ? 'text-red-600' : ($isLow ? 'text-orange-600' : 'text-gray-900') }}">
                                {{ $part['stock'] }}
                            </td>
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
                                <button @click="adjustModal = {{ $part['id'] }}; adjustQty = 0; adjustType = 'add'; adjustNote = ''"
                                    class="flex items-center gap-1 border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold px-2 py-1.5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-sm">tune</span> Adjust
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="8">
                                <span class="material-symbols-outlined text-4xl block mb-2">warehouse</span>
                                No spare parts in inventory. <a href="{{ route('tenant.maintenance.spare-parts', $organization->org_slug) }}" class="text-purple-600 underline">Add parts first</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Movement History -->
    @if(count($movements) > 0)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Recent Stock Movements</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Date</th>
                        <th class="py-3 px-4 font-semibold">Part</th>
                        <th class="py-3 px-4 font-semibold">Type</th>
                        <th class="py-3 px-4 font-semibold">Qty</th>
                        <th class="py-3 px-4 font-semibold">Reference</th>
                        <th class="py-3 px-4 font-semibold">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $mv)
                        @php
                            $typeColor = match($mv['type']) {
                                'Issue'    => 'bg-amber-100 text-amber-700',
                                'Receive'  => 'bg-green-100 text-green-700',
                                'Adjust+'  => 'bg-blue-100 text-blue-700',
                                'Adjust-'  => 'bg-red-100 text-red-700',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 text-gray-600">{{ $mv['date'] }}</td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-900">{{ $mv['part_name'] }}</p>
                                <p class="text-xs text-gray-400">{{ $mv['part_code'] }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $typeColor }}">{{ $mv['type'] }}</span>
                            </td>
                            <td class="py-3 px-4 font-semibold {{ str_starts_with($mv['type'], 'Issue') || $mv['type'] === 'Adjust-' ? 'text-red-600' : 'text-green-600' }}">
                                {{ str_starts_with($mv['type'], 'Issue') || $mv['type'] === 'Adjust-' ? '-' : '+' }}{{ $mv['qty'] }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $mv['reference'] ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-500 text-xs">{{ $mv['note'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Adjust Stock Modal -->
    <div x-show="adjustModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="adjustModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 p-2 rounded-lg"><span class="material-symbols-outlined text-blue-600 text-xl">tune</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Adjust Stock</h3>
                    <p class="text-sm text-gray-500">Manual stock correction</p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/stock-management/' + adjustModal + '/adjust'" method="POST">
                @csrf
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
                        <select name="type" x-model="adjustType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                            <option value="add">Add Stock</option>
                            <option value="subtract">Subtract Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" name="qty" x-model="adjustQty" required min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Note</label>
                        <input type="text" name="note" x-model="adjustNote" placeholder="e.g. Physical count correction"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">Apply</button>
                    <button type="button" @click="adjustModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
