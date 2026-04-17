<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreQCParameterRequest;
use App\Models\Tenant\QCParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QCParameterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QCParameter::with(['material', 'product', 'testType']);

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->integer('material_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('test_type_id')) {
            $query->where('test_type_id', $request->integer('test_type_id'));
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('parameter_code', 'like', "%{$search}%")
                    ->orWhere('parameter_name', 'like', "%{$search}%")
                    ->orWhere('test_method', 'like', "%{$search}%");
            });
        }

        $parameters = $query
            ->orderBy('display_order')
            ->orderBy('parameter_code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parameters,
            'message' => 'QC parameters retrieved successfully',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $parameter = QCParameter::with(['material', 'testType'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $parameter,
            'message' => 'QC parameter retrieved successfully',
        ]);
    }

    public function store(StoreQCParameterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $parameter = QCParameter::create([
                ...$validated,
                'parameter_code' => strtoupper($validated['parameter_code']),
                'is_critical' => $validated['is_critical'] ?? false,
                'display_order' => $validated['display_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => $request->input('auth_user_id'),
            ]);

            return response()->json([
                'success' => true,
                'data' => $parameter->load(['material', 'testType']),
                'message' => 'QC parameter created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_CREATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    public function update(int $id, StoreQCParameterRequest $request): JsonResponse
    {
        try {
            $parameter = QCParameter::findOrFail($id);
            $validated = $request->validated();

            $parameter->update([
                ...$validated,
                'parameter_code' => strtoupper($validated['parameter_code']),
                'is_critical' => $validated['is_critical'] ?? false,
                'display_order' => $validated['display_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? $parameter->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data' => $parameter->fresh()->load(['material', 'testType']),
                'message' => 'QC parameter updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_UPDATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $parameter = QCParameter::findOrFail($id);
            $parameterName = $parameter->parameter_name;
            $parameter->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'QC parameter deactivated successfully',
                'data' => [
                    'id' => $id,
                    'parameter_name' => $parameterName,
                    'is_active' => false,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate QC parameter: ' . $e->getMessage(),
                'error' => ['code' => 'QC_PARAMETER_DELETE_FAILED', 'details' => []],
            ], 500);
        }
    }

    /**
     * Download CSV template for import
     */
    public function downloadTemplate()
    {
        $headers = [
            'parameter_code',
            'parameter_name',
            'material_code',
            'test_type_code',
            'parameter_category',
            'test_method',
            'data_type',
            'tolerance_type',
            'standard_min',
            'standard_max',
            'standard_value',
            'unit_of_measurement',
            'is_critical',
            'is_active'
        ];
        
        $sampleRows = [
            ['', 'Moisture Content', 'RM-0001', 'PHYSICAL', 'PHYSICAL', 'ASTM D2216', 'NUMERIC', 'MAX_ONLY', '', '12', '', '%', 'false', 'true'],
            ['', 'Purity', 'RM-0002', 'CHEMICAL', 'CHEMICAL', 'IS 548', 'NUMERIC', 'MIN_ONLY', '98', '', '', '%', 'true', 'true'],
            ['', 'Color', 'RM-0003', 'VISUAL', 'VISUAL', 'Visual inspection', 'TEXT', 'EXACT', '', '', 'Brown', '', 'false', 'true'],
        ];

        $lines = [implode(',', $headers)];
        foreach ($sampleRows as $row) {
            $lines[] = implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row));
        }

        $csv = implode("\n", $lines);
        $fileName = 'qc_parameters_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'qc_parameters');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import QC Parameters from CSV
     */
    public function importCSV(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty',
                ], 400);
            }

            $headers = array_shift($csvData);
            $imported = 0;
            $errors = [];
            $userId = $request->input('auth_user_id');

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                $data = array_combine($headers, $row);

                try {
                    $parameterName = trim($data['parameter_name'] ?? '');
                    $materialCode = trim($data['material_code'] ?? '');

                    if (empty($parameterName)) {
                        $errors[] = "Row {$rowNumber}: parameter_name is required";
                        continue;
                    }

                    if (empty($materialCode)) {
                        $errors[] = "Row {$rowNumber}: material_code is required";
                        continue;
                    }

                    // Find material by code
                    $material = \App\Models\Tenant\Material::where('material_code', $materialCode)->first();
                    if (!$material) {
                        $errors[] = "Row {$rowNumber}: Material '{$materialCode}' not found";
                        continue;
                    }

                    // Find test type by code (optional)
                    $testTypeId = null;
                    if (!empty($data['test_type_code'])) {
                        $testType = \App\Models\Tenant\QCTestType::where('type_code', strtoupper(trim($data['test_type_code'])))->first();
                        if ($testType) {
                            $testTypeId = $testType->id;
                        }
                    }

                    // Auto-generate parameter_code if blank
                    $parameterCode = trim($data['parameter_code'] ?? '');
                    if (empty($parameterCode)) {
                        $parameterCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '', strtoupper(substr($parameterName, 0, 20))));
                        
                        // Ensure uniqueness
                        $counter = 1;
                        $originalCode = $parameterCode;
                        while (QCParameter::where('parameter_code', $parameterCode)->where('material_id', $material->id)->exists()) {
                            $parameterCode = $originalCode . $counter;
                            $counter++;
                        }
                    } else {
                        $parameterCode = strtoupper($parameterCode);
                        
                        // Check for duplicates
                        if (QCParameter::where('parameter_code', $parameterCode)->where('material_id', $material->id)->exists()) {
                            $errors[] = "Row {$rowNumber}: Parameter '{$parameterCode}' already exists for this material";
                            continue;
                        }
                    }

                    $dataType = strtoupper(trim($data['data_type'] ?? 'NUMERIC'));
                    $toleranceType = strtoupper(trim($data['tolerance_type'] ?? 'RANGE'));

                    QCParameter::create([
                        'parameter_code' => $parameterCode,
                        'parameter_name' => $parameterName,
                        'material_id' => $material->id,
                        'test_type_id' => $testTypeId,
                        'parameter_category' => $data['parameter_category'] ?? null,
                        'test_method' => $data['test_method'] ?? null,
                        'data_type' => $dataType,
                        'tolerance_type' => $toleranceType,
                        'standard_min' => $data['standard_min'] ?? null,
                        'standard_max' => $data['standard_max'] ?? null,
                        'standard_value' => $data['standard_value'] ?? null,
                        'unit_of_measurement' => $data['unit_of_measurement'] ?? null,
                        'is_critical' => filter_var($data['is_critical'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'display_order' => 0,
                        'created_by' => $userId,
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
                ],
                'message' => "{$imported} QC parameter(s) imported successfully" . (count($errors) ? ", " . count($errors) . " failed" : ""),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import CSV: ' . $e->getMessage(),
            ], 500);
        }
    }
}
