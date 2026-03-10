<?php

namespace App\Http\Controllers;

use App\Models\Tenant\BinLocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BinLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = BinLocation::with(['warehouse']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->input('warehouse_id'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('bin_code', 'like', "%{$search}%")
                        ->orWhere('aisle', 'like', "%{$search}%")
                        ->orWhere('rack', 'like', "%{$search}%");
                });
            }

            $bins = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'bin_locations' => $bins
                ],
                'message' => 'Bin locations retrieved successfully',
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
                'message' => 'Failed to retrieve bin locations: ' . $e->getMessage(),
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
            $bin = BinLocation::with(['warehouse'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'bin_location' => $bin
                ],
                'message' => 'Bin location retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BIN_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Bin location not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Auto-generate bin code if not provided or auto_generate_code is checked
        $binCode = $request->input('bin_code');
        $autoGenerate = $request->input('auto_generate_code');
        $warehouseId = $request->input('warehouse_id');
        
        \Log::info('Bin location creation debug:', [
            'bin_code_input' => $binCode,
            'auto_generate_code' => $autoGenerate,
            'warehouse_id' => $warehouseId,
            'all_request_data' => $request->all()
        ]);
        
        if (empty($binCode) || $autoGenerate) {
            $binCode = $this->generateBinCode($warehouseId);
            \Log::info('Generated bin code: ' . $binCode);
            
            // Override the request data with generated code
            $request->merge(['bin_code' => $binCode]);
        }
        
        \Log::info('Final request data before validation:', $request->all());

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer|exists:tenant.warehouse_master,id',
            'bin_code' => 'sometimes|string|max:30|unique:tenant.bin_locations,bin_code',
            'aisle' => 'nullable|string|max:10',
            'rack' => 'nullable|string|max:10',
            'shelf' => 'nullable|string|max:10',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'auto_generate_code' => 'sometimes|boolean',
            'manual_prefix' => 'nullable|string|max:10',
            'manual_number' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            \Log::error('Bin location validation failed:', $validator->errors()->toArray());
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
            $bin = BinLocation::create([
                'warehouse_id' => $request->input('warehouse_id'),
                'bin_code' => $request->input('bin_code'),
                'aisle' => $request->input('aisle'),
                'rack' => $request->input('rack'),
                'shelf' => $request->input('shelf'),
                'max_weight_kg' => $request->input('max_weight_kg'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'bin_location' => $bin
                ],
                'message' => 'Bin location created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BIN_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create bin location: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'sometimes|integer|exists:tenant.warehouse_master,id',
            'bin_code' => 'sometimes|string|max:30|unique:tenant.bin_locations,bin_code,' . $id . ',id',
            'aisle' => 'nullable|string|max:10',
            'rack' => 'nullable|string|max:10',
            'shelf' => 'nullable|string|max:10',
            'max_weight_kg' => 'nullable|numeric|min:0',
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
            $bin = BinLocation::findOrFail($id);

            if ($request->has('warehouse_id')) {
                $bin->warehouse_id = $request->input('warehouse_id');
            }
            if ($request->has('bin_code')) {
                $bin->bin_code = $request->input('bin_code');
            }
            if ($request->has('aisle')) {
                $bin->aisle = $request->input('aisle');
            }
            if ($request->has('rack')) {
                $bin->rack = $request->input('rack');
            }
            if ($request->has('shelf')) {
                $bin->shelf = $request->input('shelf');
            }
            if ($request->has('max_weight_kg')) {
                $bin->max_weight_kg = $request->input('max_weight_kg');
            }
            if ($request->has('is_active')) {
                $bin->is_active = $request->input('is_active');
            }

            $bin->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'bin_location' => $bin
                ],
                'message' => 'Bin location updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BIN_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update bin location: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bin = BinLocation::findOrFail($id);
            $bin->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Bin location deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BIN_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete bin location: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateBinCode(int $warehouseId): string
    {
        // Get warehouse code to use as prefix
        $warehouse = \App\Models\Tenant\Warehouse::find($warehouseId);
        $warehouseCode = $warehouse ? $warehouse->warehouse_code : 'WH';
        
        // Extract prefix from warehouse code (use first part before dash)
        $prefix = explode('-', $warehouseCode)[0] ?? 'BIN';

        \Log::info('Generating bin code for warehouse: ' . $warehouseCode . ' with prefix: ' . $prefix);

        // Get the last bin code for this warehouse
        $lastCode = BinLocation::where('warehouse_id', $warehouseId)
            ->where('bin_code', 'like', $prefix . '-%')
            ->orderBy('bin_code', 'desc')
            ->value('bin_code');

        \Log::info('Last bin code found: ' . ($lastCode ?? 'none'));

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int)$parts[1] + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        \Log::info('Final generated bin code: ' . $generatedCode);

        return $generatedCode;
    }
}
