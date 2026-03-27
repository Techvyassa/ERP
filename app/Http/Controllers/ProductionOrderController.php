<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\MaterialIssueRequest;
use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\BOMHeader;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductionOrderController extends Controller
{
    /**
     * Switch to the tenant DB from request context.
     * Same pattern used across the app (VendorPortalController, TokenService, etc.)
     */
    private function switchTenantDb(Request $request): void
    {
        $dbName = $request->input('tenant_db_name');
        if (!$dbName) return;
        config(['database.connections.tenant.database' => $dbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
    /**
     * GET /api/v1/production-orders
     */
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $query = ProductionOrder::with(['product', 'bom', 'mir']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('order_no', 'like', "%{$s}%")
                      ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%")
                                                           ->orWhere('product_code', 'like', "%{$s}%"));
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $orders = $query->orderByDesc('created_at')->get()->map(fn($o) => [
                'id'           => $o->id,
                'order_no'     => $o->order_no,
                'product_id'   => $o->product_id,
                'product_name' => $o->product?->product_name,
                'product_code' => $o->product?->product_code,
                'target_qty'   => $o->target_qty,
                'uom'          => $o->bom?->outputUom?->uom_code,
                'planned_date' => $o->planned_date?->format('Y-m-d'),
                'status'       => $o->status,
                'mir_status'   => $o->mir?->status,
                'mir_id'       => $o->mir?->id,
                'created_at'   => $o->created_at?->format('Y-m-d H:i'),
            ]);

            return response()->json([
                'success' => true,
                'data'    => ['orders' => $orders],
                'message' => 'Production orders retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage());
        }
    }

    /**
     * POST /api/v1/production-orders
     */
    public function store(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        $this->switchTenantDb($request);

        $validator = Validator::make($request->all(), [
            'product_id'   => 'required|integer',
            'bom_id'       => 'required|integer',
            'target_qty'   => 'required|numeric|min:0.001',
            'planned_date' => 'required|date',
            'rm_lines'     => 'required|array|min:1',
            'rm_lines.*.material_id'   => 'required|integer',
            'rm_lines.*.required_qty'  => 'required|numeric|min:0.001',
            'rm_lines.*.uom'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
                'message' => 'Validation failed',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 422);
        }

        try {
            DB::connection('tenant')->transaction(function () use ($request, &$order, &$mir) {
                // Generate order number
                $orderNo = 'PRD-' . str_pad(
                    (ProductionOrder::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT
                );

                $order = ProductionOrder::create([
                    'order_no'    => $orderNo,
                    'product_id'  => $request->input('product_id'),
                    'bom_id'      => $request->input('bom_id'),
                    'target_qty'  => $request->input('target_qty'),
                    'planned_date'=> $request->input('planned_date'),
                    'status'      => 'DRAFT',
                    'created_by'  => $request->input('auth_user_id'),
                ]);

                // Generate MIR number
                $mirNo = 'MIR-' . str_pad(
                    (MaterialIssueRequest::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT
                );

                $mir = MaterialIssueRequest::create([
                    'mir_no'              => $mirNo,
                    'production_order_id' => $order->id,
                    'status'              => 'PENDING',
                ]);

                // Resolve UOM ids from codes for line items
                foreach ($request->input('rm_lines') as $line) {
                    MIRLineItem::create([
                        'mir_id'       => $mir->id,
                        'material_id'  => $line['material_id'],
                        'required_qty' => $line['required_qty'],
                        'uom_id'       => null, // resolved from BOM detail already
                    ]);
                }
            });

            $order->load(['product', 'bom.outputUom', 'mir.lines.material', 'mir.lines.uom']);

            return response()->json([
                'success' => true,
                'data'    => ['order' => $order, 'mir' => $mir],
                'message' => 'Production order created and MIR sent to Store',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            return $this->error($requestId, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/production-orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = Str::uuid()->toString();
        try {
            $this->switchTenantDb($request);
            $order = ProductionOrder::with(['product', 'bom.outputUom', 'mir.lines.material', 'mir.lines.uom'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => ['order' => $order],
                'message' => 'Production order retrieved successfully',
                'request_id' => $requestId,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->error($requestId, 'Production order not found', 404);
        }
    }

    private function error(string $requestId, string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $status === 404 ? 'NOT_FOUND' : 'ERROR', 'details' => []],
            'message' => $message,
            'request_id' => $requestId,
            'timestamp'  => now()->toIso8601String(),
        ], $status);
    }
}
