<?php

declare(strict_types=1);

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

$options = getopt('', [
    'org:',
    'action:',
    'vendor-id::',
    'material-id::',
    'uom-id::',
    'warehouse-id::',
    'bin-id::',
    'qty::',
    'unit-price::',
    'batch::',
]);

$orgSlug = $options['org'] ?? null;
$action = $options['action'] ?? null;

if (!$orgSlug || !$action) {
    fail(
        "Usage:\n" .
            "  php scripts/tenant_reset_and_seed.php --org=an-tech-solutions-pvt-ltd --action=cleanup\n" .
            "  php scripts/tenant_reset_and_seed.php --org=an-tech-solutions-pvt-ltd --action=seed-dummy --vendor-id=1 --material-id=1 --uom-id=1 --warehouse-id=1 --bin-id=1\n" .
            "  php scripts/tenant_reset_and_seed.php --org=an-tech-solutions-pvt-ltd --action=cleanup-and-seed --vendor-id=1 --material-id=1 --uom-id=1 --warehouse-id=1 --bin-id=1"
    );
}

$organization = Organization::where('org_slug', $orgSlug)->first();
if (!$organization) {
    fail("Organization not found: {$orgSlug}");
}

if (!$organization->tenant_db_name) {
    fail("Organization {$orgSlug} does not have a tenant database configured.");
}

/** @var DatabaseConnectionRouter $router */
$router = $app->make(DatabaseConnectionRouter::class);

try {
    $router->switchToTenant($organization->tenant_db_name);

    out("Connected to tenant DB: {$organization->tenant_db_name}");

    if (in_array($action, ['cleanup', 'cleanup-and-seed'], true)) {
        cleanupTransactionalData();
        out('Transactional data cleanup completed.');
    }

    if (in_array($action, ['seed-dummy', 'cleanup-and-seed'], true)) {
        seedDummyData($options);
        out('Dummy transactional data seeded successfully.');
    }

    out('Done.');
} catch (Throwable $e) {
    fail('Error: ' . $e->getMessage());
} finally {
    $router->switchToControl();
}

