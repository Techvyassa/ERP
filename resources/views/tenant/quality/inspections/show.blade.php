@extends('layouts.quality')

@section('title', 'QC Inspection Detail - ' . $organization->org_name)
@section('page-title', 'Inspection Detail')

@section('content')
<div x-data="qcInspectionDetail()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Inspection Lot <span x-text="'LOT-' + lot.id"></span></h2>
            <p class="text-gray-500 text-sm">Record test results and manage QC inspection</p>
        </div>
        <div class="flex gap-2">
            <button x-show="lot.status === 'PENDING'" @click="startInspection()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <span class="material-symbols-outlined inline mr-2">play_arrow</span> Start Inspection
            </button>
            <button x-show="lot.status === 'IN_PROGRESS'" @click="completeInspection()" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <span class="material-symbols-outlined inline mr-2">check</span> Complete Inspection
            </button>
        </div>
    </div>

    <!-- Lot Details -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Material</p>
            <p class="font-semibold text-gray-900" x-text="lot.material?.material_name || '—'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">GRN Number</p>
            <p class="font-semibold text-gray-900" x-text="lot.grn?.grn_number || '—'"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Sample Size</p>
            <p class="font-semibold text-gray-900" x-text="lot.sample_size"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 font-semibold mb-1">Status</p>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="statusClass(lot.status)" x-text="lot.status?.replace(/_/g,' ')"></span>
        </div>
    </div>

    <!-- QC Parameters & Test Results Section -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <!-- Header with Parameters Info -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Test Parameters & Results</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        <span class="material-symbols-outlined inline text-sm mr-1">info</span>
                        <span x-text="qcParameters.length"></span> parameters configured for this material
                    </p>
                </div>
                <button x-show="lot.status === 'IN_PROGRESS'" @click="showAddResultModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                    <span class="material-symbols-outlined inline mr-1 text-sm">add_circle</span> Record Result
                </button>
            </div>
        </div>

        <!-- QC Parameters Reference Table -->
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Reference Specifications</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <template x-for="param in qcParameters" :key="param.id">
                    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h5 class="font-semibold text-gray-900 text-sm" x-text="param.parameter_name"></h5>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700" x-text="param.parameter_code"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-text="param.parameter_category"></p>
                            </div>
                            <span class="text-xs font-medium" :class="param.is_critical ? 'text-red-600 bg-red-50 px-1.5 py-0.5 rounded' : 'text-gray-400'" x-text="param.is_critical ? 'CRITICAL' : 'Standard'"></span>
                        </div>
                        
                        <!-- Tolerance Info -->
                        <div class="mt-2 text-xs text-gray-600 space-y-1">
                            <div class="flex items-center gap-1.5" x-show="param.tolerance_type === 'RANGE'">
                                <span class="material-symbols-outlined text-gray-400 text-xs">trending_up</span>
                                <span class="font-mono" x-text="param.standard_min + ' to ' + param.standard_max + ' ' + (param.unit_of_measurement || '')"></span>
                            </div>
                            <div class="flex items-center gap-1.5" x-show="param.tolerance_type === 'MIN_ONLY'">
                                <span class="material-symbols-outlined text-gray-400 text-xs">arrow_downward</span>
                                <span class="font-mono" x-text="'> ' + param.standard_min + ' ' + (param.unit_of_measurement || '')"></span>
                            </div>
                            <div class="flex items-center gap-1.5" x-show="param.tolerance_type === 'MAX_ONLY'">
                                <span class="material-symbols-outlined text-gray-400 text-xs">arrow_upward</span>
                                <span class="font-mono" x-text="'< ' + param.standard_max + ' ' + (param.unit_of_measurement || '')"></span>
                            </div>
                            <div class="flex items-center gap-1.5" x-show="param.tolerance_type === 'EXACT'">
                                <span class="material-symbols-outlined text-gray-400 text-xs">center_focus_strong</span>
                                <span class="font-mono" x-text="param.standard_value + ' ' + (param.unit_of_measurement || '')"></span>
                            </div>
                        </div>
                        
                        <!-- Test Method -->
                        <div class="mt-2 pt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-xs">test_tube</span>
                                <span x-text="param.test_method || 'Standard method'"></span>
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Test Results Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-600 uppercase">Parameter</th>
                        <th class="text-center py-3 px-5 text-xs font-bold text-gray-600 uppercase">Tolerance</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-600 uppercase">Standard</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-600 uppercase">Observed</th>
                        <th class="text-center py-3 px-5 text-xs font-bold text-gray-600 uppercase">Result</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-600 uppercase">Status</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-600 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="qcResults.length === 0">
                        <tr>
                            <td colspan="7" class="py-10 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <span class="material-symbols-outlined text-4xl mb-3 opacity-50">not_listed_location</span>
                                    <p class="text-sm">No test results recorded yet</p>
                                    <p class="text-xs mt-1 text-gray-500">Click "Record Result" to add test results</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="result in qcResults" :key="result.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 text-sm" x-text="result.parameter_name"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600" x-text="result.parameter_code"></span>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-center">
                                <span class="text-xs text-gray-500" x-text="result.tolerance_type || '—'"></span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <div class="text-xs text-gray-600 space-y-0.5">
                                    <p x-show="result.tolerance_type === 'RANGE'" class="font-mono text-gray-700" x-text="result.standard_min + ' - ' + result.standard_max"></p>
                                    <p x-show="result.tolerance_type === 'MIN_ONLY'" class="font-mono text-gray-700" x-text="'> ' + result.standard_min"></p>
                                    <p x-show="result.tolerance_type === 'MAX_ONLY'" class="font-mono text-gray-700" x-text="'< ' + result.standard_max"></p>
                                    <p x-show="result.tolerance_type === 'EXACT'" class="font-mono text-gray-700" x-text="result.standard_value"></p>
                                </div>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <span class="font-bold text-gray-900 text-sm" x-text="result.observed_value"></span>
                                <span class="text-xs text-gray-400 ml-1" x-text="result.unit_of_measurement || ''"></span>
                            </td>
                            <td class="py-3 px-5 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full" :class="result.is_pass === true ? 'bg-green-100 text-green-600' : (result.is_pass === false ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400')">
                                    <span class="material-symbols-outlined text-xs" x-text="result.is_pass === true ? 'check' : (result.is_pass === false ? 'close' : 'help')"></span>
                                </span>
                            </td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded text-xs font-bold" :class="result.is_pass === true ? 'bg-green-100 text-green-700' : (result.is_pass === false ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')" x-text="result.is_pass === true ? 'PASS' : (result.is_pass === false ? 'FAIL' : '—')"></span>
                            </td>
                            <td class="py-3 px-5">
                                <span class="text-xs text-gray-500 truncate max-w-[150px]" x-text="result.remarks || '—'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Usage Decision Section -->
    <div x-show="lot.status === 'COMPLETED'" class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4">Usage Decision</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Decision *</label>
                <select x-model="decision.decision" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Select decision</option>
                    <option value="ACCEPTED">Accepted - Stock Released</option>
                    <option value="REJECTED">Rejected - Return to Vendor</option>
                    <option value="CONDITIONAL">Conditional - Requires Approval</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                <textarea x-model="decision.remarks" rows="3"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Add any remarks or notes..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="makeDecision()" :disabled="!decision.decision || saving"
                    class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50">
                    <span x-show="!saving">Make Decision</span>
                    <span x-show="saving">Processing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Test Result Section (Inline Form) -->
    <div x-show="lot.status === 'IN_PROGRESS'" class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">add_circle</span>
            Record New Test Result
        </h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Parameter Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Parameter *</label>
                <div class="relative">
                    <select x-model="selectedParameterId" @change="onParameterChange" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-600 appearance-none">
                        <option value="">Select a parameter</option>
                        <template x-for="param in qcParameters" :key="param.id">
                            <option :value="param.id" 
                                :data-standard_min="param.standard_min"
                                :data-standard_max="param.standard_max"
                                :data-standard_value="param.standard_value"
                                :data-tolerance_type="param.tolerance_type"
                                :data-unit_of_measurement="param.unit_of_measurement"
                                :data-parameter_name="param.parameter_name"
                                :data-parameter_code="param.parameter_code"
                                :data-is_critical="param.is_critical">
                                <span class="font-medium" x-text="param.parameter_name"></span>
                                <span class="text-xs text-gray-500 ml-2" x-text="param.parameter_code"></span>
                                <span class="text-xs text-gray-400 ml-2" x-text="param.tolerance_type"></span>
                            </option>
                        </template>
                    </select>
                    <span class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                        <span class="material-symbols-outlined text-sm">keyboard_arrow_down</span>
                    </span>
                </div>
                
                <!-- Parameter Details Preview -->
                <div x-show="selectedParameterId" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <h5 class="font-semibold text-blue-900 text-sm" x-text="selectedParameter?.parameter_name || ''"></h5>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700" x-text="selectedParameter?.parameter_code || ''"></span>
                                <span class="text-xs text-blue-600" x-text="selectedParameter?.parameter_category || ''"></span>
                                <span x-show="selectedParameter?.is_critical" class="text-xs font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">CRITICAL</span>
                            </div>
                        </div>
                        <span class="text-xs text-blue-400" x-text="selectedParameter?.test_method || ''"></span>
                    </div>
                    
                    <!-- Expected Range Display -->
                    <div class="mt-2 pt-2 border-t border-blue-100">
                        <p class="text-xs text-blue-700 font-medium mb-1">Expected Range:</p>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span x-show="selectedParameter?.tolerance_type === 'RANGE'" class="font-mono bg-white px-2 py-1 rounded border border-blue-200" x-text="selectedParameter?.standard_min + ' to ' + selectedParameter?.standard_max + ' ' + (selectedParameter?.unit_of_measurement || '')"></span>
                            <span x-show="selectedParameter?.tolerance_type === 'MIN_ONLY'" class="font-mono bg-white px-2 py-1 rounded border border-blue-200" x-text="'> ' + selectedParameter?.standard_min + ' ' + (selectedParameter?.unit_of_measurement || '')"></span>
                            <span x-show="selectedParameter?.tolerance_type === 'MAX_ONLY'" class="font-mono bg-white px-2 py-1 rounded border border-blue-200" x-text="'< ' + selectedParameter?.standard_max + ' ' + (selectedParameter?.unit_of_measurement || '')"></span>
                            <span x-show="selectedParameter?.tolerance_type === 'EXACT'" class="font-mono bg-white px-2 py-1 rounded border border-blue-200" x-text="selectedParameter?.standard_value + ' ' + (selectedParameter?.unit_of_measurement || '')"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observed Value Input -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observed Value *</label>
                <div class="relative">
                    <input type="number" x-model="newResult.observed_value" step="0.0001" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-600"
                        placeholder="Enter observed value">
                    <span class="absolute right-3 top-2.5 text-gray-400" x-text="selectedParameter?.unit_of_measurement || ''"></span>
                </div>

                <!-- Result Status Preview -->
                <div x-show="selectedParameterId && newResult.observed_value" class="mt-3 p-3 rounded-lg border border-gray-200">
                    <p class="text-xs font-semibold text-gray-600 mb-2">Result Status:</p>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 mb-1">Tolerance Type</p>
                            <p class="text-sm font-medium text-gray-900" x-text="selectedParameter?.tolerance_type || '—'"></p>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 mb-1">Standard Value</p>
                            <p class="text-sm font-medium text-gray-900" x-text="getStandardValueDisplay()"></p>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 mb-1">Status</p>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :class="getResultStatusColor()"></span>
                                <p class="text-sm font-bold" :class="getResultStatusColor()" x-text="getResultStatusText()"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="mt-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks (Optional)</label>
            <textarea x-model="newResult.remarks" rows="2"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-600"
                placeholder="Add any remarks or notes..."></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex items-center justify-end gap-3">
            <button @click="clearNewResultForm" type="button"
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 text-gray-700">
                <span class="material-symbols-outlined inline mr-1 text-sm">clear</span> Clear
            </button>
            <button @click="addResult" :disabled="!selectedParameterId || !newResult.observed_value || saving"
                class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <span x-show="!saving" class="material-symbols-outlined">save</span>
                <span x-show="!saving">Record Result</span>
                <span x-show="saving">Saving...</span>
            </button>
        </div>
    </div>

</div>

<script>
function qcInspectionDetail() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const lotId = {{ $lotId }};
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        lot: {},
        qcResults: [],
        qcParameters: [],
        showAddResultModal: false,
        saving: false,
        selectedParameterId: '',
        newResult: { 
            parameter_id: '', 
            parameter_name: '', 
            parameter_code: '', 
            observed_value: '', 
            remarks: '',
            standard_min: '',
            standard_max: '',
            standard_value: '',
            unit_of_measurement: '',
            tolerance_type: ''
        },
        decision: { decision: '', remarks: '' },

        async init() {
            await this.loadLot();
        },

        async loadLot() {
            try {
                const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data || {};
                    await this.loadResults();
                    await this.loadParameters();
                }
            } catch (e) {
                console.error('Failed to load lot:', e);
            }
        },

        async loadResults() {
            try {
                const res = await fetch(`/api/v1/qc/${lotId}`, { headers: headers() });
                const data = await res.json();
                this.qcResults = data.data?.test_results || [];
            } catch (e) {
                console.error('Failed to load results:', e);
            }
        },

        async loadParameters() {
            try {
                const materialId = this.lot.material_id;
                if (!materialId) return;
                
                const res = await fetch(`/api/v1/qc/parameters/${materialId}`, { headers: headers() });
                const data = await res.json();
                this.qcParameters = data.data || [];
            } catch (e) {
                console.error('Failed to load parameters:', e);
            }
        },

        async startInspection() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/start`, { method: 'PATCH', headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Inspection started successfully');
                } else {
                    alert(data.message || 'Failed to start inspection');
                }
            } finally { this.saving = false; }
        },

        async completeInspection() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/complete`, { method: 'PATCH', headers: headers() });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Inspection completed successfully');
                } else {
                    alert(data.message || 'Failed to complete inspection');
                }
            } finally { this.saving = false; }
        },

        async addResult() {
            if (!this.selectedParameterId || !this.newResult.observed_value) {
                alert('Please select a parameter and enter an observed value');
                return;
            }

            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/test-results`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({
                        parameter_id: parseInt(this.selectedParameterId),
                        parameter_name: this.newResult.parameter_name,
                        parameter_code: this.newResult.parameter_code,
                        standard_min: this.newResult.standard_min,
                        standard_max: this.newResult.standard_max,
                        standard_value: this.newResult.standard_value,
                        unit_of_measurement: this.newResult.unit_of_measurement,
                        tolerance_type: this.newResult.tolerance_type,
                        observed_value: parseFloat(this.newResult.observed_value),
                        remarks: this.newResult.remarks
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.newResult = { 
                        parameter_id: '', 
                        parameter_name: '', 
                        parameter_code: '', 
                        observed_value: '', 
                        remarks: '',
                        standard_min: '',
                        standard_max: '',
                        standard_value: '',
                        unit_of_measurement: '',
                        tolerance_type: ''
                    };
                    this.selectedParameterId = '';
                    await this.loadResults();
                    alert('Test result recorded successfully');
                } else {
                    alert(data.message || 'Failed to record result');
                }
            } catch (e) {
                console.error('Error recording result:', e);
                alert('Failed to record result: ' + e.message);
            } finally { 
                this.saving = false; 
            }
        },

        async makeDecision() {
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/qc/${lotId}/decision`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify(this.decision)
                });
                const data = await res.json();
                if (data.success) {
                    this.lot = data.data;
                    alert('Decision made successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.message || 'Failed to make decision');
                }
            } finally { this.saving = false; }
        },

        onParameterChange() {
            const select = document.querySelector('select[x-model="selectedParameterId"]');
            const option = select.options[select.selectedIndex];
            
            if (option) {
                this.newResult = {
                    parameter_id: parseInt(this.selectedParameterId),
                    parameter_name: option.dataset.parameter_name || '',
                    parameter_code: option.dataset.parameter_code || '',
                    standard_min: option.dataset.standard_min || '',
                    standard_max: option.dataset.standard_max || '',
                    standard_value: option.dataset.standard_value || '',
                    unit_of_measurement: option.dataset.unit_of_measurement || '',
                    tolerance_type: option.dataset.tolerance_type || '',
                    remarks: ''
                };
            }
        },

        getStandardValueDisplay() {
            if (!this.selectedParameter) return '';
            
            const type = this.selectedParameter.tolerance_type;
            const unit = this.selectedParameter.unit_of_measurement || '';
            
            if (type === 'RANGE') return `${this.selectedParameter.standard_min} - ${this.selectedParameter.standard_max} ${unit}`;
            if (type === 'MIN_ONLY') return `> ${this.selectedParameter.standard_min} ${unit}`;
            if (type === 'MAX_ONLY') return `< ${this.selectedParameter.standard_max} ${unit}`;
            if (type === 'EXACT') return `${this.selectedParameter.standard_value} ${unit}`;
            
            return '';
        },

        getResultStatusColor() {
            if (!this.selectedParameter || !this.newResult.observed_value) return 'bg-gray-300';
            
            const value = parseFloat(this.newResult.observed_value);
            const type = this.selectedParameter.tolerance_type;
            
            if (type === 'RANGE') {
                const min = parseFloat(this.selectedParameter.standard_min);
                const max = parseFloat(this.selectedParameter.standard_max);
                if (value >= min && value <= max) return 'bg-green-500 text-green-700';
                return 'bg-red-500 text-red-700';
            }
            
            if (type === 'MIN_ONLY') {
                const min = parseFloat(this.selectedParameter.standard_min);
                if (value >= min) return 'bg-green-500 text-green-700';
                return 'bg-red-500 text-red-700';
            }
            
            if (type === 'MAX_ONLY') {
                const max = parseFloat(this.selectedParameter.standard_max);
                if (value <= max) return 'bg-green-500 text-green-700';
                return 'bg-red-500 text-red-700';
            }
            
            if (type === 'EXACT') {
                const exact = parseFloat(this.selectedParameter.standard_value);
                const tolerance = 0.05; // 5% tolerance
                const lower = exact * (1 - tolerance);
                const upper = exact * (1 + tolerance);
                if (value >= lower && value <= upper) return 'bg-green-500 text-green-700';
                return 'bg-red-500 text-red-700';
            }
            
            return 'bg-gray-300';
        },

        getResultStatusText() {
            if (!this.selectedParameter || !this.newResult.observed_value) return '—';
            
            const value = parseFloat(this.newResult.observed_value);
            const type = this.selectedParameter.tolerance_type;
            
            if (type === 'RANGE') {
                const min = parseFloat(this.selectedParameter.standard_min);
                const max = parseFloat(this.selectedParameter.standard_max);
                return (value >= min && value <= max) ? 'PASS' : 'FAIL';
            }
            
            if (type === 'MIN_ONLY') {
                const min = parseFloat(this.selectedParameter.standard_min);
                return (value >= min) ? 'PASS' : 'FAIL';
            }
            
            if (type === 'MAX_ONLY') {
                const max = parseFloat(this.selectedParameter.standard_max);
                return (value <= max) ? 'PASS' : 'FAIL';
            }
            
            if (type === 'EXACT') {
                const exact = parseFloat(this.selectedParameter.standard_value);
                const tolerance = 0.05;
                const lower = exact * (1 - tolerance);
                const upper = exact * (1 + tolerance);
                return (value >= lower && value <= upper) ? 'PASS' : 'FAIL';
            }
            
            return '—';
        },

        clearNewResultForm() {
            this.newResult = { 
                parameter_id: '', 
                parameter_name: '', 
                parameter_code: '', 
                observed_value: '', 
                remarks: '',
                standard_min: '',
                standard_max: '',
                standard_value: '',
                unit_of_measurement: '',
                tolerance_type: ''
            };
            this.selectedParameterId = '';
        },

        get selectedParameter() {
            return this.qcParameters.find(p => p.id == this.selectedParameterId);
        },

        statusClass(s) {
            return { 
                'PENDING': 'bg-amber-100 text-amber-700', 
                'IN_PROGRESS': 'bg-blue-100 text-blue-700', 
                'COMPLETED': 'bg-green-100 text-green-700', 
                'DECISION_MADE': 'bg-purple-100 text-purple-700' 
            }[s] || 'bg-gray-100 text-gray-600';
        },
    };
}
</script>
@endsection
