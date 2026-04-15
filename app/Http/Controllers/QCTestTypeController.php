<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreQCTestTypeRequest;
use App\Models\Tenant\QCTestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QCTestTypeController extends Controller
{
    /**
     * List all QC Test Types
     */
    public function index(Request $request): JsonResponse
    {
        $query = QCTestType::with('creator');

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        $testTypes = $query->orderBy('type_code')->get();

        return response()->json([
            'success' => true,
            'data'    => $testTypes,
            'message' => 'QC test types retrieved successfully',
        ]);
    }

    /**
     * Get single QC Test Type
     */
    public function show(int $id): JsonResponse
    {
        $testType = QCTestType::with(['creator', 'qcParameters'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $testType,
            'message' => 'QC test type retrieved successfully',
        ]);
    }

    /**
     * Create QC Test Type
     */
    public function store(StoreQCTestTypeRequest $request): JsonResponse
    {
        try {
            $userId   = $request->input('auth_user_id');
            $validated = $request->validated();

            $testType = QCTestType::create([
                'type_code'   => strtoupper($validated['type_code']),
                'type_name'   => $validated['type_name'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? true,
                'created_by'  => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $testType->load('creator'),
                'message' => 'QC test type created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QC test type: ' . $e->getMessage(),
                'error'   => ['code' => 'QC_TEST_TYPE_CREATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    /**
     * Update QC Test Type
     */
    public function update(int $id, StoreQCTestTypeRequest $request): JsonResponse
    {
        try {
            $testType  = QCTestType::findOrFail($id);
            $validated = $request->validated();

            $testType->update([
                'type_code'   => strtoupper($validated['type_code']),
                'type_name'   => $validated['type_name'],
                'description' => $validated['description'] ?? $testType->description,
                'is_active'   => $validated['is_active'] ?? $testType->is_active,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $testType->fresh()->load('creator'),
                'message' => 'QC test type updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update QC test type: ' . $e->getMessage(),
                'error'   => ['code' => 'QC_TEST_TYPE_UPDATE_FAILED', 'details' => []],
            ], 500);
        }
    }

    /**
     * Deactivate QC Test Type
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $testType = QCTestType::findOrFail($id);
            $testType->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'QC test type deactivated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate QC test type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CSV template for import
     */
    public function downloadTemplate()
    {
        $headers = ['type_code', 'type_name', 'description', 'is_active'];
        
        $sampleRows = [
            ['', 'Visual Inspection', 'Appearance, color, packaging checks', 'true'],  // type_code BLANK
            ['', 'Physical Test', 'Dimensions, weight, density measurements', 'true'],  // type_code BLANK
            ['', 'Chemical Analysis', 'Purity, pH, moisture content', 'true'],  // type_code BLANK
        ];

        $lines = [implode(',', $headers)];
        foreach ($sampleRows as $row) {
            $lines[] = implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row));
        }

        $csv = implode("\n", $lines);
        $fileName = 'qc_test_types_template_' . date('Y-m-d') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'qc_test_types');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import QC Test Types from CSV
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
                    $typeName = trim($data['type_name'] ?? '');

                    if (empty($typeName)) {
                        $errors[] = "Row {$rowNumber}: type_name is required";
                        continue;
                    }

                    // Auto-generate type_code if blank
                    $typeCode = trim($data['type_code'] ?? '');
                    if (empty($typeCode)) {
                        // Generate code from type_name (e.g., "Visual Inspection" -> "VISUAL")
                        $typeCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '', strtoupper(substr($typeName, 0, 20))));
                        
                        // Ensure uniqueness
                        $counter = 1;
                        $originalCode = $typeCode;
                        while (QCTestType::where('type_code', $typeCode)->exists()) {
                            $typeCode = $originalCode . $counter;
                            $counter++;
                        }
                    } else {
                        $typeCode = strtoupper($typeCode);
                        
                        // Check for duplicates
                        if (QCTestType::where('type_code', $typeCode)->exists()) {
                            $errors[] = "Row {$rowNumber}: Test type '{$typeCode}' already exists";
                            continue;
                        }
                    }

                    QCTestType::create([
                        'type_code' => $typeCode,
                        'type_name' => $typeName,
                        'description' => $data['description'] ?? null,
                        'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
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
                'message' => "{$imported} QC test type(s) imported successfully" . (count($errors) ? ", " . count($errors) . " failed" : ""),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import CSV: ' . $e->getMessage(),
            ], 500);
        }
    }
}
