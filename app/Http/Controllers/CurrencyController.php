<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CurrencyController extends Controller
{
    /**
     * List currencies
     * GET /api/v1/currencies
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $query = Currency::query();

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('currency_code', 'like', "%{$search}%")
                      ->orWhere('currency_name', 'like', "%{$search}%");
                });
            }

            $currencies = $query->orderBy('currency_code')->get();

            return response()->json([
                'success' => true,
                'data' => ['currencies' => $currencies],
                'message' => 'Currencies retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve currencies: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single currency
     * GET /api/v1/currencies/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $currency = Currency::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['currency' => $currency],
                'message' => 'Currency retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CURRENCY_NOT_FOUND', 'details' => []],
                'message' => 'Currency not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create currency
     * POST /api/v1/currencies
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'currency_code' => 'required|string|max:3|unique:tenant.currency_master,currency_code',
            'currency_name' => 'required|string|max:60',
            'symbol' => 'required|string|max:5',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_base_currency' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $currency = Currency::create([
                'currency_code' => $request->input('currency_code'),
                'currency_name' => $request->input('currency_name'),
                'symbol' => $request->input('symbol'),
                'exchange_rate' => $request->input('exchange_rate', 1.0),
                'is_base_currency' => $request->input('is_base_currency', false),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['currency' => $currency],
                'message' => 'Currency created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CURRENCY_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create currency: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update currency
     * PUT /api/v1/currencies/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        $validator = Validator::make($request->all(), [
            'currency_code' => "sometimes|string|max:10|unique:tenant.currency_master,currency_code,{$id}",
            'currency_name' => 'sometimes|string|max:100',
            'currency_symbol' => 'sometimes|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_base_currency' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 422);
        }

        try {
            $currency = Currency::findOrFail($id);
            $currency->update($request->only(['currency_code', 'currency_name', 'currency_symbol', 'exchange_rate', 'is_base_currency', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['currency' => $currency],
                'message' => 'Currency updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CURRENCY_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update currency: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete currency
     * DELETE /api/v1/currencies/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        
        try {
            $currency = Currency::findOrFail($id);
            $currency->is_active = false;
            $currency->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Currency deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CURRENCY_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate currency: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
}
