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

    public function barcode(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:30',
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
            $code = $request->input('code');
            $html = $this->bar128($code);

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html
                ],
                'message' => 'Barcode generated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BARCODE_GENERATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to generate barcode: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function bar128(string $text): string
    {
        $char128asc = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $char128wid = [
            '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213',
            '221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
            '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211',
            '212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
            '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331',
            '231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
            '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214',
            '112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
            '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141',
            '214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
            '114131','311141','411131','211412','211214','211232','23311120'
        ];

        $sum = 104;
        $w = $char128wid[$sum];
        $onChar = 1;

        for ($x = 0; $x < strlen($text); $x++) {
            $pos = strpos($char128asc, $text[$x]);
            if ($pos !== false) {
                $w .= $char128wid[$pos];
                $sum += $onChar++ * $pos;
            }
        }

        $checksum = $sum % 103;
        $w .= $char128wid[$checksum];
        $w .= $char128wid[106];

        $html = "<table cellpadding=0 cellspacing=0 style='text-align:center'><tr>";
        for ($x = 0; $x < strlen($w); $x += 2) {
            $border = (int) $w[$x];
            $width = (int) $w[$x + 1];
            $html .= "<td><div class=\"b128\" style=\"display:inline-block;height:30px;border-left:{$border}px solid #000;width:{$width}px;margin-left:1px\"></div></td>";
        }

        return $html . "</tr></table>";
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

        // Auto-generate product code if not provided or auto_generate_code is checked
        $productCode = $request->input('product_code');
        $autoGenerate = $request->input('auto_generate_code');
        $productCategory = $request->input('product_category');
        
        \Log::info('Product creation debug:', [
            'product_code_input' => $productCode,
            'auto_generate_code' => $autoGenerate,
            'product_category' => $productCategory,
            'all_request_data' => $request->all()
        ]);
        
        if (empty($productCode) || $autoGenerate) {
            $productCode = $this->generateProductCode($productCategory);
            \Log::info('Generated product code: ' . $productCode);
            
            // Override the request data with generated code
            $request->merge(['product_code' => $productCode]);
        }
        
        \Log::info('Final request data before validation:', $request->all());

        $validator = Validator::make($request->all(), [
            'product_code' => 'sometimes|string|max:30|unique:tenant.product_master,product_code',
            'product_name' => 'required|string|max:200',
            'product_category' => 'nullable|string|max:60',
            'pack_size' => 'required|numeric|min:0',
            'pack_uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'required|integer|exists:tenant.hsn_codes,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'auto_generate_code' => 'sometimes|boolean',
            'manual_prefix' => 'nullable|string|max:10',
            'manual_number' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            \Log::error('Product validation failed:', $validator->errors()->toArray());
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
            $product->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Product deleted successfully',
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
                'message' => 'Failed to delete product: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateProductCode(string $productCategory): string
    {
        $prefix = match($productCategory) {
            'ELECTRONICS' => 'ELEC',
            'CLOTHING' => 'CLO',
            'FOOD' => 'FD',
            'BEVERAGES' => 'BEV',
            'FURNITURE' => 'FUR',
            'TOYS' => 'TOY',
            'BOOKS' => 'BK',
            'SPORTS' => 'SP',
            'BEAUTY' => 'BEA',
            'AUTOMOTIVE' => 'AUTO',
            default => 'PROD'
        };

        \Log::info('Generating product code for category: ' . $productCategory . ' with prefix: ' . $prefix);

        // Get the last product code for this category
        $lastCode = Product::where('product_code', 'like', $prefix . '-%')
            ->orderBy('product_code', 'desc')
            ->value('product_code');

        \Log::info('Last product code found: ' . ($lastCode ?? 'none'));

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int)$parts[1] + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        \Log::info('Final generated product code: ' . $generatedCode);

        return $generatedCode;
    }
}
