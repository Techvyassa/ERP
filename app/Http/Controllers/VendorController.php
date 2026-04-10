<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Vendor;
use App\Models\Tenant\VendorContact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VendorDirectMail;

class VendorController extends Controller
{
    /**
     * List vendors
     * GET /api/v1/vendors
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = Vendor::with(['currency', 'contacts']);

            if ($request->has('vendor_type')) {
                $query->where('vendor_type', $request->input('vendor_type'));
            }
            if ($request->has('is_approved')) {
                $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('blacklisted')) {
                $query->where('blacklisted', filter_var($request->input('blacklisted'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('vendor_code', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $vendors = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'vendors' => $vendors->items(),
                    'pagination' => [
                        'current_page' => $vendors->currentPage(),
                        'per_page' => $vendors->perPage(),
                        'total' => $vendors->total(),
                        'last_page' => $vendors->lastPage(),
                    ],
                ],
                'message' => 'Vendors retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
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

    /**
     * Get single vendor
     * GET /api/v1/vendors/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $vendor = Vendor::with(['currency', 'contacts', 'materialMaps.material'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_NOT_FOUND', 'details' => []],
                'message' => 'Vendor not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 404);
        }
    }

    /**
     * Create vendor
     * POST /api/v1/vendors
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_code'      => 'nullable|string|max:20|unique:tenant.vendor_master,vendor_code',
            'vendor_name'      => 'required|string|max:200',
            'vendor_type'      => 'nullable|string|max:20',
            'gstin'            => 'nullable|string|max:20|unique:tenant.vendor_master,gstin',
            'pan_number'       => 'nullable|string|max:10',
            'msme_category'    => 'nullable|string|max:10',
            'payment_terms'    => 'nullable|string|max:30',
            'credit_days'      => 'nullable|integer|min:0',
            'currency_id'      => 'required|integer|exists:tenant.currency_master,id',
            'delivery_terms'   => 'nullable|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'bank_account_no'  => 'nullable|string|max:30',
            'ifsc_code'        => 'nullable|string|max:11',
            'rating_score'     => 'nullable|numeric|min:0|max:100',
            'contact_name'     => 'nullable|string|max:100',
            'contact_type'     => 'nullable|string|max:50',
            'contact_phone'    => 'nullable|string|max:20',
            'contact_email'    => 'nullable|email|max:100',
            'contacts'               => 'nullable|array',
            'contacts.*.contact_name' => 'nullable|string|max:100',
            'contacts.*.contact_type' => 'nullable|string|max:50',
            'contacts.*.contact_phone' => 'nullable|string|max:20',
            'contacts.*.contact_email' => 'nullable|email|max:100',
            'contacts.*.is_primary'  => 'nullable|boolean',
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
            // Auto-generate vendor code if not provided
            $vendorCode = $request->input('vendor_code');
            if (empty($vendorCode)) {
                $vendorCode = $this->generateVendorCode(
                    $request->input('vendor_type', 'SUPPLIER'),
                    $request->input('vendor_name', '')
                );
            }

            $vendor = Vendor::create(array_merge(
                $request->only([
                    'vendor_name',
                    'vendor_type',
                    'gstin',
                    'pan_number',
                    'msme_category',
                    'payment_terms',
                    'credit_days',
                    'currency_id',
                    'delivery_terms',
                    'bank_name',
                    'bank_account_no',
                    'ifsc_code',
                    'rating_score',
                ]),
                [
                    'vendor_code' => $vendorCode,
                    'is_approved' => $request->input('is_approved', false),
                    'blacklisted' => $request->input('blacklisted', false),
                    'approved_by' => $request->input('is_approved') ? $request->input('auth_user_id') : null,
                    'approved_date' => $request->input('is_approved') ? now()->toDateString() : null,
                ]
            ));

            if ($request->has('contacts') && is_array($request->input('contacts'))) {
                foreach ($request->input('contacts') as $contact) {
                    if (!empty($contact['contact_name'])) {
                        VendorContact::create([
                            'vendor_id'    => $vendor->id,
                            'contact_name' => $contact['contact_name'],
                            'contact_type' => $contact['contact_type'] ?? 'PRIMARY',
                            'phone'        => $contact['contact_phone'] ?? null,
                            'email'        => $contact['contact_email'] ?? null,
                            'is_primary'   => !empty($contact['is_primary']),
                            'is_active'    => true,
                        ]);
                    }
                }
            } else if ($request->filled('contact_name')) {
                VendorContact::create([
                    'vendor_id'    => $vendor->id,
                    'contact_name' => $request->input('contact_name'),
                    'contact_type' => $request->input('contact_type', 'PRIMARY'),
                    'phone'        => $request->input('contact_phone'),
                    'email'        => $request->input('contact_email'),
                    'is_primary'   => true,
                    'is_active'    => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_CREATION_FAILED', 'details' => []],
                'message' => 'Failed to create vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Update vendor
     * PUT /api/v1/vendors/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'vendor_code'      => 'sometimes|string|max:20|unique:tenant.vendor_master,vendor_code,' . $id . ',id',
            'vendor_name'      => 'sometimes|string|max:200',
            'vendor_type'      => 'sometimes|string|max:20',
            'gstin'            => 'nullable|string|max:20|unique:tenant.vendor_master,gstin,' . $id . ',id',
            'pan_number'       => 'nullable|string|max:10',
            'msme_category'    => 'nullable|string|max:10',
            'payment_terms'    => 'nullable|string|max:30',
            'credit_days'      => 'nullable|integer|min:0',
            'currency_id'      => 'sometimes|integer|exists:tenant.currency_master,id',
            'delivery_terms'   => 'nullable|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'bank_account_no'  => 'nullable|string|max:30',
            'ifsc_code'        => 'nullable|string|max:11',
            'is_approved'      => 'sometimes|boolean',
            'approved_date'    => 'nullable|date',
            'rating_score'     => 'nullable|numeric|min:0|max:100',
            'blacklisted'      => 'sometimes|boolean',
            'contact_name'     => 'nullable|string|max:100',
            'contact_type'     => 'nullable|string|max:50',
            'contact_phone'    => 'nullable|string|max:20',
            'contact_email'    => 'nullable|email|max:100',
            'contacts'               => 'nullable|array',
            'contacts.*.contact_name' => 'nullable|string|max:100',
            'contacts.*.contact_type' => 'nullable|string|max:50',
            'contacts.*.contact_phone' => 'nullable|string|max:20',
            'contacts.*.contact_email' => 'nullable|email|max:100',
            'contacts.*.is_primary'  => 'nullable|boolean',
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
            $vendor = Vendor::findOrFail($id);

            $fields = [
                'vendor_code',
                'vendor_name',
                'vendor_type',
                'gstin',
                'pan_number',
                'msme_category',
                'payment_terms',
                'credit_days',
                'currency_id',
                'delivery_terms',
                'bank_name',
                'bank_account_no',
                'ifsc_code',
                'is_approved',
                'approved_date',
                'rating_score',
                'blacklisted',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $vendor->$field = $request->input($field);
                }
            }

            // Auto-set approved_by when approving
            if ($request->has('is_approved') && $request->input('is_approved')) {
                $vendor->approved_by = $request->input('auth_user_id');
                if (!$vendor->approved_date) {
                    $vendor->approved_date = now()->toDateString();
                }
            }

            $vendor->save();

            if ($request->has('contacts') && is_array($request->input('contacts'))) {
                // To safely update multiple, we overwrite existing for simplicity if an array is passed
                VendorContact::where('vendor_id', $vendor->id)->delete();
                foreach ($request->input('contacts') as $contact) {
                    if (!empty($contact['contact_name'])) {
                        VendorContact::create([
                            'vendor_id'    => $vendor->id,
                            'contact_name' => $contact['contact_name'],
                            'contact_type' => $contact['contact_type'] ?? 'PRIMARY',
                            'phone'        => $contact['contact_phone'] ?? null,
                            'email'        => $contact['contact_email'] ?? null,
                            'is_primary'   => !empty($contact['is_primary']),
                            'is_active'    => true,
                        ]);
                    }
                }
            } else if ($request->has('contact_name')) {
                VendorContact::updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'contact_type' => 'PRIMARY',
                    ],
                    [
                        'contact_name' => $request->input('contact_name'),
                        'phone' => $request->input('contact_phone'),
                        'email' => $request->input('contact_email'),
                        'is_primary' => true,
                        'is_active' => true,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'data' => ['vendor' => $vendor],
                'message' => 'Vendor updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_UPDATE_FAILED', 'details' => []],
                'message' => 'Failed to update vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Deactivate vendor (blacklist)
     * DELETE /api/v1/vendors/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->blacklisted = true;
            $vendor->save();

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Vendor blacklisted successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VENDOR_DELETE_FAILED', 'details' => []],
                'message' => 'Failed to blacklist vendor: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Send direct email to vendor
     * POST /api/v1/vendors/{id}/send-email
     */
    public function sendEmail(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
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
            $vendor = Vendor::with('contacts')->findOrFail($id);
            
            // Get primary contact email or fallback to first contact
            $contact = $vendor->contacts()->where('is_primary', true)->first() 
                      ?? $vendor->contacts()->first();

            if (!$contact || empty($contact->email)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'CONTACT_EMAIL_MISSING', 'details' => []],
                    'message' => 'Vendor has no contact email address defined.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            $org = $request->input('tenant_organization');
            $orgName = $org ? $org->org_name : 'ERP System';

            Mail::to($contact->email)->send(new VendorDirectMail(
                $request->input('subject'),
                $request->input('message'),
                $vendor->vendor_name,
                $orgName
            ));

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully to ' . $contact->email,
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'EMAIL_SEND_FAILED', 'details' => []],
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Export vendors to CSV
     * GET /api/v1/vendors/export
     */
    public function export(Request $request)
    {
        try {
            $query = Vendor::with(['currency', 'contacts']);

            if ($request->has('vendor_type')) {
                $query->where('vendor_type', $request->input('vendor_type'));
            }
            if ($request->has('is_approved')) {
                $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('blacklisted')) {
                $query->where('blacklisted', filter_var($request->input('blacklisted'), FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('vendor_code', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%");
                });
            }

            $vendors = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="vendors_export_' . date('Y-m-d_His') . '.csv"',
            ];

            $callback = function () use ($vendors) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, [
                    'vendor_name',
                    'vendor_type',
                    'gstin',
                    'pan_number',
                    'msme_category',
                    'payment_terms',
                    'credit_days',
                    'currency_code',
                    'delivery_terms',
                    'bank_name',
                    'bank_account_no',
                    'ifsc_code',
                    'rating_score',
                    'contact_name',
                    'contact_type',
                    'contact_phone',
                    'contact_email',
                    'is_approved',
                    'blacklisted'
                ]);

                // Data rows
                foreach ($vendors as $vendor) {
                    $primaryContact = $vendor->contacts()->where('is_primary', true)->first()
                                    ?? $vendor->contacts()->first();

                    fputcsv($file, [
                        $vendor->vendor_name,
                        $vendor->vendor_type,
                        $vendor->gstin ?? '',
                        $vendor->pan_number ?? '',
                        $vendor->msme_category ?? '',
                        $vendor->payment_terms ?? '',
                        $vendor->credit_days ?? '',
                        $vendor->currency?->currency_code ?? '',
                        $vendor->delivery_terms ?? '',
                        $vendor->bank_name ?? '',
                        $vendor->bank_account_no ?? '',
                        $vendor->ifsc_code ?? '',
                        $vendor->rating_score ?? '',
                        $primaryContact?->contact_name ?? '',
                        $primaryContact?->contact_type ?? '',
                        $primaryContact?->phone ?? '',
                        $primaryContact?->email ?? '',
                        $vendor->is_approved ? 'true' : 'false',
                        $vendor->blacklisted ? 'true' : 'false'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export vendors: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import vendors from CSV
     * POST /api/v1/vendors/import
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
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_map('trim', $csvData[0]);
            $rows = array_slice($csvData, 1);

            $imported = 0;
            $errors = [];

            // Fetch currency mappings
            $currencyMap = \App\Models\Tenant\Currency::pluck('id', 'currency_code')->toArray();

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;

                $rowNumber = $index + 2;
                $data = array_combine($headers, $row);

                try {
                    // Validate required fields
                    if (empty($data['vendor_name'])) {
                        $errors[] = "Row {$rowNumber}: Vendor name is required";
                        continue;
                    }

                    // Check for duplicate vendor name
                    $existingVendor = Vendor::where('vendor_name', trim($data['vendor_name']))->first();
                    if ($existingVendor) {
                        $errors[] = "Row {$rowNumber}: Vendor '{$data['vendor_name']}' already exists";
                        continue;
                    }

                    // Check for duplicate GSTIN if provided
                    if (!empty($data['gstin'])) {
                        $existingGstin = Vendor::where('gstin', trim($data['gstin']))->first();
                        if ($existingGstin) {
                            $errors[] = "Row {$rowNumber}: GSTIN '{$data['gstin']}' already exists";
                            continue;
                        }
                    }

                    // Resolve Currency
                    $currencyId = null;
                    if (!empty($data['currency_code'])) {
                        $currencyCode = strtoupper(trim($data['currency_code']));
                        $currencyId = $currencyMap[$currencyCode] ?? null;
                        if (!$currencyId) {
                            $errors[] = "Row {$rowNumber}: Currency code '{$data['currency_code']}' not found";
                            continue;
                        }
                    } else {
                        // Default to INR or first available currency
                        $currencyId = $currencyMap['INR'] ?? $currencyMap['USD'] ?? array_values($currencyMap)[0] ?? null;
                        if (!$currencyId) {
                            $errors[] = "Row {$rowNumber}: No currency available in system";
                            continue;
                        }
                    }

                    // Generate vendor code
                    $vendorType = !empty($data['vendor_type']) ? strtoupper(trim($data['vendor_type'])) : 'SUPPLIER';
                    $vendorCode = $this->generateVendorCode($vendorType, trim($data['vendor_name']));

                    DB::beginTransaction();

                    // Create vendor
                    $vendor = Vendor::create([
                        'vendor_code' => $vendorCode,
                        'vendor_name' => trim($data['vendor_name']),
                        'vendor_type' => $vendorType,
                        'gstin' => !empty($data['gstin']) ? trim($data['gstin']) : null,
                        'pan_number' => !empty($data['pan_number']) ? trim($data['pan_number']) : null,
                        'msme_category' => !empty($data['msme_category']) ? trim($data['msme_category']) : null,
                        'payment_terms' => !empty($data['payment_terms']) ? trim($data['payment_terms']) : 'NET30',
                        'credit_days' => !empty($data['credit_days']) ? intval($data['credit_days']) : 30,
                        'currency_id' => $currencyId,
                        'delivery_terms' => !empty($data['delivery_terms']) ? trim($data['delivery_terms']) : null,
                        'bank_name' => !empty($data['bank_name']) ? trim($data['bank_name']) : null,
                        'bank_account_no' => !empty($data['bank_account_no']) ? trim($data['bank_account_no']) : null,
                        'ifsc_code' => !empty($data['ifsc_code']) ? trim($data['ifsc_code']) : null,
                        'rating_score' => !empty($data['rating_score']) ? floatval($data['rating_score']) : 0,
                        'is_approved' => !empty($data['is_approved']) && in_array(strtolower($data['is_approved']), ['true', '1', 'yes']),
                        'blacklisted' => !empty($data['blacklisted']) && in_array(strtolower($data['blacklisted']), ['true', '1', 'yes']),
                        'approved_by' => null,
                        'approved_date' => null,
                    ]);

                    // Create contact if provided
                    if (!empty($data['contact_name'])) {
                        VendorContact::create([
                            'vendor_id' => $vendor->id,
                            'contact_name' => trim($data['contact_name']),
                            'contact_type' => !empty($data['contact_type']) ? trim($data['contact_type']) : 'PRIMARY',
                            'phone' => !empty($data['contact_phone']) ? trim($data['contact_phone']) : null,
                            'email' => !empty($data['contact_email']) ? trim($data['contact_email']) : null,
                            'is_primary' => true,
                            'is_active' => true,
                        ]);
                    }

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
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
                'message' => "{$imported} vendor(s) imported successfully" . (count($errors) > 0 ? ", " . count($errors) . " failed" : ""),
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
                'message' => 'Failed to import vendors: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }


    /**
     * Generate unique vendor code
     */
    private function generateVendorCode(string $vendorType, string $vendorName = ''): string
    {
        // 1. Generate Name Prefix (Up to 3 initials from first 3 significant words)
        $namePrefix = '';
        if (!empty($vendorName)) {
            $words = explode(' ', strtoupper(trim($vendorName)));
            $ignoredWords = ['AND', '&', 'PVT', 'LTD', 'LLP', 'CO', 'CORP', 'LIMITED', 'PRIVATE', 'INC', 'INDIA', 'LLC'];
            $selectedWords = [];

            foreach ($words as $w) {
                // Remove non-alphanumeric characters
                $cleanWord = preg_replace('/[^A-Z0-9]/', '', $w);
                if (!empty($cleanWord) && !in_array($cleanWord, $ignoredWords) && strlen($cleanWord) > 1) {
                    $selectedWords[] = substr($cleanWord, 0, 3);
                }
                if (count($selectedWords) >= 3) break;
            }
            if (!empty($selectedWords)) {
                $namePrefix = implode('-', $selectedWords);
            }
        }

        // 2. Generate Type Prefix (3 chars)
        $vendorTypeClean = strtoupper(trim($vendorType));
        if ($vendorTypeClean === 'SUPPLIER') {
            $typePrefix = 'SUP';
        } elseif (str_contains($vendorTypeClean, 'SERVICE')) {
            $typePrefix = 'SRV';
        } elseif ($vendorTypeClean === 'TRADER') {
            $typePrefix = 'TRD';
        } else {
            $typePrefix = substr(preg_replace('/[^A-Z]/', '', $vendorTypeClean), 0, 3);
        }

        if (empty($typePrefix)) $typePrefix = 'VND';

        // 3. Combine into a Base Prefix for searching
        $basePrefix = !empty($namePrefix) ? ($namePrefix . '-' . $typePrefix) : $typePrefix;

        // 4. Find the next incremental number for this specific base
        $lastVendor = Vendor::where('vendor_code', 'like', $basePrefix . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $newNumber = 1;
        if ($lastVendor) {
            $codeParts = explode('-', $lastVendor->vendor_code);
            $lastPart = end($codeParts);
            if (is_numeric($lastPart)) {
                $newNumber = intval($lastPart) + 1;
            }
        }

        // 5. Return final formatted code (e.g., SAF-LAB-SER-SRV-001)
        return sprintf('%s-%03d', $basePrefix, $newNumber);
    }
}
