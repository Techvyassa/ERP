@extends('layouts.warehouse')

@section('title', 'Putaway - ' . $organization->org_name)
@section('page-title', 'Putaway Tasks')

@section('content')
<div x-data="putawayData()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Putaway Tasks</h2>
            <p class="text-gray-500 text-sm">Place accepted materials into warehouse bins — completes inward process</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Pending</p>
            <p class="text-3xl font-bold text-amber-500" x-text="counts.pending">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">In Progress</p>
            <p class="text-3xl font-bold text-blue-600" x-text="counts.in_progress">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Completed</p>
            <p class="text-3xl font-bold text-green-600" x-text="counts.completed">0</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Cancelled</p>
            <p class="text-3xl font-bold text-red-500" x-text="counts.cancelled">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select x-model="filters.status" @change="loadPutawayTasks()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All</option>
                    <option value="PENDING">Pending</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Material</label>
                <input type="text" x-model="filters.material" @change="loadPutawayTasks()" placeholder="Search material..."
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Assigned To</label>
                <select x-model="filters.assigned_to" @change="loadPutawayTasks()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Users</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.first_name + ' ' + user.last_name"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">Reset</button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Task ID</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Material</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Qty</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">GRN Number</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Assigned To</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && tasks.length === 0">
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No putaway tasks found</td></tr>
                    </template>
                    <template x-for="task in tasks" :key="task.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm" x-text="'PT-' + String(task.id).padStart(5, '0')"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="task.material?.material_name || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700">
                                <span x-text="task.quantity"></span> <span x-text="task.uom?.uom_code || 'UNT'"></span>
                            </td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="task.grn?.grn_number || '—'"></td>
                            <td class="py-3 px-5 text-sm text-gray-700" x-text="task.assigned_user?.first_name + ' ' + task.assigned_user?.last_name || '—'"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="statusClass(task.status)" x-text="task.status?.replace(/_/g,' ')"></span>
                            </td>
                            <td class="py-3 px-5 text-right flex items-center justify-end gap-2">
                                <button @click="executePutaway(task.id)" x-show="task.status === 'PENDING'" title="Start Putaway"
                                    class="text-blue-600 hover:text-blue-800">
                                    <span class="material-symbols-outlined text-lg">play_arrow</span>
                                </button>
                                <button @click="viewTask(task.id)" title="View Details"
                                    class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button x-show="task.status === 'PENDING'" @click="openCancelModal(task)" title="Cancel"
                                    class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined text-lg">cancel</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-3 flex items-center justify-between text-sm text-gray-600">
            <span>Showing <span x-text="pagination.from"></span>–<span x-text="pagination.to"></span> of <span x-text="pagination.total"></span></span>
            <div class="flex gap-2">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">Prev</button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">Next</button>
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
                        Cancelling putaway task. This action cannot be undone.
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
function putawayData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        tasks: [], users: [],
        loading: false, saving: false,
        showCancelModal: false,
        selectedTask: null,
        cancelReason: '',
        counts: { pending: 0, in_progress: 0, completed: 0, cancelled: 0 },
        filters: { status: '', material: '', assigned_to: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },

        async init() {
            await Promise.all([this.loadPutawayTasks(), this.loadUsers()]);
        },

        async loadPutawayTasks(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams({ page, per_page: 15 });
                if (this.filters.status) p.append('status', this.filters.status);
                if (this.filters.material) p.append('material', this.filters.material);
                if (this.filters.assigned_to) p.append('assigned_to', this.filters.assigned_to);

                const res = await fetch(`/api/v1/putaway-tasks?${p}`, { headers: headers() });
                const data = await res.json();
                
                if (data.success) {
                    this.tasks = data.data.putaway_tasks || [];
                    this.pagination = data.data.pagination || {};
                    this.updateCounts();
                }
            } catch (e) {
                console.error('Error loading putaway tasks:', e);
                alert('Failed to load putaway tasks');
            } finally {
                this.loading = false;
            }
        },

        async loadUsers() {
            try {
                const res = await fetch(`/api/v1/users`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.users = data.data || [];
                }
            } catch (e) {
                console.error('Error loading users:', e);
            }
        },

        updateCounts() {
            this.counts = {
                pending: this.tasks.filter(t => t.status === 'PENDING').length,
                in_progress: this.tasks.filter(t => t.status === 'IN_PROGRESS').length,
                completed: this.tasks.filter(t => t.status === 'COMPLETED').length,
                cancelled: this.tasks.filter(t => t.status === 'CANCELLED').length,
            };
        },

        async executePutaway(taskId) {
            window.location.href = `/org/{{ $organization->org_slug }}/warehouse/putaway/${taskId}`;
        },

        async viewTask(taskId) {
            window.location.href = `/org/{{ $organization->org_slug }}/warehouse/putaway/${taskId}`;
        },

        openCancelModal(task) {
            this.selectedTask = task;
            this.cancelReason = '';
            this.showCancelModal = true;
        },

        async submitCancel() {
            if (!this.selectedTask || !this.cancelReason.trim()) {
                alert('Please provide a reason');
                return;
            }

            this.saving = true;
            try {
                const res = await fetch(`/api/v1/putaway-tasks/${this.selectedTask.id}/cancel`, {
                    method: 'PATCH',
                    headers: headers(),
                    body: JSON.stringify({ reason: this.cancelReason })
                });
                const data = await res.json();
                
                if (data.success) {
                    this.showCancelModal = false;
                    await this.loadPutawayTasks();
                    alert('Putaway task cancelled successfully');
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

        resetFilters() {
            this.filters = { status: '', material: '', assigned_to: '' };
            this.loadPutawayTasks();
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadPutawayTasks(page);
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

        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('en-IN');
        }
    };
}
</script>
@endsection
