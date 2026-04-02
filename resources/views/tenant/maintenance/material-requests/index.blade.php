@extends('layouts.maintenance')

@section('title', 'Material Requests - ' . $organization->org_name)
@section('page-title', 'Material Requests')

@section('content')
<div x-data="{ showForm: false, woId: '', partCode: '', partName: '', partUnit: 'Nos' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

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
            Material Process Flow
        </p>
        <div class="flex flex-wrap items-center gap-2 text-xs text-blue-700">
            <span class="bg-white border border-blue-200 px-2 py-1 rounded">1. Identify Material</span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
            <span class="bg-white border border-blue-200 px-2 py-1 rounded">2. Check Stock</span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
            <span class="bg-green-100 border border-green-300 text-green-700 px-2 py-1 rounded">In Stock → Issue to WO</span>
            <span class="text-blue-400">or</span>
            <span class="bg-red-100 border border-red-300 text-red-700 px-2 py-1 rounded">Not in Stock → Procurement Request</span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
            <span class="bg-white border border-blue-200 px-2 py-1 rounded">3. Receive → Issue → Repair</span>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work Order <span class="text-red-500">*</span></label>
                    @php $activeWOs = array_values(array_filter($workOrders, fn($w) => !in_array($w['status'], ['Closed']))); @endphp
                    <select name="wo_id" x-model="woId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                        <option value="">Select work order</option>
                        @forelse($activeWOs as $wo)
                            <option value="{{ $wo['wo'] }}">{{ $wo['wo'] }} — {{ $wo['asset'] }} ({{ $wo['status'] }})</option>
                        @empty
                            <option value="" disabled>No active work orders — create one in Assignments first</option>
                        @endforelse
                    </select>
                    @if(count($activeWOs) === 0)
                        <p class="text-xs text-gray-400 mt-1">
                            <a href="{{ route('tenant.maintenance.assignments', $organization->org_slug) }}" class="text-amber-600 underline">Go to Assignments</a> to create a work order first.
                        </p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Spare Part / Material <span class="text-red-500">*</span></label>
                    @if(count($parts) > 0)
                        <select name="part_code" x-model="partCode" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none"
                            @change="
                                const opt = $event.target.options[$event.target.selectedIndex];
                                partName = opt.dataset.name || '';
                                partUnit = opt.dataset.unit || 'Nos';
                            ">
                            <option value="">Select part</option>
                            @foreach($parts as $p)
                                <option value="{{ $p['code'] }}"
                                    data-name="{{ $p['name'] }}"
                                    data-unit="{{ $p['unit'] ?? 'Nos' }}"
                                    data-stock="{{ $p['stock'] }}">
                                    {{ $p['name'] }} ({{ $p['code'] }}) — {{ $p['stock'] }} {{ $p['unit'] ?? 'Nos' }} in stock
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="part_code" x-model="partCode" required placeholder="Part code e.g. SP-001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                        <p class="text-xs text-gray-400 mt-1">No parts in inventory. <a href="{{ route('tenant.maintenance.spare-parts', $organization->org_slug) }}" class="text-purple-600 underline">Add spare parts first</a>.</p>
                    @endif
                </div>
                <input type="hidden" name="part_name" :value="partName">
                <input type="hidden" name="unit" :value="partUnit">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Required <span class="text-red-500">*</span></label>
                    <input type="number" name="qty" required min="1" value="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                </div>
                <div class="flex items-end">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700 w-full">
                        <span class="material-symbols-outlined text-sm align-middle">info</span>
                        System will automatically check stock. If available → "Pending Issue". If not → "Procurement Required".
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Submit Request</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    @php
        $pendingIssue   = count(array_filter($matRequests, fn($m) => $m['status'] === 'Pending Issue'));
        $procRequired   = count(array_filter($matRequests, fn($m) => $m['status'] === 'Procurement Required'));
        $issued         = count(array_filter($matRequests, fn($m) => $m['status'] === 'Issued'));
    @endphp
    @if(count($matRequests) > 0)
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-700">{{ $pendingIssue }}</p>
            <p class="text-xs text-amber-600 font-semibold mt-1">Pending Issue</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700">{{ $procRequired }}</p>
            <p class="text-xs text-red-600 font-semibold mt-1">Procurement Required</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $issued }}</p>
            <p class="text-xs text-green-600 font-semibold mt-1">Issued</p>
        </div>
    </div>
    @endif

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
                    @forelse($matRequests as $mr)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50 {{ $mr['status'] === 'Procurement Required' ? 'bg-red-50' : '' }}">
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $mr['id'] }}</td>
                            <td class="py-3 px-4 font-medium text-amber-700">{{ $mr['wo_id'] }}</td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-900">{{ $mr['part_name'] ?: $mr['part_code'] }}</p>
                                <p class="text-xs text-gray-400">{{ $mr['part_code'] }}</p>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $mr['qty'] }} {{ $mr['unit'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $mr['raised_on'] }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $sc = [
                                        'Pending Issue'         => 'bg-amber-100 text-amber-700',
                                        'Procurement Required'  => 'bg-red-100 text-red-700',
                                        'Issued'                => 'bg-green-100 text-green-700',
                                    ][$mr['status']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $mr['status'] }}</span>
                                @if($mr['status'] === 'Procurement Required')
                                    <p class="text-xs text-red-500 mt-0.5">Stock insufficient — order required</p>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($mr['status'] === 'Pending Issue')
                                    <form method="POST" action="{{ route('tenant.maintenance.material-requests.issue', [$organization->org_slug, $mr['id']]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-sm">output</span> Issue from Stock
                                        </button>
                                    </form>
                                @elseif($mr['status'] === 'Procurement Required')
                                    <a href="{{ route('tenant.maintenance.spare-parts', $organization->org_slug) }}"
                                        class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">shopping_cart</span> Receive Stock
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        Issued {{ $mr['issued_on'] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">inventory_2</span>
                                No material requests yet. Click "Request Material" to identify materials needed for a work order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
