@extends('tenant.layouts.app')

@section('title', 'QC Parameters')
@section('page-title', 'QC Parameter Master')

@section('content')
<div x-data="qcParameterData()" x-init="init()">
    <div class="bg-gradient-to-r from-sky-600 via-cyan-600 to-teal-500 rounded-2xl p-6 mb-6 text-white shadow-lg">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                    <span class="material-symbols-outlined text-base">biotech</span>
                    QC Parameter Master
                </div>
                <h2 class="mt-4 text-3xl font-bold">Create material specifications quickly</h2>
                <p class="mt-2 text-sm text-cyan-50">Use filters, presets, and smart field visibility to add QC specs with fewer clicks.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ url($tenantType === 'subdomain' ? '/quality-dashboard' : "/org/{$organization->org_slug}/quality-dashboard") }}"
                   class="px-4 py-3 border border-white/20 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <button @click="openCreateModal()"
                    class="px-4 py-3 bg-white text-sky-700 rounded-xl hover:bg-sky-50 transition-colors font-semibold">
                    <i class="fas fa-plus mr-2"></i>Add QC Parameter
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Total Specs</p>
            <p class="mt-3 text-3xl font-bold text-gray-900" x-text="items.length">0</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Critical</p>
            <p class="mt-3 text-3xl font-bold text-red-600" x-text="criticalCount()">0</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Materials Covered</p>
            <p class="mt-3 text-3xl font-bold text-cyan-700" x-text="coveredMaterialCount()">0</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Active</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600" x-text="activeCount()">0</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_1fr] gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Find or Prefill</h3>
                <p class="text-sm text-gray-500 mt-1">Use current filters, then open the form with those selections already filled in.</p>
            </div>
            <button @click="openCreateModal(true)"
                class="px-4 py-2.5 border border-sky-200 bg-sky-50 text-sky-700 rounded-xl hover:bg-sky-100 transition-colors font-semibold">
                <i class="fas fa-wand-magic-sparkles mr-2"></i>Quick Add From Filters
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.300ms="loadParameters()"
                   placeholder="Search by code, name, or method..."
                   class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent md:col-span-2">

            <select x-model="filters.material_id" @change="loadParameters()"
                    class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Materials</option>
                <template x-for="material in materials" :key="material.id">
                    <option :value="material.id" x-text="`${material.material_code} - ${material.material_name}`"></option>
                </template>
            </select>

            <select x-model="filters.test_type_id" @change="loadParameters()"
                    class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Test Types</option>
                <template x-for="testType in testTypes" :key="testType.id">
                    <option :value="testType.id" x-text="`${testType.type_code} - ${testType.type_name}`"></option>
                </template>
            </select>
        </div>
        <div class="mt-4">
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900">Fast Entry Presets</h3>
        <p class="text-sm text-gray-500 mt-1">Start with a pattern and then only edit the values that differ.</p>
        <div class="mt-4 space-y-3">
            <template x-for="preset in presets" :key="preset.key">
                <button @click="openCreateWithPreset(preset)"
                    class="w-full text-left rounded-2xl border border-gray-200 px-4 py-4 hover:border-sky-300 hover:bg-sky-50 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                            <i :class="preset.icon"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900" x-text="preset.label"></div>
                            <div class="text-xs text-gray-500 mt-1" x-text="preset.help"></div>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">QC Specifications</h3>
                <p class="text-sm text-gray-500">Material, limit, and control flags are visible without opening each record.</p>
            </div>
            <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                <span x-text="items.length"></span> records
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material / Parameter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tolerance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flags</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                <p class="text-gray-600 mt-2">Loading QC parameters...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-flask text-5xl text-gray-300 mb-4"></i>
                                <p class="text-gray-900 font-semibold">No QC parameters found.</p>
                                <p class="text-gray-500 mt-1">Start with a preset and then adjust only the required limits.</p>
                                <button @click="openCreateModal()" class="mt-4 px-4 py-2 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition-colors">
                                    Create First Parameter
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-gray-900" x-text="item.material?.material_name || '-'"></div>
                                <div class="text-xs text-gray-500" x-text="item.material?.material_code || '-'"></div>
                                <div class="mt-3 rounded-xl bg-gray-50 px-3 py-2">
                                    <div class="font-semibold text-blue-700 text-xs font-mono" x-text="item.parameter_code"></div>
                                    <div class="text-sm text-gray-900" x-text="item.parameter_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.parameter_category || 'No category'"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700" x-text="item.test_type?.type_name || 'Unassigned'"></td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700" x-text="formatTolerance(item)"></td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                <div x-text="item.test_method || 'No test method'"></div>
                                <div class="text-xs text-gray-500 mt-1">Order: <span x-text="item.display_order ?? 0"></span></div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                                          :class="item.is_critical ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="item.is_critical ? 'Critical' : 'Standard'"></span>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                                          :class="item.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="duplicateItem(item)" class="text-gray-500 hover:text-sky-700" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button @click="openEditModal(item)" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button x-show="item.is_active" @click="deactivate(item.id)" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-5xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Fast Create</div>
                    <h3 class="text-xl font-semibold text-gray-900" x-text="editId ? 'Edit QC Parameter' : 'Create QC Parameter'"></h3>
                    <p class="text-sm text-gray-500 mt-1">Only relevant inputs stay visible based on the tolerance and data type you choose.</p>
                </div>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveParameter()" class="p-6 space-y-6">
                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in presets" :key="preset.key">
                        <button type="button" @click="applyPreset(preset)"
                            class="px-3 py-1.5 rounded-full border border-sky-200 text-xs font-semibold text-sky-700 hover:bg-sky-50 transition-colors"
                            x-text="preset.label"></button>
                    </template>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Material *</label>
                        <select x-model="form.material_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select material</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="`${material.material_code} - ${material.material_name}`"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Test Type</label>
                        <select x-model="form.test_type_id" @change="syncCategoryFromTestType()"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select test type</option>
                            <template x-for="testType in testTypes" :key="testType.id">
                                <option :value="testType.id" x-text="`${testType.type_code} - ${testType.type_name}`"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Code *</label>
                        <input type="text" x-model="form.parameter_code" maxlength="50" required
                               placeholder="e.g. MOISTURE"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl uppercase focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Name *</label>
                        <input type="text" x-model="form.parameter_name" maxlength="100" required
                               placeholder="e.g. Moisture Content"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" x-model="form.parameter_category" maxlength="50"
                               placeholder="PHYSICAL / CHEMICAL / MICROBIOLOGICAL"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Test Method</label>
                        <input type="text" x-model="form.test_method" maxlength="100"
                               placeholder="ASTM / IS / SOP reference"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Type *</label>
                        <select x-model="form.data_type" @change="onDataTypeChange()" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="NUMERIC">Numeric</option>
                            <option value="TEXT">Text</option>
                            <option value="BOOLEAN">Boolean</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tolerance Type *</label>
                        <select x-model="form.tolerance_type" @change="onToleranceTypeChange()" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="RANGE">Range</option>
                            <option value="MIN_ONLY">Min Only</option>
                            <option value="MAX_ONLY">Max Only</option>
                            <option value="EXACT">Exact</option>
                        </select>
                    </div>

                    <div x-show="showMinField()">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Min</label>
                        <input type="text" x-model="form.standard_min" maxlength="50"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div x-show="showMaxField()">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Max</label>
                        <input type="text" x-model="form.standard_max" maxlength="50"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div x-show="showExactField()">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Value</label>
                        <input type="text" x-model="form.standard_value" maxlength="100"
                               :placeholder="exactPlaceholder()"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                        <input type="text" x-model="form.unit_of_measurement" maxlength="30"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                        <input type="number" min="0" max="65535" x-model="form.display_order"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="form.is_critical = !form.is_critical"
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="form.is_critical ? 'bg-red-100 text-red-700 ring-1 ring-red-200' : 'bg-gray-100 text-gray-700 ring-1 ring-gray-200'">
                        <i class="fas fa-triangle-exclamation mr-2"></i>Critical parameter
                    </button>
                    <button type="button" @click="form.is_active = !form.is_active"
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="form.is_active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-700 ring-1 ring-gray-200'">
                        <i class="fas fa-toggle-on mr-2"></i>Active
                    </button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center pt-4 border-t border-gray-100">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" x-model="saveAndAddAnother" class="rounded border-gray-300">
                        Save and add another
                    </label>
                    <div class="flex justify-end gap-3">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving" x-text="editId ? 'Update Parameter' : 'Create Parameter'"></span>
                        <span x-show="saving">Saving...</span>
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function qcParameterData() {
    const orgSlug = '{{ $organization->org_slug }}';
    const token = () => localStorage.getItem('access_token');
    const headers = () => ({
        'Authorization': `Bearer ${token()}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Org-Slug': orgSlug
    });

    return {
        items: [],
        materials: [],
        testTypes: [],
        loading: false,
        saving: false,
        showModal: false,
        editId: null,
        saveAndAddAnother: false,
        filters: {
            search: '',
            material_id: '',
            test_type_id: ''
        },
        presets: [
            { key: 'range_numeric', label: 'Numeric Range', icon: 'fas fa-ruler-combined', help: 'For pH, moisture, dimensions, assay.', data_type: 'NUMERIC', tolerance_type: 'RANGE', parameter_category: 'PHYSICAL' },
            { key: 'minimum_limit', label: 'Minimum Limit', icon: 'fas fa-arrow-up', help: 'For purity or strength checks.', data_type: 'NUMERIC', tolerance_type: 'MIN_ONLY', parameter_category: 'CHEMICAL' },
            { key: 'maximum_limit', label: 'Maximum Limit', icon: 'fas fa-arrow-down', help: 'For contamination or moisture caps.', data_type: 'NUMERIC', tolerance_type: 'MAX_ONLY', parameter_category: 'CHEMICAL' },
            { key: 'pass_fail', label: 'Pass / Fail', icon: 'fas fa-toggle-on', help: 'For visual or boolean checks.', data_type: 'BOOLEAN', tolerance_type: 'EXACT', parameter_category: 'VISUAL' }
        ],
        form: {},

        async init() {
            this.resetForm();
            await Promise.all([
                this.loadMaterials(),
                this.loadTestTypes()
            ]);
            await this.loadParameters();
        },

        resetForm() {
            this.form = {
                material_id: '',
                test_type_id: '',
                parameter_code: '',
                parameter_name: '',
                parameter_category: '',
                data_type: 'NUMERIC',
                tolerance_type: 'RANGE',
                standard_min: '',
                standard_max: '',
                standard_value: '',
                unit_of_measurement: '',
                test_method: '',
                is_critical: false,
                display_order: 0,
                is_active: true
            };
        },

        async loadMaterials() {
            const response = await fetch('/api/v1/materials?is_active=1&per_page=100', { headers: headers() });
            const data = await response.json();
            if (data.success) {
                this.materials = data.data || [];
            }
        },

        async loadTestTypes() {
            const response = await fetch('/api/v1/qc-test-types?active_only=1', { headers: headers() });
            const data = await response.json();
            if (data.success) {
                this.testTypes = data.data || [];
            }
        },

        async loadParameters() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.material_id) params.append('material_id', this.filters.material_id);
                if (this.filters.test_type_id) params.append('test_type_id', this.filters.test_type_id);

                const query = params.toString();
                const response = await fetch(`/api/v1/qc-parameters${query ? `?${query}` : ''}`, { headers: headers() });
                const data = await response.json();
                if (data.success) {
                    this.items = data.data || [];
                }
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = { search: '', material_id: '', test_type_id: '' };
            this.loadParameters();
        },

        openCreateModal(fromFilters = false) {
            this.editId = null;
            this.saveAndAddAnother = false;
            this.resetForm();
            if (fromFilters) {
                this.applyFilterDefaults();
            }
            this.showModal = true;
        },

        openCreateWithPreset(preset) {
            this.openCreateModal(true);
            this.applyPreset(preset);
        },

        applyFilterDefaults() {
            if (this.filters.material_id) {
                this.form.material_id = this.filters.material_id;
            }
            if (this.filters.test_type_id) {
                this.form.test_type_id = this.filters.test_type_id;
                this.syncCategoryFromTestType();
            }
        },

        applyPreset(preset) {
            this.form.data_type = preset.data_type;
            this.form.tolerance_type = preset.tolerance_type;
            if (!this.form.parameter_category) {
                this.form.parameter_category = preset.parameter_category;
            }
            this.onDataTypeChange();
            this.onToleranceTypeChange();
        },

        duplicateItem(item) {
            this.openCreateModal();
            this.form = {
                material_id: item.material_id ?? '',
                test_type_id: item.test_type_id ?? '',
                parameter_code: `${item.parameter_code ?? ''}_COPY`,
                parameter_name: item.parameter_name ?? '',
                parameter_category: item.parameter_category ?? '',
                data_type: item.data_type ?? 'NUMERIC',
                tolerance_type: item.tolerance_type ?? 'RANGE',
                standard_min: item.standard_min ?? '',
                standard_max: item.standard_max ?? '',
                standard_value: item.standard_value ?? '',
                unit_of_measurement: item.unit_of_measurement ?? '',
                test_method: item.test_method ?? '',
                is_critical: Boolean(item.is_critical),
                display_order: item.display_order ?? 0,
                is_active: Boolean(item.is_active)
            };
        },

        openEditModal(item) {
            this.editId = item.id;
            this.saveAndAddAnother = false;
            this.form = {
                material_id: item.material_id ?? '',
                test_type_id: item.test_type_id ?? '',
                parameter_code: item.parameter_code ?? '',
                parameter_name: item.parameter_name ?? '',
                parameter_category: item.parameter_category ?? '',
                data_type: item.data_type ?? 'NUMERIC',
                tolerance_type: item.tolerance_type ?? 'RANGE',
                standard_min: item.standard_min ?? '',
                standard_max: item.standard_max ?? '',
                standard_value: item.standard_value ?? '',
                unit_of_measurement: item.unit_of_measurement ?? '',
                test_method: item.test_method ?? '',
                is_critical: Boolean(item.is_critical),
                display_order: item.display_order ?? 0,
                is_active: Boolean(item.is_active)
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editId = null;
            this.saveAndAddAnother = false;
            this.resetForm();
        },

        syncCategoryFromTestType() {
            if (this.form.parameter_category) return;
            const selected = this.testTypes.find(type => String(type.id) === String(this.form.test_type_id));
            if (!selected) return;
            const code = (selected.type_code || '').toUpperCase();
            if (code === 'CHEMICAL') this.form.parameter_category = 'CHEMICAL';
            if (code === 'MICROBIOLOGICAL') this.form.parameter_category = 'MICROBIOLOGICAL';
            if (['VISUAL', 'DIMENSIONAL', 'PHYSICAL'].includes(code)) this.form.parameter_category = 'PHYSICAL';
        },

        onDataTypeChange() {
            if (this.form.data_type !== 'NUMERIC') {
                this.form.unit_of_measurement = '';
            }
            if (this.form.data_type === 'BOOLEAN') {
                this.form.tolerance_type = 'EXACT';
                this.form.standard_min = '';
                this.form.standard_max = '';
                if (!this.form.standard_value) this.form.standard_value = 'true';
            }
            if (this.form.data_type === 'TEXT') {
                this.form.tolerance_type = 'EXACT';
                this.form.standard_min = '';
                this.form.standard_max = '';
            }
        },

        onToleranceTypeChange() {
            if (!this.showMinField()) this.form.standard_min = '';
            if (!this.showMaxField()) this.form.standard_max = '';
            if (!this.showExactField()) this.form.standard_value = '';
        },

        showMinField() {
            return this.form.data_type === 'NUMERIC' && ['RANGE', 'MIN_ONLY'].includes(this.form.tolerance_type);
        },

        showMaxField() {
            return this.form.data_type === 'NUMERIC' && ['RANGE', 'MAX_ONLY'].includes(this.form.tolerance_type);
        },

        showExactField() {
            return this.form.tolerance_type === 'EXACT' || this.form.data_type !== 'NUMERIC';
        },

        exactPlaceholder() {
            if (this.form.data_type === 'BOOLEAN') return 'true / false';
            if (this.form.data_type === 'TEXT') return 'Expected text';
            return 'Exact expected value';
        },

        async saveParameter() {
            this.saving = true;
            try {
                const payload = {
                    ...this.form,
                    test_type_id: this.form.test_type_id || null,
                    standard_min: this.showMinField() ? (this.form.standard_min || null) : null,
                    standard_max: this.showMaxField() ? (this.form.standard_max || null) : null,
                    standard_value: this.showExactField() ? (this.form.standard_value || null) : null,
                    parameter_category: this.form.parameter_category || null,
                    unit_of_measurement: this.form.data_type === 'NUMERIC' ? (this.form.unit_of_measurement || null) : null,
                    test_method: this.form.test_method || null
                };

                const url = this.editId ? `/api/v1/qc-parameters/${this.editId}` : '/api/v1/qc-parameters';
                const method = this.editId ? 'PUT' : 'POST';
                const response = await fetch(url, {
                    method,
                    headers: headers(),
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    await this.loadParameters();
                    if (this.saveAndAddAnother && !this.editId) {
                        const preserved = {
                            material_id: this.form.material_id,
                            test_type_id: this.form.test_type_id,
                            parameter_category: this.form.parameter_category,
                            data_type: this.form.data_type,
                            tolerance_type: this.form.tolerance_type,
                            test_method: this.form.test_method,
                            is_active: this.form.is_active
                        };
                        this.resetForm();
                        Object.assign(this.form, preserved);
                    } else {
                        this.closeModal();
                    }
                } else {
                    alert(data.message || 'Failed to save QC parameter');
                }
            } finally {
                this.saving = false;
            }
        },

        async deactivate(id) {
            if (!confirm('Deactivate this QC parameter?')) return;

            const response = await fetch(`/api/v1/qc-parameters/${id}`, {
                method: 'DELETE',
                headers: headers()
            });
            const data = await response.json();

            if (data.success) {
                await this.loadParameters();
            } else {
                alert(data.message || 'Failed to deactivate QC parameter');
            }
        },

        formatTolerance(item) {
            const unit = item.unit_of_measurement ? ` ${item.unit_of_measurement}` : '';

            if (item.tolerance_type === 'RANGE') {
                return `${item.standard_min ?? '-'} to ${item.standard_max ?? '-'}${unit}`;
            }

            if (item.tolerance_type === 'MIN_ONLY') {
                return `>= ${item.standard_min ?? '-'}${unit}`;
            }

            if (item.tolerance_type === 'MAX_ONLY') {
                return `<= ${item.standard_max ?? '-'}${unit}`;
            }

            return `= ${item.standard_value ?? '-'}${unit}`;
        },

        criticalCount() {
            return this.items.filter(item => item.is_critical).length;
        },

        activeCount() {
            return this.items.filter(item => item.is_active).length;
        },

        coveredMaterialCount() {
            return new Set(this.items.map(item => item.material_id).filter(Boolean)).size;
        }
    };
}
</script>
@endsection
