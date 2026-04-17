<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Tenant\Customer;
use App\Models\Tenant\User;
use App\Models\Tenant\Product;
use App\Models\Tenant\UOM;
use App\Services\StockQueryService;

class LookupController extends Controller
{
    public function __construct(protected StockQueryService $stockQueryService) {}

    private function switchTenant(Request $request): void
    {
        $tenantDb = $request->input('tenant_db_name');
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            DB::purge('tenant');
            DB::reconnect('tenant');
        }
    }

    /**
     * GET /api/v1/lookup/customers
     * Search customers and users combined (for Sales Order customer picker)
     */
    public function customers(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        $customers = Customer::where('is_active', true)
            ->when($request->filled('search'), fn($q) => $q->where('customer_name', 'like', '%' . $request->search . '%'))
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'customer_code', 'phone', 'email'])
            ->map(fn($c) => [
                'id'      => 'c_' . $c->id,
                'label'   => $c->customer_name,
                'sub'     => $c->customer_code,
                'source'  => 'customer',
                'raw_id'  => $c->id,
            ]);

        $users = User::where('is_active', true)
            ->when($request->filled('search'), fn($q) => $q->where(
                fn($q2) => $q2
                    ->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
            ))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'employee_code'])
            ->map(fn($u) => [
                'id'     => 'u_' . $u->id,
                'label'  => trim($u->first_name . ' ' . $u->last_name),
                'sub'    => $u->email,
                'source' => 'user',
                'raw_id' => $u->id,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $customers->concat($users)->sortBy('label')->values(),
        ]);
    }

    /**
     * POST /api/v1/lookup/customers
     * Quick-create a customer inline from the Sales Order form
     */
    public function createCustomer(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $customer = Customer::create([
            'customer_name' => $request->customer_name,
            'customer_code' => Customer::generateCode(),
            'created_by'    => $request->input('auth_user_id'),
        ]);

        return response()->json(['success' => true, 'data' => $customer], 201);
    }

    /**
     * GET /api/v1/lookup/products
     * Search active products (for Sales Order line picker)
     */
    public function products(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        $stockByProduct = collect($this->stockQueryService->getGlobalStockSummary())
            ->where('item_type', 'Product')
            ->keyBy('item_id');

        $products = Product::query()
            ->where('product_master.is_active', true)
            ->when($request->filled('search'), fn($q) => $q->where(
                fn($q2) => $q2
                    ->where('product_master.product_name', 'like', '%' . $request->search . '%')
                    ->orWhere('product_master.product_code', 'like', '%' . $request->search . '%')
            ))
            ->orderBy('product_master.product_name')
            ->get([
                'product_master.id',
                'product_master.product_code',
                'product_master.product_name',
                'product_master.pack_size',
                'product_master.pack_uom_id',
                'product_master.standard_cost',
                'product_master.mrp',
            ])
            ->map(function ($product) use ($stockByProduct) {
                $stock = $stockByProduct->get($product->id, []);
                $currentStock = (float) ($stock['available'] ?? 0);

                return [
                    'id'            => $product->id,
                    'product_code'  => $product->product_code,
                    'product_name'  => $product->product_name,
                    'pack_size'     => $product->pack_size,
                    'pack_uom_id'   => $product->pack_uom_id,
                    'standard_cost' => $product->standard_cost,
                    'mrp'           => $product->mrp,
                    'qty_on_hand'   => (float) ($stock['on_hand'] ?? 0),
                    'qty_reserved'  => (float) ($stock['reserved'] ?? 0),
                    'current_stock' => $currentStock,
                    'stock_status'  => $currentStock > 0 ? 'IN_STOCK' : 'OUT_OF_STOCK',
                ];
            });

        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET /api/v1/lookup/uoms
     * List all active UOMs
     */
    public function uoms(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        return response()->json([
            'success' => true,
            'data'    => UOM::where('is_active', true)->orderBy('uom_name')->get(['id', 'uom_code', 'uom_name']),
        ]);
    }

    /**
     * GET /api/v1/lookup/stock-bins?product_id={id}
     * Available bin locations for a product (Sales Order dispatch / picking)
     */
    public function stockBins(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        $productId = $request->input('product_id');
        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'product_id required'], 422);
        }

        $bins = $this->stockQueryService->getProductBinAvailability((int) $productId);

        return response()->json(['success' => true, 'data' => $bins]);
    }

    /**
     * GET /api/v1/lookup/material-bins?material_id={id}
     * All bin locations for a raw material (MIR issue modal)
     */
    public function materialBins(Request $request): JsonResponse
    {
        $this->switchTenant($request);

        $materialId = $request->input('material_id');
        if (!$materialId) {
            return response()->json(['success' => false, 'message' => 'material_id required'], 422);
        }

        $bins = collect($this->stockQueryService->getMaterialBinAvailability((int) $materialId));

        return response()->json([
            'success'         => true,
            'data'            => $bins,
            'total_available' => $bins->sum('qty_available'),
            'bin_count'       => $bins->count(),
        ]);
    }
}
