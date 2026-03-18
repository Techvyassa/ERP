<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Material::with(['uom', 'purchaseUom', 'hsnCode', 'defaultWarehouse']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('material_type')) {
                $query->where('material_type', $request->input('material_type'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('material_code', 'like', "%{$search}%")
                        ->orWhere('material_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $materials = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'materials' => $materials->items(),
                    'pagination' => [
                        'current_page' => $materials->currentPage(),
                        'per_page' => $materials->perPage(),
                        'total' => $materials->total(),
                        'last_page' => $materials->lastPage(),
                    ]
                ],
                'message' => 'Materials retrieved successfully',
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
                'message' => 'Failed to retrieve materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $material = Material::with(['uom', 'purchaseUom', 'hsnCode', 'defaultWarehouse'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Material not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Auto-generate material code if not provided or auto_generate_code is checked
        $materialCode = $request->input('material_code');
        $autoGenerate = $request->input('auto_generate_code');
        $materialType = $request->input('material_type');

        \Log::info('Material creation debug:', [
            'material_code_input' => $materialCode,
            'auto_generate_code' => $autoGenerate,
            'material_type' => $materialType,
            'all_request_data' => $request->all()
        ]);

        if (empty($materialCode) || $autoGenerate) {
            $materialCode = $this->generateMaterialCode($materialType);
            \Log::info('Generated material code: ' . $materialCode);

            // Override the request data with generated code
            $request->merge(['material_code' => $materialCode]);
        }

        \Log::info('Final request data before validation:', $request->all());

        $validator = Validator::make($request->all(), [
            'material_code' => 'sometimes|string|max:30|unique:tenant.material_master,material_code',
            'material_name' => 'required|string|max:200',
            'material_type' => 'required|string|max:20',
            'uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'purchase_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'required|integer|exists:tenant.hsn_codes,id',
            'default_warehouse_id' => 'nullable|integer|exists:tenant.warehouse_master,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'qc_required' => 'sometimes|boolean',
            'inspection_type' => 'nullable|string|max:10',
            'is_batch_tracked' => 'sometimes|boolean',
            'standard_cost' => 'nullable|numeric|min:0',
            'valuation_method' => 'nullable|string|max:10',
            'is_active' => 'sometimes|boolean',
            'auto_generate_code' => 'sometimes|boolean',
            'manual_prefix' => 'nullable|string|max:10',
            'manual_number' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
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
            $material = Material::create(array_merge(
                $request->all(),
                [
                    'material_code' => $materialCode,
                    'created_by' => $request->input('auth_user_id'),
                    'is_active' => true
                ]
            ));

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateMaterialCode(string $materialType): string
    {
        $prefix = match ($materialType) {
            'RAW' => 'RM',
            'PACKAGING' => 'PKG',
            'CONSUMABLE' => 'CON',
            'SEMI' => 'SF',
            default => 'MAT'
        };

        \Log::info('Generating material code for type: ' . $materialType . ' with prefix: ' . $prefix);

        // Get the last material code for this type
        $lastCode = Material::where('material_code', 'like', $prefix . '-%')
            ->orderBy('material_code', 'desc')
            ->value('material_code');

        \Log::info('Last material code found: ' . ($lastCode ?? 'none'));

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        \Log::info('Final generated code: ' . $generatedCode);

        return $generatedCode;
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

    /**
     * Search materials by barcode (actually searches by material_code)
     * Used by store department for quick material lookup during receiving/pickup
     */
    public function searchByBarcode(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
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

            $material = Material::with(['uom', 'hsnCode', 'defaultWarehouse'])
                ->where('material_code', $code)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'MATERIAL_NOT_FOUND',
                        'details' => []
                    ],
                    'message' => 'Material not found for code: ' . $code,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material,
                    'barcode_html' => $this->bar128($material->material_code)
                ],
                'message' => 'Material found successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BARCODE_SEARCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to search material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Search materials with filters
     * Used by store department for material lookup
     */
    public function search(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:100',
            'material_code' => 'nullable|string|max:30',
            'id' => 'nullable|integer',
            'category_id' => 'nullable|integer|exists:tenant.material_categories,id',
            'material_type' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
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
            $query = Material::with(['uom', 'hsnCode', 'defaultWarehouse']);

            // Search by material code
            if ($request->has('material_code')) {
                $query->where('material_code', 'LIKE', '%' . $request->input('material_code') . '%');
            }

            // Search by ID
            if ($request->has('id')) {
                $query->where('id', $request->input('id'));
            }

            // Search by keyword (material_code or material_name)
            if ($request->has('q')) {
                $keyword = $request->input('q');
                $query->where(function ($q) use ($keyword) {
                    $q->where('material_code', 'LIKE', '%' . $keyword . '%')
                      ->orWhere('material_name', 'LIKE', '%' . $keyword . '%');
                });
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // Filter by material type
            if ($request->has('material_type')) {
                $query->where('material_type', $request->input('material_type'));
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->input('is_active'));
            }

            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $materials = $query->orderBy('material_code', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $materials,
                'message' => 'Materials retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_SEARCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to search materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function bar128(string $text): string
    {
        $char128asc = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $char128wid = [
            '212222',
            '222122',
            '222221',
            '121223',
            '121322',
            '131222',
            '122213',
            '122312',
            '132212',
            '221213',
            '221312',
            '231212',
            '112232',
            '122132',
            '122231',
            '113222',
            '123122',
            '123221',
            '223211',
            '221132',
            '221231',
            '213212',
            '223112',
            '312131',
            '311222',
            '321122',
            '321221',
            '312212',
            '322112',
            '322211',
            '212123',
            '212321',
            '232121',
            '111323',
            '131123',
            '131321',
            '112313',
            '132113',
            '132311',
            '211313',
            '231113',
            '231311',
            '112133',
            '112331',
            '132131',
            '113123',
            '113321',
            '133121',
            '313121',
            '211331',
            '231131',
            '213113',
            '213311',
            '213131',
            '311123',
            '311321',
            '331121',
            '312113',
            '312311',
            '332111',
            '314111',
            '221411',
            '431111',
            '111224',
            '111422',
            '121124',
            '121421',
            '141122',
            '141221',
            '112214',
            '112412',
            '122114',
            '122411',
            '142112',
            '142211',
            '241211',
            '221114',
            '413111',
            '241112',
            '134111',
            '111242',
            '121142',
            '121241',
            '114212',
            '124112',
            '124211',
            '411212',
            '421112',
            '421211',
            '212141',
            '214121',
            '412121',
            '111143',
            '111341',
            '131141',
            '114113',
            '114311',
            '411113',
            '411311',
            '113141',
            '114131',
            '311141',
            '411131',
            '211412',
            '211214',
            '211232',
            '23311120'
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

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'material_code' => 'sometimes|string|max:30|unique:tenant.material_master,material_code,' . $id . ',id',
            'material_name' => 'sometimes|string|max:200',
            'material_type' => 'sometimes|string|max:20',
            'uom_id' => 'sometimes|integer|exists:tenant.uom_master,id',
            'purchase_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'hsn_code_id' => 'sometimes|integer|exists:tenant.hsn_codes,id',
            'default_warehouse_id' => 'nullable|integer|exists:tenant.warehouse_master,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'qc_required' => 'sometimes|boolean',
            'inspection_type' => 'nullable|string|max:10',
            'is_batch_tracked' => 'sometimes|boolean',
            'standard_cost' => 'nullable|numeric|min:0',
            'valuation_method' => 'nullable|string|max:10',
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
            $material = Material::findOrFail($id);
            $material->update(array_merge(
                $request->all(),
                ['updated_by' => $request->input('auth_user_id')]
            ));

            return response()->json([
                'success' => true,
                'data' => [
                    'material' => $material
                ],
                'message' => 'Material updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $material = Material::findOrFail($id);
            $material->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Material deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MATERIAL_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete material: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
