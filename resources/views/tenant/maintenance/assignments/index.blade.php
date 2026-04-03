@extends('layouts.maintenance')

@section('title', 'Assignments - ' . $organization->org_name)
@section('page-title', 'Assignments')

@section('content')
<div x-data="{ showAssignForm: false, statusModal: null, newStatus: '' }">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Assignments</h2>
            <p class="text-sm text-gray-500">Assign approved requests to technicians and track work orders</p>
        </div>
        @if(count($approved) > 0)
        <button @click="showAssignForm = !showAssignForm"
            class="flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">assignment_ind</span>
            Assign Technician
        </button>
        @endif
    </div>

    <!-- Assign Form -->
    <div x-show="showAssignForm" x-cloak x-transition
         class="bg-white rounded-xl border border-amber-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">assignment_ind</span>
            Create Work Order & Assign Technician
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.assignments.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approved Request <span class="text-red-500">*</span></label>
                    <select name="request_id" id="requestSelect" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none"
                        onchange="fillAsset(this)">
                        <option value="">Select approved request</option>
                        @foreach($approved as $req)
                            <option value="{{ $req['id'] }}" data-asset="{{ $req['asset'] }}" data-priority="{{ $req['priority'] }}">
                                {{ $req['id'] }} — {{ $req['asset'] }} ({{ $req['priority'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset</label>
                    <input type="text" name="asset" id="assetField" readonly
                        class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm outline-none text-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Technician Name <span class="text-red-500">*</span></label>
                    <input type="text" name="technician" required placeholder="e.g. Ravi Kumar"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" required min="{{ date('Y-m-d') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any special instructions for the technician..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                    Create Work Order
                </button>
                <button type="button" @click="showAssignForm = false"
                    class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Approved Requests Awaiting Assignment -->
    @if(count($approved) > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-amber-800 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-base">pending_actions</span>
            {{ count($approved) }} approved request(s) awaiting assignment
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($approved as $req)
                <span class="bg-white border border-amber-300 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">
                    {{ $req['id'] }} — {{ $req['asset'] }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Work Orders Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">Active Work Orders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">Work Order</th>
                        <th class="py-3 px-4 font-semibold">Request</th>
                        <th class="py-3 px-4 font-semibold">Maintanance Name</th>
                        <th class="py-3 px-4 font-semibold">Technician</th>
                        <th class="py-3 px-4 font-semibold">Due Date</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $wo)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $wo['wo'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $wo['mr_id'] ?? '-' }}</td>
                            <td class="py-3 px-4">{{ $wo['asset'] }}</td>
                            <td class="py-3 px-4">{{ $wo['technician'] }}</td>
                            <td class="py-3 px-4 {{ isset($wo['due']) && $wo['due'] < date('Y-m-d') && $wo['status'] !== 'Closed' ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                {{ $wo['due'] ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $sc = ['Assigned' => 'bg-blue-100 text-blue-700', 'In Progress' => 'bg-amber-100 text-amber-700', 'Completed' => 'bg-green-100 text-green-700', 'Closed' => 'bg-gray-100 text-gray-600'][$wo['status']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $wo['status'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if(!in_array($wo['status'], ['Closed']))
                                <button @click="statusModal = '{{ $wo['wo'] }}'; newStatus = '{{ $wo['status'] }}'"
                                    class="text-xs text-amber-600 hover:text-amber-800 font-semibold underline">
                                    Change Status
                                </button>
                                @else
                                <span class="text-xs text-gray-400">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="7">
                                <span class="material-symbols-outlined text-4xl block mb-2">assignment_ind</span>
                                No work orders yet. Approve requests first, then assign technicians.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div x-show="statusModal !== null" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="statusModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h3 class="font-semibold text-gray-900 mb-4">Update Work Order Status</h3>
            <p class="text-sm text-gray-500 mb-4" x-text="'Work Order: ' + statusModal"></p>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/assignments/' + statusModal + '/update-status'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                    <select name="status" x-model="newStatus"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                        <option value="Assigned">Assigned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">
                        Update
                    </button>
                    <button type="button" @click="statusModal = null"
                        class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillAsset(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('assetField').value = opt.dataset.asset || '';
}
</script>
@endsection