function cleanupTransactionalData(): void
{
    $tables = [
        'carton_items',
        'cartons',
        'packing_orders',
        'mir_line_items',
        'material_issue_requests',
        'production_orders',
        'putaway_lines',
        'putaway_tasks',
        'usage_decisions',
        'inspection_results',
        'inspection_lots',
        'stock_balances',
        'inventory_transactions',
        'grn_line_items',
        'grn_headers',
        'mr_line_items',
        'material_receipts',
        'gate_verifications',
        'gate_entries',
        'asn_documents',
        'asn_line_items',
        'asn_headers',
        'shortage_excess_reports',
        'vendor_invoices',
        'inward_payments',
        'return_to_vendor',
        'inward_activity_log',
    ];

    DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS = 0');

    try {
        foreach ($tables as $table) {
            DB::connection('tenant')->table($table)->delete();
            out("  cleared {$table}");
        }
    } finally {
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function seedDummyData(array $options): void
{
    $vendorId = requiredIntOption($options, 'vendor-id');
    $materialId = requiredIntOption($options, 'material-id');
    $uomId = requiredIntOption($options, 'uom-id');
    $warehouseId = requiredIntOption($options, 'warehouse-id');
    $binId = requiredIntOption($options, 'bin-id');
    $qty = isset($options['qty']) ? (float) $options['qty'] : 25.000;
    $unitPrice = isset($options['unit-price']) ? (float) $options['unit-price'] : 10.0000;
    $batch = $options['batch'] ?? ('DUMMY-RM-' . date('Ymd-His'));

    $userId = (int) DB::connection('tenant')->table('users')->min('id');
    if ($userId <= 0) {
        fail('No users found in tenant master data. Seed users first.');
    }

    $vendorExists = DB::connection('tenant')->table('vendor_master')->where('id', $vendorId)->exists();
    $materialExists = DB::connection('tenant')->table('material_master')->where('id', $materialId)->exists();
    $uomExists = DB::connection('tenant')->table('uom_master')->where('id', $uomId)->exists();
    $warehouseExists = DB::connection('tenant')->table('warehouse_master')->where('id', $warehouseId)->exists();
    $binExists = DB::connection('tenant')->table('bin_locations')->where('id', $binId)->exists();

    if (!$vendorExists || !$materialExists || !$uomExists || !$warehouseExists || !$binExists) {
        fail('One or more master-data ids are invalid. Check vendor/material/uom/warehouse/bin ids.');
    }

    $now = now();
    $lineValue = round($qty * $unitPrice, 2);

    DB::connection('tenant')->transaction(function () use (
        $vendorId,
        $materialId,
        $uomId,
        $warehouseId,
        $binId,
        $qty,
        $unitPrice,
        $batch,
        $userId,
        $now,
        $lineValue
    ) {
        DB::connection('tenant')->table('grn_headers')->insert([
            'grn_number' => 'GRN/DUMMY/' . date('YmdHis'),
            'vendor_id' => $vendorId,
            'grn_date' => $now->toDateString(),
            'posting_date' => $now->toDateString(),
            'total_received_value' => $lineValue,
            'total_tax_amount' => 0,
            'grand_total' => $lineValue,
            'status' => 'QC_PENDING',
            'remarks' => 'Dummy GRN for stock flow test',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $grnId = (int) DB::connection('tenant')->getPdo()->lastInsertId();

        DB::connection('tenant')->table('grn_line_items')->insert([
            'grn_id' => $grnId,
            'mr_line_id' => null,
            'po_line_id' => null,
            'material_id' => $materialId,
            'accepted_qty' => $qty,
            'rejected_qty' => 0,
            'uom_id' => $uomId,
            'batch_number' => $batch,
            'unit_price' => $unitPrice,
            'tax_rate' => 0,
            'line_value' => $lineValue,
            'tax_amount' => 0,
            'warehouse_bin_id' => null,
            'stock_status' => 'AVAILABLE',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $grnLineId = (int) DB::connection('tenant')->getPdo()->lastInsertId();

        DB::connection('tenant')->table('inspection_lots')->insert([
            'lot_number' => 'LOT-DUMMY-' . date('His'),
            'source_type' => 'GRN',
            'grn_id' => $grnId,
            'grn_line_id' => $grnLineId,
            'material_id' => $materialId,
            'warehouse_id' => $warehouseId,
            'bin_id' => null,
            'batch_number' => $batch,
            'lot_qty' => $qty,
            'sample_size' => max(1, round($qty * 0.1, 3)),
            'sampling_method' => 'MANUAL',
            'status' => 'DECISION_MADE',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $lotId = (int) DB::connection('tenant')->getPdo()->lastInsertId();

        DB::connection('tenant')->table('usage_decisions')->insert([
            'lot_id' => $lotId,
            'decision' => 'ACCEPTED',
            'accepted_qty' => $qty,
            'rejected_qty' => 0,
            'remarks' => 'Accepted after QC inspection.',
            'decided_by' => $userId,
            'decided_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('tenant')->table('inventory_transactions')->insert([
            [
                'material_id' => $materialId,
                'product_id' => null,
                'batch_number' => $batch,
                'bucket' => 'QC_HOLD',
                'qty_change' => $qty,
                'uom_id' => $uomId,
                'warehouse_id' => $warehouseId,
                'bin_id' => null,
                'transaction_type' => 'GRN_RECEIPT',
                'reference_type' => 'GRN',
                'reference_id' => $grnId,
                'reference_number' => "GRN/DUMMY/{$grnId}",
                'unit_cost' => $unitPrice,
                'total_cost' => $lineValue,
                'created_by' => $userId,
                'remarks' => 'Dummy GRN receipt',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => $materialId,
                'product_id' => null,
                'batch_number' => $batch,
                'bucket' => 'QC_HOLD',
                'qty_change' => -$qty,
                'uom_id' => $uomId,
                'warehouse_id' => $warehouseId,
                'bin_id' => null,
                'transaction_type' => 'QC_PASS',
                'reference_type' => 'GRN',
                'reference_id' => $grnId,
                'reference_number' => "GRN/DUMMY/{$grnId}",
                'unit_cost' => $unitPrice,
                'total_cost' => $lineValue,
                'created_by' => $userId,
                'remarks' => 'Dummy QC pass OUT',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => $materialId,
                'product_id' => null,
                'batch_number' => $batch,
                'bucket' => 'PUTAWAY_PENDING',
                'qty_change' => $qty,
                'uom_id' => $uomId,
                'warehouse_id' => $warehouseId,
                'bin_id' => null,
                'transaction_type' => 'QC_PASS',
                'reference_type' => 'GRN',
                'reference_id' => $grnId,
                'reference_number' => "GRN/DUMMY/{$grnId}",
                'unit_cost' => $unitPrice,
                'total_cost' => $lineValue,
                'created_by' => $userId,
                'remarks' => 'Dummy QC pass IN',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::connection('tenant')->table('stock_balances')->insert([
            [
                'material_id' => $materialId,
                'product_id' => null,
                'batch_number' => $batch,
                'bucket' => 'QC_HOLD',
                'warehouse_id' => $warehouseId,
                'bin_id' => null,
                'qty_on_hand' => 0,
                'qty_reserved' => 0,
                'uom_id' => $uomId,
                'avg_cost' => $unitPrice,
                'last_transaction_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => $materialId,
                'product_id' => null,
                'batch_number' => $batch,
                'bucket' => 'PUTAWAY_PENDING',
                'warehouse_id' => $warehouseId,
                'bin_id' => null,
                'qty_on_hand' => $qty,
                'qty_reserved' => 0,
                'uom_id' => $uomId,
                'avg_cost' => $unitPrice,
                'last_transaction_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::connection('tenant')->table('putaway_tasks')->insert([
            'task_number' => 'PT-DUMMY-' . date('His'),
            'grn_line_id' => $grnLineId,
            'material_id' => $materialId,
            'batch_number' => $batch,
            'quantity' => $qty,
            'uom_id' => $uomId,
            'source_bin_id' => null,
            'destination_bin_id' => $binId,
            'strategy' => 'MANUAL',
            'status' => 'IN_PROGRESS',
            'assigned_to' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        out("  seeded GRN {$grnId}, line {$grnLineId}, lot {$lotId}, batch {$batch}");
    });
}

function requiredIntOption(array $options, string $name): int
{
    if (!isset($options[$name]) || $options[$name] === false || $options[$name] === '') {
        fail("Missing required option --{$name}");
    }

    return (int) $options[$name];
}
