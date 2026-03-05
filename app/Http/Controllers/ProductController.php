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
