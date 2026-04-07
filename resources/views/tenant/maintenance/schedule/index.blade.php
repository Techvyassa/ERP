@extends('layouts.maintenance')

@section('title', 'PM Schedule - ' . $organization->org_name)
@section('page-title', 'PM Schedule')

@section('content')
<div x-data="{
    showForm: false,
    doneModal: null,
    doneNotes: '',
    materials: [{ name: '', qty: 1, unit: 'Nos' }],
    addMat() { this.materials.push({ name: '', qty: 1, unit: 'Nos' }); },
    removeMat(i) { if (this.materials.length > 1) this.materials.splice(i, 1); }
}" x-init="
    const urlAsset = new URLSearchParams(window.location.search).get('asset');
    if (urlAsset) { showForm = true; $nextTick(() => { const el = document.getElementById('assetSelect'); if(el) el.value = urlAsset; }); }
">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Preventive Maintenance Schedule</h2>
            <p class="text-sm text-gray-500">Plan PM tasks with required materials — materials auto-deducted from spare parts on completion</p>
        </div>
        <button @click="showForm = !showForm"
            class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-base">add</span>
            Schedule PM Task
        </button>
    </div>

    <!-- Schedule Form -->
    <div x-show="showForm" x-cloak x-transition class="bg-white rounded-xl border border-green-200 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-green-500">calendar_month</span>
            New PM Task
        </h3>
        <form method="POST" action="{{ route('tenant.maintenance.schedule.store', $organization->org_slug) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintanance Name <span class="text-red-500">*</span></label>
                    @if(count($assets) > 0)
                        <select name="asset" id="assetSelect" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                            <option value="">Select asset</option>
                            @foreach($assets as $a)
                                <option value="{{ $a['name'] }}" {{ request('asset') === $a['name'] ? 'selected' : '' }}>{{ $a['code'] }} — {{ $a['name'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="asset" id="assetSelect" required placeholder="e.g. Air Compressor"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                        <p class="text-xs text-gray-400 mt-1">No assets registered yet — type manually or <a href="{{ route('tenant.maintenance.assets', $organization->org_slug) }}" class="text-blue-500 underline">add assets first</a>.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Description <span class="text-red-500">*</span></label>
                    <input type="text" name="task" required placeholder="e.g. Oil change, Filter cleaning"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency <span class="text-red-500">*</span></label>
                    <select name="frequency" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                        <option value="">Select</option>
                        <option>Daily</option><option>Weekly</option><option>Monthly</option>
                        <option>Quarterly</option><option>Half-Yearly</option><option>Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                    <input type="text" name="assigned_to" placeholder="Technician name"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Next Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="next_due" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Est. Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 2 hours"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none">
                </div>
            </div>

            <!-- Materials Required -->
            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-purple-500">inventory_2</span>
                        Materials / Spare Parts Required
                        <span class="text-xs font-normal text-gray-400">(auto-deducted from stock on completion)</span>
                    </h4>
                    <button type="button" @click="addMat()"
                        class="text-xs text-green-600 hover:text-green-800 font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Add Row
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(mat, i) in materials" :key="i">
                        <div class="flex gap-2 items-center">
                            <div class="flex-1">
                                @if(count($parts) > 0)
                                    <select :name="'mat_name[]'" x-model="mat.name"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                                        <option value="">Select spare part</option>
                                        @foreach($parts as $p)
                                            <option value="{{ $p['name'] }}">{{ $p['name'] }} ({{ $p['stock'] }} {{ $p['unit'] ?? 'Nos' }} in stock)</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" :name="'mat_name[]'" x-model="mat.name" placeholder="Material / part name"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                                @endif
                            </div>
                            <input type="number" :name="'mat_qty[]'" x-model="mat.qty" min="1" placeholder="Qty"
                                class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                            <input type="text" :name="'mat_unit[]'" x-model="mat.unit" placeholder="Unit"
                                class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                            <button type="button" @click="removeMat(i)" class="text-red-400 hover:text-red-600">
                                <span class="material-symbols-outlined text-base">remove_circle</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">Save Schedule</button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Schedule Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-3 px-4 font-semibold">PM ID</th>
                        <th class="py-3 px-4 font-semibold">Maintanance Name</th>
                        <th class="py-3 px-4 font-semibold">Task</th>
                        <th class="py-3 px-4 font-semibold">Frequency</th>
                        <th class="py-3 px-4 font-semibold">Assigned To</th>
                        <th class="py-3 px-4 font-semibold">Materials</th>
                        <th class="py-3 px-4 font-semibold">Next Due</th>
                        <th class="py-3 px-4 font-semibold">Last Done</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $pm)
                        @php
                            $isOverdue  = isset($pm['next_due']) && $pm['next_due'] < date('Y-m-d') && $pm['status'] !== 'Done';
                            $isDueToday = isset($pm['next_due']) && $pm['next_due'] === date('Y-m-d') && $pm['status'] !== 'Done';
                        @endphp
                        <tr class="border-b last:border-b-0 hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50' : '' }}">
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $pm['id'] }}</td>
                            <td class="py-3 px-4">{{ $pm['asset'] }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $pm['task'] }}</td>
                            <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">{{ $pm['frequency'] }}</span></td>
                            <td class="py-3 px-4 text-gray-600">{{ $pm['assigned_to'] ?: '-' }}</td>
                            <td class="py-3 px-4">
                                @if(!empty($pm['materials']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($pm['materials'] as $mat)
                                            <span class="bg-purple-50 border border-purple-200 text-purple-700 text-xs px-2 py-0.5 rounded">
                                                {{ $mat['name'] }} × {{ $mat['qty'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">None</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 {{ $isOverdue ? 'text-red-600 font-semibold' : ($isDueToday ? 'text-amber-600 font-semibold' : 'text-gray-600') }}">
                                {{ $pm['next_due'] }}
                                @if($isOverdue)<span class="text-xs ml-1">(Overdue)</span>@endif
                                @if($isDueToday)<span class="text-xs ml-1">(Today)</span>@endif
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $pm['last_done'] ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @php $sc = ['Scheduled'=>'bg-blue-100 text-blue-700','Done'=>'bg-green-100 text-green-700','Overdue'=>'bg-red-100 text-red-700'][$pm['status']] ?? 'bg-gray-100 text-gray-700'; @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $pm['status'] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($pm['status'] !== 'Done')
                                    <button @click="doneModal = '{{ $pm['id'] }}'; doneNotes = ''"
                                        class="flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">check</span> Mark Done
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-400" colspan="10">
                                <span class="material-symbols-outlined text-4xl block mb-2">calendar_month</span>
                                No PM tasks scheduled yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mark Done Modal -->
    <div x-show="doneModal !== null" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="doneModal = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-100 p-2 rounded-lg"><span class="material-symbols-outlined text-green-600 text-xl">task_alt</span></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Mark PM Task as Done</h3>
                    <p class="text-sm text-gray-500" x-text="doneModal + ' — materials will be auto-deducted from spare parts stock'"></p>
                </div>
            </div>
            <form :action="'/org/{{ $organization->org_slug }}/maintenance/schedule/' + doneModal + '/done'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Completion Notes</label>
                    <textarea name="notes" x-model="doneNotes" rows="3" placeholder="Any observations..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 outline-none resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 rounded-lg text-sm transition-colors">Confirm Done</button>
                    <button type="button" @click="doneModal = null" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
