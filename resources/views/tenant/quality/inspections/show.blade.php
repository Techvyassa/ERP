@extends('layouts.quality')

@section('title', 'QC Inspection Detail - ' . $organization->org_name)
@section('page-title', 'Inspection Detail')

@section('content')
<div x-data="qcInspectionDetail()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <button type="button" onclick="window.history.back()" class="inline-flex items-center gap-2 mb-3 px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Back
            </button>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lot <span x-text="'LOT-' + (lot.id || '')"></span></h2>
            <p class="text-sm text-gray-500">Record test results and submit the final QC decision.</p>
        </div>
        <div class="flex gap-2">
            <button x-show="lot.status === 'PENDING'" @click="startInspection()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Start</button>
            <button x-show="lot.status === 'IN_PROGRESS'" @click="completeInspection()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Complete</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Source</p><p class="font-semibold text-gray-900" x-text="lot.source_type || 'GRN'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Item</p><p class="font-semibold text-gray-900" x-text="lot.product?.product_name || lot.material?.material_name || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Reference</p><p class="font-semibold text-gray-900" x-text="lot.production_order?.order_no || lot.grn?.grn_number || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Batch</p><p class="font-semibold text-gray-900" x-text="lot.batch_number || lot.production_order?.fg_batch_number || '—'"></p></div>
        <div class="bg-white rounded-xl border border-gray-200 p-4"><p class="text-xs text-gray-500 font-semibold mb-1">Status</p><span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="(lot.status || '').replace(/_/g, ' ')"></span></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.05fr_0.95fr] gap-6">
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Recorded Results</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Parameter</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tolerance</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Observed</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="qcResults.length === 0">
                                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No test results recorded yet.</td></tr>
                            </template>
                            <template x-for="result in qcResults" :key="result.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900" x-text="result.parameter_name"></div>
                                        <div class="text-xs text-gray-400" x-text="result.parameter_code || 'Manual parameter'"></div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" x-text="formatTolerance(result)"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="result.observed_value + ' ' + (result.unit_of_measurement || '')"></td>
                                    <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-bold" :class="result.is_pass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="result.is_pass ? 'PASS' : 'FAIL'"></span></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="result.remarks || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="lot.status === 'IN_PROGRESS'" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Record Test Result</h3>
                    <p class="text-sm text-gray-500">Select a configured parameter or enter manual test details.</p>
                </div>

                <!-- Configured Parameter Selection -->
                <div x-show="qcParameters.length > 0">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Configured Parameter</label>
                    <select x-model="selectedParameterId" @change="onParameterChange()" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">— Select from list —</option>
                        <template x-for="param in qcParameters" :key="param.id">
                            <option :value="param.id"
                                    :disabled="isParameterRecorded(param.parameter_name)"
                                    x-text="param.parameter_name + ' • ' + param.parameter_code + (isParameterRecorded(param.parameter_name) ? ' ✓ Recorded' : '')"></option>
                        </template>
                    </select>
                </div>

                <!-- Manual Parameter Entry (when no configured parameters or user wants manual entry) -->
                <div x-show="!selectedParameterId && qcParameters.length > 0" class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs text-amber-800">
                        <span class="font-semibold">Note:</span> No parameter selected. You can manually enter parameter details below or select a configured parameter above.
                    </p>
                </div>

                <!-- Selected Parameter Details -->
                <div x-show="selectedParameterId" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                    <h4 class="text-sm font-bold text-blue-900 mb-3">Parameter Configuration (Auto-loaded)</h4>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-blue-600 font-semibold">Name:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.parameter_name || '—'"></span>
                        </div>
                        <div>
                            <span class="text-blue-600 font-semibold">Code:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.parameter_code || '—'"></span>
                        </div>
                        <div>
                            <span class="text-blue-600 font-semibold">Type:</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 ml-1" x-text="newResult.tolerance_type"></span>
                        </div>
                        <div>
                            <span class="text-blue-600 font-semibold">Unit:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.unit_of_measurement || '—'"></span>
                        </div>
                        <div x-show="newResult.tolerance_type === 'RANGE'">
                            <span class="text-blue-600 font-semibold">Tolerance Range:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.standard_min + ' to ' + newResult.standard_max"></span>
                        </div>
                        <div x-show="newResult.tolerance_type === 'MIN_ONLY'">
                            <span class="text-blue-600 font-semibold">Min Value:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.standard_min || '—'"></span>
                        </div>
                        <div x-show="newResult.tolerance_type === 'MAX_ONLY'">
                            <span class="text-blue-600 font-semibold">Max Value:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.standard_max || '—'"></span>
                        </div>
                        <div x-show="newResult.tolerance_type === 'EXACT'">
                            <span class="text-blue-600 font-semibold">Target Value:</span>
                            <span class="text-gray-800 ml-1" x-text="newResult.standard_value || '—'"></span>
                        </div>
                    </div>
                </div>

                <!-- Manual Entry Info -->
                <div x-show="!selectedParameterId" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-4">
                    <h4 class="text-sm font-bold text-gray-700 mb-3">Manual Parameter Entry</h4>
                    <p class="text-xs text-gray-600 mb-2">Enter parameter details manually. You must provide:</p>
                    <ul class="text-xs text-gray-600 space-y-1 ml-4 list-disc">
                        <li><strong>Parameter Name</strong> - What are you testing? (e.g., "Moisture")</li>
                        <li><strong>Tolerance Type</strong> - How will you judge it? (Range, Min Only, Max Only, or Exact)</li>
                        <li><strong>Standard Values</strong> - What is acceptable? (e.g., 10 to 20)</li>
                        <li><strong>Observed Value</strong> - What did you measure?</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-3"><strong>Note:</strong> Tolerance = The acceptable range/limit for your test.</p>
                </div>

                <!-- Duplicate Warning -->
                <div x-show="hasDuplicate()" class="bg-red-50 border border-red-200 rounded-lg p-4 mt-4">
                    <div class="flex items-start gap-2">
                        <span class="text-red-600 text-xl">⚠️</span>
                        <div>
                            <h4 class="text-sm font-bold text-red-800 mb-1">Duplicate Result Detected!</h4>
                            <p class="text-xs text-red-700" x-text="getDuplicateMessage()"></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Parameter fields - editable when no parameter selected, readonly when configured parameter is selected -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Parameter Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newResult.parameter_name" :placeholder="selectedParameterId ? 'From configuration' : 'Parameter name'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Parameter Code</label>
                        <input type="text" x-model="newResult.parameter_code" :placeholder="selectedParameterId ? 'From configuration' : 'Parameter code'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tolerance Type <span class="text-red-500">*</span></label>
                        <select x-model="newResult.tolerance_type" :disabled="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                            <option value="RANGE">Range</option>
                            <option value="MIN_ONLY">Min Only</option>
                            <option value="MAX_ONLY">Max Only</option>
                            <option value="EXACT">Exact</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unit</label>
                        <input type="text" x-model="newResult.unit_of_measurement" :placeholder="selectedParameterId ? 'From configuration' : 'Unit'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <!-- Standard values -->
                    <div x-show="['RANGE', 'MIN_ONLY'].includes(newResult.tolerance_type)">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Standard Min</label>
                        <input type="text" x-model="newResult.standard_min" :placeholder="selectedParameterId ? 'From configuration' : 'e.g., 10.5'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <div x-show="['RANGE', 'MAX_ONLY'].includes(newResult.tolerance_type)">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Standard Max</label>
                        <input type="text" x-model="newResult.standard_max" :placeholder="selectedParameterId ? 'From configuration' : 'e.g., 20.5'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <div x-show="newResult.tolerance_type === 'EXACT'">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Standard Value</label>
                        <input type="text" x-model="newResult.standard_value" :placeholder="selectedParameterId ? 'From configuration' : 'e.g., 15.0'" :readonly="selectedParameterId" class="px-3 py-2 border rounded-lg text-sm" :class="selectedParameterId ? 'border-gray-200 bg-gray-50' : 'border-primary/30 focus:ring-2 focus:ring-primary/20 focus:border-primary'">
                    </div>
                    <!-- Observed value (always editable) -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Observed Value <span class="text-red-500">*</span></label>
                        <input type="number" step="0.0001" x-model="newResult.observed_value" placeholder="Enter measured value" class="px-3 py-2 border border-primary/30 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks</label>
                    <textarea x-model="newResult.remarks" rows="2" placeholder="Optional remarks about this test result" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></textarea>
                </div>

                <div class="flex justify-end">
                    <button @click="addResult()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Record Result</button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Trigger button to open decision modal -->
            <div class="bg-white rounded-xl border border-gray-200 p-5" x-show="lot.status === 'COMPLETED' && !showDecisionModal">
                <h3 class="font-semibold text-gray-900 mb-4">Usage Decision Required</h3>
                <p class="text-sm text-gray-600 mb-4">All test results have been recorded. Please make the final usage decision.</p>
                <button @click="openDecisionModal()" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">assignment_turned_in</span>
                    Make Usage Decision
                </button>
            </div>

            <!-- QC Barcode / Certificate Modal -->
            <div x-show="showBarcodeModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900/60" @click="showBarcodeModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg" id="qc-certificate">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-600">verified</span>
                                <h3 class="text-lg font-bold text-gray-900">QC Certificate</h3>
                            </div>
                            <button @click="showBarcodeModal = false" class="text-gray-400 hover:text-gray-600">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <!-- Certificate Body -->
                        <div class="p-6 space-y-4">
                            <!-- Decision Badge -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Inspection Lot</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="'LOT-' + (lot.id || '')"></p>
                                </div>
                                <span class="px-3 py-1.5 rounded-full text-sm font-bold"
                                      :class="{
                                          'bg-green-100 text-green-700': lot.usage_decision?.decision === 'ACCEPTED',
                                          'bg-red-100 text-red-700': lot.usage_decision?.decision === 'REJECTED',
                                          'bg-amber-100 text-amber-700': lot.usage_decision?.decision === 'CONDITIONALLY_ACCEPTED',
                                          'bg-blue-100 text-blue-700': lot.usage_decision?.decision === 'REWORK_REQUIRED',
                                      }"
                                      x-text="(lot.usage_decision?.decision || '').replace(/_/g, ' ')">
                                </span>
                            </div>

                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-3 text-sm bg-gray-50 rounded-lg p-4">
                                <div>
                                    <p class="text-xs text-gray-500">Item</p>
                                    <p class="font-semibold text-gray-900 truncate" x-text="lot.product?.product_name || lot.material?.material_name || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Batch / Reference</p>
                                    <p class="font-semibold text-gray-900" x-text="lot.batch_number || lot.production_order?.fg_batch_number || lot.grn?.grn_number || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Accepted Qty</p>
                                    <p class="font-semibold text-green-700" x-text="parseFloat(lot.usage_decision?.accepted_qty || 0).toFixed(3)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Rejected Qty</p>
                                    <p class="font-semibold text-red-600" x-text="parseFloat(lot.usage_decision?.rejected_qty || 0).toFixed(3)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Source</p>
                                    <p class="font-semibold text-gray-900" x-text="lot.source_type || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Decision Date</p>
                                    <p class="font-semibold text-gray-900" x-text="lot.usage_decision?.decided_at ? new Date(lot.usage_decision.decided_at).toLocaleDateString() : new Date().toLocaleDateString()"></p>
                                </div>
                            </div>

                            <!-- Barcode -->
                            <div class="flex flex-col items-center py-4 border border-gray-200 rounded-lg bg-white">
                                <svg id="qc-barcode"></svg>
                                <p class="text-xs text-gray-400 mt-1" x-text="barcodeValue"></p>
                            </div>

                            <!-- Remarks -->
                            <div x-show="lot.usage_decision?.remarks" class="text-xs text-gray-600 bg-gray-50 rounded-lg px-4 py-2">
                                <span class="font-semibold">Remarks:</span> <span x-text="lot.usage_decision?.remarks"></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 px-6 pb-6">
                            <button @click="showBarcodeModal = false" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Close</button>
                            <button @click="printCertificate()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">print</span>
                                Print Certificate
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision Modal -->
            <div x-show="showDecisionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900/50" @click="showDecisionModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl">
                        <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900">Make Usage Decision</h3>
                            <button @click="showDecisionModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                        </div>
                        <form @submit.prevent="makeDecision()" class="p-6 space-y-4">
                            <!-- Info Banner -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                                <strong>Important:</strong> This decision will determine the final stock status and cannot be easily reversed.
                            </div>

                            <!-- Decision Type -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Decision <span class="text-red-500">*</span></label>
                                <select x-model="decision.decision" @change="applyDecisionDefaults()" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="">Select decision</option>
                                    <option value="ACCEPTED">✅ Accepted - Stock released to unrestricted use</option>
                                    <option value="REJECTED">❌ Rejected - Stock blocked, RTV initiated</option>
                                    <option value="CONDITIONALLY_ACCEPTED">⚠️ Conditionally Accepted - Stock restricted (requires override approval)</option>
                                    <option value="REWORK_REQUIRED">🔄 Rework Required - Material needs reprocessing</option>
                                </select>
                            </div>

                            <!-- Quantity Fields -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Accepted Qty <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.001" min="0" x-model="decision.accepted_qty" @input="syncDecisionQty('accepted')" @blur="normalizeDecisionQty('accepted')" required placeholder="e.g., 100.000" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-gray-500 mt-1">Quantity approved for use</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rejected Qty <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.001" min="0" x-model="decision.rejected_qty" @input="syncDecisionQty('rejected')" @blur="normalizeDecisionQty('rejected')" required placeholder="e.g., 0.000" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-gray-500 mt-1">Quantity not approved</p>
                                </div>
                            </div>

                            <!-- Rejected Qty Disposition -->
                            <div x-show="parseFloat(decision.rejected_qty) > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-red-800 uppercase tracking-wide">Rejected Qty Disposition</p>
                                    <span class="text-xs text-red-600">Must total <span class="font-bold" x-text="parseFloat(decision.rejected_qty).toFixed(3)"></span></span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                                Return to Vendor
                                            </span>
                                        </label>
                                        <input type="number" step="0.001" min="0"
                                               x-model="decision.return_qty"
                                               @input="syncDisposition('return')"
                                               @blur="normalizeDisposition()"
                                               placeholder="0.000"
                                               class="w-full px-3 py-2 border border-red-200 rounded-lg text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400">
                                        <input type="text" x-model="decision.return_remarks" placeholder="Return reason (optional)" class="w-full mt-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-orange-500 inline-block"></span>
                                                Scrap / Write-off
                                            </span>
                                        </label>
                                        <input type="number" step="0.001" min="0"
                                               x-model="decision.scrap_qty"
                                               @input="syncDisposition('scrap')"
                                               @blur="normalizeDisposition()"
                                               placeholder="0.000"
                                               class="w-full px-3 py-2 border border-orange-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-200 focus:border-orange-400">
                                        <input type="text" x-model="decision.scrap_remarks" placeholder="Scrap reason (optional)" class="w-full mt-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs">
                                    </div>
                                </div>
                                <!-- Running total indicator -->
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-red-200">
                                    <span class="text-gray-500">Allocated:</span>
                                    <span :class="dispositionBalanced() ? 'text-green-600 font-bold' : 'text-red-600 font-bold'"
                                          x-text="(parseFloat(decision.return_qty||0) + parseFloat(decision.scrap_qty||0)).toFixed(3) + ' / ' + parseFloat(decision.rejected_qty).toFixed(3)"></span>
                                </div>
                                <p x-show="!dispositionBalanced() && (parseFloat(decision.return_qty||0) + parseFloat(decision.scrap_qty||0)) > 0"
                                   class="text-xs text-red-600">Return + Scrap must equal the rejected qty.</p>
                            </div>

                            <!-- Remarks -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks <span class="text-red-500">*</span></label>
                                <textarea x-model="decision.remarks" required rows="3" placeholder="Provide justification for this decision..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <button type="button" @click="showDecisionModal = false" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">Cancel</button>
                                <button type="submit" :disabled="saving" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50 transition">
                                    <span x-show="!saving">Submit Decision</span>
                                    <span x-show="saving">Processing...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5" x-show="lot.usage_decision">
                <h3 class="font-semibold text-gray-900 mb-4">Decision Summary</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span class="text-gray-500">Decision</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.decision || '—'"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Accepted Qty</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.accepted_qty || '0'"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Rejected Qty</span><span class="font-semibold text-gray-900" x-text="lot.usage_decision?.rejected_qty || '0'"></span></div>
                    <template x-if="parseFloat(lot.usage_decision?.rejected_qty) > 0">
                        <div class="pl-3 border-l-2 border-red-200 space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-red-600">↩ Return to Vendor</span>
                                <span class="font-semibold text-gray-800" x-text="lot.usage_decision?.return_qty || '0.000'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-orange-600">🗑 Scrap</span>
                                <span class="font-semibold text-gray-800" x-text="lot.usage_decision?.scrap_qty || '0.000'"></span>
                            </div>
                        </div>
                    </template>
                    <div><p class="text-gray-500 mb-1">Remarks</p><p class="text-gray-900" x-text="lot.usage_decision?.remarks || '—'"></p></div>
                </div>
                <button @click="openBarcodeModal()" class="mt-4 w-full px-4 py-2 border border-indigo-200 text-indigo-700 rounded-lg text-sm hover:bg-indigo-50 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">barcode</span>
                    View QC Certificate &amp; Barcode
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function qcInspectionDetail() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const lotId = {{ $lotId }};
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug, 'Content-Type': 'application/json' });

    return {
        lot: {},
        qcResults: [],
        qcParameters: [],
        selectedParameterId: '',
        newResult: { parameter_name: '', parameter_code: '', standard_min: '', standard_max: '', standard_value: '', unit_of_measurement: '', tolerance_type: 'RANGE', observed_value: '', remarks: '' },
        decision: { decision: '', accepted_qty: '', rejected_qty: '', return_qty: '', scrap_qty: '', return_remarks: '', scrap_remarks: '', remarks: '' },
        showDecisionModal: false,
        showBarcodeModal: false,
        barcodeValue: '',
        saving: false,

        async init() { await this.loadLot(); },

        async loadLot() {
            const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
            const data = await res.json();
            this.lot = data.data || {};
            this.qcResults = this.lot.test_results || [];
            if (this.lot.material_id) {
                const paramRes = await fetch(`/api/v1/qc/parameters/${this.lot.material_id}`, { headers: headers() });
                const paramData = await paramRes.json();
                this.qcParameters = paramData.data || [];
            } else {
                this.qcParameters = [];
            }
        },

        onParameterChange() {
            const param = this.qcParameters.find(item => item.id == this.selectedParameterId);
            if (!param) return;
            this.newResult = {
                parameter_name: param.parameter_name || '',
                parameter_code: param.parameter_code || '',
                standard_min: param.standard_min || '',
                standard_max: param.standard_max || '',
                standard_value: param.standard_value || '',
                unit_of_measurement: param.unit_of_measurement || '',
                tolerance_type: param.tolerance_type || 'RANGE',
                observed_value: '',
                remarks: ''
            };
        },

        async startInspection() {
            const res = await fetch(`/api/v1/qc/${lotId}/start`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to start inspection');
            await this.loadLot();
        },

        async completeInspection() {
            const res = await fetch(`/api/v1/qc/${lotId}/complete`, { method: 'PATCH', headers: headers() });
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Failed to complete inspection');
            await this.loadLot();
        },

        async addResult() {
            // Validate observed value before sending
            if (!this.newResult.observed_value || this.newResult.observed_value === '') {
                return alert('Please enter an observed value');
            }
            
            // Validate parameter name
            if (!this.newResult.parameter_name || this.newResult.parameter_name === '') {
                return alert('Please enter a parameter name');
            }
            
            // Validate tolerance type
            if (!this.newResult.tolerance_type || this.newResult.tolerance_type === '') {
                return alert('Please select a tolerance type (Range, Min Only, Max Only, or Exact)');
            }
            
            // Validate standard values based on tolerance type
            if (this.newResult.tolerance_type === 'RANGE') {
                if (!this.newResult.standard_min && !this.newResult.standard_max) {
                    return alert('For RANGE tolerance, please enter at least Standard Min OR Standard Max');
                }
            } else if (this.newResult.tolerance_type === 'MIN_ONLY') {
                if (!this.newResult.standard_min) {
                    return alert('For MIN ONLY tolerance, please enter Standard Min value');
                }
            } else if (this.newResult.tolerance_type === 'MAX_ONLY') {
                if (!this.newResult.standard_max) {
                    return alert('For MAX ONLY tolerance, please enter Standard Max value');
                }
            } else if (this.newResult.tolerance_type === 'EXACT') {
                if (!this.newResult.standard_value) {
                    return alert('For EXACT tolerance, please enter Standard Value');
                }
            }
            
            // DUPLICATE CHECK: Prevent re-entering the same parameter name
            const isDuplicate = this.qcResults.some(result =>
                result.parameter_name.toLowerCase() === this.newResult.parameter_name.toLowerCase()
            );

            if (isDuplicate) {
                return alert(`⚠️ Duplicate Parameter!\n\nA test result for "${this.newResult.parameter_name}" has already been recorded for this lot.\n\nEach parameter can only be recorded once.`);
            }
            
            const payload = { 
                ...this.newResult, 
                observed_value: parseFloat(this.newResult.observed_value) 
            };
            
            // Remove empty/null fields to avoid validation issues
            if (!payload.parameter_code) delete payload.parameter_code;
            if (!payload.standard_min) delete payload.standard_min;
            if (!payload.standard_max) delete payload.standard_max;
            if (!payload.standard_value) delete payload.standard_value;
            if (!payload.unit_of_measurement) delete payload.unit_of_measurement;
            if (!payload.remarks) delete payload.remarks;
            
            const res = await fetch(`/api/v1/qc/${lotId}/test-results`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) {
                // Show detailed error message
                let errorMsg = data.message || 'Failed to record result';
                if (data.error?.details) {
                    Object.entries(data.error.details).forEach(([field, errors]) => {
                        errorMsg += '\n\n' + field + ':\n  - ' + (Array.isArray(errors) ? errors.join('\n  - ') : errors);
                    });
                }
                return alert(errorMsg);
            }
            this.selectedParameterId = '';
            this.newResult = { parameter_name: '', parameter_code: '', standard_min: '', standard_max: '', standard_value: '', unit_of_measurement: '', tolerance_type: 'RANGE', observed_value: '', remarks: '' };
            await this.loadLot();
        },

        async makeDecision() {
            // Validate decision
            if (!this.decision.decision) {
                return alert('Please select a decision');
            }
            
            // Validate quantities
            const acceptedQty = parseFloat(this.decision.accepted_qty) || 0;
            const rejectedQty = parseFloat(this.decision.rejected_qty) || 0;
            
            if (acceptedQty < 0 || rejectedQty < 0) {
                return alert('Quantities cannot be negative');
            }
            
            if (acceptedQty === 0 && rejectedQty === 0) {
                return alert('At least one of Accepted Qty or Rejected Qty must be greater than zero');
            }

            // Validate disposition split when rejected qty > 0
            if (rejectedQty > 0) {
                const returnQty = parseFloat(this.decision.return_qty) || 0;
                const scrapQty  = parseFloat(this.decision.scrap_qty)  || 0;
                if (Math.round((returnQty + scrapQty) * 1000) > Math.round(rejectedQty * 1000)) {
                    return alert('Return qty + Scrap qty cannot exceed rejected qty (' + rejectedQty.toFixed(3) + ')');
                }
                // Default: all rejected goes to return if nothing entered
                if (returnQty === 0 && scrapQty === 0) {
                    this.decision.return_qty = rejectedQty.toFixed(3);
                }
            }

            if (!this.decision.remarks || this.decision.remarks.trim() === '') {
                return alert('Please provide remarks for your decision');
            }
            
            this.saving = true;
            
            try {
                const payload = {
                    decision:       this.decision.decision,
                    accepted_qty:   acceptedQty,
                    rejected_qty:   rejectedQty,
                    return_qty:     rejectedQty > 0 ? (parseFloat(this.decision.return_qty) || 0) : 0,
                    scrap_qty:      rejectedQty > 0 ? (parseFloat(this.decision.scrap_qty)  || 0) : 0,
                    return_remarks: this.decision.return_remarks || null,
                    scrap_remarks:  this.decision.scrap_remarks  || null,
                    remarks:        this.decision.remarks,
                };
                
                const res = await fetch(`/api/v1/qc/${lotId}/decision`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (!data.success) {
                    let errorMsg = data.message || 'Failed to make decision';
                    if (data.error?.details) {
                        Object.entries(data.error.details).forEach(([field, errors]) => {
                            errorMsg += '\n\n' + field + ':\n  - ' + (Array.isArray(errors) ? errors.join('\n  - ') : errors);
                        });
                    }
                    return alert(errorMsg);
                }
                
                // Success - show barcode certificate modal
                this.showDecisionModal = false;
                await this.loadLot();
                this.openBarcodeModal();
            } catch (error) {
                console.error('Decision error:', error);
                alert('An error occurred while submitting the decision');
            } finally {
                this.saving = false;
            }
        },

        openDecisionModal() {
            // Reset decision form
            this.decision = { decision: '', accepted_qty: '', rejected_qty: '', return_qty: '', scrap_qty: '', return_remarks: '', scrap_remarks: '', remarks: '' };
            
            // Auto-calculate quantities from test results
            this.calculateDefaultQuantities();
            
            this.showDecisionModal = true;
        },

        calculateDefaultQuantities() {
            const lotQty = parseFloat(this.lot?.lot_qty) || 0;
            if (lotQty <= 0) {
                return;
            }
            
            const passCount = this.qcResults.filter(r => r.is_pass === true).length;
            const failCount = this.qcResults.filter(r => r.is_pass === false).length;
            const totalCount = this.qcResults.length;
            
            // If all results PASS → Set as accepted
            if (passCount === totalCount && passCount > 0) {
                this.decision.decision = 'ACCEPTED';
                this.decision.accepted_qty = lotQty.toFixed(3);
                this.decision.rejected_qty = '0.000';
            }
            // If all results FAIL → Set as rejected
            else if (failCount === totalCount && failCount > 0) {
                this.decision.decision = 'REJECTED';
                this.decision.accepted_qty = '0.000';
                this.decision.rejected_qty = lotQty.toFixed(3);
            }
            // Mixed results → Split based on pass/fail ratio
            else {
                this.decision.decision = totalCount > 0 ? 'CONDITIONALLY_ACCEPTED' : 'ACCEPTED';
                this.decision.accepted_qty = lotQty.toFixed(3);
                this.decision.rejected_qty = '0.000';
            }
            this.applyDecisionDefaults();
        },

        applyDecisionDefaults() {
            const lotQty = parseFloat(this.lot?.lot_qty) || 0;
            const currentRemarks = (this.decision.remarks || '').trim();
            const hasManualRemarks = currentRemarks !== '' && !this.isAutoDecisionRemark(currentRemarks);

            if (this.decision.decision === 'ACCEPTED') {
                this.decision.accepted_qty = lotQty.toFixed(3);
                this.decision.rejected_qty = '0.000';
                if (!hasManualRemarks) this.decision.remarks = this.getDefaultDecisionRemark('ACCEPTED');
            } else if (this.decision.decision === 'REJECTED') {
                this.decision.accepted_qty = '0.000';
                this.decision.rejected_qty = lotQty.toFixed(3);
                if (!hasManualRemarks) this.decision.remarks = this.getDefaultDecisionRemark('REJECTED');
            } else if (this.decision.decision === 'CONDITIONALLY_ACCEPTED') {
                if (!this.decision.accepted_qty && !this.decision.rejected_qty) {
                    this.decision.accepted_qty = lotQty.toFixed(3);
                    this.decision.rejected_qty = '0.000';
                }
                if (!hasManualRemarks) this.decision.remarks = this.getDefaultDecisionRemark('CONDITIONALLY_ACCEPTED');
            } else if (this.decision.decision === 'REWORK_REQUIRED') {
                if (!this.decision.accepted_qty && !this.decision.rejected_qty) {
                    this.decision.accepted_qty = '0.000';
                    this.decision.rejected_qty = lotQty.toFixed(3);
                }
                if (!hasManualRemarks) this.decision.remarks = this.getDefaultDecisionRemark('REWORK_REQUIRED');
            }
        },

        getDefaultDecisionRemark(decision) {
            return {
                ACCEPTED: 'Accepted after QC inspection.',
                REJECTED: 'Rejected after QC inspection.',
                CONDITIONALLY_ACCEPTED: 'Conditionally accepted after QC inspection.',
                REWORK_REQUIRED: 'Rework required based on QC inspection.'
            }[decision] || '';
        },

        isAutoDecisionRemark(remarks) {
            return Object.values({
                ACCEPTED: 'Accepted after QC inspection.',
                REJECTED: 'Rejected after QC inspection.',
                CONDITIONALLY_ACCEPTED: 'Conditionally accepted after QC inspection.',
                REWORK_REQUIRED: 'Rework required based on QC inspection.'
            }).includes(remarks);
        },

        syncDecisionQty(changedField) {
            const lotQty = parseFloat(this.lot?.lot_qty) || 0;
            if (lotQty <= 0) {
                return;
            }

            if (changedField === 'rejected') {
                let rejectedQty = parseFloat(this.decision.rejected_qty);
                if (!Number.isFinite(rejectedQty)) {
                    this.decision.accepted_qty = lotQty.toFixed(3);
                    return;
                }
                rejectedQty = Math.min(Math.max(rejectedQty, 0), lotQty);
                this.decision.accepted_qty = (lotQty - rejectedQty).toFixed(3);
                return;
            }

            let acceptedQty = parseFloat(this.decision.accepted_qty);
            if (!Number.isFinite(acceptedQty)) {
                this.decision.rejected_qty = lotQty.toFixed(3);
                return;
            }
            acceptedQty = Math.min(Math.max(acceptedQty, 0), lotQty);
            this.decision.rejected_qty = (lotQty - acceptedQty).toFixed(3);
        },

        normalizeDecisionQty(changedField) {
            const lotQty = parseFloat(this.lot?.lot_qty) || 0;
            if (lotQty <= 0) {
                return;
            }

            if (changedField === 'rejected') {
                let rejectedQty = parseFloat(this.decision.rejected_qty);
                rejectedQty = Number.isFinite(rejectedQty) ? rejectedQty : 0;
                rejectedQty = Math.min(Math.max(rejectedQty, 0), lotQty);
                this.decision.rejected_qty = rejectedQty.toFixed(3);
                this.decision.accepted_qty = (lotQty - rejectedQty).toFixed(3);
                return;
            }

            let acceptedQty = parseFloat(this.decision.accepted_qty);
            acceptedQty = Number.isFinite(acceptedQty) ? acceptedQty : 0;
            acceptedQty = Math.min(Math.max(acceptedQty, 0), lotQty);
            this.decision.accepted_qty = acceptedQty.toFixed(3);
            this.decision.rejected_qty = (lotQty - acceptedQty).toFixed(3);
        },

        formatTolerance(result) {
            // If no tolerance type, show "No tolerance set"
            if (!result.tolerance_type || result.tolerance_type === '') {
                return 'No tolerance set';
            }
            
            // Show based on tolerance type
            if (result.tolerance_type === 'RANGE') {
                if (result.standard_min && result.standard_max) {
                    return `${result.standard_min} to ${result.standard_max}`;
                }
                return 'Range not set';
            }
            if (result.tolerance_type === 'MIN_ONLY') {
                if (result.standard_min) {
                    return `>= ${result.standard_min}`;
                }
                return 'Min not set';
            }
            if (result.tolerance_type === 'MAX_ONLY') {
                if (result.standard_max) {
                    return `<= ${result.standard_max}`;
                }
                return 'Max not set';
            }
            if (result.tolerance_type === 'EXACT') {
                if (result.standard_value) {
                    return result.standard_value;
                }
                return 'Target not set';
            }
            return '—';
        },

        statusClass(value) {
            return {
                'PENDING': 'bg-amber-100 text-amber-700',
                'IN_PROGRESS': 'bg-blue-100 text-blue-700',
                'COMPLETED': 'bg-green-100 text-green-700',
                'DECISION_MADE': 'bg-purple-100 text-purple-700'
            }[value] || 'bg-gray-100 text-gray-600';
        },

        // Check if the current parameter name is already recorded
        hasDuplicate() {
            if (!this.newResult.parameter_name) return false;
            return this.qcResults.some(result =>
                result.parameter_name.toLowerCase() === this.newResult.parameter_name.toLowerCase()
            );
        },

        // Get duplicate warning message
        getDuplicateMessage() {
            if (!this.newResult.parameter_name) return '';
            return `"${this.newResult.parameter_name}" has already been recorded for this lot. Each parameter can only be recorded once.`;
        },

        // Sync return/scrap qty so they don't exceed rejected_qty
        syncDisposition(changed) {
            const rejected = parseFloat(this.decision.rejected_qty) || 0;
            if (rejected <= 0) return;
            if (changed === 'return') {
                let v = parseFloat(this.decision.return_qty) || 0;
                v = Math.min(Math.max(v, 0), rejected);
                this.decision.scrap_qty = (rejected - v).toFixed(3);
            } else {
                let v = parseFloat(this.decision.scrap_qty) || 0;
                v = Math.min(Math.max(v, 0), rejected);
                this.decision.return_qty = (rejected - v).toFixed(3);
            }
        },

        normalizeDisposition() {
            const rejected = parseFloat(this.decision.rejected_qty) || 0;
            let r = Math.min(Math.max(parseFloat(this.decision.return_qty) || 0, 0), rejected);
            let s = Math.min(Math.max(parseFloat(this.decision.scrap_qty)  || 0, 0), rejected);
            if (Math.round((r + s) * 1000) > Math.round(rejected * 1000)) s = rejected - r;
            this.decision.return_qty = r.toFixed(3);
            this.decision.scrap_qty  = s.toFixed(3);
        },

        dispositionBalanced() {
            const rejected  = Math.round((parseFloat(this.decision.rejected_qty) || 0) * 1000);
            const allocated = Math.round(((parseFloat(this.decision.return_qty) || 0) + (parseFloat(this.decision.scrap_qty) || 0)) * 1000);
            return rejected === 0 || allocated === rejected;
        },

        isParameterRecorded(parameterName) {
            return this.qcResults.some(result =>
                result.parameter_name.toLowerCase() === parameterName.toLowerCase()
            );
        },

        openBarcodeModal() {
            // Build barcode value: QC-{lotId}-{decision}-{date}
            const decision = (this.lot.usage_decision?.decision || 'DECISION').substring(0, 3);
            const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            this.barcodeValue = `QC-${lotId}-${decision}-${date}`;
            this.showBarcodeModal = true;

            this.$nextTick(() => {
                try {
                    JsBarcode('#qc-barcode', this.barcodeValue, {
                        format: 'CODE128',
                        width: 2,
                        height: 60,
                        displayValue: false,
                        margin: 8,
                    });
                } catch (e) {
                    console.error('Barcode generation failed', e);
                }
            });
        },

        printCertificate() {
            const el = document.getElementById('qc-certificate');
            const win = window.open('', '_blank', 'width=600,height=700');
            win.document.write(`
                <html><head><title>QC Certificate - LOT-${lotId}</title>
                <style>
                    body { font-family: Inter, sans-serif; padding: 24px; color: #111; }
                    .label { font-size: 11px; color: #6b7280; text-transform: uppercase; }
                    .value { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
                    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f9fafb; padding: 16px; border-radius: 8px; margin: 12px 0; }
                    .barcode-wrap { text-align: center; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 12px 0; }
                    .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 13px; }
                    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
                    @media print { button { display: none; } }
                </style></head><body>
                ${el.innerHTML}
                <script>window.onload = function(){ window.print(); }<\/script>
                </body></html>
            `);
            win.document.close();
        },
    };
}
</script>
@endsection
