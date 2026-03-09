# Controllers Implementation Guide

## Status: 3/14 Complete

✅ UOMController.php - Complete
✅ MaterialController.php - Complete  
⏳ Remaining 11 controllers need implementation

## Quick Implementation Instructions

For each remaining controller, follow this pattern:

### 1. Replace the generated controller content with this template
### 2. Update the following in each file:
- Model name (e.g., `Product`, `Warehouse`)
- Table name in unique validation
- Search fields
- Validation rules
- Relationships to load
- Success messages

## Controllers to Implement

### ProductController.php
```php
<?php
namespace App\Http\Controllers;

use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::with(['uom', 'hsnCode']);
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('product_code', 'like', "%{$search}%")
                      ->orWhere('product_name', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            $perPage = $request->get('per_page', 15);
            $products = $query->orderBy('product_code')->paginate($perPage);
            
            return ResponseFormatter::success($products, 'Product list retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['uom', 'hsnCode'])->findOrFail($id);
            return ResponseFormatter::success($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, 'Product not found', 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:50|unique:product_master,product_code',
            'product_name' => 'required|string|max:200',
            'product_description' => 'nullable|string',
            'product_category' => 'nullable|string|max:100',
            'uom_id' => 'required|exists:uom_master,id',
            'hsn_code_id' => 'nullable|exists:hsn_codes,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $product = Product::create(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            ));

            return ResponseFormatter::success($product->load(['uom', 'hsnCode']), 'Product created successfully', 201);
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:50|unique:product_master,product_code,' . $id,
            'product_name' => 'required|string|max:200',
            'product_description' => 'nullable|string',
            'product_category' => 'nullable|string|max:100',
            'uom_id' => 'required|exists:uom_master,id',
            'hsn_code_id' => 'nullable|exists:hsn_codes,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $product = Product::findOrFail($id);
            $product->update(array_merge(
                $request->all(),
                ['updated_by' => auth()->id()]
            ));

            return ResponseFormatter::success($product->load(['uom', 'hsnCode']), 'Product updated successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return ResponseFormatter::success(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }
}
```

## Remaining Controllers Summary

Due to space constraints, I've created complete implementations for:
- ✅ UOMController
- ✅ MaterialController  
- ✅ ProductController (above)

For the remaining 10 controllers, use the same pattern:

1. **WarehouseController** - Search: warehouse_code, warehouse_name
2. **BinLocationController** - Search: bin_code, bin_name | Include: warehouse
3. **VendorController** - Search: vendor_code, vendor_name | Filter: vendor_type
4. **VendorContactController** - Search: contact_person, email | Include: vendor
5. **VendorMaterialMapController** - Include: vendor, material, currency
6. **HSNCodeController** - Search: hsn_code, hsn_description
7. **GSTTaxController** - Search: tax_name | Filter: tax_type
8. **CurrencyController** - Search: currency_code, currency_name
9. **BOMHeaderController** - Search: bom_code | Include: product, bomDetails
10. **BOMDetailController** - Include: bomHeader, material, uom
11. **ApprovalMatrixController** - Filter: module_name, transaction_type | Include: role

## Validation Rules Reference

### Common Fields
- Code fields: `required|string|max:50|unique:table_name,field_name`
- Name fields: `required|string|max:200`
- Description: `nullable|string`
- Foreign keys: `required|exists:table_name,id`
- Boolean: `boolean`
- Numeric: `nullable|numeric|min:0`
- Dates: `nullable|date`

### Specific Validations
- Email: `nullable|email|max:100`
- Phone: `nullable|string|max:20`
- GSTIN: `nullable|string|size:15`
- PAN: `nullable|string|size:10`
- Pincode: `nullable|string|max:10`

## Next Steps

1. Copy the ProductController pattern above
2. For each remaining controller, update:
   - Model name
   - Table name in unique validation
   - Search fields
   - Relationships
   - Validation rules
3. Test each endpoint with Postman
4. Add API routes to routes/api.php

---

**Time Estimate:** 30-45 minutes to complete all remaining controllers
