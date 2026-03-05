# Master Pages Implementation Guide

## Overview
This guide provides the structure and templates for implementing all 19 master data management pages based on your database schema.

## Master Tables List

### Organization Masters (5 tables)
1. ✅ department_master - Created
2. ✅ role_master - Created  
3. ✅ users - Created
4. ⏳ zone_master - Needs creation
5. ⏳ approval_matrix_master - Needs creation

### Inventory Masters (5 tables)
6. ⏳ uom_master - Needs creation
7. ⏳ material_master - Needs creation
8. ⏳ product_master - Needs creation
9. ⏳ warehouse_master - Needs creation
10. ⏳ bin_locations - Needs creation

### Tax Masters (3 tables)
11. ⏳ hsn_codes - Needs creation
12. ⏳ gst_taxes - Needs creation
13. ⏳ currency_master - Needs creation

### Vendor Masters (3 tables)
14. ⏳ vendors - Needs creation
15. ⏳ vendor_contacts - Needs creation
16. ⏳ vendor_material_map - Needs creation

### BOM Masters (2 tables)
17. ⏳ bom_header - Needs creation
18. ⏳ bom_detail - Needs creation

## Implementation Status

### Completed
- ✅ Profile Completion System
- ✅ Master Setup Dashboard
- ✅ Users placeholder page
- ✅ Departments placeholder page
- ✅ Roles placeholder page
- ✅ Reports placeholder page

### Next Steps
Create full CRUD pages for all remaining masters with proper field handling.

## Page Template Structure

Each master page should follow this structure:

```
resources/views/tenant/
├── [master-name]/
│   ├── index.blade.php    (List view)
│   ├── create.blade.php   (Create form)
│   ├── edit.blade.php     (Edit form)
│   └── show.blade.php     (Detail view)
```

## Standard Page Template

### Index Page Template
```blade
@extends('tenant.layouts.app')

@section('title', 'Master Name')
@section('page-title', 'Master Name Management')

@section('content')
<div x-data="masterData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Master Name</h2>
                <p class="text-gray-600 mt-1">Manage master records</p>
            </div>
            <button @click="openCreateModal" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search..." 
                   class="px-4 py-2 border rounded-lg">
            <!-- Add more filters as needed -->
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Field 1
                    </th>
                    <!-- Add more columns -->
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="item in items" :key="item.id">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.field1"></td>
                        <!-- Add more cells -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="edit(item)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
            <!-- Add pagination controls -->
        </div>
    </div>
</div>

<script>
function masterData() {
    return {
        items: [],
        filters: { search: '' },
        
        async loadData() {
            // Fetch data from API
        },
        
        openCreateModal() {
            // Open create modal or navigate to create page
        },
        
        edit(item) {
            // Navigate to edit page
        },
        
        async deleteItem(item) {
            if (confirm('Are you sure?')) {
                // Delete via API
            }
        }
    }
}
</script>
@endsection
```

## Routes Structure

Add to `routes/tenant.php`:

```php
// Zone Master
Route::prefix('zones')->name('tenant.zones.')->group(function () {
    Route::get('/', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.zones.index', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('index');
    
    Route::get('/create', function () {
        $org = request()->get('tenant_organization');
        return view('tenant.zones.create', [
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('create');
    
    Route::get('/{id}/edit', function ($id) {
        $org = request()->get('tenant_organization');
        return view('tenant.zones.edit', [
            'id' => $id,
            'organization' => $org,
            'tenantType' => request()->get('tenant_type')
        ]);
    })->name('edit');
});

// Repeat for all other masters...
```

## API Endpoints Structure

Add to `routes/api.php`:

```php
// Zone Master API
Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])
    ->prefix('zones')
    ->group(function () {
        Route::get('/', [ZoneController::class, 'index']);
        Route::post('/', [ZoneController::class, 'store']);
        Route::get('/{id}', [ZoneController::class, 'show']);
        Route::put('/{id}', [ZoneController::class, 'update']);
        Route::delete('/{id}', [ZoneController::class, 'destroy']);
    });

// Repeat for all other masters...
```

