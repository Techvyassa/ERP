@extends('layouts.production')

@section('title', 'Production Orders')
@section('page-title', 'Production Orders')

@section('content')
    <div x-data="productionOrders('{{ $organization->org_slug }}')" x-init="init()">

        <!-- Variance Modal -->
        <div x-show="varianceModal.show" x-transition:enter="transition ease-out duration-200" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeVarianceModal()"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl z-10 overflow-hidden" @click.stop>
                    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">Efficiency & Variance Analysis</h3>
                            <p class="text-xs text-white/70" x-text="varianceModal.report?.order?.order_no || ''"></p>
                        </div>
                        <button @click="closeVarianceModal()" class="text-white/60 hover:text-white">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="px-6 py-5 max-h-[75vh] overflow-y-auto">
                        <div x-show="varianceModal.loading" class="text-center py-8 text-gray-400">
                            <span
                                class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>
                            Loading...
                        </div>
                        <template x-if="!varianceModal.loading && varianceModal.report">
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500">Target Qty</p>
                                        <p class="font-bold text-gray-900"
                                            x-text="varianceModal.report.production?.target_qty ?? '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500">Actual Qty</p>
                                        <p class="font-bold text-gray-900"
                                            x-text="varianceModal.report.production?.actual_qty ?? '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500">Rejected Qty</p>
                                        <p class="font-bold text-gray-900"
                                            x-text="varianceModal.report.production?.rejected_qty ?? '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500">Yield %</p>
                                        <p class="font-bold text-gray-900"
                                            x-text="varianceModal.report.production?.yield_percent ?? '—'"></p>
                                    </div>
                                </div>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                    Material</th>
                                                <th
                                                    class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    BOM Effective</th>
                                                <th
                                                    class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Consumed</th>
                                                <th
                                                    class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Variance</th>
                                                <th
                                                    class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Variance %</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="line in varianceModal.report.rm_lines || []"
                                                :key="line.material_id">
                                                <tr>
                                                    <td class="px-4 py-2">
                                                        <div class="font-medium text-gray-900" x-text="line.material_name">
                                                        </div>
                                                        <div class="text-xs text-gray-400" x-text="line.material_code">
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2 text-right" x-text="line.bom_effective"></td>
                                                    <td class="px-4 py-2 text-right" x-text="line.actually_consumed"></td>
                                                    <td class="px-4 py-2 text-right font-semibold"
                                                        :class="parseFloat(line.variance) > 0 ? 'text-red-600' : 'text-green-600'"
                                                        x-text="line.variance"></td>
                                                    <td class="px-4 py-2 text-right" x-text="line.variance_percent + '%'">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                        <button @click="closeVarianceModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Close</button>
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
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl z-10 overflow-hidden"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100" @click.stop>
                    <div class="bg-gradient-to-r from-indigo-800 to-indigo-950 px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 rounded-lg">
                                <span class="material-symbols-outlined text-indigo-200">contract</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white" x-text="viewModal.order?.order_no"></h3>
                                <p class="text-xs text-indigo-200/70" x-text="viewModal.order?.product_name"></p>
                            </div>
                        </div>
                        <button @click="viewModal.show = false" class="text-white/60 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div x-show="viewModal.loading" class="text-center py-8 text-gray-400">
                            <span
                                class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>
                            Loading...
                        </div>

                        <template x-if="!viewModal.loading && viewModal.order">
                            <div class="space-y-5">
                                <!-- Order Info -->
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">Target Quantity</p>
                                        <p class="font-bold text-gray-900"
                                            x-text="viewModal.order.target_qty + ' ' + (viewModal.order.bom?.output_uom?.uom_name || viewModal.order.uom?.uom_name || '')">
                                        </p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">Planned Date</p>
                                        <p class="font-bold text-gray-900" x-text="viewModal.order.planned_date"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">Production Order Status</p>
                                        <span class="px-2 py-1 text-xs rounded-full font-semibold" :class="{
                                                          'bg-gray-100 text-gray-700': viewModal.order.status === 'DRAFT',
                                                          'bg-blue-100 text-blue-700': viewModal.order.status === 'IN_PROGRESS',
                                                          'bg-green-100 text-green-800': viewModal.order.status === 'COMPLETED',
                                                          'bg-red-100 text-red-800': viewModal.order.status === 'CANCELLED'
                                                      }" x-text="viewModal.order.status"></span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">MIR Status</p>
                                        <span class="px-2 py-1 text-xs rounded-full font-semibold" :class="{
                                                          'bg-yellow-100 text-yellow-800': viewModal.order.mir_status === 'PENDING',
                                                          'bg-green-100 text-green-800': viewModal.order.mir_status === 'APPROVED',
                                                          'bg-red-100 text-red-800': viewModal.order.mir_status === 'REJECTED',
                                                          'bg-gray-100 text-gray-500': !viewModal.order.mir_status
                                                      }" x-text="viewModal.order.mir_status || 'Not Generated'"></span>
                                    </div>
                                </div>

                                <!-- RM Lines -->
                                <div x-show="viewModal.rmLines.length > 0">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-orange-500 text-base">inventory_2</span>
                                        Material Issue Request — Raw Materials
                                    </h4>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                        #</th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                        Material</th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                        Code</th>
                                                    <th
                                                        class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                        Required Qty</th>
                                                    <th
                                                        class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                        UOM</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-for="(line, idx) in viewModal.rmLines" :key="idx">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 text-gray-400 text-xs" x-text="idx + 1"></td>
                                                        <td class="px-4 py-2 text-gray-900 font-medium"
                                                            x-text="line.material_name"></td>
                                                        <td class="px-4 py-2 text-gray-500 text-xs"
                                                            x-text="line.material_code"></td>
                                                        <td class="px-4 py-2 text-right font-bold text-orange-600"
                                                            x-text="line.required_qty"></td>
                                                        <td class="px-4 py-2 text-gray-500"
                                                            x-text="line.uom?.uom_name || line.uom || ''"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button x-show="viewModal.order?.status === 'DRAFT' && viewModal.order?.mir_status === 'CLOSED'"
                            @click="startOrder(viewModal.order)"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Start Production
                        </button>
                        <button
                            x-show="viewModal.order?.status === 'DRAFT' && viewModal.order?.mir_status === 'FULLY_ISSUED'"
                            @click="viewModal.show = false; window.location.href = `/org/{{ $organization->org_slug }}/production/orders/${viewModal.order.id}/receiving`"
                            class="px-3 py-2 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600">
                            Confirm Floor Receipt
                        </button>
                        <button
                            x-show="viewModal.order?.status === 'DRAFT' && !['FULLY_ISSUED','CLOSED'].includes(viewModal.order?.mir_status)"
                            disabled class="px-3 py-2 bg-gray-200 text-gray-400 rounded-lg text-sm cursor-not-allowed"
                            title="Materials not yet fully issued by Store">
                            Pending Issue
                        </button>
                        <button x-show="viewModal.order?.status === 'COMPLETED'" @click="openVarianceModal(viewModal.order)"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Variance
                        </button>
                        <button @click="viewModal.show = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
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
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal()"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl z-10 overflow-hidden"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0" @click.stop>

                    <div class="bg-gradient-to-r from-slate-800 to-slate-900 flex items-center justify-between px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                                <span class="material-symbols-outlined text-production">precision_manufacturing</span>
                            </div>
                            <h3 class="text-lg font-bold text-white">Create Production Order</h3>
                        </div>
                        <button @click="closeModal()" class="text-white/70 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">

                        <!-- Step 1: Product Dropdown -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Product <span class="text-red-500">*</span>
                            </label>
                            <div x-show="productLoading" class="text-sm text-gray-500 flex items-center gap-2 py-2">
                                <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                                Loading products...
                            </div>
                            <select x-show="!productLoading" x-model="form.product_id" @change="onProductChange()"
                                class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 transition-all font-medium text-gray-900 appearance-none shadow-inner">
                                <option value="">— Select Product —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id"
                                        x-text="p.product_name + (p.product_code ? ' (' + p.product_code + ')' : '')">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Step 2: BOM Version -->
                        <div x-show="form.product_id">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                BOM Version <span class="text-red-500">*</span>
                            </label>
                            <div x-show="bomLoading" class="text-sm text-gray-500 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                                Loading BOMs...
                            </div>
                            <!-- BOM Selection / Display -->
                            <div x-show="!bomLoading" class="space-y-2">
                                <!-- Only one BOM: Show as text, no dropdown -->
                                <template x-if="boms.length === 1">
                                    <div
                                        class="flex items-center justify-between px-4 py-3 bg-orange-50 border border-orange-100 rounded-lg shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-orange-100 rounded-lg">
                                                <span
                                                    class="material-symbols-outlined text-orange-600 text-lg">settings_b_roll</span>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900"
                                                        x-text="boms[0].bom_code + ' (v' + boms[0].version + ')'"></span>
                                                    <span
                                                        class="text-[10px] uppercase font-black bg-orange-200 text-orange-800 px-1.5 py-0.5 rounded leading-none">Master</span>

                                                </div>
                                                <span
                                                    class="material-symbols-outlined text-green-500 text-base">check_circle</span>
                                            </div>
                                </template>

                                <!-- Multiple BOMs: Show Dropdown -->
                                <template x-if="boms.length > 1">
                                    <select x-model="form.bom_id" @change="onBomChange()"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                                        <option value="">— Select BOM Version —</option>
                                        <template x-for="b in boms" :key="b.id">
                                            x-text="b.bom_code + ' v' + b.version">
                                            </option>
                                        </template>
                                    </select>
                                </template>
                            </div>
                            <div x-show="!bomLoading && boms.length === 0"
                                class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2.5 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">warning</span>
                                No active BOM found for this product. Please create a BOM first.
                            </div>
                        </div>

                        <!-- Step 3: Target Qty + Date -->
                        <div x-show="form.bom_id" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Target Quantity <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <input type="number" x-model.number="form.target_qty" @input="calculateRM()" min="0.001"
                                        step="0.001" placeholder="0.000"
                                        class="w-full pl-4 pr-16 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 transition-all font-bold text-gray-900 shadow-inner">
                                    <span
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-gray-400 uppercase tracking-tighter"
                                        x-text="selectedBom?.output_uom?.uom_code || ''"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Planned Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" x-model="form.planned_date" :min="today"
                                    class="w-full px-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 transition-all font-semibold text-gray-900 shadow-inner"
                                    :value="form.planned_date">
                            </div>
                        </div>

                        <!-- Step 4: RM Auto-calculation Preview -->
                        <div x-show="rmLines.length > 0">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-symbols-outlined text-orange-500">calculate</span>
                                <h4 class="text-sm font-semibold text-gray-800">Auto-calculated Raw Materials</h4>
                                <span class="text-xs text-gray-500 ml-auto">
                                    Target Qty: <strong x-text="form.target_qty"></strong>
                                    &nbsp;→&nbsp; Multiplier: <strong x-text="multiplier.toFixed(4)"></strong>
                                </span>
                            </div>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">#
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                Material</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                Code</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                                Required Qty</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">
                                                UOM</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(line, idx) in rmLines" :key="line.material_id">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-gray-400 text-xs" x-text="idx + 1"></td>
                                                <td class="px-4 py-2 text-gray-900 font-medium" x-text="line.material_name">
                                                </td>
                                                <td class="px-4 py-2 text-gray-500 text-xs" x-text="line.material_code">
                                                </td>
                                                <td class="px-4 py-2 text-right font-bold text-orange-600"
                                                    x-text="line.required_qty"></td>
                                                <td class="px-4 py-2 text-gray-500"
                                                    x-text="line.uom?.uom_name || line.uom || ''"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                                <p class="font-semibold mb-1">How this is calculated:</p>
                                <ul class="space-y-1 ml-4 list-disc">
                                    <li><strong>Effective Qty</strong> = Base Qty × (1 + Scrap %)</li>
                                    <li><strong>Required Qty</strong> = Effective Qty × Target Qty</li>
                                    <li>Example: 5.10 × 10 = 51.0 KG</li>
                                </ul>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">info</span>
                                A Material Issue Request (MIR) will be sent to Store for approval on submission.
                            </p>
                        </div>

                        <div x-show="formError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"
                            x-text="formError"></div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                        <button @click="closeModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button @click="submitOrder()" :disabled="submitting || !canSubmit()"
                            :class="(!submitting && canSubmit()) ? 'hover:bg-orange-600' : 'opacity-50 cursor-not-allowed'"
                            class="px-5 py-2 bg-orange-500 text-white font-semibold rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm" :class="submitting ? 'animate-spin' : ''"
                                x-text="submitting ? 'progress_activity' : 'send'"></span>
                            <span x-text="submitting ? 'Submitting...' : 'Create & Send MIR'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Draft Orders</p>
                        <p class="text-xl font-bold text-gray-900" x-text="orders.filter(o => o.status === 'DRAFT').length">
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all border-l-4 border-l-blue-500">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined">sync</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">In Progress</p>
                        <p class="text-xl font-bold text-gray-900"
                            x-text="orders.filter(o => o.status === 'IN_PROGRESS').length"></p>
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all border-l-4 border-l-green-500">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined">task_alt</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Completed</p>
                        <p class="text-xl font-bold text-gray-900"
                            x-text="orders.filter(o => o.status === 'COMPLETED').length"></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined">trending_up</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Yield Avg</p>
                        <p class="text-xl font-bold text-gray-900"
                            x-text="orders.length > 0 ? (orders.reduce((acc, o) => acc + parseFloat(o.yield_percent || 0), 0) / orders.length).toFixed(1) + '%' : '0%'">
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Production Execution</h2>
                <p class="text-sm text-gray-500 mt-1">Monitor floor status, issue raw materials, and confirm finished goods
                    production.</p>
            </div>
            <button @click="openModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-production text-white font-bold rounded-xl hover:bg-orange-600 shadow-lg shadow-orange-200 transition-all hover:-translate-y-0.5 active:translate-y-0">
                <span class="material-symbols-outlined text-lg font-bold">add</span>
                New Order
            </button>
        </div>

        <!-- Filters -->
        <div
            class="bg-white/80 backdrop-blur-md rounded-2xl border border-gray-100 p-4 mb-6 shadow-sm flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" x-model="filters.search" @input.debounce.400ms="loadOrders()"
                    placeholder="Search by order no, product..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 text-sm transition-all">
            </div>
            <div class="w-48 relative">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">filter_alt</span>
                <select x-model="filters.status" @change="loadOrders()"
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-orange-400 text-sm appearance-none cursor-pointer transition-all">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <button @click="filters.search=''; filters.status=''; loadOrders()"
                class="px-4 py-2 text-gray-500 hover:text-production font-medium text-sm transition-colors">
                Clear Filters
            </button>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order & Product
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Target / Actual
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Yield & Batch</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Planned Date</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MIR Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    <span
                                        class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-2">progress_activity</span>
                                    Loading...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && orders.length === 0">
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    <span class="material-symbols-outlined text-5xl block mx-auto mb-2">factory</span>
                                    No production orders yet. Create one to get started.
                                </td>
                            </tr>
                        </template>
                        <template x-for="order in orders" :key="order.id">
                            <tr class="group hover:bg-orange-50/30 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="mt-1 w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600">
                                            <span class="material-symbols-outlined text-lg">precision_manufacturing</span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold text-slate-800" x-text="order.order_no"></span>
                                            <div class="text-sm font-medium text-gray-500"
                                                x-text="typeof order.product_name === 'object' ? order.product_name.product_name : order.product_name">
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-mono tracking-tight uppercase"
                                                x-text="typeof order.product_code === 'object' ? order.product_code.product_code : order.product_code">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5 font-bold text-gray-400 text-xs">
                                            <span>Target:</span>
                                            <span class="text-gray-900" x-text="order.target_qty"></span>
                                            <span class="text-[10px] font-normal uppercase"
                                                x-text="typeof order.uom === 'object' ? (order.uom.uom_name || order.uom.uom_code) : order.uom"></span>
                                        </div>
                                        <template x-if="order.actual_qty && order.actual_qty > 0">
                                            <div class="flex items-center gap-1.5 font-bold text-blue-600 text-xs">
                                                <span>Actual:</span>
                                                <span x-text="order.actual_qty"></span>
                                                <span class="text-[10px] font-normal uppercase"
                                                    x-text="typeof order.uom === 'object' ? (order.uom.uom_name || order.uom.uom_code) : order.uom"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <template x-if="order.yield_percent">
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-green-500 rounded-full"
                                                        :style="'width: ' + Math.min(order.yield_percent, 100) + '%'"></div>
                                                </div>
                                                <span class="text-xs font-black text-green-700"
                                                    x-text="order.yield_percent + '%'"></span>
                                            </div>
                                        </template>
                                        <template x-if="order.fg_batch_number">
                                            <div class="flex items-center gap-1 text-gray-400">
                                                <span class="material-symbols-outlined text-[10px]">qr_code_2</span>
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600"
                                                    x-text="order.fg_batch_number"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    <div class="font-bold" x-text="order.planned_date"></div>
                                    <div x-show="order.actual_end_at" class="text-[10px] text-gray-400 mt-0.5"
                                        x-text="'Done: ' + order.actual_end_at"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1">
                                        <template x-if="order.mir_status === 'PENDING'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1 h-1 bg-amber-500 rounded-full"></span> PENDING ISSUE
                                            </span>
                                        </template>
                                        <template x-if="order.mir_status === 'APPROVED'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1 h-1 bg-blue-500 rounded-full"></span> APPROVED
                                            </span>
                                        </template>
                                        <template x-if="order.mir_status === 'PARTIALLY_ISSUED'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-orange-50 text-orange-700 border border-orange-200">
                                                <span class="w-1 h-1 bg-orange-500 rounded-full"></span> PARTIAL ISSUE
                                            </span>
                                        </template>
                                        <template x-if="order.mir_status === 'FULLY_ISSUED'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1 h-1 bg-emerald-500 rounded-full"></span> FULLY ISSUED
                                            </span>
                                        </template>
                                        <template x-if="order.mir_status === 'CLOSED'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1 h-1 bg-slate-500 rounded-full"></span> RECEIVED
                                            </span>
                                        </template>
                                        <template x-if="order.mir_status === 'REJECTED'">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1 h-1 bg-rose-500 rounded-full"></span> REJECTED
                                            </span>
                                        </template>
                                        <template x-if="!order.mir_status">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[9px] font-black rounded-md bg-gray-50 text-gray-400 border border-gray-200">NO
                                                MIR</span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-black rounded-full ring-1 ring-inset uppercase tracking-wide"
                                        :class="{
                                                      'bg-slate-50 text-slate-700 ring-slate-200': order.status === 'DRAFT',
                                                      'bg-blue-50 text-blue-700 ring-blue-200': order.status === 'IN_PROGRESS',
                                                      'bg-emerald-50 text-emerald-700 ring-emerald-200': order.status === 'COMPLETED',
                                                      'bg-rose-50 text-rose-700 ring-rose-200': order.status === 'CANCELLED'
                                                  }">
                                        <span x-text="order.status"></span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button @click="viewOrder(order)"
                                            class="p-2 text-gray-500 hover:bg-gray-100 hover:text-production rounded-lg transition-all"
                                            title="View Details">
                                            <span class="material-symbols-outlined text-lg">visibility</span>
                                        </button>
                                        <template x-if="order.status === 'DRAFT'">
                                            <div class="flex flex-col gap-1">
                                                <!-- Receiving confirmation pending: show link to receiving page -->
                                                <template x-if="order.mir_status === 'FULLY_ISSUED'">
                                                    <a :href="`/org/{{ $organization->org_slug }}/production/orders/${order.id}/receiving`"
                                                        class="px-3 py-1.5 bg-amber-500 text-white hover:bg-amber-600 rounded-lg text-xs font-bold transition-all shadow-sm text-center">
                                                        Confirm Receipt
                                                    </a>
                                                </template>
                                                <!-- MIR closed (materials received): allow start -->
                                                <template x-if="order.mir_status === 'CLOSED'">
                                                    <button @click="startOrder(order)"
                                                        class="px-3 py-1.5 bg-blue-600 text-white hover:bg-blue-700 rounded-lg text-xs font-bold transition-all shadow-sm">
                                                        Start Production
                                                    </button>
                                                </template>
                                                <!-- MIR not ready: disabled button with tooltip -->
                                                <template x-if="!['FULLY_ISSUED','CLOSED'].includes(order.mir_status)">
                                                    <button disabled
                                                        :title="'Cannot start: MIR is ' + (order.mir_status || 'not generated')"
                                                        class="px-3 py-1.5 bg-gray-200 text-gray-400 rounded-lg text-xs font-bold cursor-not-allowed">
                                                        Pending Issue
                                                    </button>
                                                </template>
                                            </div>
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
                orders: [],

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
                today: new Date().toISOString().split('T')[0],

                // View modal
                viewModal: {
                    show: false,
                    loading: false,
                    order: null,
                    rmLines: []
                },
                varianceModal: {
                    show: false,
                    loading: false,
                    order: null,
                    report: null
                },

                form: {
                    product_id: '',
                    bom_id: '',
                    target_qty: '',
                    planned_date: new Date().toISOString().split('T')[0]
                },

                async init() {
                    await this.loadOrders();
                },

                // ── Products ────────────────────────────────────────────────────
                async loadProducts() {
                    this.productLoading = true;
                    try {
                        const res = await this._fetch('/api/v1/products?is_active=1&per_page=500');
                        const data = await res.json();
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
                    if (this.form.product_id) this.loadBoms(this.form.product_id);
                },

                // ── BOM loading ─────────────────────────────────────────────────
                async loadBoms(productId) {
                    this.bomLoading = true;
                    this.boms = [];
                    try {
                        const res = await this._fetch(`/api/v1/bom-headers?product_id=${productId}&bom_status=ACTIVE`);
                        const data = await res.json();
                        this.boms = Array.isArray(data?.data) ? data.data : [];

                        // Default: Auto-select if at least one BOM is found
                        if (this.boms.length > 0) {
                            this.form.bom_id = this.boms[0].id;
                            this.onBomChange(); // This triggers RM calculation if target_qty exists
                        }
                    } catch (e) {
                        console.error('BOM load failed', e);
                        this.boms = [];
                    } finally {
                        this.bomLoading = false;
                    }
                },

                onBomChange() {
                    this.selectedBom = this.boms.find(b => b.id == this.form.bom_id) || null;
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
                        const res = await this._fetch(`/api/v1/bom-details?bom_id=${this.form.bom_id}`);
                        const data = await res.json();
                        // Response: data.data is a flat array
                        const details = Array.isArray(data?.data) ? data.data : [];
                        // Fixed: Use target_qty directly as the multiplier (correct formula)
                        // Old (WRONG): this.multiplier = this.form.target_qty / batchSize;
                        // New (CORRECT): this.multiplier = this.form.target_qty;
                        this.multiplier = this.form.target_qty;

                        this.rmLines = details.map(d => ({
                            material_id: d.material_id,
                            material_name: d.material?.material_name || ('Material #' + d.material_id),
                            material_code: d.material?.material_code || '',
                            // effective_qty is a DB generated column (qty_required * (1 + scrap/100))
                            required_qty: (parseFloat(d.effective_qty ?? d.qty_required) * this.multiplier).toFixed(3),
                            uom: d.uom ? {
                                uom_code: d.uom.uom_code,
                                uom_name: d.uom.uom_name
                            } : (d.uom_code || '')
                        }));
                    } catch (e) {
                        console.error('RM calculation failed', e);
                    }
                },

                canSubmit() {
                    return this.form.product_id && this.form.bom_id && this.form.target_qty > 0 && this.form.planned_date;
                },

                // ── Submit ──────────────────────────────────────────────────────
                async submitOrder() {
                    this.formError = '';
                    if (!this.canSubmit()) {
                        this.formError = 'Please fill all required fields.';
                        return;
                    }
                    this.submitting = true;
                    try {
                        const res = await this._fetch('/api/v1/production-orders', {
                            method: 'POST',
                            body: JSON.stringify({
                                product_id: this.form.product_id,
                                bom_id: this.form.bom_id,
                                target_qty: this.form.target_qty,
                                planned_date: this.form.planned_date,
                                rm_lines: this.rmLines
                            })
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to create order');
                        this.closeModal();
                        await this.loadOrders();
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { message: 'Production order created. MIR sent to Store for approval.', type: 'success' }
                        }));
                    } catch (e) {
                        this.formError = e.message || 'An error occurred. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                },

                // ── Orders list ─────────────────────────────────────────────────
                async loadOrders() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.search) params.append('search', this.filters.search);
                        if (this.filters.status) params.append('status', this.filters.status);
                        const res = await this._fetch(`/api/v1/production-orders?${params}`);
                        const data = await res.json();
                        this.orders = data?.data?.orders || data?.data || [];
                    } catch (e) {
                        console.error('Failed to load orders', e);
                        this.orders = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async viewOrder(order) {
                    this.viewModal.show = true;
                    this.viewModal.loading = true;
                    this.viewModal.order = order;
                    this.viewModal.rmLines = [];
                    try {
                        const res = await this._fetch(`/api/v1/production-orders/${order.id}`);
                        const data = await res.json();
                        const detail = data?.data?.order;
                        if (detail) {
                            this.viewModal.order = {
                                ...order,
                                ...detail,
                                product_name: detail.product?.product_name || order.product_name,
                                product_code: detail.product?.product_code || order.product_code,
                                uom: detail.bom?.output_uom?.uom_code || order.uom,
                            };
                            // MIR lines come from the nested mir.lines
                            this.viewModal.rmLines = (detail.mir?.lines || []).map(l => ({
                                material_name: l.material?.material_name || '—',
                                material_code: l.material?.material_code || '',
                                required_qty: l.required_qty,
                                uom: l.uom ? {
                                    uom_code: l.uom.uom_code,
                                    uom_name: l.uom.uom_name
                                } : (l.uom_code || '')
                            }));
                        }
                    } catch (e) {
                        console.error('Failed to load order detail', e);
                    } finally {
                        this.viewModal.loading = false;
                    }
                },

                startOrder(order) {
                    this.confirm(
                        'Start Production',
                        `Confirm starting production for ${order.order_no}? 
             Product: ${typeof order.product_name === 'object'
                            ? order.product_name.product_name
                            : (order.product_name || '')
                        }`,
                        async () => {
                            try {
                                const res = await this._fetch(`/api/v1/production-orders/${order.id}/start`, {
                                    method: 'POST'
                                });
                                const data = await res.json();
                                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to start production');
                                await this.loadOrders();
                                if (this.viewModal.show && this.viewModal.order?.id === order.id) {
                                    await this.viewOrder({
                                        ...order,
                                        status: 'IN_PROGRESS'
                                    });
                                }
                                this.notify('Production started successfully');
                            } catch (e) {
                                this.notify(e.message || 'Failed to start production', 'error');
                            }
                        },
                        'Start',
                        'blue'
                    );
                },


                async openVarianceModal(order) {
                    this.varianceModal = {
                        show: true,
                        loading: true,
                        order,
                        report: null
                    };
                    try {
                        const res = await this._fetch(`/api/v1/production-orders/${order.id}/variance`);
                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to load variance report');
                        this.varianceModal.report = data.data;
                    } catch (e) {
                        this.notify(e.message || 'Failed to load variance report', 'error');
                        this.closeVarianceModal();
                    } finally {
                        this.varianceModal.loading = false;
                    }
                },

                closeVarianceModal() {
                    this.varianceModal = {
                        show: false,
                        loading: false,
                        order: null,
                        report: null
                    };
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