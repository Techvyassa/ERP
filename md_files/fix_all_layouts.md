# Layout Fix Plan

## Issue
All master category pages have inconsistent layouts:
- Dashboard pages use category-specific layouts (inventory, vendor, tax, organization, bom)
- Individual pages use generic `app` layout
- This causes different sidebars and navigation when clicking between pages

## Solution
Update all individual pages to use their category-specific layout

## Files to Update

### Inventory (use `tenant.layouts.inventory`)
- materials/index.blade.php
- materials/create.blade.php
- products/index.blade.php
- products/create.blade.php
- warehouses/index.blade.php
- warehouses/create.blade.php
- bin-locations/index.blade.php
- bin-locations/create.blade.php
- uom/index.blade.php
- uom/create.blade.php

### Vendor (use `tenant.layouts.vendor`)
- vendors/index.blade.php
- vendors/create.blade.php
- vendor-contacts/index.blade.php
- vendor-contacts/create.blade.php
- vendor-material-map/index.blade.php
- vendor-material-map/create.blade.php

### Organization (use `tenant.layouts.organization`)
- departments/index.blade.php
- departments/create.blade.php
- roles/index.blade.php
- roles/create.blade.php
- users/index.blade.php
- users/create.blade.php
- approval-matrix/index.blade.php
- approval-matrix/create.blade.php

### BOM (use `tenant.layouts.bom`)
- bom-header/index.blade.php
- bom-header/create.blade.php
- bom-detail/index.blade.php
- bom-detail/create.blade.php

### Tax (use `tenant.layouts.tax`) ✅ DONE
- hsn-codes/index.blade.php ✅
- hsn-codes/create.blade.php ✅
- gst-taxes/index.blade.php ✅
- gst-taxes/create.blade.php ✅
- currency/index.blade.php ✅
- currency/create.blade.php ✅
