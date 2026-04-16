@extends('layouts.production')

@section('title', 'Production Order requestion')
@section('page-title', 'Production Order requestion')

@section('scripts')
@endsection

@section('content')
<div x-data="productionOrders('{{ $organization->org_slug }}')" x-init="init()">

    <!-- Variance Modal -->
    <div x-show="varianceModal.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeVarianceModal()"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl z-10 overflow-hidden border border-white/20"
                @click.stop>
                <div
                    class="bg-gradient-to-r from-indigo-700 via-indigo-800 to-indigo-950 px-8 py-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-xl border border-white/10">
                            <span class="material-symbols-outlined text-indigo-200 text-2xl">analytics</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white tracking-tight">Efficiency & Variance Analysis</h3>
                            <p class="text-[10px] text-indigo-200/50 font-black uppercase tracking-[0.2em]"
                                x-text="varianceModal.report?.order?.order_no || ''"></p>
                        </div>
                    </div>
                    <button @click="closeVarianceModal()"
                        class="w-10 h-10 flex items-center justify-center rounded-xl text-white/40 hover:text-white hover:bg-white/10 transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="px-8 py-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
                    <div x-show="varianceModal.loading" class="text-center py-12 text-gray-400">
                        <span
                            class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-3 text-indigo-600">progress_activity</span>
                        <p class="font-medium animate-pulse uppercase tracking-widest text-[10px]">Generating
                            Analytics...</p>
                    </div>

                    <template x-if="!varianceModal.loading && varianceModal.report">
                        <div class="space-y-8">
                            <!-- High Level Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div
                                    class="bg-slate-50 border border-slate-100 p-4 rounded-2xl transition-all hover:shadow-md">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">
                                        Target Quantity</p>
                                    <p class="text-xl font-black text-gray-900"
                                        x-text="varianceModal.report.production?.target_qty ?? '—'"></p>
                                </div>
                                <div
                                    class="bg-slate-50 border border-slate-100 p-4 rounded-2xl transition-all hover:shadow-md border-l-4 border-l-blue-500">
                                    <p
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 text-blue-600">
                                        Actual Produced</p>
                                    <p class="text-xl font-black text-gray-900"
                                        x-text="varianceModal.report.production?.actual_qty ?? '—'"></p>
                                </div>
                                <div
                                    class="bg-slate-50 border border-slate-100 p-4 rounded-2xl transition-all hover:shadow-md border-l-4 border-l-rose-500">
                                    <p
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1 text-rose-600">
                                        Rejected Qty</p>
                                    <p class="text-xl font-black text-gray-900"
                                        x-text="varianceModal.report.production?.rejected_qty ?? '—'"></p>
                                </div>
                                <div
                                    class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-tighter mb-1">
                                        Production Yield</p>
                                    <p class="text-xl font-black text-emerald-900"
                                        x-text="(varianceModal.report.production?.yield_percent ?? '0') + '%'"></p>
                                </div>
                            </div>

                            <!-- Materials Analysis -->
                            <div>
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="material-symbols-outlined text-indigo-600">inventory_2</span>
                                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-tighter">Raw Material
                                        Consumption Analysis</h4>
                                </div>
                                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                                    <table class="min-w-full text-[13px]">
                                        <thead>
                                            <tr
                                                class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                <th class="px-6 py-4 text-left">Material Identity</th>
                                                <th class="px-6 py-4 text-right">BOM Effective</th>
                                                <th class="px-6 py-4 text-right">Floor Consumed</th>
                                                <th class="px-6 py-4 text-right">Net Variance</th>
                                                <th class="px-6 py-4 text-right">Variance %</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <template x-for="line in varianceModal.report.rm_lines || []"
                                                :key="line.material_id">
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-gray-900 leading-none mb-1"
                                                            x-text="line.material_name"></div>
                                                        <div class="text-[10px] font-mono text-gray-400 uppercase tracking-tighter"
                                                            x-text="line.material_code"></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-medium text-gray-500"
                                                        x-text="line.bom_effective"></td>
                                                    <td class="px-6 py-4 text-right font-medium text-gray-500 underline decoration-gray-200 underline-offset-4"
                                                        x-text="line.actually_consumed"></td>
                                                    <td class="px-6 py-4 text-right font-black"
                                                        :class="parseFloat(line.variance) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                                        <span
                                                            x-text="parseFloat(line.variance) > 0 ? '+' : ''"></span><span
                                                            x-text="line.variance"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <span
                                                            class="inline-block px-2 py-0.5 rounded font-black text-[10px]"
                                                            :class="parseFloat(line.variance_percent) > 5 ? 'bg-rose-50 text-rose-700' : (parseFloat(line.variance_percent) < 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600')"
                                                            x-text="line.variance_percent + '%'">
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button @click="closeVarianceModal()"
                        class="px-8 py-2.5 bg-white border border-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl hover:bg-gray-100 transition-all shadow-sm">
                        Close Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div x-show="viewModal.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
        style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="viewModal.show = false"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl z-10 overflow-hidden border border-white/20"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" @click.stop>
                <div
                    class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-8 py-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-xl border border-white/10">
                            <span class="material-symbols-outlined text-production text-2xl">contract</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white tracking-tight"
                                x-text="viewModal.request?.request_no"></h3>
                            <p class="text-[10px] text-white/50 font-black uppercase tracking-[0.2em]"
                                x-text="viewModal.request?.product_name"></p>
                        </div>
                    </div>
                    <button @click="viewModal.show = false"
                        class="w-10 h-10 flex items-center justify-center rounded-xl text-white/40 hover:text-white hover:bg-white/10 transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="px-8 py-6 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <div x-show="viewModal.loading" class="text-center py-12 text-gray-400">
                        <span
                            class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-3 text-production">progress_activity</span>
                        <p class="font-medium animate-pulse">Retrieving order details...</p>
                    </div>

                    <template x-if="!viewModal.loading && viewModal.request">
                        <div class="space-y-6">
                            <!-- Order Metadata Cards -->
                            <div class="grid grid-cols-2 gap-4 text-sm font-bold">
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">
                                        Target Quantity</p>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg text-gray-900"
                                            x-text="viewModal.request.target_qty"></span>
                                        <span class="text-[10px] text-gray-400 uppercase font-black"
                                            x-text="viewModal.request.uom?.uom_name || viewModal.request.uom || ''"></span>
                                    </div>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-1">
                                        Planned Date</p>
                                    <p class="text-lg text-gray-900" x-text="viewModal.request.planned_date"></p>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-2">
                                        Stage</p>
                                    <span
                                        class="inline-flex px-3 py-1 text-[10px] font-black rounded-full shadow-sm ring-1 ring-inset uppercase tracking-[0.05em]"
                                        :class="{
                                                            'bg-orange-50 text-orange-700 ring-orange-100': viewModal.request.status === 'CONVERTED_TO_MIR',
                                                            'bg-indigo-50 text-indigo-700 ring-indigo-100': viewModal.request.status === 'CONVERTED_TO_ORDER',
                                                            'bg-rose-50 text-rose-700 ring-rose-100': viewModal.request.status === 'REJECTED'
                                                        }"
                                        x-text="viewModal.request.status === 'CONVERTED_TO_MIR' ? 'MIR GENERATED' : (viewModal.request.status === 'RECEIVED' ? 'RECEIVED' : viewModal.request.status)"></span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-2">
                                        Inventory Status (MIR)</p>
                                    <span
                                        class="inline-flex px-3 py-1 text-[10px] font-black rounded-full ring-1 ring-inset uppercase tracking-[0.05em]"
                                        :class="{
                                                            'bg-amber-50 text-amber-700 ring-amber-100': viewModal.request.mir_status === 'PENDING',
                                                            'bg-blue-50 text-blue-700 ring-blue-100': viewModal.request.mir_status === 'APPROVED',
                                                            'bg-indigo-50 text-indigo-700 ring-indigo-100': viewModal.request.mir_status === 'PARTIALLY_ISSUED',
                                                            'bg-emerald-50 text-emerald-700 ring-emerald-100': viewModal.request.mir_status === 'FULLY_ISSUED',
                                                            'bg-rose-50 text-rose-700 ring-rose-100': viewModal.request.mir_status === 'REJECTED',
                                                            'bg-gray-50 text-gray-400 ring-gray-100': !viewModal.request.mir_status
                                                        }" x-text="viewModal.request.mir_status || 'Not Generated'"></span>
                                </div>
                            </div>

                            <!-- Raw Materials Table -->
                            <div x-show="viewModal.rmLines.length > 0" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-production">layers</span>
                                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-tighter">Material
                                        Issue Manifest</h4>
                                </div>
                                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                                    <table class="min-w-full text-xs">
                                        <thead>
                                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                                <th
                                                    class="px-5 py-3 text-left font-black text-gray-400 uppercase tracking-[0.1em] text-[10px]">
                                                    Material</th>
                                                <th
                                                    class="px-5 py-3 text-right font-black text-gray-400 uppercase tracking-[0.1em] text-[10px]">
                                                    Required Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 font-medium">
                                            <template x-for="(line, idx) in viewModal.rmLines" :key="idx">
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-5 py-3.5">
                                                        <div class="font-bold text-gray-900"
                                                            x-text="line.material_name"></div>
                                                        <div class="text-[10px] font-mono text-gray-400 uppercase tracking-tight"
                                                            x-text="line.material_code"></div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right">
                                                        <div class="inline-flex flex-col items-end">
                                                            <div class="text-production font-black text-sm"
                                                                x-text="parseFloat(line.required_qty).toFixed(4)"></div>
                                                            <div class="text-[10px] font-black text-gray-400 uppercase"
                                                                x-text="line.uom?.uom_code || line.uom || ''"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 transition-all">
                    <!-- Workflow Actions in Modal -->
                    <template x-if="viewModal.request?.status === 'APPROVED' && !viewModal.request?.mir_id">
                        <button @click="convertToMir(viewModal.request); viewModal.show = false;"
                            class="px-6 py-2.5 bg-orange-500 text-white font-black text-xs uppercase tracking-widest rounded-xl hover:bg-orange-600 shadow-lg shadow-orange-200 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">inventory_2</span>
                            Generate MIR
                        </button>
                    </template>

                    <button @click="viewModal.show = false"
                        class="px-6 py-2.5 bg-white border border-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl hover:bg-gray-100 transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg z-10"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" @click.stop>

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">New Production requestion</h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Form -->
                <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                    <!-- Product -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <select x-model="form.product_id" @change="onProductChange()"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Product</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id"
                                    x-text="p.product_name + (p.product_code ? ' (' + p.product_code + ')' : '')">
                                </option>
                            </template>
                        </select>
                    </div>

                    <!-- BOM -->
                    <div x-show="form.product_id" x-collapse>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BOM</label>
                        <template x-if="boms.length === 1">
                            <div class="p-3 bg-orange-50 border border-orange-100 rounded-lg">
                                <p class="font-medium text-gray-900"
                                    x-text="boms[0].bom_code + ' (v' + boms[0].version + ')'"></p>
                            </div>
                        </template>
                        <template x-if="boms.length > 1">
                            <select x-model="form.bom_id" @change="onBomChange()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select BOM</option>
                                <template x-for="b in boms" :key="b.id">
                                    <option :value="b.id" x-text="b.bom_code + ' v' + b.version"></option>
                                </template>
                            </select>
                        </template>
                    </div>

                    <!-- Target Qty & Date -->
                    <div x-show="form.bom_id" x-collapse class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target Qty</label>
                            <input type="number" x-model.number="form.target_qty" @input="calculateRM()" min="0.001"
                                step="0.001" placeholder="0"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Planned Date</label>
                            <input type="date" x-model="form.planned_date" :min="today"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>

                    <!-- Materials Preview -->
                    <div x-show="rmLines.length > 0" x-collapse class="border-t pt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Materials (<span
                                x-text="rmLines.length"></span>)</p>
                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 text-xs font-medium text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Material</th>
                                        <th class="px-3 py-2 text-right">Base</th>
                                        <th class="px-3 py-2 text-right">Deviation</th>
                                        <th class="px-3 py-2 text-right">Required</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="line in rmLines" :key="line.material_id">
                                        <tr>
                                            <td class="px-3 py-2 text-gray-900" x-text="line.material_name"></td>
                                            <td class="px-3 py-2 text-right text-gray-600"
                                                x-text="parseFloat(line.qty_required).toFixed(4)"></td>
                                            <td class="px-3 py-2 text-right">
                                                <span class="text-amber-600 font-medium"
                                                    x-text="(parseFloat(line.scrap_percent) || 0) + '%'"></span>
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium text-gray-900"
                                                x-text="line.required_qty"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div x-show="formError" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg">
                        <span x-text="formError"></span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="closeModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button @click="createRequest()" :disabled="submitting || !canSubmit()"
                        class="px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50">
                        <span x-show="!submitting">Create requestion</span>
                        <span x-show="submitting">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-8 bg-production rounded-full"></div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Production Order requestion</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1 ml-4.5">Plan manufacturing orders & manage inventory reservations</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openModal()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-production hover:bg-orange-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-orange-100 active:scale-95">
                <span class="material-symbols-outlined text-xl">add_circle</span>
                <span>Create Request</span>
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">MIR Pending Issue</p>
            <div class="flex items-end justify-between">
                <h3 class="text-2xl font-black text-gray-900"
                    x-text="requests.filter(o => o.status === 'CONVERTED_TO_MIR').length">0</h3>
                <span
                    class="p-1.5 bg-orange-50 text-orange-600 rounded-lg material-symbols-outlined text-lg">inventory_2</span>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">MIR Issued</p>
            <div class="flex items-end justify-between">
                <h3 class="text-2xl font-black text-gray-900"
                    x-text="requests.filter(o => o.mir_status === 'FULLY_ISSUED').length">0</h3>
                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg material-symbols-outlined text-lg">output</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Total Requests</p>
            <div class="flex items-end justify-between">
                <h3 class="text-2xl font-black text-gray-900" x-text="requests.length">0</h3>
                <span
                    class="p-1.5 bg-gray-50 text-gray-600 rounded-lg material-symbols-outlined text-lg">analytics</span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div
        class="bg-white rounded-2xl border border-gray-100 p-3 mb-6 shadow-sm flex flex-col md:flex-row items-center gap-3">
        <div class="flex-1 w-full relative group">
            <span
                class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-production transition-colors">search</span>
            <input type="text" x-model="filters.search" @input.debounce.400ms="loadRequests()"
                placeholder="Search requests, products..."
                class="w-full pl-11 pr-4 py-2.5 bg-gray-50/50 border-gray-200 rounded-xl focus:ring-2 focus:ring-production/20 focus:border-production focus:bg-white text-sm transition-all outline-none">
        </div>
        <div class="w-full md:w-56 relative group">
            <select x-model="filters.status" @change="loadRequests()"
                class="w-full px-4 py-2.5 bg-gray-50/50 border-gray-200 rounded-xl focus:ring-2 focus:ring-production/20 focus:border-production focus:bg-white text-sm appearance-none cursor-pointer transition-all outline-none">
                <option value="">Status: All</option>
                <option value="CONVERTED_TO_MIR">MIR Generated</option>
                <option value="CONVERTED_TO_ORDER">Order Created</option>
                <option value="REJECTED">Rejected</option>
            </select>
            <span
                class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xl">expand_more</span>
        </div>
        <button @click="filters.search=''; filters.status=''; loadRequests()"
            class="w-full md:w-auto px-4 py-2.5 text-gray-500 hover:text-production font-bold text-xs uppercase tracking-widest transition-all hover:bg-orange-50 rounded-xl">
            Reset
        </button>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Request &
                            Product</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Target
                            Details</th>
                        <th
                            class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                            Efficiency</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timeline
                        </th>
                        <th
                            class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                            Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                <span
                                    class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-3 text-production">progress_activity</span>
                                <p class="font-medium animate-pulse">Fetching Production Order requestion...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && requests.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                <div
                                    class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span class="material-symbols-outlined text-3xl">factory</span>
                                </div>
                                <p class="text-gray-900 font-bold text-lg">No requestions Found</p>
                                <p class="text-sm">Start by creating your first production request.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="request in requests" :key="request.id">
                        <tr class="group hover:bg-slate-50 transition-all duration-300">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                                        <span class="material-symbols-outlined text-xl">assignment</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-gray-900 leading-none mb-1.5"
                                            x-text="request.request_no">
                                        </div>
                                        <div class="text-xs font-medium text-gray-500" x-text="request.product_name">
                                        </div>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span
                                                class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded font-mono text-[9px] font-bold uppercase tracking-tighter"
                                                x-text="request.product_code"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 rounded-lg w-fit border border-gray-100">
                                    <span class="text-sm font-black text-gray-900" x-text="request.target_qty"></span>
                                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest"
                                        x-text="request.uom?.uom_code || request.uom || ''"></span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <template x-if="request.yield_percent">
                                    <div class="flex flex-col items-center">
                                        <div class="text-[11px] font-black text-emerald-600 mb-1"
                                            x-text="request.yield_percent + '%'"></div>
                                        <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500"
                                                :style="'width: ' + Math.min(request.yield_percent, 100) + '%'"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!request.yield_percent">
                                    <div class="text-center text-gray-300 font-bold text-[10px] uppercase">NA</div>
                                </template>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2 text-gray-900 font-bold">
                                    <span class="material-symbols-outlined text-sm text-gray-400">calendar_month</span>
                                    <span x-text="request.planned_date"></span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 text-[10px] font-black rounded-full shadow-sm ring-1 ring-inset uppercase tracking-[0.05em]"
                                    :class="{
                                                        'bg-orange-50 text-orange-700 ring-orange-100': request.status === 'CONVERTED_TO_MIR',
                                                        'bg-blue-50 text-blue-700 ring-blue-100': request.mir_status === 'FULLY_ISSUED',
                                                        'bg-rose-50 text-rose-700 ring-rose-100': request.status === 'REJECTED'
                                                    }">
                                    <span
                                        x-text="request.mir_status === 'FULLY_ISSUED' ? 'MIR ISSUED' : (request.status === 'CONVERTED_TO_MIR' ? 'MIR GENERATED' : (request.status === 'RECEIVED' ? 'RECEIVED' : request.status))"></span>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button @click="viewRequest(request)"
                                        class="p-2 text-gray-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm border border-transparent hover:border-indigo-100 rounded-xl transition-all"
                                        title="View Details">
                                        <span class="material-symbols-outlined text-xl">visibility</span>
                                    </button>

                                    <template x-if="request.status === 'APPROVED' && !request.mir_id">
                                        <button @click="convertToMir(request)"
                                            class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-[10px] font-black hover:bg-orange-600 transition-all shadow-md shadow-orange-100 uppercase">
                                            GENERATE MIR
                                        </button>
                                    </template>


                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function productionOrders(orgSlug) {
        return {
            orgSlug,
            showModal: false,
            loading: false,
            submitting: false,
            requests: [],

            // Products
            products: [],
            productLoading: false,

            // BOM
            boms: [],
            bomLoading: false,
            selectedBom: null,

            // RM calculation
            rmLines: [],
            multiplier: 0,

            formError: '',
            filters: {
                search: '',
                status: ''
            },
            stats: {
                activeOrders: 0,
                pendingMIR: 0,
                approvedMIR: 0,
                products: 0
            },
            today: new Date().toISOString().split('T')[0],

            // View modal
            viewModal: {
                show: false,
                loading: false,
                request: null,
                rmLines: []
            },
            varianceModal: {
                show: false,
                loading: false,
                request: null,
                report: null
            },

            form: {
                product_id: '',
                bom_id: '',
                target_qty: '',
                uom_id: '',
                planned_date: new Date().toISOString().split('T')[0]
            },

            async init() {
                await Promise.all([
                    this.loadRequests(),
                    this.loadStats()
                ]);
            },

            async loadStats() {
                try {
                    const res = await this._fetch('/api/v1/production-orders/stats');
                    const data = await res.json();
                    if (data.success) {
                        this.stats = data.data;
                    }
                } catch (e) {
                    console.error('Stats load failed', e);
                }
            },

            // ── Products ────────────────────────────────────────────────────
            async loadProducts() {
                this.productLoading = true;
                try {
                    const res = await this._fetch('/api/v1/production-requests/products');
                    const data = await res.json();
                    console.log('Load product Data :', data);

                    this.products = data?.data?.products || [];
                } catch (e) {
                    console.error('Product load failed', e);
                    this.products = [];
                } finally {
                    this.productLoading = false;
                }
            },

            onProductChange() {
                this.form.bom_id = '';
                this.selectedBom = null;
                this.rmLines = [];

                if (this.form.product_id) {
                    const product = this.products.find(p => p.id == this.form.product_id);
                    if (product && product.boms) {
                        this.boms = product.boms;
                        if (this.boms.length > 0) {
                            this.form.bom_id = this.boms[0].id;
                            this.onBomChange();
                        }
                    }
                }
            },



            onBomChange() {
                this.selectedBom = this.boms.find(b => b.id == this.form.bom_id) || null;
                if (this.selectedBom && this.selectedBom.output_uom) {
                    this.form.uom_id = this.selectedBom.output_uom.id;
                }
                this.rmLines = [];
                this.multiplier = 0;
                if (this.form.target_qty) this.calculateRM();
            },

            // ── RM auto-calculation ─────────────────────────────────────────
            async calculateRM() {
                if (!this.form.bom_id || !this.form.target_qty || this.form.target_qty <= 0) {
                    this.rmLines = [];
                    this.multiplier = 0;
                    return;
                }
                try {
                    const res = await this._fetch(`/api/v1/bom-lookup/details?bom_id=${this.form.bom_id}`);
                    const data = await res.json();
                    const details = Array.isArray(data?.data) ? data.data : [];

                    const targetQty = parseFloat(this.form.target_qty);
                    this.multiplier = targetQty;

                    this.rmLines = details.map(d => {
                        const qtyRequired = parseFloat(d.qty_required ?? 0);
                        const scrapPercent = parseFloat(d.scrap_percent ?? 0);
                        // effective_qty = qty_required × (1 + scrap% / 100) — stored in DB
                        // Use DB value if available, otherwise calculate it here
                        const effectiveQty = d.effective_qty ?
                            parseFloat(d.effective_qty) :
                            qtyRequired * (1 + scrapPercent / 100);

                        return {
                            material_id: d.material_id,
                            material_name: d.material?.material_name || ('Material #' + d.material_id),
                            material_code: d.material?.material_code || '',
                            qty_required: qtyRequired,
                            scrap_percent: scrapPercent,
                            effective_qty: effectiveQty,
                            // required_qty = effective_qty × target_qty
                            required_qty: (effectiveQty * targetQty).toFixed(4),
                            uom: d.uom ? {
                                uom_code: d.uom.uom_code,
                                uom_name: d.uom.uom_name
                            } : (d.uom_code || '')
                        };
                    });
                } catch (e) {
                    console.error('RM calculation failed', e);
                }
            },

            canSubmit() {
                return this.form.product_id && this.form.bom_id && this.form.target_qty > 0 && this.form.planned_date;
            },

            // ── Create requestion (auto-generates MIR immediately) ─────────────
            async createRequest() {
                this.formError = '';
                if (!this.canSubmit()) {
                    this.formError = 'Please fill all required fields.';
                    return;
                }
                this.submitting = true;
                try {
                    // Step 1: Create the production requestion
                    const res = await this._fetch('/api/v1/production-requests', {
                        method: 'POST',
                        body: JSON.stringify({
                            product_id: this.form.product_id,
                            bom_id: this.form.bom_id,
                            target_qty: this.form.target_qty,
                            uom_id: this.form.uom_id,
                            planned_date: this.form.planned_date,
                            rm_lines: this.rmLines
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to create requestion');

                    const createdRequest = data.data?.request;
                    this.closeModal();

                    // Step 2: Auto-generate MIR immediately
                    if (createdRequest?.id) {
                        try {
                            const mirRes = await this._fetch(`/api/v1/production-requests/${createdRequest.id}/convert-to-mir`, {
                                method: 'POST'
                            });
                            const mirData = await mirRes.json();
                            if (mirRes.ok && mirData.success) {
                                this.notify('Production requestion created & MIR Job generated — dispatched to Warehouse.', 'success');
                            } else {
                                this.notify('Request created. MIR generation failed: ' + (mirData.message || 'Unknown error'), 'warning');
                            }
                        } catch (mirErr) {
                            this.notify('Request created. MIR auto-generation error: ' + mirErr.message, 'warning');
                        }
                    } else {
                        this.notify('Production requestion created successfully.', 'success');
                    }

                    await this.loadRequests();
                } catch (e) {
                    this.formError = e.message || 'An error occurred. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },

            // ── requestions list ──────────────────────────────────────────────
            async loadRequests() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filters.search) params.append('search', this.filters.search);
                    if (this.filters.status) params.append('status', this.filters.status);
                    const res = await this._fetch(`/api/v1/production-requests?${params}`);
                    const data = await res.json();
                    this.requests = data?.data?.requests || [];
                } catch (e) {
                    console.error('Failed to load requestions', e);
                    this.requests = [];
                } finally {
                    this.loading = false;
                }
            },

            async submitForApproval(request) {
                try {
                    const res = await this._fetch(`/api/v1/production-requests/${request.id}/submit`, {
                        method: 'POST'
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to submit requestion');
                    this.notify('Request submitted for approval');
                    await this.loadRequests();
                } catch (e) {
                    this.notify(e.message, 'error');
                }
            },

            async approveRequest(request) {
                this.confirm('Approve requestion', `Approve production requestion ${request.request_no}?`, async () => {
                    try {
                        const res = await this._fetch(`/api/v1/production-requests/${request.id}/approve`, {
                            method: 'PATCH'
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to approve');
                        this.notify('Request approved');
                        await this.loadRequests();
                    } catch (e) {
                        this.notify(e.message, 'error');
                    }
                }, 'Approve', 'emerald');
            },

            async rejectRequest(request) {
                this.confirm('Reject requestion', `Reject production requestion ${request.request_no}?`, async () => {
                    try {
                        const res = await this._fetch(`/api/v1/production-requests/${request.id}/reject`, {
                            method: 'PATCH'
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to reject');
                        this.notify('Request rejected', 'warning');
                        await this.loadRequests();
                    } catch (e) {
                        this.notify(e.message, 'error');
                    }
                }, 'Reject', 'rose');
            },

            async convertToMir(request) {
                try {
                    const res = await this._fetch(`/api/v1/production-requests/${request.id}/convert-to-mir`, {
                        method: 'POST'
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed to generate MIR');
                    this.notify('MIR generated and sent to Warehouse');
                    await this.loadRequests();
                } catch (e) {
                    this.notify(e.message, 'error');
                }
            },



            async viewRequest(request) {
                this.viewModal.show = true;
                this.viewModal.loading = true;
                this.viewModal.request = request;
                this.viewModal.rmLines = [];
                try {
                    const res = await this._fetch(`/api/v1/production-requests/${request.id}`);
                    const data = await res.json();
                    const detail = data?.data?.request;
                    if (detail) {
                        this.viewModal.request = {
                            ...request,
                            ...detail,
                            uom: detail.uom || request.uom,
                        };
                        // Get materials via dedicated endpoint for calculated quantities

                        console.log('Request List', detail);



                        const matRes = await this._fetch(`/api/v1/production-requests/${request.id}/materials`);
                        const matData = await matRes.json();

                        console.log('Request List 2', matRes);

                        this.viewModal.rmLines = matData?.data?.materials || [];
                    }
                } catch (e) {
                    console.error('Failed to load requestion detail', e);
                } finally {
                    this.viewModal.loading = false;
                }
            },

            openModal() {
                this.form = {
                    product_id: '',
                    bom_id: '',
                    target_qty: '',
                    planned_date: ''
                };
                this.boms = [];
                this.selectedBom = null;
                this.rmLines = [];
                this.multiplier = 0;
                this.formError = '';
                this.showModal = true;
                this.loadProducts();
            },

            closeModal() {
                this.showModal = false;
            },

            // ── Shared fetch helper ─────────────────────────────────────────
            _fetch(url, options = {}) {
                return fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': 'Bearer ' + (localStorage.getItem('access_token') || '')
                    },
                    ...options
                });
            },

            notify(message, type = 'success') {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message,
                        type
                    }
                }));
            },

            confirm(title, message, onConfirm, confirmText = 'Confirm', confirmColor = 'red') {
                window.dispatchEvent(new CustomEvent('open-confirm', {
                    detail: {
                        title,
                        message,
                        onConfirm,
                        confirmText,
                        confirmColor
                    }
                }));
            }
        }
    }
</script>
@endsection