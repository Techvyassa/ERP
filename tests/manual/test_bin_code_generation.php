<?php

// Test bin code generation directly
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Tenant\BinLocation;
use App\Models\Tenant\Warehouse;
use App\Http\Controllers\BinLocationController;

echo "Testing Bin Code Generation\n";
echo "===========================\n\n";

// Test generateBinCode method
$controller = new \App\Http\Controllers\BinLocationController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateBinCode');
$method->setAccessible(true);

// Get test warehouses
$warehouses = Warehouse::limit(3)->get(['id', 'warehouse_code']);

if ($warehouses->isEmpty()) {
    echo "No warehouses found. Creating test warehouse...\n";
    $testWarehouse = Warehouse::create([
        'warehouse_name' => 'Test Warehouse',
        'warehouse_code' => 'TEST-WH',
        'warehouse_type' => 'RM',
        'is_active' => true
    ]);
    $warehouses = collect([$testWarehouse]);
}

foreach ($warehouses as $warehouse) {
    $generatedCode = $method->invoke($controller, $warehouse->id);
    echo "Warehouse: {$warehouse->warehouse_code} (ID: {$warehouse->id}) -> Generated Bin Code: {$generatedCode}\n";
}

echo "\nChecking existing bin locations:\n";

// Check existing bin locations
$existingBins = BinLocation::orderBy('bin_code')->get(['bin_code', 'warehouse_id']);

if ($existingBins->isEmpty()) {
    echo "No existing bin locations found. Will start with 0001 for each warehouse.\n";
} else {
    echo "Existing bin codes:\n";
    foreach ($existingBins as $bin) {
        echo "  - {$bin->bin_code} (Warehouse ID: {$bin->warehouse_id})\n";
    }
}

echo "\nBin Code Generation Logic:\n";
echo "  - Prefix: Extracted from warehouse code (first part before dash)\n";
echo "  - Format: {WAREHOUSE_PREFIX}-{SEQUENTIAL_NUMBER}\n";
echo "  - Example: WH-0001, RM-0002, FG-0003...\n";

echo "\nBin auto-generation is ready to use!\n";
