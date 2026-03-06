# Frontend Schema Updates Required

## Overview
The database schema has been updated to match the specification, but the frontend views still use the old field names. This causes validation errors when trying to create/edit records.

## Changes Made

### 1. Currency Master ✅ FIXED
**File**: `resources/views/tenant/masters/tax/currency/index.blade.php`

**Changes**:
- `currency_symbol` → `symbol`
- `maxlength="10"` → `maxlength="5"` (matches new schema)

**Status**: ✅ Complete

### 2. HSN Codes ⚠️ PARTIALLY FIXED
**File**: `resources/views/tenant/masters/tax/hsn-codes/index.blade.php`

**Changes**:
- `hsn_description` → `description`
- `gst_rate` → `default_gst_id` (now references gst_taxes table)
- `maxlength="255"` → `maxlength="300"`

**Status**: ⚠️ Basic fix applied, but needs dropdown for GST tax selection

**TODO**:
- Add GST taxes dropdown instead of manual ID input
- Load GST taxes list on page load
- Display GST tax name instead of ID in table

### 3. GST Taxes ❌ NEEDS UPDATE
**File**: `resources/views/tenant/masters/tax/gst-taxes/index.blade.php`

**Required Changes**:
- Add `tax_code` field (required, unique)
- Remove `tax_type` dropdown
- Remove `cess_rate` field
- Add `ugst_rate` field
- Add `effective_from` date field (required)
- Add `effective_to` date field (optional)
- Update table columns to show new fields
- Remove tax_type filter

**Status**: ❌ Not yet updated

## Detailed Changes Needed

### GST Taxes Form Fields

**Remove**:
```html
<select x-model="formData.tax_type">
    <option value="CGST_SGST">CGST/SGST</option>
    <option value="IGST">IGST</option>
    <option value="CESS">CESS</option>
</select>

<input type="number" x-model="formData.cess_rate">
```

**Add**:
```html
<input type="text" x-model="formData.tax_code" required maxlength="20" 
       placeholder="e.g., GST5, GST12, GST18">

<input type="number" x-model="formData.ugst_rate" step="0.01" min="0" max="100">

<input type="date" x-model="formData.effective_from" required>

<input type="date" x-model="formData.effective_to">
```

**Update formData**:
```javascript
formData: { 
    tax_code: '',
    tax_name: '', 
    cgst_rate: 0, 
    sgst_rate: 0, 
    igst_rate: 0, 
    ugst_rate: 0,
    effective_from: '',
    effective_to: '',
    is_active: true 
}
```

### GST Taxes Table Columns

**Current**:
- Tax Name
- Type
- CGST Rate
- SGST Rate
- IGST Rate
- CESS Rate
- Status
- Actions

**Should Be**:
- Tax Code
- Tax Name
- CGST Rate
- SGST Rate
- IGST Rate
- UGST Rate
- Effective From
- Effective To
- Status
- Actions

### HSN Codes - GST Tax Dropdown

**Current** (temporary fix):
```html
<input type="number" x-model="formData.default_gst_id" required>
```

**Should Be**:
```html
<select x-model="formData.default_gst_id" required>
    <option value="">Select GST Tax</option>
    <template x-for="gst in gstTaxes" :key="gst.id">
        <option :value="gst.id" x-text="`${gst.tax_code} - ${gst.tax_name}`"></option>
    </template>
</select>
```

**Add to Alpine data**:
```javascript
gstTaxes: [],

async loadGstTaxes() {
    const response = await fetch('/api/v1/gst-taxes?is_active=true', {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
            'X-Org-Slug': localStorage.getItem('org_slug')
        }
    });
    const data = await response.json();
    this.gstTaxes = data.data.gst_taxes;
}
```

## Testing Checklist

### Currency Master
- [x] Create new currency with symbol field
- [x] Edit existing currency
- [x] Display symbol in table
- [ ] Test with special characters (₹, €, $, £, ¥)

### HSN Codes
- [ ] Create HSN code with GST tax ID
- [ ] Edit HSN code
- [ ] Display description in table
- [ ] Add GST tax dropdown (future enhancement)

### GST Taxes
- [ ] Update form with new fields
- [ ] Create GST tax with tax_code
- [ ] Add effective date fields
- [ ] Remove tax_type and cess_rate
- [ ] Test date range validation
- [ ] Display new columns in table

## Priority

1. **High**: Currency Master ✅ (DONE)
2. **High**: HSN Codes ⚠️ (Basic fix done, enhancement needed)
3. **Medium**: GST Taxes ❌ (Needs complete update)

## Notes

- All frontend changes maintain backward compatibility with existing data
- The temporary HSN codes fix allows manual GST tax ID entry
- GST taxes frontend needs significant rework to match new schema
- Consider adding validation for effective date ranges
- Consider adding current/active GST tax indicator based on dates
