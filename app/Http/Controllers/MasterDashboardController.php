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
                'organization' => $this->organizationStats(),
                'inventory' => $this->inventoryStats(),
                'vendor' => $this->vendorStats(),
                'tax' => $this->taxStats(),
                'bom' => $this->bomStats(),
                'quality' => $this->qualityStats(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Master dashboard statistics retrieved successfully',
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
                'message' => 'Failed to retrieve master dashboard statistics: ' . $e->getMessage(),
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

    private function taxStats(): array
    {
        $baseCurrency = null;

        try {
            $baseCurrency = DB::connection('tenant')
                ->table('currency_master')
                ->where('is_base_currency', true)
                ->value('currency_code');
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'hsnCodes' => $this->safeCount(fn() => HSNCode::count()),
            'gstTaxes' => $this->safeCount(fn() => GSTTax::count()),
            'currencies' => $this->safeCount(fn() => DB::connection('tenant')->table('currency_master')->count()),
            'baseCurrency' => $baseCurrency ?: 'INR',
        ];
    }

    private function bomStats(): array
    {
        return [
            'bomHeaders' => $this->safeCount(fn() => BOMHeader::count()),
            'bomDetails' => $this->safeCount(fn() => BOMDetail::count()),
            'productionOrders' => $this->safeCount(fn() => ProductionOrder::count()),
            'products' => $this->safeCount(fn() => Product::whereHas('bomHeaders')->count()),
        ];
    }

    private function qualityStats(): array
    {
        return [
            'testTypes' => $this->safeCount(fn() => QCTestType::count()),
            'parameters' => $this->safeCount(fn() => QCParameter::count()),
        ];
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
}
