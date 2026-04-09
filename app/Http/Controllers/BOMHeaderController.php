<?php

namespace App\Http\Controllers;

use App\Models\Tenant\BOMDetail;
use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\Material;
use App\Models\Tenant\Product;
use App\Models\Tenant\UOM;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BOMHeaderController extends Controller
{
    /**
     * Get next available BOM code
     * GET /api/v1/bom-headers/next-code
     */
    public function getNextCode(Request $request): JsonResponse
    {
        $prefix = $request->get('prefix', 'BOM');
        $requestId = Str::uuid()->toString();

        try {
            $latest = BOMHeader::where('bom_code', 'like', $prefix . '-%')
                ->orderBy('bom_code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($latest) {
                $parts = explode('-', $latest->bom_code);
                $lastPart = end($parts);
                if (is_numeric($lastPart)) {
                    $nextNumber = (int) $lastPart + 1;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'prefix' => $prefix,
                    'next_number' => str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
                    'full_code' => $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
                ],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate next code: ' . $e->getMessage(),
                'request_id' => $requestId,
            ], 500);
        }
    }

    /**
     * Get all BOM headers
     * GET /api/v1/bom-headers
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = BOMHeader::with(['product', 'outputUom', 'bomDetails.material', 'bomDetails.uom', 'bomDetails.substituteMaterial']);

            if ($request->has('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }
            if ($request->has('bom_status')) {
                $query->where('bom_status', $request->input('bom_status'));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query = $query->where(function ($q) use ($search) {
                    $q->where('bom_code', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            $boms = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $boms->toArray(),
                'message' => 'BOM headers retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve BOM headers: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get specific BOM header
     * GET /api/v1/bom-headers/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = BOMHeader::with(['product', 'outputUom', 'creator', 'approver', 'bomDetails.material', 'bomDetails.uom', 'bomDetails.substituteMaterial'])->find($id);

            if (!$bom) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => ['id' => $id]],
                    'message' => 'BOM header not found with id: ' . $id,
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $bom->toArray(),
                'message' => 'BOM header retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('BOM Show Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'ERROR', 'details' => ['exception' => $e->getMessage()]],
                'message' => 'Failed to retrieve BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Create new BOM header
     * POST /api/v1/bom-headers
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = $this->createBomRecord($request->all(), $request->input('auth_user_id'));

            return response()->json([
                'success' => true,
                'data' => ['bom' => $bom],
                'message' => 'BOM header created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors(),
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $status = in_array($code, [404, 409, 422], true) ? $code : 422;
            $errorCode = match ($status) {
                404 => 'NOT_FOUND',
                409 => 'DUPLICATE_RECORD',
                default => 'VALIDATION_ERROR',
            };

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'details' => [],
                ],
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], $status);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATE_FAILED',
                    'details' => [],
                ],
                'message' => 'Failed to create BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Create multiple BOM headers
     * POST /api/v1/bom-headers/bulk
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'boms' => 'required|array|min:1|max:50',
            'boms.*.bom_code' => 'required|string|max:30',
            'boms.*.product_id' => 'required|integer',
            'boms.*.version' => 'required|integer|min:1',
            'boms.*.effective_from' => 'required|date',
            'boms.*.effective_to' => 'nullable|date',
            'boms.*.bom_status' => 'required|in:DRAFT,ACTIVE,OBSOLETE',
            'boms.*.batch_size' => 'required|numeric|min:0.001',
            'boms.*.output_uom_id' => 'required|integer',
            'boms.*.items' => 'required|array|min:1',
            'boms.*.items.*.material_id' => 'required|integer',
            'boms.*.items.*.qty_required' => 'required|numeric|min:0.0001',
            'boms.*.items.*.uom_id' => 'required|integer',
            'boms.*.items.*.scrap_percent' => 'nullable|numeric|min:0|max:100',
            'boms.*.items.*.substitute_material_id' => 'nullable|integer',
            'boms.*.items.*.is_critical' => 'nullable|boolean',
            'boms.*.items.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $created = [];
            $errors = [];

            foreach ($request->input('boms', []) as $index => $payload) {
                try {
                    $created[] = $this->createBomRecord($payload, $request->input('auth_user_id'));
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'bom_code' => $payload['bom_code'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($created)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'BULK_CREATE_FAILED', 'details' => $errors],
                    'message' => 'All BOMs failed to create',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'created_count' => count($created),
                    'errors' => $errors,
                    'error_count' => count($errors),
                ],
                'message' => count($created) . ' BOM(s) created successfully' . (count($errors) ? ', ' . count($errors) . ' failed' : ''),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BULK_CREATE_FAILED', 'details' => []],
                'message' => 'Failed to bulk create BOM headers: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Download BOM CSV template
     * GET /api/v1/bom-headers/import/template
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $headers = [
            'bom_code',
            'product_code',
            'version',
            'effective_from',
            'effective_to',
            'bom_status',
            'batch_size',
            'output_uom_code',
            'remarks',
            'material_code',
            'qty_required',
            'uom_code',
            'scrap_percent',
            'substitute_material_code',
            'is_critical',
            'item_remarks',
        ];

        $sampleRows = [
            [
                'BOM-FG001-0001',
                'FG-0001',
                '1',
                now()->format('Y-m-d'),
                '',
                'DRAFT',
                '100',
                'NOS',
                'Starter BOM import',
                'RM-0001',
                '80',
                'KG',
                '2.5',
                '',
                'true',
                'Main raw material',
            ],
            [
                'BOM-FG001-0001',
                'FG-0001',
                '1',
                now()->format('Y-m-d'),
                '',
                'DRAFT',
                '100',
                'NOS',
                'Starter BOM import',
                'PKG-0001',
                '20',
                'NOS',
                '0',
                '',
                'false',
                'Primary packing material',
            ],
        ];

        $lines = [implode(',', $headers)];
        foreach ($sampleRows as $row) {
            $lines[] = implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row));
        }

        $csv = implode("\n", $lines);

        $fileName = 'bom_import_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'bom_template');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import BOMs from CSV
     * POST /api/v1/bom-headers/import
     */
    public function importCSV(Request $request): JsonResponse
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
                    'details' => $validator->errors(),
                ],
                'message' => 'Invalid file upload',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'EMPTY_FILE', 'details' => []],
                    'message' => 'CSV file is empty',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            $headers = array_map([$this, 'normalizeCsvHeader'], array_shift($csvData));
            $requiredHeaders = ['bom_code', 'version', 'effective_from', 'bom_status', 'batch_size', 'remarks', 'qty_required', 'scrap_percent', 'is_critical', 'item_remarks'];
            foreach ($requiredHeaders as $header) {
                if (!in_array($header, $headers, true)) {
                    return response()->json([
                        'success' => false,
                        'error' => ['code' => 'INVALID_TEMPLATE', 'details' => ['missing_header' => $header]],
                        'message' => 'CSV template is missing required column: ' . $header,
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }
            }

            if (!$this->hasAnyHeader($headers, ['product_id', 'product_code'])) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_TEMPLATE', 'details' => ['missing_header' => 'product_id/product_code']],
                    'message' => 'CSV must include either product_id or product_code',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if (!$this->hasAnyHeader($headers, ['output_uom_id', 'output_uom_code'])) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_TEMPLATE', 'details' => ['missing_header' => 'output_uom_id/output_uom_code']],
                    'message' => 'CSV must include either output_uom_id or output_uom_code',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if (!$this->hasAnyHeader($headers, ['material_id', 'material_code'])) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_TEMPLATE', 'details' => ['missing_header' => 'material_id/material_code']],
                    'message' => 'CSV must include either material_id or material_code',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if (!$this->hasAnyHeader($headers, ['uom_id', 'uom_code'])) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_TEMPLATE', 'details' => ['missing_header' => 'uom_id/uom_code']],
                    'message' => 'CSV must include either uom_id or uom_code',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $groupedBoms = [];
            $rowErrors = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                $mapped = [];
                foreach ($headers as $colIndex => $header) {
                    $val = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
                    $mapped[$header] = $val === '' ? null : $val;
                }

                if (empty($mapped['bom_code'])) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'bom_code is required',
                    ];
                    continue;
                }

                $groupKey = $mapped['bom_code'] . '::' . ($mapped['version'] ?? '1');

                $headerData = [
                    'bom_code' => $mapped['bom_code'] ?? null,
                    'product_id' => $mapped['product_id'] ?? null,
                    'product_code' => $mapped['product_code'] ?? null,
                    'version' => $mapped['version'] ?? null,
                    'effective_from' => $mapped['effective_from'] ?? null,
                    'effective_to' => $mapped['effective_to'] ?? null,
                    'bom_status' => $mapped['bom_status'] ?? null,
                    'batch_size' => $mapped['batch_size'] ?? null,
                    'output_uom_id' => $mapped['output_uom_id'] ?? null,
                    'output_uom_code' => $mapped['output_uom_code'] ?? null,
                    'remarks' => $mapped['remarks'] ?? null,
                ];

                $itemData = [
                    'material_id' => $mapped['material_id'] ?? null,
                    'material_code' => $mapped['material_code'] ?? null,
                    'qty_required' => $mapped['qty_required'] ?? null,
                    'uom_id' => $mapped['uom_id'] ?? null,
                    'uom_code' => $mapped['uom_code'] ?? null,
                    'scrap_percent' => $mapped['scrap_percent'] ?? null,
                    'substitute_material_id' => $mapped['substitute_material_id'] ?? null,
                    'substitute_material_code' => $mapped['substitute_material_code'] ?? null,
                    'is_critical' => $mapped['is_critical'] ?? null,
                    'remarks' => $mapped['item_remarks'] ?? null,
                ];

                if (!isset($groupedBoms[$groupKey])) {
                    $groupedBoms[$groupKey] = [
                        'header' => $headerData,
                        'items' => [],
                        'rows' => [],
                    ];
                } elseif (!$this->headersMatch($groupedBoms[$groupKey]['header'], $headerData)) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Header values do not match previous rows for BOM ' . $mapped['bom_code'],
                    ];
                    continue;
                }

                $groupedBoms[$groupKey]['items'][] = $itemData;
                $groupedBoms[$groupKey]['rows'][] = $rowNumber;
            }

            $created = [];
            $errors = $rowErrors;

            foreach ($groupedBoms as $group) {
                try {
                    $payload = $group['header'];
                    $payload['items'] = $group['items'];
                    $created[] = $this->createBomRecord($payload, $request->input('auth_user_id'));
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => implode(', ', $group['rows']),
                        'bom_code' => $group['header']['bom_code'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($created)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'IMPORT_FAILED', 'details' => $errors],
                    'message' => 'No BOMs were imported',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'created_count' => count($created),
                    'errors' => $errors,
                    'error_count' => count($errors),
                ],
                'message' => count($created) . ' BOM(s) imported successfully' . (count($errors) ? ', ' . count($errors) . ' failed' : ''),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'IMPORT_FAILED', 'details' => []],
                'message' => 'Failed to import CSV: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Update BOM header
     * PUT /api/v1/bom-headers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'bom_status' => 'nullable|in:DRAFT,ACTIVE,OBSOLETE',
            'batch_size' => 'nullable|numeric|min:0.001',
            'output_uom_id' => 'nullable|integer',
            'remarks' => 'nullable|string|max:1000',
            'items' => 'nullable|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.material_id' => 'required|integer',
            'items.*.qty_required' => 'required|numeric|min:0.0001',
            'items.*.uom_id' => 'required|integer',
            'items.*.scrap_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.substitute_material_id' => 'nullable|integer',
            'items.*.is_critical' => 'nullable|boolean',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $validator->errors(),
                ],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $bom = BOMHeader::findOrFail($id);

            if ($request->has('output_uom_id') && $request->input('output_uom_id')) {
                $uom = UOM::find($request->input('output_uom_id'));
                if (!$uom) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'details' => ['output_uom_id' => ['The selected UOM does not exist.']],
                        ],
                        'message' => 'Validation failed',
                        'request_id' => $requestId,
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'effective_from',
                'effective_to',
                'bom_status',
                'batch_size',
                'output_uom_id',
                'remarks',
            ]);

            $bom->update($updateData);

            if ($request->has('items')) {
                $existingDetailIds = $bom->bomDetails()->pluck('id')->toArray();
                $updatedDetailIds = [];
                $lineNo = 1;

                foreach ($request->input('items') as $item) {
                    $detailData = [
                        'bom_id' => $bom->id,
                        'material_id' => $item['material_id'],
                        'qty_required' => $item['qty_required'],
                        'uom_id' => $item['uom_id'],
                        'scrap_percent' => $item['scrap_percent'] ?? 0,
                        'substitute_material_id' => $item['substitute_material_id'] ?? null,
                        'is_critical' => $item['is_critical'] ?? false,
                        'line_no' => $lineNo++,
                        'remarks' => $item['remarks'] ?? null,
                    ];

                    if (isset($item['id']) && in_array($item['id'], $existingDetailIds, true)) {
                        BOMDetail::where('id', $item['id'])->update($detailData);
                        $updatedDetailIds[] = $item['id'];
                    } else {
                        $newDetail = BOMDetail::create($detailData);
                        $updatedDetailIds[] = $newDetail->id;
                    }
                }

                $detailsToDelete = array_diff($existingDetailIds, $updatedDetailIds);
                if (!empty($detailsToDelete)) {
                    BOMDetail::whereIn('id', $detailsToDelete)->delete();
                }
            }

            DB::commit();

            $bom->load(['product', 'outputUom', 'creator', 'approver', 'bomDetails.material', 'bomDetails.uom', 'bomDetails.substituteMaterial']);

            return response()->json([
                'success' => true,
                'data' => $bom->toArray(),
                'message' => 'BOM header updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BOM Update Error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'details' => [],
                ],
                'message' => 'Failed to update BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Delete/Deactivate BOM header
     * DELETE /api/v1/bom-headers/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $bom = BOMHeader::findOrFail($id);
            $bom->forceDelete();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'BOM header deleted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'details' => [],
                ],
                'message' => 'Failed to delete BOM header: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    private function createBomRecord(array $payload, $authUserId = null): BOMHeader
    {
        $validator = Validator::make($payload, [
            'bom_code' => 'required|string|max:30',
            'version' => 'required|integer|min:1',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'bom_status' => 'required|in:DRAFT,ACTIVE,OBSOLETE',
            'batch_size' => 'required|numeric|min:0.001',
            'remarks' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.qty_required' => 'required|numeric|min:0.0001',
            'items.*.scrap_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.is_critical' => 'nullable',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $product = $this->resolveProduct($payload['product_id'] ?? null, $payload['product_code'] ?? null);
        $outputUom = $this->resolveUom($payload['output_uom_id'] ?? null, $payload['output_uom_code'] ?? null, 'output UOM');

        if (BOMHeader::where('bom_code', $payload['bom_code'])->exists()) {
            throw new \RuntimeException('BOM code already exists', 409);
        }

        if (BOMHeader::where('product_id', $product->id)->where('version', (int) $payload['version'])->exists()) {
            throw new \RuntimeException('BOM version already exists for this product', 409);
        }

        return DB::connection('tenant')->transaction(function () use ($payload, $product, $outputUom, $authUserId) {
            $bom = BOMHeader::create([
                'bom_code' => $payload['bom_code'],
                'product_id' => $product->id,
                'version' => (int) $payload['version'],
                'effective_from' => $payload['effective_from'],
                'effective_to' => !empty($payload['effective_to']) ? $payload['effective_to'] : null,
                'bom_status' => $payload['bom_status'],
                'batch_size' => (float) $payload['batch_size'],
                'output_uom_id' => $outputUom->id,
                'remarks' => !empty($payload['remarks']) ? $payload['remarks'] : null,
                'created_by' => $authUserId,
            ]);

            $lineNo = 1;
            foreach ($payload['items'] as $item) {
                $material = $this->resolveMaterial($item['material_id'] ?? null, $item['material_code'] ?? null, 'material');
                $itemUom = $this->resolveUom($item['uom_id'] ?? null, $item['uom_code'] ?? null, 'item UOM');

                $substituteMaterial = null;
                if (!empty($item['substitute_material_id']) || !empty($item['substitute_material_code'])) {
                    $substituteMaterial = $this->resolveMaterial(
                        $item['substitute_material_id'] ?? null,
                        $item['substitute_material_code'] ?? null,
                        'substitute material'
                    );
                }

                $scrapPercent = isset($item['scrap_percent']) && $item['scrap_percent'] !== '' ? (float) $item['scrap_percent'] : 0;
                $qtyRequired = (float) $item['qty_required'];

                BOMDetail::create([
                    'bom_id' => $bom->id,
                    'material_id' => $material->id,
                    'qty_required' => $qtyRequired,
                    'uom_id' => $itemUom->id,
                    'scrap_percent' => $scrapPercent,
                    'substitute_material_id' => $substituteMaterial?->id,
                    'is_critical' => $this->normalizeBoolean($item['is_critical'] ?? false),
                    'line_no' => $lineNo++,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $bom->load(['product', 'outputUom', 'creator', 'approver', 'bomDetails.material', 'bomDetails.uom', 'bomDetails.substituteMaterial']);
        });
    }

    private function resolveProduct($productId = null, $productCode = null): Product
    {
        if (!empty($productId)) {
            $product = Product::find((int) $productId);
            if ($product) {
                return $product;
            }
        }

        if (!empty($productCode)) {
            $product = Product::where('product_code', trim((string) $productCode))->first();
            if ($product) {
                return $product;
            }
        }

        throw new \RuntimeException('Product not found', 404);
    }

    private function resolveMaterial($materialId = null, $materialCode = null, string $label = 'material'): Material
    {
        if (!empty($materialId)) {
            $material = Material::find((int) $materialId);
            if ($material) {
                return $material;
            }
        }

        if (!empty($materialCode)) {
            $material = Material::where('material_code', trim((string) $materialCode))->first();
            if ($material) {
                return $material;
            }
        }

        throw new \RuntimeException(ucfirst($label) . ' not found', 404);
    }

    private function resolveUom($uomId = null, $uomCode = null, string $label = 'UOM'): UOM
    {
        if (!empty($uomId)) {
            $uom = UOM::find((int) $uomId);
            if ($uom) {
                return $uom;
            }
        }

        if (!empty($uomCode)) {
            $normalizedCode = trim((string) $uomCode);
            $uom = UOM::where('uom_code', $normalizedCode)
                ->orWhere('uom_name', $normalizedCode)
                ->first();
            if ($uom) {
                return $uom;
            }
        }

        throw new \RuntimeException(ucfirst($label) . ' not found', 404);
    }


    private function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeCsvHeader(string $header): string
    {
        return Str::of($header)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->replace('__', '_')
            ->value();
    }

    private function hasAnyHeader(array $headers, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $headers, true)) {
                return true;
            }
        }

        return false;
    }

    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function headersMatch(array $first, array $current): bool
    {
        foreach ($first as $key => $value) {
            if (($value ?? '') !== ($current[$key] ?? '')) {
                return false;
            }
        }

        return true;
    }
}
