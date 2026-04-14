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

class LookupController extends Controller
{
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

        $products = Product::where('is_active', true)
            ->when($request->filled('search'), fn($q) => $q->where(
                fn($q2) => $q2
                    ->where('product_name', 'like', '%' . $request->search . '%')
                    ->orWhere('product_code', 'like', '%' . $request->search . '%')
            ))
            ->orderBy('product_name')
            ->get(['id', 'product_code', 'product_name', 'pack_size', 'pack_uom_id', 'standard_cost', 'mrp']);

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

        $bins = DB::connection('tenant')
            ->table('stock_balances as sb')
            ->join('bin_locations as bl', 'sb.bin_id', '=', 'bl.id')
            ->join('warehouse_master as wm', 'sb.warehouse_id', '=', 'wm.id')
            ->where('sb.product_id', $productId)
            ->where('sb.bucket', 'AVAILABLE')
            ->whereRaw('(sb.qty_on_hand - sb.qty_reserved) > 0')
            ->select(
                'bl.bin_code',
                'wm.warehouse_name',
                DB::raw('(sb.qty_on_hand - sb.qty_reserved) as qty_available')
            )
            ->orderByDesc('qty_available')
            ->get();

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

        $bins = DB::connection('tenant')
            ->table('stock_balances as sb')
            ->leftJoin('bin_locations as bl', 'sb.bin_id', '=', 'bl.id')
            ->join('warehouse_master as wm', 'sb.warehouse_id', '=', 'wm.id')
            ->where('sb.material_id', $materialId)
            ->where('sb.bucket', 'AVAILABLE')
            ->select(
                'bl.id as bin_id',
                DB::raw("COALESCE(bl.bin_code, 'No Bin') as bin_code"),
                'wm.warehouse_name',
                'wm.id as warehouse_id',
                'sb.qty_on_hand',
                'sb.qty_reserved',
                DB::raw('(sb.qty_on_hand - sb.qty_reserved) as qty_available'),
                'sb.batch_number',
                'sb.bucket'
            )
            ->orderByDesc('qty_available')
            ->get();

        return response()->json([
            'success'         => true,
            'data'            => $bins,
            'total_available' => $bins->sum('qty_available'),
            'bin_count'       => $bins->count(),
        ]);
    }
}
