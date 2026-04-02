<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds FG stock into stock_balances and runs a complete Sales Order
 * lifecycle: CREATE → CONFIRM → STOCK_CHECK → PICKING → PACKED → DISPATCHED
 *
 * Safe to re-run — uses updateOrInsert / firstOrCreate patterns.
 */
class FGStockAndSalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('tenant');

        // ── 1. Ensure a FG Warehouse exists ──────────────────────────────
        $this->command->info('Step 1: Ensuring FG Warehouse...');

        $warehouse = $db->table('warehouse_master')->where('warehouse_code', 'WH-FG')->first();
        if (!$warehouse) {
            $warehouseId = $db->table('warehouse_master')->insertGetId([
                'warehouse_code'  => 'WH-FG',
                'warehouse_name'  => 'Finished Goods Warehouse',
                'warehouse_type'  => 'FG',
                'address'         => 'Main Plant',
                'is_active'       => true,
            ]);
            $this->command->line('  ✓ Created WH-FG warehouse (id=' . $warehouseId . ')');
        } else {
            $warehouseId = $warehouse->id;
            $this->command->line('  → Using existing WH-FG (id=' . $warehouseId . ')');
        }

        // ── 2. Ensure a FG Bin exists ─────────────────────────────────────
        $this->command->info('Step 2: Ensuring FG Bin...');

        $bin = $db->table('bin_locations')
            ->where('warehouse_id', $warehouseId)
            ->where('bin_code', 'BIN-FG-01')
            ->first();

        if (!$bin) {
            $binId = $db->table('bin_locations')->insertGetId([
                'warehouse_id' => $warehouseId,
                'bin_code'     => 'BIN-FG-01',
                'aisle'        => 'A',
                'rack'         => 'R01',
                'shelf'        => 'S01',
                'is_active'    => true,
            ]);
            $this->command->line('  ✓ Created BIN-FG-01 (id=' . $binId . ')');
        } else {
            $binId = $bin->id;
            $this->command->line('  → Using existing BIN-FG-01 (id=' . $binId . ')');
        }

        // ── 3. Get all active products ────────────────────────────────────
        $this->command->info('Step 3: Loading active products...');

        $products = $db->table('product_master')->where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->command->error('  ✗ No active products found. Add products first.');
            return;
        }

        $this->command->line('  → Found ' . $products->count() . ' active products');

        // ── 4. Seed FG stock_balances ─────────────────────────────────────
        $this->command->info('Step 4: Seeding FG stock balances...');

        foreach ($products as $product) {
            // Get the product's UOM
            $uomId = $product->pack_uom_id ?? $db->table('uom_master')->value('id');

            if (!$uomId) {
                $this->command->warn('  ✗ No UOM found for product ' . $product->product_code . ', skipping');
                continue;
            }

            $db->table('stock_balances')->updateOrInsert(
                [
                    'product_id'   => $product->id,
                    'material_id'  => null,
                    'batch_number' => 'FG-BATCH-001',
                    'bucket'       => 'AVAILABLE',
                    'warehouse_id' => $warehouseId,
                    'bin_id'       => $binId,
                ],
                [
                    'qty_on_hand'          => 500.000,
                    'qty_reserved'         => 0.000,
                    'uom_id'               => $uomId,
                    'avg_cost'             => $product->standard_cost ?? 0,
                    'last_transaction_at'  => now(),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]
            );

            $this->command->line('  ✓ Stock seeded for ' . $product->product_code . ' — 500 units AVAILABLE');
        }

        // ── 5. Ensure a Customer exists ───────────────────────────────────
        $this->command->info('Step 5: Ensuring demo customer...');

        $customer = $db->table('customers')->where('customer_code', 'CUST-DEMO')->first();
        if (!$customer) {
            $customerId = $db->table('customers')->insertGetId([
                'customer_code'  => 'CUST-DEMO',
                'customer_name'  => 'Demo Customer',
                'contact_person' => 'John Doe',
                'phone'          => '+91-9000000000',
                'email'          => 'demo@customer.com',
                'payment_terms'  => 'NET30',
                'credit_days'    => 30,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $this->command->line('  ✓ Created CUST-DEMO (id=' . $customerId . ')');
        } else {
            $customerId = $customer->id;
            $this->command->line('  → Using existing CUST-DEMO (id=' . $customerId . ')');
        }

        // ── 6. Get first product and its UOM for the demo SO ─────────────
        $product  = $products->first();
        $uomId    = $product->pack_uom_id ?? $db->table('uom_master')->value('id');

        if (!$uomId) {
            $this->command->error('  ✗ No UOM available. Cannot create Sales Order.');
            return;
        }

        // ── 7. Create Sales Order ─────────────────────────────────────────
        $this->command->info('Step 6: Creating demo Sales Order...');

        $soNumber = 'SO-' . now()->format('ym') . '-' . str_pad(
            $db->table('sales_orders')->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $unitPrice = $product->mrp ?? $product->standard_cost ?? 100;
        $qty       = 10;
        $lineTotal = $qty * $unitPrice;

        $soId = $db->table('sales_orders')->insertGetId([
            'so_number'              => $soNumber,
            'customer_id'            => $customerId,
            'so_date'                => now()->toDateString(),
            'required_delivery_date' => now()->addDays(7)->toDateString(),
            'payment_terms'          => 'NET30',
            'subtotal'               => $lineTotal,
            'discount_amount'        => 0,
            'tax_amount'             => 0,
            'grand_total'            => $lineTotal,
            'status'                 => 'DRAFT',
            'stock_status'           => 'PENDING',
            'remarks'                => 'Demo SO — seeded by FGStockAndSalesOrderSeeder',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $lineItemId = $db->table('sales_order_line_items')->insertGetId([
            'so_id'            => $soId,
            'product_id'       => $product->id,
            'qty'              => $qty,
            'uom_id'           => $uomId,
            'unit_price'       => $unitPrice,
            'discount_percent' => 0,
            'line_total'       => $lineTotal,
            'available_qty'    => 0,
            'availability'     => 'PENDING',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->command->line('  ✓ Created SO ' . $soNumber . ' (id=' . $soId . ') — ' . $qty . ' x ' . $product->product_name);

        // ── 8. CONFIRM ────────────────────────────────────────────────────
        $this->command->info('Step 7: Confirming SO...');

        $db->table('sales_orders')->where('id', $soId)->update([
            'status'       => 'CONFIRMED',
            'confirmed_at' => now(),
            'updated_at'   => now(),
        ]);

        $this->command->line('  ✓ Status → CONFIRMED');

        // ── 9. STOCK CHECK ────────────────────────────────────────────────
        $this->command->info('Step 8: Running stock availability check...');

        $stockQty = $db->table('stock_balances')
            ->where('product_id', $product->id)
            ->where('bucket', 'AVAILABLE')
            ->selectRaw('COALESCE(SUM(qty_on_hand - qty_reserved), 0) as net_available')
            ->value('net_available') ?? 0;

        $availability = $stockQty >= $qty ? 'AVAILABLE' : ($stockQty > 0 ? 'PARTIAL' : 'UNAVAILABLE');
        $stockStatus  = $availability === 'AVAILABLE' ? 'AVAILABLE' : ($availability === 'PARTIAL' ? 'PARTIAL' : 'UNAVAILABLE');

        $db->table('sales_order_line_items')->where('id', $lineItemId)->update([
            'available_qty' => min($stockQty, $qty),
            'availability'  => $availability,
            'updated_at'    => now(),
        ]);

        $db->table('sales_orders')->where('id', $soId)->update([
            'status'       => 'STOCK_CHECKED',
            'stock_status' => $stockStatus,
            'updated_at'   => now(),
        ]);

        $this->command->line('  ✓ Stock: ' . $stockQty . ' available — Status → STOCK_CHECKED (' . $stockStatus . ')');

        if ($stockStatus === 'UNAVAILABLE') {
            $this->command->warn('  ⚠ Insufficient stock. Stopping at STOCK_CHECKED.');
            return;
        }

        // ── 10. RESERVE STOCK (simulate picklist generation) ──────────────
        $this->command->info('Step 9: Generating picklist — reserving stock...');

        $db->table('stock_balances')
            ->where('product_id', $product->id)
            ->where('bucket', 'AVAILABLE')
            ->where('warehouse_id', $warehouseId)
            ->update([
                'qty_reserved' => DB::raw('qty_reserved + ' . $qty),
                'updated_at'   => now(),
            ]);

        $db->table('sales_orders')->where('id', $soId)->update([
            'status'     => 'PICKING',
            'updated_at' => now(),
        ]);

        $this->command->line('  ✓ ' . $qty . ' units reserved — Status → PICKING');

        // ── 11. PACKED ────────────────────────────────────────────────────
        $this->command->info('Step 10: Marking as PACKED (pick validation complete)...');

        $db->table('sales_orders')->where('id', $soId)->update([
            'status'     => 'PACKED',
            'updated_at' => now(),
        ]);

        $this->command->line('  ✓ Status → PACKED');

        // ── 12. DISPATCH — deduct stock ───────────────────────────────────
        $this->command->info('Step 11: Confirming dispatch — deducting FG stock...');

        // Deduct from qty_on_hand and release reservation
        $db->table('stock_balances')
            ->where('product_id', $product->id)
            ->where('bucket', 'AVAILABLE')
            ->where('warehouse_id', $warehouseId)
            ->update([
                'qty_on_hand'  => DB::raw('qty_on_hand - ' . $qty),
                'qty_reserved' => DB::raw('GREATEST(qty_reserved - ' . $qty . ', 0)'),
                'updated_at'   => now(),
            ]);

        $db->table('sales_orders')->where('id', $soId)->update([
            'status'            => 'DISPATCHED',
            'vehicle_number'    => 'MH12AB1234',
            'driver_name'       => 'Ramesh Kumar',
            'logistics_partner' => 'BlueDart',
            'dispatched_at'     => now(),
            'updated_at'        => now(),
        ]);

        $this->command->line('  ✓ Stock deducted — Status → DISPATCHED');

        // ── Summary ───────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('✅ Full Sales Order lifecycle complete!');
        $this->command->table(
            ['Step', 'Result'],
            [
                ['FG Warehouse',   'WH-FG (id=' . $warehouseId . ')'],
                ['FG Bin',         'BIN-FG-01 (id=' . $binId . ')'],
                ['Products stocked', $products->count() . ' products × 500 units'],
                ['Customer',       'CUST-DEMO (id=' . $customerId . ')'],
                ['Sales Order',    $soNumber . ' (id=' . $soId . ')'],
                ['Product',        $product->product_name . ' × ' . $qty],
                ['Final Status',   'DISPATCHED'],
                ['Stock remaining', ($stockQty - $qty) . ' units'],
            ]
        );
    }
}
