<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ProductionRequest;
use App\Models\Tenant\Product;
use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\MIRLineItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionRequestController extends Controller
{
    /**
     * Switch to the tenant DB from request context.
     */
    private function switchTenantDb(Request $request): void
    {
        $dbName = $request->input('tenant_db_name');
        if (!$dbName)
            return;
        config(['database.connections.tenant.database' => $dbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * GET /api/v1/production-requests
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            
            $query = ProductionRequest::with(['product', 'bom', 'uom', 'creator', 'mir', 'productionOrder']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('request_no', 'like', "%{$s}%")
                        ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%")
                            ->orWhere('product_code', 'like', "%{$s}%"));
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $requests = $query->orderByDesc('created_at')->get()->map(fn($r) => [
                'id' => $r->id,
                'request_no' => $r->request_no,
                'product_id' => $r->product_id,
                'product_name' => $r->product?->product_name,
                'product_code' => $r->product?->product_code,
                'bom_id' => $r->bom_id,
                'bom_code' => $r->bom?->bom_code,
                'bom_version' => $r->bom?->version,
                'target_qty' => $r->target_qty,
                'uom' => $r->uom ? [
                    'uom_code' => $r->uom->uom_code,
                    'uom_name' => $r->uom->uom_name,
                ] : null,
                'planned_date' => $r->planned_date?->format('Y-m-d'),
                'status' => $r->status,
                'remarks' => $r->remarks,
                'created_by' => $r->created_by,
                'creator_name' => $r->creator?->name,
                'approved_by' => $r->approved_by,
                'approver_name' => $r->approver?->name,
                'approved_at' => $r->approved_at?->format('Y-m-d H:i'),
                'mir_id' => $r->mir_id,
                'mir_status' => $r->mir?->status,
                'production_order_id' => $r->production_order_id,
                'production_order_no' => $r->productionOrder?->order_no,
                'yield_percent' => $r->productionOrder?->yield_percent,
                'created_at' => $r->created_at?->format('Y-m-d H:i'),
            ]);

            return response()->json([
                'success' => true,
                'data' => ['requests' => $requests],
                'message' => 'Production requests retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/production-requests/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            
            $productionRequest = ProductionRequest::with(['product', 'bom', 'bom.bomDetails', 'bom.outputUom', 'uom', 'creator', 'approver', 'mir', 'mir.lines', 'productionOrder'])
                ->findOrFail($id);

            $requestData = [
                'id' => $productionRequest->id,
                'request_no' => $productionRequest->request_no,
                'product_id' => $productionRequest->product_id,
                'product_name' => $productionRequest->product?->product_name,
                'product_code' => $productionRequest->product?->product_code,
                'bom_id' => $productionRequest->bom_id,
                'bom_code' => $productionRequest->bom?->bom_code,
                'target_qty' => $productionRequest->target_qty,
                'uom' => $productionRequest->uom ? [
                    'uom_code' => $productionRequest->uom->uom_code,
                    'uom_name' => $productionRequest->uom->uom_name,
                ] : null,
                'planned_date' => $productionRequest->planned_date?->format('Y-m-d'),
                'status' => $productionRequest->status,
                'mir_id' => $productionRequest->mir_id,
                'mir_status' => $productionRequest->mir?->status,
                'production_order_id' => $productionRequest->production_order_id,
            ];

            return response()->json([
                'success' => true,
                'data' => ['request' => $requestData],
                'message' => 'Production request retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/production-requests
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $validated = $request->validate([
                'product_id' => 'required|exists:tenant.product_master,id',
                'bom_id' => 'required|exists:tenant.bom_header,id',
                'target_qty' => 'required|numeric|min:0.001',
                'uom_id' => 'required|exists:tenant.uom_master,id',
                'planned_date' => 'required|date',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $productionRequest = ProductionRequest::create([
                'request_no' => ProductionRequest::generateRequestNo(),
                'product_id' => $validated['product_id'],
                'bom_id' => $validated['bom_id'],
                'target_qty' => $validated['target_qty'],
                'uom_id' => $validated['uom_id'],
                'planned_date' => $validated['planned_date'],
                'status' => 'APPROVED', // Skip DRAFT/PENDING — direct to APPROVED for immediate MIR generation
                'approved_by' => $request->user()?->id ?? 1,
                'approved_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => $request->user()?->id ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'data' => ['request' => $productionRequest],
                'message' => 'Production request created successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/production-requests/{id}/submit - Submit for approval
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::findOrFail($id);
            
            if ($productionRequest->status !== 'DRAFT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft requests can be submitted',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            $productionRequest->update(['status' => 'PENDING']);

            return response()->json([
                'success' => true,
                'data' => ['request' => $productionRequest],
                'message' => 'Production request submitted for approval',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@submit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/production-requests/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::findOrFail($id);
            
            if ($productionRequest->status !== 'PENDING') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be approved',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            $productionRequest->update([
                'status' => 'APPROVED',
                'approved_by' => $request->user()->id ?? 1,
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => ['request' => $productionRequest],
                'message' => 'Production request approved',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@approve: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/production-requests/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::findOrFail($id);
            
            if (!in_array($productionRequest->status, ['PENDING', 'APPROVED'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending or approved requests can be rejected',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            $productionRequest->update(['status' => 'REJECTED']);

            return response()->json([
                'success' => true,
                'data' => ['request' => $productionRequest],
                'message' => 'Production request rejected',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@reject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/production-requests/{id}/convert-to-mir - Convert to MIR
     */
    public function convertToMIR(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::with(['bom.bomDetails.material', 'bom.bomDetails.uom', 'bom.outputUom'])->findOrFail($id);
            
            if (!in_array($productionRequest->status, ['APPROVED', 'CONVERTED_TO_MIR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved requests can be converted to MIR',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            // Create MIR from production request
            $mir = MaterialIssueRequest::create([
                'mir_no' => MaterialIssueRequest::generateMirNo(),
                'production_order_id' => null, 
                'production_request_id' => $productionRequest->id,
                'status' => 'PENDING',
                'remarks' => "Created from Production Request: {$productionRequest->request_no}",
            ]);

            $bomDetails = $productionRequest->bom->bomDetails;
            $targetQty = $productionRequest->target_qty;

            foreach ($bomDetails as $detail) {
                $requiredQty = $detail->qty_required * $targetQty;
                
                MIRLineItem::create([
                    'mir_id' => $mir->id,
                    'material_id' => $detail->material_id,
                    'required_qty' => $requiredQty,
                    'uom_id' => $detail->uom_id,
                    'status' => 'PENDING',
                ]);
            }

            // Update production request
            $productionRequest->update([
                'status' => 'CONVERTED_TO_MIR',
                'mir_id' => $mir->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'request' => $productionRequest,
                    'mir' => $mir,
                ],
                'message' => 'Production request converted to MIR successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@convertToMIR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/production-requests/{id}/convert-to-order - Convert to Production Order
     */
    public function convertToOrder(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::with(['bom', 'bom.outputUom'])->findOrFail($id);
            
            if (!in_array($productionRequest->status, ['APPROVED', 'CONVERTED_TO_MIR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved requests can be converted to Production Order',
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                ], 400);
            }

            // Create Production Order
            $order = ProductionOrder::create([
                'order_no' => ProductionOrder::generateOrderNo(),
                'product_id' => $productionRequest->product_id,
                'bom_id' => $productionRequest->bom_id,
                'target_qty' => $productionRequest->target_qty,
                'planned_date' => $productionRequest->planned_date,
                'status' => 'DRAFT',
                'created_by' => $productionRequest->created_by,
            ]);

            // Update production request
            $productionRequest->update([
                'production_order_id' => $order->id,
            ]);

            // If MIR exists, link it to the production order
            if ($productionRequest->mir_id) {
                $productionRequest->mir->update([
                    'production_order_id' => $order->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'request' => $productionRequest,
                    'order' => $order,
                ],
                'message' => 'Production request converted to Production Order successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@convertToOrder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/production-requests/products - Get products with BOMs
     */
    public function products(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $products = Product::where('is_active', true)
                ->whereHas('bomHeaders', fn($q) => $q->where('bom_status', 'ACTIVE'))
                ->with(['bomHeaders' => fn($q) => $q->where('bom_status', 'ACTIVE')->orderByDesc('version')])
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'product_name' => $p->product_name,
                    'product_code' => $p->product_code,
                    'boms' => $p->bomHeaders->map(fn($b) => [
                        'id' => $b->id,
                        'bom_code' => $b->bom_code,
                        'version' => $b->version,
                        'output_uom' => $b->outputUom ? [
                            'id' => $b->outputUom->id,
                            'uom_code' => $b->outputUom->uom_code,
                            'uom_name' => $b->outputUom->uom_name,
                        ] : null,
                    ]),
                ]);

            return response()->json([
                'success' => true,
                'data' => ['products' => $products],
                'message' => 'Products retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/production-requests/{id}/materials - Get calculated materials for the request
     */
    public function materials(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);

            $productionRequest = ProductionRequest::with(['bom.bomDetails.material', 'bom.bomDetails.uom', 'bom.outputUom'])->findOrFail($id);
            
            $bomDetails = $productionRequest->bom->bomDetails;
            $targetQty = $productionRequest->target_qty;

            $materials = $bomDetails->map(fn($detail) => [
                'material_id' => $detail->material_id,
                'material_name' => $detail->material?->product_name ?? $detail->material?->material_name,
                'material_code' => $detail->material?->product_code ?? $detail->material?->material_code,
                'base_qty' => $detail->qty_required,
                'scrap_percent' => $detail->scrap_percent ?? 0,
                'required_qty' => ($detail->qty_required * $targetQty) * (1 + ($detail->scrap_percent ?? 0) / 100),
                'uom' => $detail->uom ? [
                    'id' => $detail->uom->id,
                    'uom_code' => $detail->uom->uom_code,
                    'uom_name' => $detail->uom->uom_name,
                ] : null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'request' => [
                        'id' => $productionRequest->id,
                        'request_no' => $productionRequest->request_no,
                        'target_qty' => $productionRequest->target_qty,
                        'output_uom' => $productionRequest->bom->outputUom ? [
                            'uom_code' => $productionRequest->bom->outputUom->uom_code,
                            'uom_name' => $productionRequest->bom->outputUom->uom_name,
                        ] : null,
                    ],
                    'materials' => $materials,
                ],
                'message' => 'Materials retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionRequestController@materials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}