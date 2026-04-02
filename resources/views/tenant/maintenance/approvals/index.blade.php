@extends('layouts.maintenance')

@section('title', 'Maintenance Approvals - ' . $organization->org_name)
@section('page-title', 'Approvals')

@section('content')
<div x-data="{ modal: null, remarks: '' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Pending Approvals</h2>
        <p class="text-sm text-gray-500">Review and approve or reject maintenance requests</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Request ID</th>
                        <th class="py-3 px-4 font-semibold">Asset</th>
                        <th class="py-3 px-4 font-semibold">Issue</th>
                        <th class="py-3 px-4 font-semibold">Priority</th>
                        <th class="py-3 px-4 font-semibold">Raised By</th>
                        <th class="py-3 px-4 font-semibold">Submitted On</th>
                        <th class="py-3 px-4 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $row)
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
                            <td class="py-3 px-4 text-gray-600">{{ $row['raised_by'] ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $row['raised_on'] ?? '-' }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <button @click="modal = { id: '{{ $row['id'] }}', asset: '{{ $row['asset'] }}', action: 'approve' }; remarks = ''"
                                        class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">check</span> Approve
                                    </button>
                                    <button @click="modal = { id: '{{ $row['id'] }}', asset: '{{ $row['asset'] }}', action: 'reject' }; remarks = ''"
                                        class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">close</span> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">check_circle</span>
                                No pending approvals. All requests have been processed.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div x-show="modal !== null" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="modal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div :class="modal?.action === 'approve' ? 'bg-green-100' : 'bg-red-100'" class="p-2 rounded-lg">
                    <span class="material-symbols-outlined text-xl"
                          :class="modal?.action === 'approve' ? 'text-green-600' : 'text-red-600'"
                          x-text="modal?.action === 'approve' ? 'check_circle' : 'cancel'"></span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900" x-text="modal?.action === 'approve' ? 'Approve Request' : 'Reject Request'"></h3>
                    <p class="text-sm text-gray-500" x-text="'Request: ' + (modal?.id ?? '') + ' — ' + (modal?.asset ?? '')"></p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks (optional)</label>
                <textarea x-model="remarks" rows="3" placeholder="Add any remarks or notes..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none resize-none"></textarea>
            </div>

            <!-- Approve form -->
            <template x-if="modal?.action === 'approve'">
                <form :action="'/org/{{ $organization->org_slug }}/maintenance/approvals/' + modal.id + '/approve'" method="POST">
                    @csrf
                    <input type="hidden" name="remarks" :value="remarks">
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">
                            Confirm Approve
                        </button>
                        <button type="button" @click="modal = null"
                            class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </template>

            <!-- Reject form -->
            <template x-if="modal?.action === 'reject'">
                <form :action="'/org/{{ $organization->org_slug }}/maintenance/approvals/' + modal.id + '/reject'" method="POST">
                    @csrf
                    <input type="hidden" name="remarks" :value="remarks">
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">
                            Confirm Reject
                        </button>
                        <button type="button" @click="modal = null"
                            class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
