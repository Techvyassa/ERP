@extends('layouts.production')

@section('title', 'Production Orders')
@section('page-title', 'Production Orders')

@section('content')
<div x-data="productionOrders('{{ $organization->org_slug }}')" x-init="init()">
    <!-- Confirm FG Modal -->
    <div x-show="confirmFgModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="closeConfirmFgModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl z-10" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirm Finished Goods</h3>
                        <p class="text-xs text-gray-500" x-text="confirmFgModal.order?.order_no || ''"></p>
                    </div>
                    <button @click="closeConfirmFgModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Actual Qty</label>
                            <input type="number" min="0.001" step="0.001" x-model="confirmFgForm.actual_qty" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Rejected Qty</label>
                            <input type="number" min="0" step="0.001" x-model="confirmFgForm.rejected_qty" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Rework Qty</label>
                            <input type="number" min="0" step="0.001" x-model="confirmFgForm.rework_qty" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">FG Batch Number</label>
                            <input type="text" x-model="confirmFgForm.fg_batch_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Warehouse ID</label>
                            <input type="number" min="1" step="1" x-model="confirmFgForm.fg_warehouse_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Bin ID</label>
                            <input type="number" min="1" step="1" x-model="confirmFgForm.fg_bin_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <label class="flex items-center gap-3 rounded-lg bg-orange-50 px-4 py-3 text-sm text-gray-700">
                        <input type="checkbox" x-model="confirmFgForm.qc_required" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        Hold FG in QC after confirmation
                    </label>
                    <div x-show="confirmFgError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="confirmFgError"></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button @click="closeConfirmFgModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button @click="submitConfirmFg()" class="px-5 py-2 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm" :class="confirmFgModal.submitting ? 'animate-spin' : ''"
                              x-text="confirmFgModal.submitting ? 'progress_activity' : 'inventory'"></span>
                        <span x-text="confirmFgModal.submitting ? 'Posting...' : 'Confirm FG'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Variance Modal -->
    <div x-show="varianceModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="closeVarianceModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl z-10" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Yield and Variance</h3>
                        <p class="text-xs text-gray-500" x-text="varianceModal.report?.order?.order_no || ''"></p>
                    </div>
                    <button @click="closeVarianceModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="px-6 py-5 max-h-[75vh] overflow-y-auto">
                    <div x-show="varianceModal.loading" class="text-center py-8 text-gray-400">
                        <span class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>
                        Loading...
                    </div>
                    <template x-if="!varianceModal.loading && varianceModal.report">
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Target Qty</p><p class="font-bold text-gray-900" x-text="varianceModal.report.production?.target_qty ?? '—'"></p></div>
                                <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Actual Qty</p><p class="font-bold text-gray-900" x-text="varianceModal.report.production?.actual_qty ?? '—'"></p></div>
                                <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Rejected Qty</p><p class="font-bold text-gray-900" x-text="varianceModal.report.production?.rejected_qty ?? '—'"></p></div>
                                <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Yield %</p><p class="font-bold text-gray-900" x-text="varianceModal.report.production?.yield_percent ?? '—'"></p></div>
                            </div>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">BOM Effective</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Consumed</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Variance</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Variance %</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="line in varianceModal.report.rm_lines || []" :key="line.material_id">
                                            <tr>
                                                <td class="px-4 py-2">
                                                    <div class="font-medium text-gray-900" x-text="line.material_name"></div>
                                                    <div class="text-xs text-gray-400" x-text="line.material_code"></div>
                                                </td>
                                                <td class="px-4 py-2 text-right" x-text="line.bom_effective"></td>
                                                <td class="px-4 py-2 text-right" x-text="line.actually_consumed"></td>
                                                <td class="px-4 py-2 text-right font-semibold" :class="parseFloat(line.variance) > 0 ? 'text-red-600' : 'text-green-600'" x-text="line.variance"></td>
                                                <td class="px-4 py-2 text-right" x-text="line.variance_percent + '%'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button @click="closeVarianceModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div x-show="viewModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="viewModal.show = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl z-10" @click.stop>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-orange-500">factory</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="viewModal.order?.order_no"></h3>
                            <p class="text-xs text-gray-500" x-text="viewModal.order?.product_name + ' (' + (viewModal.order?.product_code || '') + ')'"></p>
                        </div>
                    </div>
                    <button @click="viewModal.show = false" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                    <div x-show="viewModal.loading" class="text-center py-8 text-gray-400">
                        <span class="material-symbols-outlined text-3xl animate-spin block mx-auto mb-2">progress_activity</span>
                        Loading...
                    </div>

                    <template x-if="!viewModal.loading && viewModal.order">
                        <div class="space-y-5">
                            <!-- Order Info -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Target Quantity</p>
                                    <p class="font-bold text-gray-900" x-text="viewModal.order.target_qty + ' ' + (viewModal.order.uom || '')"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Planned Date</p>
                                    <p class="font-bold text-gray-900" x-text="viewModal.order.planned_date"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Order Status</p>
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                          :class="{
                                              'bg-gray-100 text-gray-700': viewModal.order.status === 'DRAFT',
                                              'bg-blue-100 text-blue-700': viewModal.order.status === 'IN_PROGRESS',
                                              'bg-green-100 text-green-800': viewModal.order.status === 'COMPLETED',
                                              'bg-red-100 text-red-800': viewModal.order.status === 'CANCELLED'
                                          }"
                                          x-text="viewModal.order.status"></span>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">MIR Status</p>
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                          :class="{
                                              'bg-yellow-100 text-yellow-800': viewModal.order.mir_status === 'PENDING',
                                              'bg-green-100 text-green-800': viewModal.order.mir_status === 'APPROVED',
                                              'bg-red-100 text-red-800': viewModal.order.mir_status === 'REJECTED',
                                              'bg-gray-100 text-gray-500': !viewModal.order.mir_status
                                          }"
                                          x-text="viewModal.order.mir_status || 'Not Generated'"></span>
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
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Required Qty</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">UOM</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(line, idx) in viewModal.rmLines" :key="idx">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-gray-400 text-xs" x-text="idx + 1"></td>
                                                    <td class="px-4 py-2 text-gray-900 font-medium" x-text="line.material_name"></td>
                                                    <td class="px-4 py-2 text-gray-500 text-xs" x-text="line.material_code"></td>
                                                    <td class="px-4 py-2 text-right font-bold text-orange-600" x-text="line.required_qty"></td>
                                                    <td class="px-4 py-2 text-gray-500" x-text="line.uom"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                    <a :href="'/org/' + orgSlug + '/production/mir'"
                       class="flex items-center gap-2 text-sm text-orange-600 hover:text-orange-700 font-semibold">
                        <span class="material-symbols-outlined text-base">assignment</span>
                        View all MIRs
                    </a>
                    <div class="flex items-center gap-2">
                        <button x-show="viewModal.order?.status === 'DRAFT'" @click="startOrder(viewModal.order)"
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Start
                        </button>
                        <button x-show="viewModal.order?.status === 'IN_PROGRESS'" @click="openConfirmFgModal(viewModal.order)"
                                class="px-3 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600">
                            Confirm FG
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
    </div>

    <!-- New Production Order Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl z-10" @click.stop>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">New Production Order</h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
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
                            <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Loading products...
                        </div>
                        <select x-show="!productLoading"
                                x-model="form.product_id"
                                @change="onProductChange()"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent">
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
                            <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Loading BOMs...
                        </div>
                        <select x-show="!bomLoading && boms.length > 0"
                                x-model="form.bom_id"
                                @change="onBomChange()"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                            <option value="">— Select BOM Version —</option>
                            <template x-for="b in boms" :key="b.id">
                                <option :value="b.id"
                                        x-text="b.bom_code + '  v' + b.version + '  (batch: ' + b.batch_size + ' ' + (b.output_uom ? b.output_uom.uom_code : '') + ')'">
                                </option>
                            </template>
                        </select>
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
                            <div class="flex gap-2">
                                <input type="number"
                                       x-model.number="form.target_qty"
                                       @input="calculateRM()"
                                       min="0.001" step="0.001"
                                       placeholder="e.g. 100"
                                       class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                                <span class="flex items-center px-3 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 whitespace-nowrap"
                                      x-text="selectedBom?.output_uom?.uom_code || ''"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Planned Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   x-model="form.planned_date"
                                   :min="today"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Step 4: RM Auto-calculation Preview -->
                    <div x-show="rmLines.length > 0">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-orange-500">calculate</span>
                            <h4 class="text-sm font-semibold text-gray-800">Auto-calculated Raw Materials</h4>
                            <span class="text-xs text-gray-500 ml-auto">
                                Batch size: <strong x-text="selectedBom?.batch_size"></strong>
                                &nbsp;→&nbsp; Multiplier: <strong x-text="multiplier.toFixed(4)"></strong>
                            </span>
                        </div>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Required Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">UOM</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(line, idx) in rmLines" :key="line.material_id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-gray-400 text-xs" x-text="idx + 1"></td>
                                            <td class="px-4 py-2 text-gray-900 font-medium" x-text="line.material_name"></td>
                                            <td class="px-4 py-2 text-gray-500 text-xs" x-text="line.material_code"></td>
                                            <td class="px-4 py-2 text-right font-bold text-orange-600" x-text="line.required_qty"></td>
                                            <td class="px-4 py-2 text-gray-500" x-text="line.uom"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                            <p class="font-semibold mb-1">How this is calculated:</p>
                            <ul class="space-y-1 ml-4 list-disc">
                                <li><strong>Effective Qty</strong> = Base Qty + (Base Qty × Scrap %)</li>
                                <li><strong>Required Qty</strong> = Effective Qty × (Target Qty ÷ Batch Size)</li>
                                <li>Example: 5.10 × (10 ÷ 100) = 0.510 KG</li>
                            </ul>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">info</span>
                            A Material Issue Request (MIR) will be sent to Store for approval on submission.
                        </p>
                    </div>

                    <div x-show="formError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="formError"></div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button @click="closeModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitOrder()"
                            :disabled="submitting || !canSubmit()"
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

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Production Orders</h2>
            <p class="text-sm text-gray-500 mt-1">Select a product, enter target quantity — system auto-calculates RM from BOM and generates MIR for Store.</p>
        </div>
        <button @click="openModal()"
                class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-colors">
            <span class="material-symbols-outlined text-lg">add</span>
            New Production Order
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.400ms="loadOrders()"
                   placeholder="Search by order no, product..."
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
            <select x-model="filters.status" @change="loadOrders()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-transparent text-sm">
                <option value="">All Status</option>
                <option value="DRAFT">Draft</option>
                <option value="IN_PROGRESS">In Progress</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
            <button @click="filters.search=''; filters.status=''; loadOrders()"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm transition-colors">
                Reset
            </button>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Target Qty</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Planned Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MIR Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-4xl animate-spin block mx-auto mb-2">progress_activity</span>
                            Loading...
                        </td></tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-5xl block mx-auto mb-2">factory</span>
                            No production orders yet. Create one to get started.
                        </td></tr>
                    </template>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-sm font-semibold text-gray-900" x-text="order.order_no"></td>
                            <td class="px-5 py-3 text-sm text-gray-700">
                                <div x-text="order.product_name"></div>
                                <div class="text-xs text-gray-400" x-text="order.product_code"></div>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700" x-text="order.target_qty + ' ' + (order.uom || '')"></td>
                            <td class="px-5 py-3 text-sm text-gray-600" x-text="order.planned_date"></td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': order.mir_status === 'PENDING',
                                          'bg-green-100 text-green-800': order.mir_status === 'APPROVED',
                                          'bg-red-100 text-red-800': order.mir_status === 'REJECTED',
                                          'bg-gray-100 text-gray-600': !order.mir_status
                                      }"
                                      x-text="order.mir_status || '—'"></span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                      :class="{
                                          'bg-gray-100 text-gray-700': order.status === 'DRAFT',
                                          'bg-blue-100 text-blue-700': order.status === 'IN_PROGRESS',
                                          'bg-green-100 text-green-800': order.status === 'COMPLETED',
                                          'bg-red-100 text-red-800': order.status === 'CANCELLED'
                                      }"
                                      x-text="order.status"></span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-2 flex-wrap justify-end">
                                    <button @click="viewOrder(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">visibility</span> View
                                    </button>
                                    <button x-show="order.status === 'DRAFT'" @click="startOrder(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white hover:bg-blue-700 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">play_arrow</span> Start
                                    </button>
                                    <button x-show="order.status === 'IN_PROGRESS'" @click="openConfirmFgModal(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-500 text-white hover:bg-orange-600 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">inventory</span> Confirm FG
                                    </button>
                                    <button x-show="order.status === 'COMPLETED'" @click="openVarianceModal(order)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs transition-colors">
                                        <span class="material-symbols-outlined text-sm">analytics</span> Variance
                                    </button>
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
        filters: { search: '', status: '' },
        today: new Date().toISOString().split('T')[0],

        // View modal
        viewModal: { show: false, loading: false, order: null, rmLines: [] },
        confirmFgModal: { show: false, submitting: false, order: null },
        varianceModal: { show: false, loading: false, order: null, report: null },
        confirmFgError: '',
        confirmFgForm: {
            actual_qty: '',
            rejected_qty: 0,
            rework_qty: 0,
            fg_bin_id: '',
            fg_warehouse_id: '',
            fg_batch_number: '',
            qc_required: false
        },

        form: {
            product_id: '',
            bom_id: '',
            target_qty: '',
            planned_date: ''
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
                // Response: data.data is a flat array
                this.boms = Array.isArray(data?.data) ? data.data : [];
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
                const batchSize = parseFloat(this.selectedBom?.batch_size) || 1;
                this.multiplier = this.form.target_qty / batchSize;

                this.rmLines = details.map(d => ({
                    material_id:   d.material_id,
                    material_name: d.material?.material_name || ('Material #' + d.material_id),
                    material_code: d.material?.material_code || '',
                    // effective_qty is a DB generated column (qty_required * (1 + scrap/100))
                    required_qty:  (parseFloat(d.effective_qty ?? d.qty_required) * this.multiplier).toFixed(3),
                    uom:           d.uom?.uom_code || ''
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
                        product_id:   this.form.product_id,
                        bom_id:       this.form.bom_id,
                        target_qty:   this.form.target_qty,
                        planned_date: this.form.planned_date,
                        rm_lines:     this.rmLines
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to create order');
                this.closeModal();
                // Redirect to MIR page so user can see the generated MIR
                window.location.href = `/org/${this.orgSlug}/production/mir`;
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
                        required_qty:  l.required_qty,
                        uom:           l.uom?.uom_code || ''
                    }));
                }
            } catch (e) {
                console.error('Failed to load order detail', e);
            } finally {
                this.viewModal.loading = false;
            }
        },

        async startOrder(order) {
            const confirmed = confirm(`Start production for ${order.order_no}?`);
            if (!confirmed) return;
            try {
                const res = await this._fetch(`/api/v1/production-orders/${order.id}/start`, { method: 'POST' });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to start production');
                await this.loadOrders();
                if (this.viewModal.show && this.viewModal.order?.id === order.id) {
                    await this.viewOrder({ ...order, status: 'IN_PROGRESS' });
                }
            } catch (e) {
                alert(e.message || 'Failed to start production');
            }
        },

        openConfirmFgModal(order) {
            this.confirmFgError = '';
            this.confirmFgModal = { show: true, submitting: false, order };
            this.confirmFgForm = {
                actual_qty: order.target_qty || '',
                rejected_qty: 0,
                rework_qty: 0,
                fg_bin_id: '',
                fg_warehouse_id: '',
                fg_batch_number: '',
                qc_required: false
            };
        },

        closeConfirmFgModal() {
            this.confirmFgModal = { show: false, submitting: false, order: null };
            this.confirmFgError = '';
        },

        async submitConfirmFg() {
            this.confirmFgError = '';
            this.confirmFgModal.submitting = true;
            try {
                const orderId = this.confirmFgModal.order.id;
                const payload = {
                    actual_qty: parseFloat(this.confirmFgForm.actual_qty || 0),
                    rejected_qty: parseFloat(this.confirmFgForm.rejected_qty || 0),
                    rework_qty: parseFloat(this.confirmFgForm.rework_qty || 0),
                    fg_batch_number: this.confirmFgForm.fg_batch_number || null,
                    qc_required: !!this.confirmFgForm.qc_required
                };
                if (this.confirmFgForm.fg_warehouse_id) payload.fg_warehouse_id = parseInt(this.confirmFgForm.fg_warehouse_id);
                if (this.confirmFgForm.fg_bin_id) payload.fg_bin_id = parseInt(this.confirmFgForm.fg_bin_id);

                const res = await this._fetch(`/api/v1/production-orders/${orderId}/confirm-fg`, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to confirm FG');
                this.closeConfirmFgModal();
                await this.loadOrders();
                if (this.viewModal.show && this.viewModal.order?.id === orderId) {
                    this.viewModal.show = false;
                }
            } catch (e) {
                this.confirmFgError = e.message || 'Failed to confirm FG';
            } finally {
                this.confirmFgModal.submitting = false;
            }
        },

        async openVarianceModal(order) {
            this.varianceModal = { show: true, loading: true, order, report: null };
            try {
                const res = await this._fetch(`/api/v1/production-orders/${order.id}/variance`);
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to load variance report');
                this.varianceModal.report = data.data;
            } catch (e) {
                alert(e.message || 'Failed to load variance report');
                this.closeVarianceModal();
            } finally {
                this.varianceModal.loading = false;
            }
        },

        closeVarianceModal() {
            this.varianceModal = { show: false, loading: false, order: null, report: null };
        },

        openModal() {
            this.form = { product_id: '', bom_id: '', target_qty: '', planned_date: '' };
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
        }
    }
}
</script>
@endsection
