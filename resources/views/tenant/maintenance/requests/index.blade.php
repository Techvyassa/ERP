@extends('layouts.maintenance')

@section('title', 'Maintenance Requests - ' . $organization->org_name)
@section('page-title', 'Maintenance Requests')

@section('content')
<div x-data="{ showForm: false }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Maintenance Requests</h2>
            <p class="text-sm text-gray-500">Submit and track maintenance requests</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            New Request
        </button>
    </div>

    <!-- Create Request Form -->
    <div x-show="showForm" x-cloak x-transition
         class="bg-white rounded-xl border border-amber-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">description</span>
            New Maintenance Request
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.requests.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset / Equipment <span class="text-red-500">*</span></label>
                    @if(count($assets) > 0)
                        <select name="asset" id="assetSel" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none"
                            onchange="document.getElementById('assetCodeField').value = this.options[this.selectedIndex].dataset.code || ''">
                            <option value="">Select asset</option>
                            @foreach($assets as $a)
                                <option value="{{ $a['name'] }}" data-code="{{ $a['code'] }}" {{ request('asset') === $a['name'] ? 'selected' : '' }}>
                                    {{ $a['code'] }} — {{ $a['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="asset_code" id="assetCodeField">
                    @else
                        <input type="text" name="asset" required placeholder="e.g. Air Compressor, CNC Machine"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
                        <p class="text-xs text-gray-400 mt-1">No assets registered. <a href="{{ route('tenant.maintenance.assets', $organization->org_slug) }}" class="text-blue-500 underline">Add assets first</a> for better tracking.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
                        <option value="">Select priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Description <span class="text-red-500">*</span></label>
                    <textarea name="issue" required rows="3" placeholder="Describe the issue or maintenance needed..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                    Submit Request
                </button>
                <button type="button" @click="showForm = false"
                    class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Request ID</th>
                        <th class="py-3 px-4 font-semibold">Asset</th>
                        <th class="py-3 px-4 font-semibold">Issue</th>
                        <th class="py-3 px-4 font-semibold">Priority</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Raised By</th>
                        <th class="py-3 px-4 font-semibold">Raised On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $row)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $row['id'] }}</td>
                            <td class="py-3 px-4">{{ $row['asset'] }}</td>
                            <td class="py-3 px-4 text-gray-600 max-w-xs truncate">{{ $row['issue'] ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $pc = ['High' => 'bg-red-100 text-red-700', 'Medium' => 'bg-yellow-100 text-yellow-700', 'Low' => 'bg-green-100 text-green-700'][$row['priority']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $pc }}">{{ $row['priority'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $sc = ['Pending Approval' => 'bg-amber-100 text-amber-700', 'Approved' => 'bg-blue-100 text-blue-700', 'Assigned' => 'bg-purple-100 text-purple-700', 'Rejected' => 'bg-red-100 text-red-700', 'Closed' => 'bg-green-100 text-green-700'][$row['status']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $row['status'] }}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $row['raised_by'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $row['raised_on'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">description</span>
                                No maintenance requests yet. Click "New Request" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
