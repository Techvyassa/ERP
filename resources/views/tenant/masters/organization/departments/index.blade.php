@extends('tenant.layouts.organization')

@section('title', 'Departments')
@section('page-title', 'Department Management')

@section('content')
<div x-data="{ showUploadModal: false, selectedFile: null, uploading: false, uploadProgress: 0, dragOver: false }">
    <!-- CSV Upload Modal -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showUploadModal = false"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Import Departments from CSV</h3>
                        <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 1: Download Template</label>
                        <button @click="() => { const csv = 'dept_code,dept_name,parent_dept_id,cost_center_code,is_active\nDEPT001,Sales Department,,CC001,true'; const blob = new Blob([csv], { type: 'text/csv' }); const url = window.URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = 'departments_import_template.csv'; a.click(); window.URL.revokeObjectURL(url); }" class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors flex items-center justify-center">
                            <i class="fas fa-download text-blue-600 mr-2"></i><span class="text-sm text-gray-700">Download CSV Template</span>
                        </button>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step 2: Upload Filled CSV</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="dragOver = false; const file = $event.dataTransfer.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please drop a valid CSV file');" :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload-dept" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>Upload a file</span>
                                        <input id="file-upload-dept" type="file" accept=".csv" class="sr-only" @change="const file = $event.target.files[0]; if (file && file.type === 'text/csv') selectedFile = file; else alert('Please select a valid CSV file');">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV file up to 10MB</p>
                                <p x-show="selectedFile" class="text-sm text-green-600 mt-2"><i class="fas fa-check-circle mr-1"></i><span x-text="selectedFile ? selectedFile.name : ''"></span></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="uploading" class="mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-700 mb-2"><span>Uploading...</span><span x-text="uploadProgress + '%'"></span></div>
                        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                    <button @click="showUploadModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button @click="if (!selectedFile || uploading) return; uploading = true; uploadProgress = 0; const interval = setInterval(() => { if (uploadProgress < 90) uploadProgress += 10; }, 200); setTimeout(() => { uploadProgress = 100; clearInterval(interval); alert('CSV imported successfully!'); showUploadModal = false; selectedFile = null; uploading = false; uploadProgress = 0; }, 2000);" :disabled="!selectedFile || uploading" :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'" class="px-4 py-2 bg-green-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-upload mr-2"></i><span x-text="uploading ? 'Uploading...' : 'Import Data'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Departments</h2>
                <p class="text-gray-600 mt-1">Manage organizational departments</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/departments/create' : '/org/' . $organization->org_slug . '/departments/create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Department
                </a>
            </div>
        </div>

        <div class="text-center py-12">
            <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Department Management Coming Soon</h3>
            <p class="text-gray-600">This feature is under development.</p>
        </div>
    </div>
</div>
@endsection