## Controller Template

```php
<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ZoneMaster;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $query = ZoneMaster::query();
            
            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('zone_name', 'like', "%{$search}%");
            }
            
            $zones = $query->paginate($request->input('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => [
                    'zones' => $zones->items(),
                    'pagination' => [
                        'current_page' => $zones->currentPage(),
                        'per_page' => $zones->perPage(),
                        'total' => $zones->total(),
                        'last_page' => $zones->lastPage(),
                    ]
                ],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve zones: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    
    public function store(Request $request): JsonResponse
    {
        // Validation and creation logic
    }
    
    public function show(int $id): JsonResponse
    {
        // Show single record
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        // Update logic
    }
    
    public function destroy(int $id): JsonResponse
    {
        // Delete logic
    }
}
```

## Field Specifications by Master

### 1. Zone Master
- zone_code (VARCHAR, unique)
- zone_name (VARCHAR)
- zone_type (VARCHAR)
- parent_zone_id (INT, nullable, self-ref)
- is_active (BOOLEAN)

### 2. Approval Matrix Master
- document_type (VARCHAR: PR/PO/PAYMENT)
- level (SMALLINT)
- min_amount (NUMERIC)
- max_amount (NUMERIC, nullable)
- approver_role_id (INT, FK)
- sla_hours (SMALLINT)
- is_active (BOOLEAN)

### 3. UOM Master
- uom_code (VARCHAR, unique)
- uom_name (VARCHAR)
- uom_type (VARCHAR: weight/volume/qty/length)
- base_uom_id (INT, nullable, self-ref)
- conversion_factor (NUMERIC)
- is_active (BOOLEAN)

### 4. Material Master (22 fields - Critical)
- material_code (VARCHAR, unique)
- material_name (VARCHAR)
- material_type (VARCHAR: RAW/PACKAGING/CONSUMABLE/SEMI)
- uom_id (INT, FK)
- purchase_uom_id (INT, FK, nullable)
- hsn_code_id (INT, FK)
- default_warehouse_id (INT, FK, nullable)
- reorder_level (NUMERIC)
- safety_stock (NUMERIC)
- lead_time_days (SMALLINT)
- shelf_life_days (SMALLINT, nullable)
- qc_required (BOOLEAN)
- inspection_type (VARCHAR: 100PCT/AQL/SKIP)
- is_batch_tracked (BOOLEAN)
- standard_cost (NUMERIC)
- valuation_method (VARCHAR: FIFO/AVG/STD)
- is_active (BOOLEAN)

### 5. Product Master (12 fields - Critical)
- product_code (VARCHAR, unique)
- product_name (VARCHAR)
- product_category (VARCHAR, nullable)
- pack_size (NUMERIC)
- pack_uom_id (INT, FK)
- hsn_code_id (INT, FK)
- standard_cost (NUMERIC)
- mrp (NUMERIC, nullable)
- is_active (BOOLEAN)

### 6. Warehouse Master
- warehouse_code (VARCHAR, unique)
- warehouse_name (VARCHAR)
- warehouse_type (VARCHAR: RM/FG/PKG/REJECTION/WIP)
- address (TEXT, nullable)
- incharge_user_id (INT, FK, nullable)
- is_active (BOOLEAN)

### 7. Bin Locations
- warehouse_id (INT, FK)
- bin_code (VARCHAR, unique)
- aisle (VARCHAR, nullable)
- rack (VARCHAR, nullable)
- shelf (VARCHAR, nullable)
- max_weight_kg (NUMERIC, nullable)
- is_active (BOOLEAN)

### 8. HSN Codes
- hsn_code (VARCHAR, unique)
- description (VARCHAR)
- default_gst_id (INT, FK)
- is_active (BOOLEAN)

### 9. GST Taxes
- tax_code (VARCHAR, unique)
- tax_name (VARCHAR)
- cgst_rate (NUMERIC)
- sgst_rate (NUMERIC)
- igst_rate (NUMERIC)
- ugst_rate (NUMERIC)
- effective_from (DATE)
- effective_to (DATE, nullable)
- is_active (BOOLEAN)

