@extends('layouts.maintenance')

@section('title', 'Assets - ' . $organization->org_name)
@section('page-title', 'Assets')

@section('content')
<div x-data="{ showForm: false, historyAsset: null }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Maintanance  Register</h2>
            <p class="text-sm text-gray-500">Register equipment and track maintenance history</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            Add Asset
        </button>
    </div>

    <!-- Add Asset Form -->
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl border border-blue-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-500">precision_manufacturing</span>
            New Asset
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.assets.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintanance Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Air Compressor"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintanance Code</label>
                    <input type="text" name="code" placeholder="Auto-generated if blank"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                        <option value="">Select category</option>
                        <option>Machinery</option><option>Electrical</option><option>HVAC</option>
                        <option>Vehicles</option><option>IT Equipment</option><option>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g. Shop Floor A"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Make / Model</label>
                    <input type="text" name="model" placeholder="e.g. Atlas Copco GA15"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Installation Date</label>
                    <input type="date" name="installed_on" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Save Asset</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Assets Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Code</th>
                        <th class="py-3 px-4 font-semibold">Name</th>
                        <th class="py-3 px-4 font-semibold">Category</th>
                        <th class="py-3 px-4 font-semibold">Location</th>
                        <th class="py-3 px-4 font-semibold">Make / Model</th>
                        <th class="py-3 px-4 font-semibold">Installed</th>
                        <th class="py-3 px-4 font-semibold">Last Maintained</th>
                        <th class="py-3 px-4 font-semibold">WOs</th>
                        <th class="py-3 px-4 font-semibold">PM Tasks</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-blue-600">{{ $asset['code'] ?? '-' }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $asset['name'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $asset['category'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ ($asset['location'] ?? '') ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ ($asset['model'] ?? '') ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ ($asset['installed_on'] ?? '') ?: '-' }}</td>
                            <td class="py-3 px-4 {{ ($asset['last_maintained'] ?? null) ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $asset['last_maintained'] ?? 'Never' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">{{ $asset['wo_count'] ?? 0 }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">{{ $asset['pm_count'] ?? 0 }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">Active</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('tenant.maintenance.requests', $organization->org_slug) }}?asset={{ urlencode($asset['name'] ?? '') }}"
                                        class="text-xs text-amber-600 hover:text-amber-800 font-semibold underline">Raise MR</a>
                                    <a href="{{ route('tenant.maintenance.schedule', $organization->org_slug) }}?asset={{ urlencode($asset['name'] ?? '') }}"
                                        class="text-xs text-green-600 hover:text-green-800 font-semibold underline">Schedule PM</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="11">
                                <span class="material-symbols-outlined text-4xl block mb-2">precision_manufacturing</span>
                                No assets registered yet. Click "Add Asset" to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
