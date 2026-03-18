<?php

namespace App\Http\Controllers;

use App\Models\Tenant\UOM;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UOMController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = UOM::with(['baseUom']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('uom_name', 'like', "%{$search}%")
                        ->orWhere('uom_code', 'like', "%{$search}%");
                });
            }

            $uoms = $query->get();

            return response()->json([
                'success' => true,
                'data' => $uoms->toArray(),
                'message' => 'UOMs retrieved successfully',
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
                'message' => 'Failed to retrieve UOMs: ' . $e->getMessage(),
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
            $uom = UOM::with(['baseUom'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'UOM not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Auto-generate UOM code if not provided or auto_generate_code is checked
        $uomCode = $request->input('uom_code');
        $autoGenerate = $request->input('auto_generate_code');
        $uomType = $request->input('uom_type');
        
        \Log::info('UOM creation debug:', [
            'uom_code_input' => $uomCode,
            'auto_generate_code' => $autoGenerate,
            'uom_type' => $uomType,
            'all_request_data' => $request->all()
        ]);
        
        if (empty($uomCode) || $autoGenerate) {
            $uomCode = $this->generateUOMCode($uomType);
            \Log::info('Generated UOM code: ' . $uomCode);
            
            // Override the request data with generated code
            $request->merge(['uom_code' => $uomCode]);
        }
        
        \Log::info('Final request data before validation:', $request->all());

        $validator = Validator::make($request->all(), [
            'uom_code' => 'sometimes|string|max:10|unique:tenant.uom_master,uom_code',
            'uom_name' => 'required|string|max:50',
            'uom_type' => 'required|string|max:20',
            'base_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'auto_generate_code' => 'sometimes|boolean',
            'manual_prefix' => 'nullable|string|max:10',
            'manual_number' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            \Log::error('UOM validation failed:', $validator->errors()->toArray());
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
            $uom = UOM::create([
                'uom_code' => $request->input('uom_code'),
                'uom_name' => $request->input('uom_name'),
                'uom_type' => $request->input('uom_type'),
                'base_uom_id' => $request->input('base_uom_id'),
                'conversion_factor' => $request->input('conversion_factor', 1),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'uom_code' => 'sometimes|string|max:10|unique:tenant.uom_master,uom_code,' . $id . ',id',
            'uom_name' => 'sometimes|string|max:50',
            'uom_type' => 'sometimes|string|max:20',
            'base_uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'conversion_factor' => 'nullable|numeric|min:0',
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
            $uom = UOM::findOrFail($id);

            if ($request->has('uom_code')) {
                $uom->uom_code = $request->input('uom_code');
            }
            if ($request->has('uom_name')) {
                $uom->uom_name = $request->input('uom_name');
            }
            if ($request->has('uom_type')) {
                $uom->uom_type = $request->input('uom_type');
            }
            if ($request->has('base_uom_id')) {
                $uom->base_uom_id = $request->input('base_uom_id');
            }
            if ($request->has('conversion_factor')) {
                $uom->conversion_factor = $request->input('conversion_factor');
            }
            if ($request->has('is_active')) {
                $uom->is_active = $request->input('is_active');
            }

            $uom->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'uom' => $uom
                ],
                'message' => 'UOM updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $uom = UOM::findOrFail($id);
            $uom->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'UOM deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UOM_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete UOM: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateUOMCode(string $uomType): string
    {
        $prefix = match($uomType) {
            'weight' => 'KG',
            'volume' => 'LIT',
            'qty' => 'PCS',
            'length' => 'MTR',
            default => 'UOM'
        };

        \Log::info('Generating UOM code for type: ' . $uomType . ' with prefix: ' . $prefix);

        // Get the last UOM code for this type
        $lastCode = UOM::where('uom_code', 'like', $prefix . '-%')
            ->orderBy('uom_code', 'desc')
            ->value('uom_code');

        \Log::info('Last UOM code found: ' . ($lastCode ?? 'none'));

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int)$parts[1] + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        \Log::info('Final generated UOM code: ' . $generatedCode);

        return $generatedCode;
    }
}
