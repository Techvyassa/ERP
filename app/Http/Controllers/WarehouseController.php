<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Warehouse;
use App\Models\Tenant\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\StockBalance;
use App\Services\StockQueryService;

class WarehouseController extends Controller
{
    public function __construct(protected StockQueryService $stockQueryService) {}

    /**
     * GET /api/v1/warehouse/dashboard-stats
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            // Stats
            $totalMaterials = Material::count();
            $activeWarehouses = Warehouse::where('is_active', true)->count();
            $pendingMIR = MaterialIssueRequest::where('status', 'PENDING')->count();
            $approvedMIR = MaterialIssueRequest::where('status', 'APPROVED')->count();
            
            // Raw Material Stock
            $allStock = $this->stockQueryService->getGlobalStockSummary();
            $rmStock = array_filter($allStock, fn($item) => $item['item_type'] === 'Material');

            return response()->json([
                'success' => true,
                'data' => [
                    'materialsCount' => $totalMaterials,
                    'warehousesCount' => $activeWarehouses,
                    'pendingMIR' => $pendingMIR,
                    'approvedMIR' => $approvedMIR,
                    'rmStock' => array_values($rmStock)
                ],
                'message' => 'Dashboard data retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    /**
     * Display stock management dashboard page
     */
    public function stockManagementPage(Request $request): View
    {
        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_code', 'warehouse_name']);
        
        return view('tenant.warehouse.stock-management', compact('warehouses'));
    }

    /**
     * Get stock data for all warehouses or specific warehouse
     */
    public function allWarehouseStock(): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $stockData = $this->stockQueryService->getGlobalStockSummary();
            
            return response()->json([
                'success' => true,
                'data' => $stockData,
                'message' => 'Stock data retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STOCK_DATA_ERROR',
                    'message' => 'Failed to retrieve stock data',
                    'details' => $e->getMessage()
                ],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Warehouse::with(['inchargeUser']);

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('warehouse_type')) {
                $query->where('warehouse_type', $request->input('warehouse_type'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('warehouse_name', 'like', "%{$search}%")
                        ->orWhere('warehouse_code', 'like', "%{$search}%");
                });
            }

            $warehouses = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses
                ],
                'message' => 'Warehouses retrieved successfully',
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
                'message' => 'Failed to retrieve warehouses: ' . $e->getMessage(),
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
            $warehouse = Warehouse::with(['inchargeUser'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Warehouse not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        // Auto-generate warehouse code if not provided or auto_generate_code is checked
        $warehouseCode = $request->input('warehouse_code');
        $autoGenerate = $request->input('auto_generate_code');
        $warehouseType = $request->input('warehouse_type');
        
        Log::info('Warehouse creation debug:', [
            'warehouse_code_input' => $warehouseCode,
            'auto_generate_code' => $autoGenerate,
            'warehouse_type' => $warehouseType,
            'all_request_data' => $request->all()
        ]);
        
        if (empty($warehouseCode) || $autoGenerate) {
            $warehouseCode = $this->generateWarehouseCode($warehouseType);
            Log::info('Generated warehouse code: ' . $warehouseCode);
            
            // Override the request data with generated code
            $request->merge(['warehouse_code' => $warehouseCode]);
        }
        
        Log::info('Final request data before validation:', $request->all());

        $validator = Validator::make($request->all(), [
            'warehouse_code' => 'sometimes|string|max:20|unique:tenant.warehouse_master,warehouse_code',
            'warehouse_name' => 'required|string|max:100',
            'warehouse_type' => 'required|string|max:20',
            'address' => 'nullable|string',
            'incharge_user_id' => 'nullable|integer|exists:tenant.users,id',
            'auto_generate_code' => 'sometimes|boolean',
            'manual_prefix' => 'nullable|string|max:10',
            'manual_number' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            Log::error('Warehouse validation failed:', $validator->errors()->toArray());
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
            $warehouse = Warehouse::create([
                'warehouse_code' => $request->input('warehouse_code'),
                'warehouse_name' => $request->input('warehouse_name'),
                'warehouse_type' => $request->input('warehouse_type'),
                'address' => $request->input('address'),
                'incharge_user_id' => $request->input('incharge_user_id'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'warehouse_code' => 'sometimes|string|max:20|unique:tenant.warehouse_master,warehouse_code,' . $id . ',id',
            'warehouse_name' => 'sometimes|string|max:100',
            'warehouse_type' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'incharge_user_id' => 'nullable|integer|exists:tenant.users,id',
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
            $warehouse = Warehouse::findOrFail($id);

            if ($request->has('warehouse_code')) {
                $warehouse->warehouse_code = $request->input('warehouse_code');
            }
            if ($request->has('warehouse_name')) {
                $warehouse->warehouse_name = $request->input('warehouse_name');
            }
            if ($request->has('warehouse_type')) {
                $warehouse->warehouse_type = $request->input('warehouse_type');
            }
            if ($request->has('address')) {
                $warehouse->address = $request->input('address');
            }
            if ($request->has('incharge_user_id')) {
                $warehouse->incharge_user_id = $request->input('incharge_user_id');
            }
            if ($request->has('is_active')) {
                $warehouse->is_active = $request->input('is_active');
            }

            $warehouse->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse
                ],
                'message' => 'Warehouse updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $warehouse = Warehouse::findOrFail($id);
            $warehouse->delete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Warehouse deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WAREHOUSE_DELETE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to delete warehouse: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    private function generateWarehouseCode(string $warehouseType): string
    {
        $prefix = match($warehouseType) {
            'RM' => 'RM',
            'FG' => 'FG',
            'PKG' => 'PKG',
            'REJECTION' => 'REJ',
            'WIP' => 'WIP',
            default => 'WH'
        };

        Log::info('Generating warehouse code for type: ' . $warehouseType . ' with prefix: ' . $prefix);

        // Get the last warehouse code for this type
        $lastCode = Warehouse::where('warehouse_code', 'like', $prefix . '-%')
            ->orderBy('warehouse_code', 'desc')
            ->value('warehouse_code');

        Log::info('Last warehouse code found: ' . ($lastCode ?? 'none'));

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int)$parts[1] + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        Log::info('Final generated warehouse code: ' . $generatedCode);

        return $generatedCode;
    }
}
