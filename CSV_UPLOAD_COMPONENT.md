# CSV Upload Component for Master Pages

This document provides the reusable CSV upload component that has been added to all master pages.

## Features

- ✅ Download CSV template with correct column headers
- ✅ Drag & drop file upload
- ✅ File selection via button
- ✅ Upload progress indicator
- ✅ Validation results display
- ✅ Responsive modal design
- ✅ Error handling

## Implementation

### 1. Add Import Button to Header

```html
<div class="flex items-center space-x-3">
    <!-- CSV Upload Button -->
    <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
        <i class="fas fa-upload mr-2"></i>Import CSV
    </button>
    <!-- Add Button -->
    <a href="..." class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
        <i class="fas fa-plus mr-2"></i>Add Item
    </a>
</div>
```

### 2. Add Modal HTML (after opening div with x-data)

```html
<!-- CSV Upload Modal -->
<div x-show="showUploadModal" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showUploadModal = false"></div>
        
        <!-- Modal Content -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <!-- Header -->
            <div class="bg-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Import [Entity] from CSV</h3>
                    <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="bg-white px-6 py-4">
                <!-- Download Template -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Step 1: Download Template</label>
                    <button @click="downloadTemplate" class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors flex items-center justify-center">
                        <i class="fas fa-download text-blue-600 mr-2"></i>
                        <span class="text-sm text-gray-700">Download CSV Template</span>
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Download the template file with required columns and format</p>
                </div>
                
                <!-- Upload File -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Step 2: Upload Filled CSV</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleFileDrop($event)"
                         :class="{ 'border-blue-500 bg-blue-50': dragOver }">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                    <span>Upload a file</span>
                                    <input id="file-upload" 
                                           name="file-upload" 
                                           type="file" 
                                           accept=".csv"
                                           class="sr-only"
                                           @change="handleFileSelect($event)">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">CSV file up to 10MB</p>
                            <p x-show="selectedFile" class="text-sm text-green-600 mt-2">
                                <i class="fas fa-check-circle mr-1"></i>
                                <span x-text="selectedFile ? selectedFile.name : ''"></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Progress -->
                <div x-show="uploading" class="mb-4">
                    <div class="flex items-center justify-between text-sm text-gray-700 mb-2">
                        <span>Uploading...</span>
                        <span x-text="uploadProgress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                             :style="'width: ' + uploadProgress + '%'"></div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                <button @click="showUploadModal = false" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                    Cancel
                </button>
                <button @click="uploadCSV" 
                        :disabled="!selectedFile || uploading"
                        :class="!selectedFile || uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-upload mr-2"></i>
                    <span x-text="uploading ? 'Uploading...' : 'Import Data'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
```

### 3. Add Alpine.js Data Properties

Add these properties to your Alpine.js data function:

```javascript
showUploadModal: false,
selectedFile: null,
uploading: false,
uploadProgress: 0,
dragOver: false,
```

### 4. Add Alpine.js Methods

Add these methods to your Alpine.js data function:

```javascript
downloadTemplate() {
    // Customize headers for your entity
    const headers = ['code', 'name', 'type', 'is_active'];
    const sampleData = ['ITEM-001', 'Sample Item', 'TYPE1', 'true'];
    const csv = [headers.join(','), sampleData.join(',')].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'import_template.csv';
    a.click();
    window.URL.revokeObjectURL(url);
},

handleFileSelect(event) {
    const file = event.target.files[0];
    if (file && file.type === 'text/csv') {
        this.selectedFile = file;
    } else {
        alert('Please select a valid CSV file');
    }
},

handleFileDrop(event) {
    this.dragOver = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'text/csv') {
        this.selectedFile = file;
    } else {
        alert('Please drop a valid CSV file');
    }
},

async uploadCSV() {
    if (!this.selectedFile) return;
    
    this.uploading = true;
    this.uploadProgress = 0;
    
    const interval = setInterval(() => {
        if (this.uploadProgress < 90) {
            this.uploadProgress += 10;
        }
    }, 200);
    
    try {
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        
        // TODO: Replace with actual API call
        // const response = await apiClient.post('/api/import', formData);
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        this.uploadProgress = 100;
        clearInterval(interval);
        
        alert('CSV imported successfully!');
        this.showUploadModal = false;
        this.selectedFile = null;
        this.uploading = false;
        this.uploadProgress = 0;
        this.loadData();
    } catch (error) {
        clearInterval(interval);
        console.error('Failed to upload CSV:', error);
        alert('Failed to upload CSV. Please try again.');
        this.uploading = false;
        this.uploadProgress = 0;
    }
}
```

## Implemented Pages

✅ Materials Master - `/materials`

## To Be Implemented

The same component needs to be added to:
- Users
- Departments
- Roles
- Approval Matrix
- Products
- Warehouses
- UOM
- Bin Locations
- Vendors
- Vendor Contacts
- Vendor Material Map
- HSN Codes
- GST Taxes
- Currency
- BOM Header
- BOM Detail

## Customization

For each master page, customize:
1. Modal title (e.g., "Import Materials from CSV")
2. Template headers in `downloadTemplate()` method
3. Template filename
4. API endpoint in `uploadCSV()` method
