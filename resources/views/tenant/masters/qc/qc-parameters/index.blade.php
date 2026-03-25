@extends('tenant.layouts.app')

@section('title', 'QC Parameters')
@section('page-title', 'QC Parameter Master')

@section('content')
<div x-data="qcParameterData()" x-init="init()">
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">QC Parameter Master</h2>
                <p class="text-gray-600 mt-1">Manage material-specific QC specifications, tolerance ranges, and critical checks.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url($tenantType === 'subdomain' ? '/quality-dashboard' : "/org/{$organization->org_slug}/quality-dashboard") }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Back
                </a>
                <button @click="openCreateModal()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add QC Parameter
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.300ms="loadParameters()"
                   placeholder="Search by code, name, or method..."
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

            <select x-model="filters.material_id" @change="loadParameters()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Materials</option>
                <template x-for="material in materials" :key="material.id">
                    <option :value="material.id" x-text="`${material.material_code} - ${material.material_name}`"></option>
                </template>
            </select>

            <select x-model="filters.test_type_id" @change="loadParameters()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Test Types</option>
                <template x-for="testType in testTypes" :key="testType.id">
                    <option :value="testType.id" x-text="`${testType.type_code} - ${testType.type_name}`"></option>
                </template>
            </select>

            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tolerance</th>
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
                                <p class="text-gray-600">No QC parameters found.</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-gray-900" x-text="item.material?.material_name || '-'"></div>
                                <div class="text-xs text-gray-500" x-text="item.material?.material_code || '-'"></div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-semibold text-blue-700" x-text="item.parameter_code"></div>
                                <div class="text-sm text-gray-900" x-text="item.parameter_name"></div>
                                <div class="text-xs text-gray-500" x-text="item.test_method || 'No test method'"></div>
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700" x-text="item.test_type?.type_name || 'Unassigned'"></td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700" x-text="formatTolerance(item)"></td>
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
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editId ? 'Edit QC Parameter' : 'Create QC Parameter'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveParameter()" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Material *</label>
                        <select x-model="form.material_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select material</option>
                            <template x-for="material in materials" :key="material.id">
                                <option :value="material.id" x-text="`${material.material_code} - ${material.material_name}`"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Test Type</label>
                        <select x-model="form.test_type_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select test type</option>
                            <template x-for="testType in testTypes" :key="testType.id">
                                <option :value="testType.id" x-text="`${testType.type_code} - ${testType.type_name}`"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Code *</label>
                        <input type="text" x-model="form.parameter_code" maxlength="50" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg uppercase focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Name *</label>
                        <input type="text" x-model="form.parameter_name" maxlength="100" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" x-model="form.parameter_category" maxlength="50"
                               placeholder="PHYSICAL / CHEMICAL / MICROBIOLOGICAL"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Test Method</label>
                        <input type="text" x-model="form.test_method" maxlength="100"
                               placeholder="ASTM / IS / SOP reference"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Type *</label>
                        <select x-model="form.data_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="NUMERIC">Numeric</option>
                            <option value="TEXT">Text</option>
                            <option value="BOOLEAN">Boolean</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tolerance Type *</label>
                        <select x-model="form.tolerance_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="RANGE">Range</option>
                            <option value="MIN_ONLY">Min Only</option>
                            <option value="MAX_ONLY">Max Only</option>
                            <option value="EXACT">Exact</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Min</label>
                        <input type="text" x-model="form.standard_min" maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Max</label>
                        <input type="text" x-model="form.standard_max" maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Value</label>
                        <input type="text" x-model="form.standard_value" maxlength="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                        <input type="text" x-model="form.unit_of_measurement" maxlength="30"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                        <input type="number" min="0" max="65535" x-model="form.display_order"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="form.is_critical" class="rounded border-gray-300">
                        Critical parameter
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300">
                        Active
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving" x-text="editId ? 'Update Parameter' : 'Create Parameter'"></span>
                        <span x-show="saving">Saving...</span>
                    </button>
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
        filters: {
            search: '',
            material_id: '',
            test_type_id: ''
        },
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

        openCreateModal() {
            this.editId = null;
            this.resetForm();
            this.showModal = true;
        },

        openEditModal(item) {
            this.editId = item.id;
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
            this.resetForm();
        },

        async saveParameter() {
            this.saving = true;
            try {
                const payload = {
                    ...this.form,
                    test_type_id: this.form.test_type_id || null,
                    standard_min: this.form.standard_min || null,
                    standard_max: this.form.standard_max || null,
                    standard_value: this.form.standard_value || null,
                    parameter_category: this.form.parameter_category || null,
                    unit_of_measurement: this.form.unit_of_measurement || null,
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
                    this.closeModal();
                    await this.loadParameters();
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
        }
    };
}
</script>
@endsection
