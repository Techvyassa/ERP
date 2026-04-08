@extends('tenant.layouts.bom')

@section('title', 'BOM Bulk Upload')
@section('page-title', 'BOM Bulk Upload')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="bomBulkUpload()" class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Bulk Upload BOMs</h2>
                <p class="text-sm text-gray-500 mt-1">Upload a CSV where each row represents one BOM component line.</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="downloadTemplate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-orange-200 text-orange-700 rounded-lg hover:bg-orange-50 transition-colors font-medium">
                    <span class="material-symbols-outlined text-lg">download</span>
                    Download Template
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/bom-header' : '/org/' . $organization->org_slug . '/bom-header') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload CSV</h3>

            <label class="block border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                :class="dragOver ? 'border-orange-400 bg-orange-50' : 'border-gray-300 hover:border-orange-300'">
                <input type="file" class="hidden" accept=".csv,text/csv" @change="handleFileSelection($event)">
                <div @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="handleDrop($event)">
                    <div class="mx-auto bg-orange-100 text-orange-700 rounded-full w-14 h-14 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-3xl">upload_file</span>
                    </div>
                    <p class="text-base font-medium text-gray-900">Drop BOM CSV here or click to choose</p>
                    <p class="text-sm text-gray-500 mt-1">Accepted format: `.csv`, max 10 MB</p>
                    <template x-if="selectedFile">
                        <p class="mt-3 text-sm font-medium text-orange-700" x-text="selectedFile.name"></p>
                    </template>
                </div>
            </label>

            <div class="mt-5 flex items-center gap-3">
                <button type="button" @click="uploadFile" :disabled="loading || !selectedFile"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                    <span class="material-symbols-outlined text-lg" x-show="!loading">cloud_upload</span>
                    <span class="material-symbols-outlined animate-spin text-lg" x-show="loading">progress_activity</span>
                    <span x-text="loading ? 'Uploading...' : 'Import BOM CSV'"></span>
                </button>
                <button type="button" @click="resetSelection" :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">
                    <span class="material-symbols-outlined text-lg">restart_alt</span>
                    Reset
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Notes</h3>
            <ul class="space-y-3 text-sm text-gray-600">
                <li>`bom_code` + `version` identify one BOM.</li>
                <li>Repeat BOM header values on every row that belongs to the same BOM.</li>
                <li>Use either IDs or codes for product, material, and UOM columns.</li>
                <li>Each row creates one component line inside that BOM.</li>
                <li>`is_critical` accepts `true/false`, `1/0`, or `yes/no`.</li>
            </ul>
        </div>
    </div>

    <template x-if="message">
        <div class="rounded-xl p-4 border"
            :class="messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
            <p class="font-medium" x-text="message"></p>
        </div>
    </template>

    <template x-if="result">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Created BOMs</p>
                <p class="text-3xl font-bold text-green-600 mt-1" x-text="result.created_count || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Failed Rows / Groups</p>
                <p class="text-3xl font-bold text-red-500 mt-1" x-text="result.error_count || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Imported File</p>
                <p class="text-lg font-semibold text-gray-900 mt-1 break-all" x-text="selectedFile ? selectedFile.name : '-'"></p>
            </div>
        </div>
    </template>

    <template x-if="result && result.errors && result.errors.length">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Import Issues</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Row</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">BOM</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(issue, index) in result.errors" :key="index">
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="issue.row || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="issue.bom_code || '-'"></td>
                                <td class="px-6 py-4 text-sm text-red-600" x-text="issue.error || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>
</div>

<script>
function bomBulkUpload() {
    return {
        dragOver: false,
        loading: false,
        selectedFile: null,
        result: null,
        message: '',
        messageType: 'success',

        handleFileSelection(event) {
            const file = event.target.files[0];
            this.setFile(file);
        },

        handleDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            this.setFile(file);
        },

        setFile(file) {
            if (!file) {
                return;
            }
            if (!file.name.toLowerCase().endsWith('.csv')) {
                this.setMessage('Please choose a valid CSV file.', 'error');
                return;
            }
            this.selectedFile = file;
            this.result = null;
            this.setMessage('', 'success');
        },

        resetSelection() {
            this.selectedFile = null;
            this.result = null;
            this.setMessage('', 'success');
        },

        downloadTemplate() {
            window.location.href = '/api/v1/bom-headers/import/template';
        },

        setMessage(message, type = 'success') {
            this.message = message;
            this.messageType = type;
        },

        async uploadFile() {
            if (!this.selectedFile) {
                this.setMessage('Please choose a CSV file first.', 'error');
                return;
            }

            this.loading = true;
            this.result = null;
            this.setMessage('', 'success');

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);

                const response = await fetch('/api/v1/bom-headers/import', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();
                this.result = data.data || null;

                if (!response.ok || data.success !== true) {
                    this.setMessage(data.message || 'BOM CSV import failed.', 'error');
                    return;
                }

                this.setMessage(data.message || 'BOM CSV imported successfully.', 'success');
            } catch (error) {
                console.error('BOM CSV upload failed:', error);
                this.setMessage('Network error while importing BOM CSV.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
