@extends('tenant.layouts.app')

@section('title', 'QC Test Types - ' . $organization->org_name)
@section('page-title', 'QC Test Type Master')

@push('head')
<style>
[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="qcTestTypeData()" x-init="init()">

    <!-- Header -->
    <div class="bg-gradient-to-r from-sky-600 to-cyan-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100">QC Setup</div>
                <h2 class="text-3xl font-bold">QC Test Types</h2>
                <p class="text-cyan-50 mt-1">Create reusable test categories first, then use them while adding QC parameters.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="downloadTemplate()" 
                        class="px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-semibold">
                    <i class="fas fa-download mr-2"></i>Download CSV Template
                </button>
                <button @click="openImportModal()" 
                        class="px-4 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-semibold">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <a href="{{ url($tenantType === 'subdomain' ? '/quality-dashboard' : "/org/{$organization->org_slug}/quality-dashboard") }}"
                   class="px-4 py-3 border border-white/20 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-colors">
                    Back
                </a>
                <button @click="openCreateModal()"
                    class="px-4 py-3 bg-white text-sky-700 rounded-xl hover:bg-sky-50 transition-colors font-semibold">
                    <i class="fas fa-plus mr-2"></i>New Test Type
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl bg-sky-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Total Types</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900" x-text="testTypes.length">0</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Active</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-900" x-text="activeCount()">0</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Recommended</p>
                    <p class="mt-2 text-sm font-semibold text-amber-900">Visual, Physical, Chemical</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Quick Start</h3>
            <p class="text-sm text-gray-500 mt-1">Use standard names to keep QC parameter setup consistent.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <template x-for="preset in presets" :key="preset.type_code">
                    <button @click="openPreset(preset)"
                        class="px-3 py-2 rounded-full border border-sky-200 text-sm font-semibold text-sky-700 hover:bg-sky-50 transition-colors"
                        x-text="preset.type_name"></button>
                </template>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Type Code</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Type Name</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Description</th>
                        <th class="text-left py-3 px-5 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-3 px-5 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="5" class="py-12 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        </td></tr>
                    </template>
                    <template x-if="!loading && testTypes.length === 0">
                        <tr><td colspan="5" class="py-12 text-center text-gray-400">No QC test types found</td></tr>
                    </template>
                    <template x-for="t in testTypes" :key="t.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-5 font-semibold text-primary text-sm font-mono" x-text="t.type_code"></td>
                            <td class="py-3 px-5 text-sm font-medium text-gray-900" x-text="t.type_name"></td>
                            <td class="py-3 px-5 text-sm text-gray-500" x-text="t.description || '—'"></td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                    :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    x-text="t.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="py-3 px-5 text-right flex items-center justify-end gap-2">
                                <button @click="openEditModal(t)" title="Edit" class="text-primary hover:text-primary/70">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button x-show="t.is_active" @click="deactivate(t.id)" title="Deactivate"
                                    class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined text-lg">block</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900" x-text="editId ? 'Edit QC Test Type' : 'New QC Test Type'"></h3>
                    <button @click="showModal = false"><span class="material-symbols-outlined text-gray-400">close</span></button>
                </div>
                <form @submit.prevent="saveTestType()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Type Code *</label>
                        <input type="text" required maxlength="20" x-model="form.type_code"
                            :readonly="!!editId"
                            :class="editId ? 'bg-gray-50' : ''"
                            placeholder="e.g. CHEMICAL, PHYSICAL"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary uppercase">
                        <p class="text-xs text-gray-400 mt-1">Unique code (cannot change after creation)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Type Name *</label>
                        <input type="text" required maxlength="100" x-model="form.type_name"
                            placeholder="e.g. Chemical Analysis"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                        <textarea rows="3" x-model="form.description" maxlength="500"
                            placeholder="Optional description"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" class="rounded border-gray-300" x-model="form.is_active">
                            <span class="font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="showModal = false"
                            class="px-5 py-2 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!saving" x-text="editId ? 'Update' : 'Create'"></span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="closeImportModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-upload text-purple-600"></i>
                        Import QC Test Types from CSV
                    </h3>
                    <button @click="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="p-6">
                    <template x-if="!uploadComplete">
                        <div>
                            <!-- File Upload Area -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center"
                                     :class="{'border-purple-500 bg-purple-50': dragOver}"
                                     @dragover.prevent="dragOver = true"
                                     @dragleave.prevent="dragOver = false"
                                     @drop.prevent="handleFileDrop($event)">
                                    <input type="file" 
                                           id="csvFileInput" 
                                           accept=".csv" 
                                           @change="handleFileSelect($event)" 
                                           class="hidden">
                                    
                                    <template x-if="!selectedFile">
                                        <div>
                                            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                                            <p class="text-gray-600 mb-2">Drag and drop your CSV file here, or</p>
                                            <button @click="document.getElementById('csvFileInput').click()" 
                                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                                Browse Files
                                            </button>
                                        </div>
                                    </template>
                                    
                                    <template x-if="selectedFile">
                                        <div class="flex items-center justify-center gap-3">
                                            <i class="fas fa-file-csv text-3xl text-green-600"></i>
                                            <div class="text-left">
                                                <p class="text-sm font-medium text-gray-900" x-text="selectedFile.name"></p>
                                                <p class="text-xs text-gray-500" x-text="(selectedFile.size / 1024).toFixed(2) + ' KB'"></p>
                                            </div>
                                            <button @click="clearFile()" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    Import Instructions
                                </h4>
                                <ul class="text-xs text-blue-800 space-y-1 ml-6 list-disc">
                                    <li>Download the CSV template first to see the required format</li>
                                    <li><strong>type_code column must be BLANK</strong> - it will be auto-generated from type_name</li>
                                    <li>Required field: type_name</li>
                                    <li>Auto-generated type_code format: First 20 chars of type_name (uppercase, alphanumeric only)</li>
                                    <li>is_active: use "true" or "false" (default: true)</li>
                                    <li>Duplicate type_code entries will be skipped</li>
                                </ul>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end gap-3">
                                <button @click="closeImportModal()" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button @click="uploadCSV()" 
                                        :disabled="!selectedFile || uploading"
                                        :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-purple-700'"
                                        class="px-6 py-2 bg-purple-600 text-white rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fas" :class="uploading ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                    <span x-text="uploading ? 'Uploading...' : 'Upload & Import'"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Upload Complete -->
                    <template x-if="uploadComplete">
                        <div>
                            <div class="text-center py-6">
                                <i class="fas text-6xl mb-4"
                                   :class="uploadErrors.length === 0 ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-yellow-500'"></i>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2" x-text="uploadMessage"></h4>
                                
                                <template x-if="uploadErrors.length > 0">
                                    <div class="mt-4 max-h-60 overflow-y-auto">
                                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-left">
                                            <h5 class="text-sm font-semibold text-red-900 mb-2">Errors:</h5>
                                            <ul class="text-xs text-red-800 space-y-1">
                                                <template x-for="error in uploadErrors" :key="error">
                                                    <li x-text="error"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex justify-end">
                                <button @click="closeImportModal()" 
                                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                    Close
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function qcTestTypeData() {
    const token = () => localStorage.getItem('access_token');
    const orgSlug = '{{ $organization->org_slug }}';
    const headers = () => ({ 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Org-Slug': orgSlug });

    return {
        testTypes: [],
        loading: false,
        saving: false,
        showModal: false,
        editId: null,
        
        // Import modal state
        showImportModal: false,
        selectedFile: null,
        uploading: false,
        uploadComplete: false,
        uploadMessage: '',
        uploadErrors: [],
        dragOver: false,
        
        presets: [
            { type_code: 'VISUAL', type_name: 'Visual Inspection', description: 'Appearance, color, packaging, and visible defects', is_active: true },
            { type_code: 'PHYSICAL', type_name: 'Physical Test', description: 'Dimensions, weight, density, hardness, and measurable attributes', is_active: true },
            { type_code: 'CHEMICAL', type_name: 'Chemical Analysis', description: 'Purity, assay, pH, moisture, or chemical composition', is_active: true },
            { type_code: 'MICROBIO', type_name: 'Microbiology', description: 'Microbial load, pathogen presence, and sterility checks', is_active: true }
        ],
        form: { type_code: '', type_name: '', description: '', is_active: true },

        async init() {
            await this.loadTestTypes();
        },

        async loadTestTypes() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/qc-test-types', { headers: headers() });
                const data = await res.json();
                if (data.success) this.testTypes = data.data || [];
            } finally { this.loading = false; }
        },

        openCreateModal() {
            this.editId = null;
            this.form = { type_code: '', type_name: '', description: '', is_active: true };
            this.showModal = true;
        },

        openPreset(preset) {
            this.editId = null;
            this.form = { ...preset };
            this.showModal = true;
        },

        openEditModal(t) {
            this.editId = t.id;
            this.form = { type_code: t.type_code, type_name: t.type_name, description: t.description || '', is_active: t.is_active };
            this.showModal = true;
        },

        async saveTestType() {
            this.saving = true;
            try {
                const url    = this.editId ? `/api/v1/qc-test-types/${this.editId}` : '/api/v1/qc-test-types';
                const method = this.editId ? 'PUT' : 'POST';
                const res    = await fetch(url, { method, headers: headers(), body: JSON.stringify(this.form) });
                const data   = await res.json();
                if (data.success) {
                    this.showModal = false;
                    await this.loadTestTypes();
                } else {
                    alert(data.message || 'Failed to save QC test type');
                }
            } finally { this.saving = false; }
        },

        async deactivate(id) {
            if (!confirm('Deactivate this QC test type?')) return;
            const res  = await fetch(`/api/v1/qc-test-types/${id}`, { method: 'DELETE', headers: headers() });
            const data = await res.json();
            if (data.success) await this.loadTestTypes();
            else alert(data.message || 'Failed to deactivate');
        },

        downloadTemplate() {
            window.location.href = '/api/v1/qc-test-types/import/template';
        },

        openImportModal() {
            this.showImportModal = true;
            this.selectedFile = null;
            this.uploading = false;
            this.uploadComplete = false;
            this.uploadMessage = '';
            this.uploadErrors = [];
        },

        closeImportModal() {
            this.showImportModal = false;
            this.selectedFile = null;
            this.uploading = false;
            this.uploadComplete = false;
            this.uploadMessage = '';
            this.uploadErrors = [];
            if (this.uploadComplete) {
                this.loadTestTypes();
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.name.endsWith('.csv')) {
                this.selectedFile = file;
            } else {
                alert('Please select a valid CSV file');
            }
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file && file.name.endsWith('.csv')) {
                this.selectedFile = file;
            } else {
                alert('Please drop a valid CSV file');
            }
        },

        clearFile() {
            this.selectedFile = null;
            document.getElementById('csvFileInput').value = '';
        },

        async uploadCSV() {
            if (!this.selectedFile) {
                alert('Please select a file first');
                return;
            }

            this.uploading = true;
            this.uploadComplete = false;

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);

                // Remove Content-Type header for FormData
                const uploadHeaders = {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'X-Org-Slug': orgSlug
                };

                const response = await fetch('/api/v1/qc-test-types/import', {
                    method: 'POST',
                    headers: uploadHeaders,
                    body: formData
                });

                const data = await response.json();
                
                this.uploading = false;
                this.uploadComplete = true;

                if (data.success) {
                    this.uploadMessage = data.message;
                    this.uploadErrors = data.data?.errors || [];
                    
                    // Reload data after successful import
                    setTimeout(() => {
                        this.loadTestTypes();
                    }, 2000);
                } else {
                    this.uploadMessage = 'Import failed';
                    this.uploadErrors = [data.message];
                }
            } catch (error) {
                this.uploading = false;
                this.uploadComplete = true;
                this.uploadMessage = 'Import failed';
                this.uploadErrors = ['Network error occurred'];
            }
        },

        activeCount() {
            return this.testTypes.filter(type => type.is_active).length;
        },
    };
}
</script>
@endsection
