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
            $query = Material::with(['uom', 'purchaseUom', 'hsnCode.defaultGst', 'defaultWarehouse']);

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
                'data' => $materials->items(),
                'pagination' => [
                    'current_page' => $materials->currentPage(),
                    'per_page' => $materials->perPage(),
                    'total' => $materials->total(),
                    'last_page' => $materials->lastPage(),
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

    /**
     * Bulk create materials
     * POST /api/v1/materials/bulk
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'materials' => 'required|array|min:1|max:50',
            'materials.*.material_name' => 'required|string|max:200',
            'materials.*.material_type' => 'required|string|max:20',
            'materials.*.uom_id' => 'required|integer',
            'materials.*.purchase_uom_id' => 'nullable|integer',
            'materials.*.hsn_code_id' => 'nullable|integer',
            'materials.*.default_warehouse_id' => 'nullable|integer',
            'materials.*.reorder_level' => 'nullable|numeric|min:0',
            'materials.*.safety_stock' => 'nullable|numeric|min:0',
            'materials.*.lead_time_days' => 'nullable|integer|min:0',
            'materials.*.shelf_life_days' => 'nullable|integer|min:0',
            'materials.*.qc_required' => 'nullable|boolean',
            'materials.*.inspection_type' => 'nullable|string|max:10',
            'materials.*.is_batch_tracked' => 'nullable|boolean',
            'materials.*.standard_cost' => 'nullable|numeric|min:0',
            'materials.*.valuation_method' => 'nullable|string|max:10',
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
            $created = [];
            $errors = [];

            \DB::beginTransaction();

            $typeCounters = [];

            foreach ($request->input('materials') as $index => $item) {
                try {
                    $materialType = $item['material_type'] ?? 'RAW';
                    if (!isset($typeCounters[$materialType])) {
                        $typeCounters[$materialType] = 0;
                    }
                    $offset = $typeCounters[$materialType];
                    $typeCounters[$materialType]++;

                    // Auto-generate material code with batch offset
                    $materialCode = $this->generateMaterialCode($materialType, $offset);

                    \Log::info("Bulk Create Row " . ($index + 1) . ": Type=$materialType, Code=$materialCode");

                    $material = Material::create([
                        'material_code' => $materialCode,
                        'material_name' => $item['material_name'],
                        'material_type' => $materialType,
                        'uom_id' => $item['uom_id'],
                        'purchase_uom_id' => $item['purchase_uom_id'] ?? null,
                        'hsn_code_id' => $item['hsn_code_id'] ?? null,
                        'default_warehouse_id' => $item['default_warehouse_id'] ?? null,
                        'reorder_level' => $item['reorder_level'] ?? 0,
                        'safety_stock' => $item['safety_stock'] ?? 0,
                        'lead_time_days' => $item['lead_time_days'] ?? 0,
                        'shelf_life_days' => $item['shelf_life_days'] ?? null,
                        'qc_required' => $item['qc_required'] ?? true,
                        'inspection_type' => $item['inspection_type'] ?? 'AQL',
                        'is_batch_tracked' => $item['is_batch_tracked'] ?? false,
                        'standard_cost' => $item['standard_cost'] ?? 0,
                        'valuation_method' => $item['valuation_method'] ?? 'FIFO',
                        'is_active' => true,
                        'created_by' => $request->input('auth_user_id'),
                    ]);

                    $created[] = $material;
                } catch (\Exception $e) {
                    \Log::error("Bulk Row " . ($index + 1) . " Error: " . $e->getMessage());
                    $errors[] = [
                        'row' => $index + 1,
                        'name' => $item['material_name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            if (!empty($errors) && empty($created)) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'BULK_CREATE_FAILED', 'details' => $errors],
                    'message' => 'All materials failed to create',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'created_count' => count($created),
                    'errors' => $errors,
                    'error_count' => count($errors)
                ],
                'message' => count($created) . ' material(s) created successfully' .
                    (count($errors) > 0 ? ', ' . count($errors) . ' failed' : ''),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BULK_CREATE_FAILED', 'details' => []],
                'message' => 'Failed to bulk create materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateMaterialCode(string $materialType, int $offset = 0): string
    {
        $prefix = match ($materialType) {
            'RAW' => 'RM',
            'PACKAGING' => 'PKG',
            'CONSUMABLE' => 'CON',
            'SEMI' => 'SF',
            default => 'MAT'
        };

        // Get all codes with this prefix to find the highest number
        // We use like then manual parsing to ensure we handle string sorting correctly
        $existingCodes = Material::where('material_code', 'like', $prefix . '-%')
            ->pluck('material_code')
            ->toArray();

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            $parts = explode('-', $code);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $num = (int)$parts[1];
                if ($num > $maxNumber) $maxNumber = $num;
            }
        }

        // Apply batch offset to avoid duplicates within the same bulk request
        $nextNumber = $maxNumber + 1 + $offset;

        // Final check to ensure it's absolutely unique (handling race conditions or gaps)
        do {
            $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = Material::where('material_code', $generatedCode)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

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

    /**
     * Export materials to CSV
     * GET /api/v1/materials/export
     */
    public function export(Request $request)
    {
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

            $materials = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="materials_export_' . date('Y-m-d_His') . '.csv"',
            ];

            $callback = function () use ($materials) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, [
                    'material_code',
                    'material_name',
                    'material_type',
                    'uom_code',
                    'purchase_uom_code',
                    'hsn_code',
                    'reorder_level',
                    'safety_stock',
                    'lead_time_days',
                    'shelf_life_days',
                    'qc_required',
                    'inspection_type',
                    'is_batch_tracked',
                    'standard_cost',
                    'valuation_method',
                    'is_active'
                ]);

                // Data rows
                foreach ($materials as $material) {
                    fputcsv($file, [
                        $material->material_code,
                        $material->material_name,
                        $material->material_type,
                        $material->uom?->uom_code ?? '',
                        $material->purchaseUom?->uom_code ?? '',
                        $material->hsnCode?->hsn_code ?? '',
                        $material->reorder_level ?? '',
                        $material->safety_stock ?? '',
                        $material->lead_time_days ?? '',
                        $material->shelf_life_days ?? '',
                        $material->qc_required ? 'true' : 'false',
                        $material->inspection_type ?? '',
                        $material->is_batch_tracked ? 'true' : 'false',
                        $material->standard_cost ?? '',
                        $material->valuation_method ?? '',
                        $material->is_active ? 'true' : 'false'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import materials from CSV
     * POST /api/v1/materials/import
     */
    public function import(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
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
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_map('trim', $csvData[0]);
            $rows = array_slice($csvData, 1);

            $imported = 0;
            $errors = [];

            // Fetch UOM and HSN mappings
            $uomMap = \App\Models\Tenant\UOM::pluck('id', 'uom_code')->toArray();
            $hsnMap = \App\Models\Tenant\HSNCode::pluck('id', 'hsn_code')->toArray();

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;

                $rowNumber = $index + 2;
                $data = array_combine($headers, $row);

                try {
                    // Validate required fields
                    if (empty($data['material_name'])) {
                        $errors[] = "Row {$rowNumber}: Material name is required";
                        continue;
                    }

                    if (empty($data['material_type'])) {
                        $errors[] = "Row {$rowNumber}: Material type is required";
                        continue;
                    }

                    // Check for duplicate material name
                    $existingMaterial = Material::where('material_name', trim($data['material_name']))->first();
                    if ($existingMaterial) {
                        $errors[] = "Row {$rowNumber}: Material '{$data['material_name']}' already exists";
                        continue;
                    }

                    // Resolve UOM
                    $uomId = null;
                    if (!empty($data['uom_code'])) {
                        $uomCode = strtoupper(trim($data['uom_code']));
                        $uomId = $uomMap[$uomCode] ?? null;
                        if (!$uomId) {
                            $errors[] = "Row {$rowNumber}: UOM code '{$data['uom_code']}' not found";
                            continue;
                        }
                    } else {
                        $errors[] = "Row {$rowNumber}: UOM code is required";
                        continue;
                    }

                    // Resolve Purchase UOM
                    $purchaseUomId = null;
                    if (!empty($data['purchase_uom_code'])) {
                        $purchaseUomCode = strtoupper(trim($data['purchase_uom_code']));
                        $purchaseUomId = $uomMap[$purchaseUomCode] ?? null;
                    }

                    // Resolve HSN Code
                    $hsnCodeId = null;
                    if (!empty($data['hsn_code'])) {
                        $hsnCode = trim($data['hsn_code']);
                        $hsnCodeId = $hsnMap[$hsnCode] ?? null;
                    }

                    // Generate material code
                    $materialType = strtoupper(trim($data['material_type']));
                    $materialCode = $this->generateMaterialCode($materialType);

                    // Create material
                    Material::create([
                        'material_code' => $materialCode,
                        'material_name' => trim($data['material_name']),
                        'material_type' => $materialType,
                        'uom_id' => $uomId,
                        'purchase_uom_id' => $purchaseUomId,
                        'hsn_code_id' => $hsnCodeId,
                        'reorder_level' => !empty($data['reorder_level']) ? floatval($data['reorder_level']) : 0,
                        'safety_stock' => !empty($data['safety_stock']) ? floatval($data['safety_stock']) : 0,
                        'lead_time_days' => !empty($data['lead_time_days']) ? intval($data['lead_time_days']) : 0,
                        'shelf_life_days' => !empty($data['shelf_life_days']) ? intval($data['shelf_life_days']) : null,
                        'qc_required' => !empty($data['qc_required']) && in_array(strtolower($data['qc_required']), ['true', '1', 'yes']),
                        'inspection_type' => !empty($data['inspection_type']) ? trim($data['inspection_type']) : null,
                        'is_batch_tracked' => !empty($data['is_batch_tracked']) && in_array(strtolower($data['is_batch_tracked']), ['true', '1', 'yes']),
                        'standard_cost' => !empty($data['standard_cost']) ? floatval($data['standard_cost']) : 0,
                        'valuation_method' => !empty($data['valuation_method']) ? trim($data['valuation_method']) : 'FIFO',
                        'is_active' => true,
                        'created_by' => $request->input('auth_user_id'),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'errors' => $errors,
                    'total_rows' => count($rows)
                ],
                'message' => "{$imported} material(s) imported successfully" . (count($errors) > 0 ? ", " . count($errors) . " failed" : ""),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'IMPORT_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to import materials: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

}
