<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Product::with(['packUom', 'hsnCode']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('product_category')) {
                $query->where('product_category', $request->input('product_category'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products->items(),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'last_page' => $products->lastPage(),
                    ]
                ],
                'message' => 'Products retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve products: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $product = Product::with(['packUom', 'hsnCode'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product
                ],
                'message' => 'Product retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Product not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:30|unique:tenant.product_master,product_code',
            'product_name' => 'required|string|max:200',
            'product_category' => 'nullable|string|max:60',
            'pack_size' => 'required|numeric|min:0',
            'pack_uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'required|integer|exists:tenant.hsn_codes,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $product = Product::create(array_merge(
                $request->all(),
                ['is_active' => true]
            ));

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product
                ],
                'message' => 'Product created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create product: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'product_code' => 'sometimes|string|max:30|unique:tenant.product_master,product_code,' . $id . ',id',
            'product_name' => 'sometimes|string|max:200',
            'product_category' => 'nullable|string|max:60',
            'pack_size' => 'sometimes|numeric|min:0',
            'pack_uom_id' => 'sometimes|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'sometimes|integer|exists:tenant.hsn_codes,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors()
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $product->update($request->all());

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product
                ],
                'message' => 'Product updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update product: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $product = Product::findOrFail($id);
            $product->is_active = false;
            $product->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Product deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to deactivate product: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
