# CSV Upload Implementation Status

## Completed ✅

The CSV upload component has been successfully added to the following master pages:

### Organization Masters
1. ✅ **Users** (`/users`) - Template: employee_code, email, full_name, phone, dept_id, role_id, is_active
2. ✅ **Departments** (`/departments`) - Template: dept_code, dept_name, parent_dept_id, cost_center_code, is_active

### Inventory Masters
3. ✅ **Materials** (`/materials`) - Template: material_code, material_name, material_type, uom_id, reorder_level, safety_stock, is_active

## To Be Implemented

The same CSV upload component needs to be added to the following pages:

### Organization Masters
- Roles (`/roles`)
- Approval Matrix (`/approval-matrix`)

### Inventory Masters
- Products (`/products`)
- Warehouses (`/warehouses`)
- UOM (`/uom`)
- Bin Locations (`/bin-locations`)

### Vendor Masters
- Vendors (`/vendors`)
- Vendor Contacts (`/vendor-contacts`)
- Vendor Material Map (`/vendor-material-map`)

### Tax & Finance Masters
- HSN Codes (`/hsn-codes`)
- GST Taxes (`/gst-taxes`)
- Currency (`/currency`)

### BOM Masters
- BOM Header (`/bom-header`)
- BOM Detail (`/bom-detail`)

## Implementation Guide

For each remaining page, follow these steps:

### 1. Wrap content in Alpine.js div with CSV data
```html
<div x-data="{ showUploadModal: false, selectedFile: null, uploading: false, uploadProgress: 0, dragOver: false }">
```

### 2. Add CSV Upload Modal (copy from completed pages)
The modal includes:
- Download template button
- Drag & drop upload area
- Progress bar
- Import button

### 3. Update Header Buttons
Replace single "Add" button with:
```html
<div class="flex items-center space-x-3">
    <button @click="showUploadModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
        <i class="fas fa-upload mr-2"></i>Import CSV
    </button>
    <a href="..." class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
        <i class="fas fa-plus mr-2"></i>Add [Entity]
    </a>
</div>
```

### 4. Customize Template Headers
Update the CSV template headers in the download button for each entity:

**Roles:**
```javascript
const csv = 'role_code,role_name,description,is_active\nROLE001,Manager,Department Manager,true';
```

**Products:**
```javascript
const csv = 'product_code,product_name,product_category,pack_size,pack_uom_id,hsn_code_id,standard_cost,mrp,is_active\nPROD001,Sample Product,Category A,100,1,1,50.00,100.00,true';
```

**Vendors:**
```javascript
const csv = 'vendor_code,vendor_name,vendor_type,gstin,payment_terms,credit_days,currency_id,is_active\nVEN001,ABC Suppliers,SUPPLIER,29ABCDE1234F1Z5,NET30,30,1,true';
```

**HSN Codes:**
```javascript
const csv = 'hsn_code,description,default_gst_id,is_active\n0904,Pepper,1,true';
```

**GST Taxes:**
```javascript
const csv = 'tax_code,tax_name,cgst_rate,sgst_rate,igst_rate,ugst_rate,effective_from,is_active\nGST12,GST @ 12%,6.00,6.00,12.00,0.00,2024-01-01,true';
```

**Currency:**
```javascript
const csv = 'currency_code,currency_name,symbol,exchange_rate,is_base_currency,is_active\nINR,Indian Rupee,₹,1.00,true,true';
```

**BOM Header:**
```javascript
const csv = 'bom_code,product_id,version,effective_from,bom_status,batch_size,output_uom_id\nBOM-FG001-V1,1,1,2024-01-01,ACTIVE,1000,1';
```

**BOM Detail:**
```javascript
const csv = 'bom_id,material_id,qty_required,uom_id,scrap_percent,line_no\n1,1,10.5000,1,2.50,10';
```

## Features Included

Each CSV upload component includes:
- ✅ Green "Import CSV" button
- ✅ Modal with 2-step process
- ✅ Download template with correct headers
- ✅ Drag & drop file upload
- ✅ File validation (CSV only)
- ✅ Upload progress bar
- ✅ Success/error handling
- ✅ Responsive design

## API Integration

To connect to backend API, update the upload function in each page's Alpine.js:

```javascript
async uploadCSV() {
    if (!this.selectedFile) return;
    
    this.uploading = true;
    this.uploadProgress = 0;
    
    try {
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        
        // Replace with actual API endpoint
        const response = await fetch('/api/[entity]/import', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            alert('CSV imported successfully!');
            this.showUploadModal = false;
            this.selectedFile = null;
            this.loadData(); // Reload data
        } else {
            throw new Error('Upload failed');
        }
    } catch (error) {
        alert('Failed to upload CSV. Please try again.');
    } finally {
        this.uploading = false;
        this.uploadProgress = 0;
    }
}
```

## Next Steps

1. Copy the CSV upload modal from Materials page
2. Paste into each remaining master page
3. Update modal title and template headers
4. Test upload functionality
5. Connect to backend API endpoints
