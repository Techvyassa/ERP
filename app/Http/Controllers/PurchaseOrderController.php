<?php

namespace App\Http\Controllers;

use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PoLineItem;
use App\Models\Tenant\GSTTax;
use App\Models\Tenant\VendorContact;
use App\Models\Control\Organization;
use App\Http\Requests\Tenant\StorePurchaseOrderRequest;
use App\Http\Requests\Tenant\UpdatePurchaseOrderRequest;
use App\Mail\PurchaseOrderMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the purchase orders.
     * GET /api/v1/purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $query = PurchaseOrder::with(['vendor', 'currency', 'createdBy', 'approvedBy']);

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->input('vendor_id'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('vendor_name', 'like', "%{$search}%")
                              ->orWhere('vendor_code', 'like', "%{$search}%");
                        });
                });
            }

            // Pagination (15 per page by default)
            $perPage = $request->input('per_page', 15);
            $purchaseOrders = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $purchaseOrders,
                'message' => 'Purchase orders retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to retrieve purchase orders: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Store a newly created purchase order in storage.
     * POST /api/v1/purchase-orders
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        $authUserId = $request->input('auth_user_id') ?? ($request->user() ? $request->user()->id : null);

        try {
            DB::connection('tenant')->beginTransaction();

            // 1. Create Header
            $poNumber = PurchaseOrder::generatePoNumber();
            
            $purchaseOrder = new PurchaseOrder([
                'po_number' => $poNumber,
                'vendor_id' => $request->input('vendor_id'),
                'currency_id' => $request->input('currency_id'),
                'billing_address' => $request->input('billing_address'),
                'ship_to_address' => $request->input('ship_to_address'),
                'payment_terms' => $request->input('payment_terms', 'NET30'),
                'credit_days' => $request->input('credit_days', 30),
                'delivery_terms' => $request->input('delivery_terms'),
                'po_date' => $request->input('po_date'),
                'expected_delivery' => $request->input('expected_delivery'),
                'valid_until' => $request->input('valid_until'),
                'terms_conditions' => $request->input('terms_conditions'),
                'remarks' => $request->input('remarks'),
                'status' => 'DRAFT',
                'created_by' => $authUserId,
                
                // These will be calculated
                'discount_amount' => $request->input('discount_amount', 0),
                'freight_charges' => $request->input('freight_charges', 0),
                'subtotal' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
            ]);
            
            $purchaseOrder->save();

            // 2. Process Line Items
            $lineItems = $request->input('line_items');
            $lineNumber = 1;
            $headerSubtotal = 0;
            $headerTaxAmount = 0;

            foreach ($lineItems as $item) {
                $orderedQty = (float) $item['ordered_qty'];
                $unitPrice = (float) $item['unit_price'];
                $discountPct = (float) ($item['discount_pct'] ?? 0);
                
                // Calculate line total: (qty * price) * (1 - discount%)
                $lineTotal = ($orderedQty * $unitPrice) * (1 - ($discountPct / 100));
                
                // Calculate tax
                $taxAmount = 0;
                if (!empty($item['gst_tax_id'])) {
                    $tax = GSTTax::find($item['gst_tax_id']);
                    if ($tax) {
                        $taxAmount = $lineTotal * ($tax->getTotalTaxRate() / 100);
                    }
                }

                PoLineItem::create([
                    'po_id' => $purchaseOrder->id,
                    'line_number' => $lineNumber++,
                    'material_id' => $item['material_id'],
                    'material_description' => $item['material_description'] ?? null,
                    'ordered_qty' => $orderedQty,
                    'uom_id' => $item['uom_id'],
                    'unit_price' => $unitPrice,
                    'discount_pct' => $discountPct,
                    'line_total' => $lineTotal,
                    'gst_tax_id' => $item['gst_tax_id'] ?? null,
                    'tax_amount' => $taxAmount,
                    'scheduled_delivery' => $item['scheduled_delivery'] ?? null,
                    'under_delivery_tolerance' => $item['under_delivery_tolerance'] ?? 3.00,
                    'over_delivery_tolerance' => $item['over_delivery_tolerance'] ?? 5.00,
                    'receipt_status' => 'OPEN',
                    'received_qty' => 0
                ]);

                $headerSubtotal += $lineTotal;
                $headerTaxAmount += $taxAmount;
            }

            // 3. Update Header Totals
            $headerDiscount = (float) $purchaseOrder->discount_amount;
            $freightCharges = (float) $purchaseOrder->freight_charges;
            
            $purchaseOrder->subtotal = $headerSubtotal;
            $purchaseOrder->tax_amount = $headerTaxAmount;
            // Grand Total = Subtotal - Header Discount + Tax + Freight
            $purchaseOrder->grand_total = $headerSubtotal - $headerDiscount + $headerTaxAmount + $freightCharges;
            
            $purchaseOrder->save();

            DB::connection('tenant')->commit();

            $purchaseOrder->load(['lineItems.material', 'lineItems.uom', 'vendor', 'currency']);

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 201);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_CREATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to create purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Display the specified purchase order.
     * GET /api/v1/purchase-orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $purchaseOrder = PurchaseOrder::with([
                'lineItems.material', 
                'lineItems.uom', 
                'lineItems.gstTax',
                'vendor', 
                'currency', 
                'createdBy', 
                'approvedBy'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_NOT_FOUND',
                    'details' => []
                ],
                'message' => 'Purchase order not found',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 404);
        }
    }

    /**
     * Update the specified purchase order in storage.
     * PUT /api/v1/purchase-orders/{id}
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Only DRAFT POs can be fully edited
            if ($purchaseOrder->status !== 'DRAFT') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'PO_NOT_DRAFT',
                        'details' => [
                            'current_status' => $purchaseOrder->status
                        ]
                    ],
                    'message' => 'Only DRAFT purchase orders can be edited',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            DB::connection('tenant')->beginTransaction();

            // 1. Update Header fields
            $fillableFields = [
                'vendor_id', 'currency_id', 'billing_address', 'ship_to_address',
                'payment_terms', 'delivery_terms', 'po_date',
                'expected_delivery', 'valid_until', 'terms_conditions', 'remarks'
            ];

            foreach ($fillableFields as $field) {
                if ($request->has($field)) {
                    $purchaseOrder->$field = $request->input($field);
                }
            }

            // credit_days: default to 0 if null/missing to satisfy NOT NULL constraint
            if ($request->has('credit_days')) {
                $purchaseOrder->credit_days = $request->input('credit_days') ?? 0;
            }

            if ($request->has('discount_amount')) {
                $purchaseOrder->discount_amount = $request->input('discount_amount');
            }
            if ($request->has('freight_charges')) {
                $purchaseOrder->freight_charges = $request->input('freight_charges');
            }

            // 2. Process Line Items (if provided, we replace all existing lines for a draft)
            if ($request->has('line_items')) {
                // Delete existing line items
                PoLineItem::where('po_id', $purchaseOrder->id)->delete();

                $lineItems = $request->input('line_items');
                $lineNumber = 1;
                $headerSubtotal = 0;
                $headerTaxAmount = 0;

                foreach ($lineItems as $item) {
                    $orderedQty = (float) $item['ordered_qty'];
                    $unitPrice = (float) $item['unit_price'];
                    $discountPct = (float) ($item['discount_pct'] ?? 0);
                    
                    // Calculate line total
                    $lineTotal = ($orderedQty * $unitPrice) * (1 - ($discountPct / 100));
                    
                    // Calculate tax
                    $taxAmount = 0;
                    if (!empty($item['gst_tax_id'])) {
                        $tax = GSTTax::find($item['gst_tax_id']);
                        if ($tax) {
                            $taxAmount = $lineTotal * ($tax->getTotalTaxRate() / 100);
                        }
                    }

                    PoLineItem::create([
                        'po_id' => $purchaseOrder->id,
                        'line_number' => $lineNumber++,
                        'material_id' => $item['material_id'],
                        'material_description' => $item['material_description'] ?? null,
                        'ordered_qty' => $orderedQty,
                        'uom_id' => $item['uom_id'],
                        'unit_price' => $unitPrice,
                        'discount_pct' => $discountPct,
                        'line_total' => $lineTotal,
                        'gst_tax_id' => $item['gst_tax_id'] ?? null,
                        'tax_amount' => $taxAmount,
                        'scheduled_delivery' => $item['scheduled_delivery'] ?? null,
                        'under_delivery_tolerance' => $item['under_delivery_tolerance'] ?? 3.00,
                        'over_delivery_tolerance' => $item['over_delivery_tolerance'] ?? 5.00,
                        'receipt_status' => 'OPEN',
                        'received_qty' => 0
                    ]);

                    $headerSubtotal += $lineTotal;
                    $headerTaxAmount += $taxAmount;
                }

                // Update Header Totals
                $headerDiscount = (float) $purchaseOrder->discount_amount;
                $freightCharges = (float) $purchaseOrder->freight_charges;
                
                $purchaseOrder->subtotal = $headerSubtotal;
                $purchaseOrder->tax_amount = $headerTaxAmount;
                $purchaseOrder->grand_total = $headerSubtotal - $headerDiscount + $headerTaxAmount + $freightCharges;
            }

            $purchaseOrder->save();

            DB::connection('tenant')->commit();

            $purchaseOrder->load(['lineItems', 'vendor', 'currency']);

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order updated successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_UPDATE_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to update purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Submit the purchase order for approval.
     * PATCH /api/v1/purchase-orders/{id}/submit
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status !== 'DRAFT') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_STATUS_TRANSITION',
                        'details' => [
                            'current_status' => $purchaseOrder->status
                        ]
                    ],
                    'message' => 'Only DRAFT purchase orders can be submitted for approval',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            // Ensure there are line items
            if ($purchaseOrder->lineItems()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'EMPTY_PO',
                        'details' => []
                    ],
                    'message' => 'Cannot submit a PO with no line items',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            $purchaseOrder->status = 'PENDING_APPROVAL';
            $purchaseOrder->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order submitted for approval successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_SUBMIT_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to submit purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Approve the purchase order.
     * PATCH /api/v1/purchase-orders/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        $authUserId = $request->input('auth_user_id') ?? ($request->user() ? $request->user()->id : null);

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status !== 'DRAFT' && $purchaseOrder->status !== 'PENDING_APPROVAL') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_STATUS_TRANSITION',
                        'details' => [
                            'current_status' => $purchaseOrder->status
                        ]
                    ],
                    'message' => 'Only DRAFT or PENDING_APPROVAL orders can be approved',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            // Ensure there are line items
            if ($purchaseOrder->lineItems()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'EMPTY_PO',
                        'details' => []
                    ],
                    'message' => 'Cannot approve a PO with no line items',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            $purchaseOrder->status = 'OPEN';
            $purchaseOrder->approved_by = $authUserId;
            $purchaseOrder->approved_at = now();
            $purchaseOrder->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order approved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_APPROVAL_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to approve purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }
    /**
     * Reject the purchase order (PENDING_APPROVAL → DRAFT).
     * PATCH /api/v1/purchase-orders/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Rejection reason is required.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 422);
        }

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status !== 'PENDING_APPROVAL') {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'INVALID_STATUS_TRANSITION', 'details' => ['current_status' => $purchaseOrder->status]],
                    'message' => 'Only PENDING_APPROVAL purchase orders can be rejected.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            $purchaseOrder->status = 'DRAFT';
            $purchaseOrder->save();

            return response()->json([
                'success' => true,
                'data' => ['purchase_order' => $purchaseOrder],
                'message' => 'Purchase order rejected and returned to DRAFT.',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'PO_REJECT_FAILED', 'details' => []],
                'message' => 'Failed to reject purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Cancel the purchase order.
     * PATCH /api/v1/purchase-orders/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if (in_array($purchaseOrder->status, ['PARTIALLY_RECEIVED', 'FULLY_RECEIVED', 'CLOSED'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CANNOT_CANCEL',
                        'details' => [
                            'current_status' => $purchaseOrder->status
                        ]
                    ],
                    'message' => 'Cannot cancel an order that has already been received or closed',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }

            $purchaseOrder->status = 'CANCELLED';
            $purchaseOrder->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_order' => $purchaseOrder
                ],
                'message' => 'Purchase order cancelled successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PO_CANCELLATION_FAILED',
                    'details' => []
                ],
                'message' => 'Failed to cancel purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Send PO to vendor via email.
     * POST /api/v1/purchase-orders/{id}/send-to-vendor
     */
    public function sendToVendor(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $purchaseOrder = PurchaseOrder::with([
                'vendor.contacts',
                'lineItems.material',
                'lineItems.uom',
            ])->findOrFail($id);

            // Only OPEN (approved) POs can be sent
            if (!in_array($purchaseOrder->status, ['OPEN', 'PARTIAL'])) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'PO_NOT_APPROVED', 'details' => ['current_status' => $purchaseOrder->status]],
                    'message' => 'Only approved (OPEN) purchase orders can be sent to vendors',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            if (!$purchaseOrder->vendor) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'VENDOR_NOT_FOUND', 'details' => []],
                    'message' => 'Vendor not found for this purchase order',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Find primary active contact with email, fallback to any active contact with email
            $contact = $purchaseOrder->vendor->contacts
                ->where('is_active', true)
                ->where('is_primary', true)
                ->whereNotNull('email')
                ->first()
                ?? $purchaseOrder->vendor->contacts
                    ->where('is_active', true)
                    ->whereNotNull('email')
                    ->first();

            if (!$contact || !$contact->email) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NO_VENDOR_EMAIL', 'details' => []],
                    'message' => 'No email address found for this vendor. Please add a contact with an email in Vendor Master.',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Resolve org name from tenant DB name or request
            $orgName = $request->input('org_name', config('app.name'));
            $tenantDb = $request->input('tenant_db_name');
            $org = null;
            if ($tenantDb) {
                $org = Organization::where('tenant_db_name', $tenantDb)->first();
                if ($org) $orgName = $org->org_name;
            }

            Mail::to($contact->email, $contact->contact_name)
                ->send(new PurchaseOrderMail(
                    $purchaseOrder,
                    $contact->contact_name,
                    $orgName,
                    \App\Http\Controllers\VendorPortalController::generateToken($org ? $org->org_slug : '', $purchaseOrder->id)
                ));

            return response()->json([
                'success' => true,
                'data' => [
                    'sent_to' => $contact->email,
                    'contact_name' => $contact->contact_name,
                ],
                'message' => 'Purchase order sent to ' . $contact->contact_name . ' (' . $contact->email . ')',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'SEND_FAILED', 'details' => []],
                'message' => 'Failed to send purchase order: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