### 10. Currency Master
- currency_code (CHAR(3), unique)
- currency_name (VARCHAR)
- symbol (VARCHAR)
- exchange_rate (NUMERIC)
- is_base_currency (BOOLEAN)
- is_active (BOOLEAN)

### 11. Vendor Master (20 fields - Critical)
- vendor_code (VARCHAR, unique)
- vendor_name (VARCHAR)
- vendor_type (VARCHAR: SUPPLIER/SERVICE/TRADER)
- gstin (VARCHAR, unique, nullable)
- pan_number (CHAR(10), nullable)
- msme_category (VARCHAR, nullable)
- payment_terms (VARCHAR)
- credit_days (SMALLINT)
- currency_id (INT, FK)
- delivery_terms (VARCHAR, nullable)
- bank_name (VARCHAR, nullable)
- bank_account_no (VARCHAR, nullable)
- ifsc_code (CHAR(11), nullable)
- is_approved (BOOLEAN)
- approved_date (DATE, nullable)
- approved_by (INT, FK, nullable)
- rating_score (NUMERIC, nullable)
- blacklisted (BOOLEAN)

### 12. Vendor Contacts
- vendor_id (INT, FK)
- contact_name (VARCHAR)
- contact_type (VARCHAR: SALES/FINANCE/LOGISTICS/GM)
- phone (VARCHAR, nullable)
- email (VARCHAR, nullable)
- is_primary (BOOLEAN)
- is_active (BOOLEAN)

### 13. Vendor Material Map
- vendor_id (INT, FK)
- material_id (INT, FK)
- vendor_material_code (VARCHAR, nullable)
- last_purchase_price (NUMERIC, nullable)
- lead_time_days (SMALLINT, nullable)
- min_order_qty (NUMERIC, nullable)
- is_preferred (BOOLEAN)
- is_active (BOOLEAN)

### 14. BOM Header (14 fields - Critical)
- bom_code (VARCHAR, unique)
- product_id (INT, FK)
- version (SMALLINT)
- effective_from (DATE)
- effective_to (DATE, nullable)
- bom_status (VARCHAR: DRAFT/ACTIVE/OBSOLETE)
- batch_size (NUMERIC)
- output_uom_id (INT, FK)
- remarks (TEXT, nullable)
- created_by (INT, FK, nullable)
- approved_by (INT, FK, nullable)

### 15. BOM Detail (11 fields - Critical)
- bom_id (INT, FK)
- material_id (INT, FK)
- qty_required (NUMERIC)
- uom_id (INT, FK)
- scrap_percent (NUMERIC)
- effective_qty (NUMERIC, generated)
- substitute_material_id (INT, FK, nullable)
- is_critical (BOOLEAN)
- line_no (SMALLINT)
- remarks (VARCHAR, nullable)

## Implementation Priority

### Phase 1 (High Priority - Critical Masters)
1. Material Master
2. Product Master
3. Vendor Master
4. BOM Header & Detail

### Phase 2 (Medium Priority - Supporting Masters)
5. UOM Master
6. Warehouse Master
7. HSN Codes
8. GST Taxes
9. Currency Master

### Phase 3 (Low Priority - Optional Masters)
10. Zone Master
11. Approval Matrix
12. Bin Locations
13. Vendor Contacts
14. Vendor Material Map

## Quick Start Commands

```bash
# Create controller
php artisan make:controller ZoneController

# Create model
php artisan make:model Tenant/ZoneMaster

# Create migration (if needed)
php artisan make:migration create_zone_master_table --path=database/migrations/tenant

# Clear caches
php artisan route:clear
php artisan view:clear
```

## Summary

This guide provides the complete structure for implementing all 19 master data pages. Each master follows the same pattern:

1. Create routes in `routes/tenant.php`
2. Create API routes in `routes/api.php`
3. Create controller with CRUD operations
4. Create model with relationships
5. Create views (index, create, edit, show)
6. Test functionality

The system is now ready for full master data implementation. Start with Phase 1 (critical masters) and work through the phases based on business priority.
