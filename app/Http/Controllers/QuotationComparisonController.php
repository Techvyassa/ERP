<?php

namespace App\Http\Controllers;

use App\Models\Tenant\VendorQuotation;
use App\Models\Tenant\QuotationSelection;
use App\Models\Tenant\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuotationComparisonController extends Controller
{
    /**
     * Get all quotations grouped by PR number
     * GET /api/v1/quotation-comparison
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $query = VendorQuotation::with('vendor')
                ->select('pr_number', DB::raw('MAX(created_at) as latest_created_at'))
                ->groupBy('pr_number')
                ->orderByDesc('latest_created_at');

            if ($request->filled('pr_number')) {
                $query->where('pr_number', 'like', '%' . $request->input('pr_number') . '%');
            }

            $prNumbers = $query->get();

            // Get quotation counts per PR
            $data = $prNumbers->map(function ($item) {
                $count = VendorQuotation::where('pr_number', $item->pr_number)->count();
                $vendors = VendorQuotation::where('pr_number', $item->pr_number)
                    ->with('vendor:id,vendor_name')
                    ->get()
                    ->pluck('vendor.vendor_name')
                    ->unique()
                    ->values();
                
                return [
                    'pr_number' => $item->pr_number,
                    'quotation_count' => $count,
                    'vendors' => $vendors,
                    'created_at' => $item->latest_created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Quotation comparisons retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve quotations: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get quotations for a specific PR number
     * GET /api/v1/quotation-comparison/{prNumber}
     */
    public function show(Request $request, string $prNumber): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $quotations = VendorQuotation::where('pr_number', $prNumber)
                ->with('vendor')
                ->orderBy('vendor_id')
                ->orderBy('item_name')
                ->get();

            if ($quotations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NOT_FOUND', 'details' => []],
                    'message' => 'No quotations found for this PR number',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Group by item for comparison
            $comparison = $quotations->groupBy('item_name')->map(function ($items, $itemName) {
                return [
                    'item_name' => $itemName,
                    'quotations' => $items->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'vendor_id' => $q->vendor_id,
                            'vendor_name' => $q->vendor->vendor_name,
                            'quantity' => $q->quantity,
                            'unit_price' => $q->unit_price,
                            'total_price' => $q->total_price,
                            'delivery_date' => $q->delivery_date?->format('Y-m-d'),
                            'remarks' => $q->remarks,
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'pr_number' => $prNumber,
                    'comparison' => $comparison,
                ],
                'message' => 'Quotation comparison retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve comparison: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Upload vendor quotation (form or CSV)
     * POST /api/v1/quotation-comparison/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'pr_number' => 'required|string|max:50',
            'vendor_id' => 'required|integer|exists:tenant.vendor_master,id',
            'upload_type' => 'required|in:form,csv',
            // For form upload
            'quotations' => 'required_if:upload_type,form|array|min:1',
            'quotations.*.item_name' => 'required_if:upload_type,form|string|max:200',
            'quotations.*.quantity' => 'required_if:upload_type,form|numeric|min:0.001',
            'quotations.*.unit_price' => 'required_if:upload_type,form|numeric|min:0',
            'quotations.*.delivery_date' => 'nullable|date',
            'quotations.*.remarks' => 'nullable|string',
            // For CSV upload
            'csv_file' => 'required_if:upload_type,csv|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $quotationsData = [];

            if ($request->input('upload_type') === 'form') {
                $quotationsData = $request->input('quotations');
            } else {
                // Parse CSV
                $file = $request->file('csv_file');
                $csvData = array_map('str_getcsv', file($file->getRealPath()));
                $header = array_shift($csvData); // Remove header row

                foreach ($csvData as $row) {
                    if (count($row) < 3) continue; // Skip invalid rows
                    
                    $quotationsData[] = [
                        'item_name' => $row[0] ?? '',
                        'quantity' => $row[1] ?? 0,
                        'unit_price' => $row[2] ?? 0,
                        'delivery_date' => $row[3] ?? null,
                        'remarks' => $row[4] ?? null,
                    ];
                }
            }

            $created = [];
            foreach ($quotationsData as $item) {
                $totalPrice = floatval($item['quantity']) * floatval($item['unit_price']);
                
                $quotation = VendorQuotation::create([
                    'pr_number' => $request->input('pr_number'),
                    'vendor_id' => $request->input('vendor_id'),
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'delivery_date' => $item['delivery_date'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
                
                $created[] = $quotation;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['quotations' => $created],
                'message' => count($created) . ' quotation(s) uploaded successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => ['code' => 'UPLOAD_FAILED', 'details' => []],
                'message' => 'Failed to upload quotations: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Select winning quotation
     * POST /api/v1/quotation-comparison/select
     */
    public function selectQuotation(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'pr_number' => 'required|string|max:50',
            'quotation_id' => 'required|integer|exists:tenant.vendor_quotations,id',
            'selection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            // Validate minimum 2 vendors
            $vendorCount = VendorQuotation::where('pr_number', $request->input('pr_number'))
                ->distinct('vendor_id')
                ->count('vendor_id');

            if ($vendorCount < 2) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INSUFFICIENT_QUOTATIONS', 'details' => []],
                    'message' => 'At least 2 vendor quotations are required for comparison',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $quotation = VendorQuotation::findOrFail($request->input('quotation_id'));

            $selection = QuotationSelection::create([
                'pr_number' => $request->input('pr_number'),
                'vendor_id' => $quotation->vendor_id,
                'quotation_id' => $quotation->id,
                'selected_price' => $quotation->total_price,
                'selected_delivery_date' => $quotation->delivery_date,
                'selection_reason' => $request->input('selection_reason'),
                'status' => 'PENDING',
                'selected_by' => $request->input('auth_user_id'),
                'selected_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => ['selection' => $selection],
                'message' => 'Quotation selected successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'SELECTION_FAILED', 'details' => []],
                'message' => 'Failed to select quotation: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get vendors list for dropdown
     * GET /api/v1/quotation-comparison/vendors
     */
    public function getVendors(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $vendors = Vendor::where('blacklisted', false)
                ->where('is_approved', true)
                ->orderBy('vendor_name')
                ->get(['id', 'vendor_code', 'vendor_name']);

            return response()->json([
                'success' => true,
                'data' => ['vendors' => $vendors],
                'message' => 'Vendors retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FETCH_FAILED', 'details' => []],
                'message' => 'Failed to retrieve vendors: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
