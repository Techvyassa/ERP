@extends('layouts.maintenance')

@section('title', 'Work Order Closure - ' . $organization->org_name)
@section('page-title', 'Closure')

@section('content')
<div x-data="{ closeModal: null, verifiedBy: '', notes: '' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Work Order Closure</h2>
        <p class="text-sm text-gray-500">Verify and close completed work orders</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Work Order</th>
                        <th class="py-3 px-4 font-semibold">Asset</th>
                        <th class="py-3 px-4 font-semibold">Technician</th>
                        <th class="py-3 px-4 font-semibold">Completed On</th>
                        <th class="py-3 px-4 font-semibold">Verified By</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($closures as $wo)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $wo['wo'] }}</td>
                            <td class="py-3 px-4">{{ $wo['asset'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $wo['technician'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $wo['closed_on'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $wo['verified_by'] ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $sc = ['Completed' => 'bg-blue-100 text-blue-700', 'Closed' => 'bg-green-100 text-green-700'][$wo['status']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $wo['status'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($wo['status'] === 'Completed')
                                    <button @click="closeModal = '{{ $wo['wo'] }}'; verifiedBy = ''; notes = ''"
                                        class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">task_alt</span> Close WO
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">verified</span> Closed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">task_alt</span>
                                No completed work orders yet. Mark assignments as "Completed" to close them here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Close WO Modal -->
    <div x-show="closeModal !== null" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="closeModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-2 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-xl">task_alt</span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Close Work Order</h3>
                    <p class="text-sm text-gray-500" x-text="'Work Order: ' + closeModal"></p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/closure/' + closeModal + '/close'" method="POST">
                @csrf
                <div class="space-y-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Verified By <span class="text-red-500">*</span></label>
                        <input type="text" name="verified_by" x-model="verifiedBy" required placeholder="Name of verifying person"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:border-green-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Closure Notes</label>
                        <textarea name="closure_notes" x-model="notes" rows="3" placeholder="Any notes about the closure..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:border-green-400 outline-none resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                        class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">
                        Confirm Close
                    </button>
                    <button type="button" @click="closeModal = null"
                        class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
