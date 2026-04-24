@extends('layouts.production')

@section('title', 'Capacity Planning - ' . $organization->org_name)
@section('page-title', 'Capacity Planning')

@section('content')
<div x-data="capacityPage()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Capacity Planning</h2>
            <p class="text-sm text-gray-500 mt-1">Define and monitor daily production capacity per product</p>
        </div>
        <button @click="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Capacity
        </button>
    </div>

    <!-- Month Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex items-center gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1">Month</label>
            <input type="month" x-model="month" @change="load()"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div class="flex items-end">
            <button @click="load()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold">
                Refresh
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Total Capacity</p>
            <p class="text-3xl font-black text-gray-900" x-text="summary.total_capacity.toFixed(0)">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Utilized</p>
            <p class="text-3xl font-black text-orange-600" x-text="summary.total_utilized.toFixed(0)">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Available</p>
            <p class="text-3xl font-black text-emerald-600" x-text="(summary.total_capacity - summary.total_utilized).toFixed(0)">0</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Utilization %</p>
            <div class="flex items-baseline gap-1">
                <p class="text-3xl font-black" :class="summary.overall_pct > 90 ? 'text-red-600' : summary.overall_pct > 70 ? 'text-amber-600' : 'text-emerald-600'"
                    x-text="summary.overall_pct + '%'">0%</p>
            </div>
        </div>
    </div>

    <!-- Capacity Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-black text-gray-900 uppercase tracking-widest text-xs">Capacity Records</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Shift</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Daily Capacity</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Utilized</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Available</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Utilization</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Remarks</th>
                        <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="row in capacityData" :key="row.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 text-sm" x-text="row.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="row.product_code"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="row.capacity_date"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded uppercase"
                                    :class="{
                                        'bg-blue-100 text-blue-700': row.shift === 'SINGLE',
                                        'bg-purple-100 text-purple-700': row.shift === 'DOUBLE',
                                        'bg-orange-100 text-orange-700': row.shift === 'TRIPLE'
                                    }"
                                    x-text="row.shift"></span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="row.daily_capacity.toFixed(0)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-orange-600" x-text="row.utilized_capacity.toFixed(0)"></td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600" x-text="row.available_capacity.toFixed(0)"></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all"
                                            :class="row.utilization_pct > 90 ? 'bg-red-500' : row.utilization_pct > 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                                            :style="`width: ${Math.min(row.utilization_pct, 100)}%`"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-700 w-10 text-right" x-text="row.utilization_pct + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="row.remarks || '—'"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openModal(row)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <button @click="deleteRecord(row.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="capacityData.length === 0">
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">speed</span>
                                <p class="text-sm font-bold">No capacity records for this month. Click "Add Capacity" to create one.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-lg w-full mx-4 shadow-2xl" @click.away="showModal = false">
            <h3 class="text-lg font-bold text-gray-900 mb-5" x-text="form.id ? 'Edit Capacity Record' : 'Add Capacity Record'"></h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Product <span class="text-gray-400 font-normal">(leave blank for overall)</span></label>
                    <select x-model="form.product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">— Overall Capacity —</option>
                        <template x-for="p in products" :key="p.id">
                            <option :value="p.id" x-text="p.product_code + ' — ' + p.product_name"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                        <input type="date" x-model="form.capacity_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Shift</label>
                        <select x-model="form.shift" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="SINGLE">Single</option>
                            <option value="DOUBLE">Double</option>
                            <option value="TRIPLE">Triple</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Daily Capacity (units)</label>
                        <input type="number" x-model="form.daily_capacity" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Utilized Capacity</label>
                        <input type="number" x-model="form.utilized_capacity" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks</label>
                    <textarea x-model="form.remarks" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
            </div>

            <div x-show="formError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="formError"></div>

            <div class="flex gap-3 mt-6">
                <button @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold">
                    Cancel
                </button>
                <button @click="saveCapacity()" :disabled="saving"
                    class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold disabled:opacity-50">
                    <span x-text="saving ? 'Saving...' : (form.id ? 'Update' : 'Save')"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function capacityPage() {
    return {
        month: new Date().toISOString().slice(0, 7),
        capacityData: [],
        products: [],
        summary: { total_capacity: 0, total_utilized: 0, overall_pct: 0, records: 0 },
        showModal: false,
        saving: false,
        formError: '',
        form: {
            id: null,
            product_id: '',
            capacity_date: new Date().toISOString().split('T')[0],
            daily_capacity: '',
            utilized_capacity: 0,
            shift: 'SINGLE',
            remarks: ''
        },

        async init() {
            await Promise.all([this.load(), this.loadProducts()]);
        },

        async load() {
            try {
                const res = await fetch(`/api/v1/production-planning/capacity?month=${this.month}`);
                const result = await res.json();
                if (result.success) {
                    this.capacityData = result.data;
                    this.summary = result.summary;
                }
            } catch (e) {
                console.error('Failed to load capacity', e);
            }
        },

        async loadProducts() {
            try {
                const res = await fetch('/api/v1/products?per_page=500&is_active=true');
                const result = await res.json();
                if (result.success) {
                    this.products = result.data?.products ?? [];
                }
            } catch (e) {
                console.error('Failed to load products', e);
            }
        },

        openModal(row = null) {
            this.formError = '';
            if (row) {
                this.form = {
                    id: row.id,
                    product_id: row.product_id ?? '',
                    capacity_date: row.capacity_date,
                    daily_capacity: row.daily_capacity,
                    utilized_capacity: row.utilized_capacity,
                    shift: row.shift,
                    remarks: row.remarks ?? ''
                };
            } else {
                this.form = {
                    id: null,
                    product_id: '',
                    capacity_date: new Date().toISOString().split('T')[0],
                    daily_capacity: '',
                    utilized_capacity: 0,
                    shift: 'SINGLE',
                    remarks: ''
                };
            }
            this.showModal = true;
        },

        async saveCapacity() {
            this.formError = '';
            if (!this.form.capacity_date || !this.form.daily_capacity) {
                this.formError = 'Date and Daily Capacity are required.';
                return;
            }
            this.saving = true;
            try {
                const res = await fetch('/api/v1/production-planning/capacity', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const result = await res.json();
                if (result.success) {
                    this.showModal = false;
                    await this.load();
                } else {
                    this.formError = result.message ?? 'Failed to save.';
                }
            } catch (e) {
                this.formError = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async deleteRecord(id) {
            if (!confirm('Delete this capacity record?')) return;
            try {
                const res = await fetch(`/api/v1/production-planning/capacity/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });
                const result = await res.json();
                if (result.success) {
                    await this.load();
                } else {
                    alert(result.message ?? 'Failed to delete.');
                }
            } catch (e) {
                alert('Network error.');
            }
        }
    }
}
</script>
@endsection
