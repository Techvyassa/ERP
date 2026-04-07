<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\StockService;

// ============================================================================
// CONFIGURATION - Edit these values
// ============================================================================
$materialId = 3; // RM-0001 - Cumin Seeds (Jeera)
$warehouseId = 1; // Your default warehouse
$binId = null; // Leave null for warehouse-level
$uomId = 1; // From material_master
$qtyToAdd = 500.0; // Quantity to add
$batchNumber = 'BATCH-TEST-001';
$userId = 1; // Admin user
$unitCost = 350.0; // Standard cost from material_master
// ============================================================================

echo "Adding {$qtyToAdd} units to Material ID: {$materialId}\n";

DB::connection('tenant')->transaction(function () use (
    $materialId,
    $warehouseId,
    $binId,
    $uomId,
    $qtyToAdd,
    $batchNumber,
    $userId,
    $unitCost
) {
    $stockService = app(StockService::class);

    // Post directly to AVAILABLE bucket
    $txn = $stockService->post(
        item: [
            'material_id' => $materialId,
            'uom_id' => $uomId,
            'warehouse_id' => $warehouseId,
            'batch_number' => $batchNumber,
        ],
        bucket: 'AVAILABLE',
        qtyChange: $qtyToAdd,
        transactionType: 'STOCK_ADJUSTMENT',
        referenceType: 'ManualAdjustment',
        referenceId: 999, // Dummy ID for manual adjustments
        referenceNumber: 'MANUAL/' . date('YmdHis'),
        userId: $userId,
        binId: $binId,
        unitCost: $unitCost,
        remarks: 'Manual stock addition for testing purposes'
    );

    echo "Transaction created with ID: {$txn->id}\n";
    echo "Stock balance updated successfully!\n";
});

echo "Done!\n";
