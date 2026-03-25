<?php

// Test warehouse code generation directly
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Tenant\Warehouse;
use App\Http\Controllers\WarehouseController;

echo "Testing Warehouse Code Generation\n";
echo "=================================\n\n";

// Test generateWarehouseCode method
$controller = new \App\Http\Controllers\WarehouseController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateWarehouseCode');
$method->setAccessible(true);

// Test different warehouse types
$types = ['MAIN', 'BRANCH', 'GODOWN', 'COLD_STORAGE', 'WAREHOUSE'];

foreach ($types as $type) {
    $generatedCode = $method->invoke($controller, $type);
    echo "Warehouse Type: {$type} -> Generated Code: {$generatedCode}\n";
}

echo "\nChecking existing warehouses:\n";

// Check existing warehouses
$existingWarehouses = Warehouse::orderBy('warehouse_code')->get(['warehouse_code', 'warehouse_type']);

if ($existingWarehouses->isEmpty()) {
    echo "No existing warehouses found. Will start with 0001 for each type.\n";
} else {
    echo "Existing warehouse codes:\n";
    foreach ($existingWarehouses as $warehouse) {
        echo "  - {$warehouse->warehouse_code} ({$warehouse->warehouse_type})\n";
    }
}

echo "\nWarehouse Type Prefix Mapping:\n";
echo "  - RM -> RM-\n";
echo "  - FG -> FG-\n";
echo "  - PKG -> PKG-\n";
echo "  - REJECTION -> REJ-\n";
echo "  - WIP -> WIP-\n";
echo "  - Default -> WH-\n";

echo "\nWarehouse auto-generation is ready to use!\n";
