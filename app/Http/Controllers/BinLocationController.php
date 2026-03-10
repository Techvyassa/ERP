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
