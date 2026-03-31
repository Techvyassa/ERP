@extends('layouts.warehouse')

@section('title', 'Execute Putaway - ' . $organization->org_name)
@section('page-title', 'Execute Putaway Task')

@section('content')
<div x-data="executePutawayData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Execute Putaway Task</h2>
            <p class="text-gray-500 text-sm">Scan bins and confirm material placement</p>
        </div>
        <a href="/org/{{ $organization->org_slug }}/warehouse/putaway"
            class="px-5 py-2.5 border border-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Tasks
        </a>
    </div>

    <!-- Task Details Card -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6" x-show="task">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Task Number</p>
                <p class="text-lg font-bold text-primary" x-text="task?.task_number || '—'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Material</p>
                <p class="font-semibold text-gray-900" x-text="task?.material?.material_name || '—'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Quantity</p>
                <p class="font-semibold text-gray-900">
                    <span x-text="task?.quantity || '0.000'"></span>
                    <span x-text="task?.uom?.uom_code || ''"></span>
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">GRN Number</p>
                <p class="font-semibold text-gray-900" x-text="task?.grn_line_item?.grn_number || '—'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Status</p>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(task?.status)" x-text="task?.status?.replace(/_/g,' ')"></span>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Batch Number</p>
                <p class="font-semibold text-gray-900" x-text="task?.batch_number || '—'"></p>
            </div>
            <!-- <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Current Bin</p>
                <p class="font-semibold text-gray-900" x-text="task?.source_bin?.bin_code || '—'"></p>
            </div> -->
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Assigned To</p>
                <p class="font-semibold text-gray-900" x-text="task?.assigned_operator?.full_name || '—'"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Destination Bin</p>
                <p class="font-semibold text-blue-600" x-text="task?.destination_bin?.bin_code || 'Not Scanned'"></p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4 border-t border-gray-200">
            <button x-show="task?.status === 'PENDING'" @click="startPutaway()"
                class="px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <span class="material-symbols-outlined">play_arrow</span> Start Putaway
            </button>
            <button x-show="task?.status === 'IN_PROGRESS'" @click="openBinScanModal()"
                class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
                <span class="material-symbols-outlined">qr_code_2</span> Scan Bin
            </button>
            <button x-show="task?.status === 'IN_PROGRESS'" @click="openCompleteModal()"
                class="px-5 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span> Complete Putaway
            </button>
            <button x-show="['PENDING','IN_PROGRESS'].includes(task?.status)" @click="openCancelModal()"
                class="px-5 py-2.5 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                <span class="material-symbols-outlined">cancel</span> Cancel Task
            </button>
        </div>
    </div>

    <!-- Complete Modal -->
    <div x-show="showCompleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCompleteModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Complete Putaway</h3>
                    <button @click="showCompleteModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="completePutaway()" class="p-6 space-y-5">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800">
                        Review the task details and confirm the quantity before completing the putaway.
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Putaway Qty *</label>
                        <input type="number" step="0.001" min="0.001" x-model="completeQty" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            :max="task?.quantity || null"
                            placeholder="Enter quantity to put away">
                        <p class="text-xs text-gray-500 mt-1">
                            Maximum: <span x-text="formatQty(task?.quantity)"></span>
                            <span x-text="task?.uom?.uom_code || ''"></span>
                        </p>
                    </div>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Review Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Task Number</p>
                                <p class="font-semibold text-gray-900" x-text="task?.task_number || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Material</p>
                                <p class="font-semibold text-gray-900" x-text="task?.material?.material_name || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Batch Number</p>
                                <p class="font-semibold text-gray-900" x-text="task?.batch_number || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Destination Bin</p>
                                <p class="font-semibold text-blue-600" x-text="task?.destination_bin?.bin_code || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Task Quantity</p>
                                <p class="font-semibold text-gray-900">
                                    <span x-text="formatQty(task?.quantity)"></span>
                                    <span x-text="task?.uom?.uom_code || ''"></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Entered Quantity</p>
                                <p class="font-semibold text-gray-900">
                                    <span x-text="formatQty(completeQty)"></span>
                                    <span x-text="task?.uom?.uom_code || ''"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showCompleteModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Back</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
                            <span x-show="!saving">Confirm Complete</span><span x-show="saving">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bin Scan Modal -->
    <div x-show="showBinScanModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showBinScanModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Scan Bin Location</h3>
                    <button @click="showBinScanModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitBinScan()" class="p-6 space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                        Scan or enter the bin location where this material will be placed.
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Bin Location *</label>
                        <input type="text" x-model="binScanInput" @keyup.enter="submitBinScan()" autofocus required
                            placeholder="Scan barcode or enter bin code..."
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks (Optional)</label>
                        <textarea x-model="binRemarks" rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Any notes about this placement..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showBinScanModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving">Confirm Bin</span><span x-show="saving">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showCancelModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Cancel Putaway Task</h3>
                    <button @click="showCancelModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="submitCancel()" class="p-6 space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                        Cancelling this putaway task. This action cannot be undone.
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Reason *</label>
                        <textarea x-model="cancelReason" required rows="3"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Provide a reason for cancellation"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showCancelModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Back</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 disabled:opacity-50">
                            <span x-show="!saving">Confirm Cancel</span><span x-show="saving">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    function executePutawayData() {
        const token = () => localStorage.getItem('access_token');
        const orgSlug = '{{ $organization->org_slug }}';
        const taskId = '{{ $taskId }}';
        const headers = () => ({
            'Authorization': `Bearer ${token()}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Org-Slug': orgSlug
        });

        return {
            task: null,
            loading: false,
            saving: false,
            showBinScanModal: false,
            showCompleteModal: false,
            showCancelModal: false,
            binScanInput: '',
            binRemarks: '',
            completeQty: '',
            cancelReason: '',

            async init() {
                await this.loadTask();
            },

            async loadTask() {
                this.loading = true;
                try {
                    const res = await fetch(`/api/v1/putaway-tasks/${taskId}`, {
                        headers: headers()
                    });
                    const data = await res.json();

                    if (data.success) {
                        this.task = data.data;
                    } else {
                        alert(data.message || 'Failed to load task');
                        window.location.href = `/org/${orgSlug}/warehouse/putaway`;
                    }
                } catch (e) {
                    console.error('Error loading task:', e);
                    alert('Failed to load task');
                    window.location.href = `/org/${orgSlug}/warehouse/putaway`;
                } finally {
                    this.loading = false;
                }
            },

            async startPutaway() {
                if (!confirm('Start putaway execution for this task?')) return;

                this.saving = true;
                try {
                    const res = await fetch(`/api/v1/putaway-tasks/${taskId}/start`, {
                        method: 'PATCH',
                        headers: headers(),
                        body: JSON.stringify({})
                    });
                    const data = await res.json();

                    if (data.success) {
                        await this.loadTask();
                        alert('Putaway started successfully');
                    } else {
                        alert(data.message || 'Failed to start putaway');
                    }
                } catch (e) {
                    console.error('Error starting putaway:', e);
                    alert('Failed to start putaway');
                } finally {
                    this.saving = false;
                }
            },

            openBinScanModal() {
                this.binScanInput = '';
                this.binRemarks = '';
                this.showBinScanModal = true;
                setTimeout(() => document.querySelector('input[placeholder*="Scan"]')?.focus(), 100);
            },

            openCompleteModal() {
                if (!this.task?.destination_bin_id) {
                    alert('Please scan a destination bin location before completing the putaway.');
                    this.openBinScanModal();
                    return;
                }

                this.completeQty = this.formatQty(this.task?.quantity);
                this.showCompleteModal = true;
            },

            async submitBinScan() {
                if (!this.binScanInput.trim()) {
                    alert('Please enter a bin location');
                    return;
                }

                this.saving = true;
                try {
                    const res = await fetch(`/api/v1/putaway-tasks/${taskId}/scan-bin`, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({
                            bin_code: this.binScanInput,
                            remarks: this.binRemarks
                        })
                    });
                    const data = await res.json();

                    if (data.success) {
                        this.showBinScanModal = false;
                        await this.loadTask();
                        alert('Bin scanned successfully');
                    } else {
                        alert(data.message || 'Failed to scan bin');
                    }
                } catch (e) {
                    console.error('Error scanning bin:', e);
                    alert('Failed to scan bin');
                } finally {
                    this.saving = false;
                }
            },

            async completePutaway() {
                if (!this.task?.destination_bin_id) {
                    alert('Please scan a destination bin location before completing the putaway.');
                    this.openBinScanModal();
                    return;
                }

                const taskQty = parseFloat(this.task?.quantity) || 0;
                const enteredQty = parseFloat(this.completeQty);
                if (!Number.isFinite(enteredQty) || enteredQty <= 0) {
                    alert('Please enter a valid putaway quantity.');
                    return;
                }

                if (enteredQty > taskQty) {
                    alert(`Putaway quantity cannot exceed task quantity of ${this.formatQty(taskQty)} ${this.task.uom?.uom_code || 'UNT'}.`);
                    return;
                }

                this.saving = true;
                try {
                    const res = await fetch(`/api/v1/putaway-tasks/${taskId}/complete`, {
                        method: 'PATCH',
                        headers: headers(),
                        body: JSON.stringify({
                            destination_bin_id: this.task.destination_bin_id,
                            putaway_lines: [
                                {
                                    line_number: 1,
                                    batch_number: this.task.batch_number,
                                    quantity: enteredQty
                                }
                            ]
                        })
                    });
                    const data = await res.json();

                    if (data.success) {
                        this.showCompleteModal = false;
                        alert('Putaway completed successfully');
                        window.location.href = `/org/${orgSlug}/warehouse/putaway`;
                    } else {
                        alert(data.message || 'Failed to complete putaway');
                    }
                } catch (e) {
                    console.error('Error completing putaway:', e);
                    alert('Failed to complete putaway');
                } finally {
                    this.saving = false;
                }
            },

            openCancelModal() {
                this.cancelReason = '';
                this.showCancelModal = true;
            },

            async submitCancel() {
                if (!this.cancelReason.trim()) {
                    alert('Please provide a reason');
                    return;
                }

                this.saving = true;
                try {
                    const res = await fetch(`/api/v1/putaway-tasks/${taskId}/cancel`, {
                        method: 'PATCH',
                        headers: headers(),
                        body: JSON.stringify({
                            reason: this.cancelReason
                        })
                    });
                    const data = await res.json();

                    if (data.success) {
                        alert('Putaway task cancelled successfully');
                        window.location.href = `/org/${orgSlug}/warehouse/putaway`;
                    } else {
                        alert(data.message || 'Failed to cancel task');
                    }
                } catch (e) {
                    console.error('Error cancelling task:', e);
                    alert('Failed to cancel task');
                } finally {
                    this.saving = false;
                }
            },

            statusClass(status) {
                const classes = {
                    'PENDING': 'bg-amber-100 text-amber-800',
                    'IN_PROGRESS': 'bg-blue-100 text-blue-800',
                    'COMPLETED': 'bg-green-100 text-green-800',
                    'CANCELLED': 'bg-red-100 text-red-800',
                };
                return classes[status] || 'bg-gray-100 text-gray-800';
            },

            formatQty(value) {
                const qty = parseFloat(value);
                return Number.isFinite(qty) ? qty.toFixed(3) : '0.000';
            }
        };
    }
</script>
@endsection
