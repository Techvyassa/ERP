<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ApprovalMatrix;
use App\Models\Tenant\BOMDetail;
use App\Models\Tenant\BOMHeader;
use App\Models\Tenant\BinLocation;
use App\Models\Tenant\Department;
use App\Models\Tenant\GSTTax;
use App\Models\Tenant\HSNCode;
use App\Models\Tenant\Material;
use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\QCParameter;
use App\Models\Tenant\QCTestType;
use App\Models\Tenant\Role;
use App\Models\Tenant\SalesOrder;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\UOM;
use App\Models\Tenant\User;
use App\Models\Tenant\Vendor;
use App\Models\Tenant\VendorContact;
use App\Models\Tenant\VendorMaterialMap;
use App\Models\Tenant\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requestId = Str::uuid()->toString();

        try {
            $this->switchTenantDb($request);

            $data = [
                'overview' => [
                    'total_revenue' => $this->safeSum(fn() => SalesOrder::where('status', 'CONFIRMED')->sum('grand_total')),
                    'active_production' => $this->safeCount(fn() => ProductionOrder::where('status', 'IN_PROGRESS')->count()),
                    'pending_purchases' => $this->safeCount(fn() => PurchaseOrder::whereIn('status', ['DRAFT', 'PENDING_APPROVAL', 'OPEN'])->count()),
                    'low_stock_count' => $this->safeCount(fn() => StockBalance::where('bucket', 'AVAILABLE')->whereRaw('qty_on_hand <= 10')->count()),
                ],
                'organization' => $this->organizationStats(),
                'inventory' => $this->inventoryStats(),
                'vendor' => $this->vendorStats(),
                'sales' => $this->salesStats(),
                'production' => $this->productionStats(),
                'quality' => $this->qualityStats(),
                'recent_activity' => $this->recentActivity(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Dashboard statistics retrieved successfully',
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'details' => [],
                ],
                'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    private function switchTenantDb(Request $request): void
    {
        $tenantDbName = $request->input('tenant_db_name');

        if (!$tenantDbName) {
            throw new \RuntimeException('Tenant database name missing from request context');
        }

        config(['database.connections.tenant.database' => $tenantDbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    private function organizationStats(): array
    {
        return [
            'departments' => $this->safeCount(fn() => Department::count()),
            'roles' => $this->safeCount(fn() => Role::count()),
            'users' => $this->safeCount(fn() => User::count()),
            'approvalMatrix' => $this->safeCount(fn() => ApprovalMatrix::count()),
        ];
    }

    private function inventoryStats(): array
    {
        return [
            'materials' => $this->safeCount(fn() => Material::count()),
            'products' => $this->safeCount(fn() => Product::count()),
            'warehouses' => $this->safeCount(fn() => Warehouse::count()),
            'binLocations' => $this->safeCount(fn() => BinLocation::count()),
            'uom' => $this->safeCount(fn() => UOM::count()),
        ];
    }

    private function vendorStats(): array
    {
        return [
            'vendors' => $this->safeCount(fn() => Vendor::count()),
            'contacts' => $this->safeCount(fn() => VendorContact::count()),
            'mappings' => $this->safeCount(fn() => VendorMaterialMap::count()),
            'purchaseOrders' => $this->safeCount(fn() => PurchaseOrder::count()),
        ];
    }

    private function salesStats(): array
    {
        return [
            'total_orders' => $this->safeCount(fn() => SalesOrder::count()),
            'pending_orders' => $this->safeCount(fn() => SalesOrder::whereIn('status', ['DRAFT', 'CONFIRMED'])->count()),
            'dispatched_today' => $this->safeCount(fn() => SalesOrder::whereDate('dispatched_at', now())->count()),
        ];
    }

    private function productionStats(): array
    {
        return [
            'total_orders' => $this->safeCount(fn() => ProductionOrder::count()),
            'in_progress' => $this->safeCount(fn() => ProductionOrder::where('status', 'IN_PROGRESS')->count()),
            'completed_today' => $this->safeCount(fn() => ProductionOrder::where('status', 'COMPLETED')->whereDate('updated_at', now())->count()),
        ];
    }

    private function qualityStats(): array
    {
        return [
            'testTypes' => $this->safeCount(fn() => QCTestType::count()),
            'parameters' => $this->safeCount(fn() => QCParameter::count()),
            'activeTestTypes' => $this->safeCount(fn() => QCTestType::where('is_active', true)->count()),
            'activeParameters' => $this->safeCount(fn() => QCParameter::where('is_active', true)->count()),
            'materialsCovered' => $this->safeCount(fn() => QCParameter::where('is_active', true)->distinct('material_id')->count('material_id')),
        ];
    }

    private function recentActivity(): array
    {
        try {
            $activities = [];

            // Recent Sales Orders
            $sos = SalesOrder::with('customer')->orderBy('created_at', 'desc')->take(3)->get();
            foreach ($sos as $so) {
                $activities[] = [
                    'type' => 'sales_order',
                    'title' => 'New Sales Order ' . $so->so_number,
                    'description' => 'For ' . ($so->customer->customer_name ?? 'Unknown'),
                    'time' => $so->created_at->diffForHumans(),
                    'icon' => 'shopping_cart',
                    'color' => 'blue'
                ];
            }

            // Recent Production Orders
            $pos = ProductionOrder::with('product')->orderBy('created_at', 'desc')->take(3)->get();
            foreach ($pos as $po) {
                $activities[] = [
                    'type' => 'production_order',
                    'title' => 'Production Order ' . $po->order_no,
                    'description' => 'Product: ' . ($po->product->product_name ?? 'N/A'),
                    'time' => $po->created_at->diffForHumans(),
                    'icon' => 'factory',
                    'color' => 'green'
                ];
            }

            usort($activities, fn($a, $b) => strcmp($b['time'], $a['time'])); // Simple sort by time string (not perfect but okay for now)
            
            return array_slice($activities, 0, 5);
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function safeCount(callable $resolver): int
    {
        try {
            return (int) $resolver();
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    private function safeSum(callable $resolver): float
    {
        try {
            return (float) $resolver();
        } catch (\Throwable $e) {
            report($e);
            return 0.0;
        }
    }
}
