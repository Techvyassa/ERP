<?php

namespace App\Http\Controllers;

use App\Models\Tenant\HSNCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HSNCodeController extends Controller
{
    /**
     * List HSN codes
     * GET /api/v1/hsn-codes
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = HSNCode::with('defaultGst');

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('hsn_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $hsnCodes = $query->orderBy('hsn_code')->get();

            return response()->json([
                'success' => true,
                'data' => ['hsn_codes' => $hsnCodes],
                'message' => 'HSN codes retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve HSN codes: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get single HSN code
     * GET /api/v1/hsn-codes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $hsnCode = HSNCode::with('defaultGst')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_NOT_FOUND', 'details' => []],
                'message' => 'HSN code not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Create HSN code
     * POST /api/v1/hsn-codes
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'hsn_code' => 'required|string|max:10|unique:tenant.hsn_codes,hsn_code',
            'description' => 'required|string|max:300',
            'default_gst_id' => 'required|integer|exists:tenant.gst_taxes,id',
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
            $hsnCode = HSNCode::create([
                'hsn_code' => $request->input('hsn_code'),
                'description' => $request->input('description'),
                'default_gst_id' => $request->input('default_gst_id'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Update HSN code
     * PUT /api/v1/hsn-codes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'hsn_code' => "sometimes|string|max:10|unique:tenant.hsn_codes,hsn_code,{$id}",
            'description' => 'sometimes|string|max:300',
            'default_gst_id' => 'sometimes|integer|exists:tenant.gst_taxes,id',
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
            $hsnCode = HSNCode::findOrFail($id);
            $hsnCode->update($request->only(['hsn_code', 'description', 'default_gst_id', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['hsn_code' => $hsnCode],
                'message' => 'HSN code updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Delete HSN code
     * DELETE /api/v1/hsn-codes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $hsnCode = HSNCode::findOrFail($id);
            $hsnCode->is_active = false;
            $hsnCode->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'HSN code deactivated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HSN_CODE_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to deactivate HSN code: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Import HSN codes from CSV
     * POST /api/v1/hsn-codes/import
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

            // Read file with proper UTF-8 encoding handling
            $content = file_get_contents($file->getRealPath());

            // Convert to UTF-8 if needed
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1, Windows-1252', true));
            }

            // Remove BOM if present
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Parse CSV
            $lines = explode("\n", $content);
            $csvData = array_map('str_getcsv', $lines);

            $headers = array_map('trim', $csvData[0]);
            $rows = array_slice($csvData, 1);

            $imported = 0;
            $errors = [];

            // Fetch GST tax mappings
            $gstTaxMap = \App\Models\Tenant\GSTTax::pluck('id', 'tax_code')->toArray();

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row)))
                    continue;

                $rowNumber = $index + 2;

                // Ensure row has same number of columns as headers
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }

                $data = array_combine($headers, $row);

                try {
                    // Validate required fields
                    if (empty($data['hsn_code'])) {
                        $errors[] = "Row {$rowNumber}: HSN code is required";
                        continue;
                    }

                    if (empty($data['description'])) {
                        $errors[] = "Row {$rowNumber}: Description is required";
                        continue;
                    }

                    if (empty($data['gst_tax_code'])) {
                        $errors[] = "Row {$rowNumber}: GST tax code is required";
                        continue;
                    }

                    // Check for duplicate HSN code
                    $hsnCode = trim($data['hsn_code']);
                    $existingHsn = HSNCode::where('hsn_code', $hsnCode)->first();
                    if ($existingHsn) {
                        $errors[] = "Row {$rowNumber}: HSN code '{$hsnCode}' already exists";
                        continue;
                    }

                    // Resolve GST Tax
                    $gstTaxCode = strtoupper(trim($data['gst_tax_code']));
                    $gstTaxId = $gstTaxMap[$gstTaxCode] ?? null;

                    log::debug("Row {$rowNumber}: Raw GST tax code = '{$data['gst_tax_code']}', Normalized = '{$gstTaxCode}'");

                    Log::debug("GST Tax Map Keys: ", array_keys($gstTaxMap));
                    if (!$gstTaxId) {
                        $errors[] = "Row {$rowNumber}: GST tax code '{$data['gst_tax_code']}' not found";
                        continue;
                    }

                    // Create HSN code
                    HSNCode::create([
                        'hsn_code' => $hsnCode,
                        'description' => trim($data['description']),
                        'default_gst_id' => $gstTaxId,
                        'is_active' => true,
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
                'message' => "{$imported} HSN code(s) imported successfully" . (count($errors) > 0 ? ", " . count($errors) . " failed" : ""),
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
                'message' => 'Failed to import HSN codes: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

}
