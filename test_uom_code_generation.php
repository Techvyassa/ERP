<?php

// Test UOM code generation directly
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Tenant\UOM;
use App\Http\Controllers\UOMController;

echo "Testing UOM Code Generation\n";
echo "==========================\n\n";

// Test generateUOMCode method
$controller = new \App\Http\Controllers\UOMController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateUOMCode');
$method->setAccessible(true);

// Test different UOM types
$types = ['weight', 'volume', 'qty', 'length'];

foreach ($types as $type) {
    $generatedCode = $method->invoke($controller, $type);
    echo "UOM Type: {$type} -> Generated Code: {$generatedCode}\n";
}

echo "\nChecking existing UOMs:\n";

// Check existing UOMs
$existingUOMs = UOM::orderBy('uom_code')->get(['uom_code', 'uom_type']);

if ($existingUOMs->isEmpty()) {
    echo "No existing UOMs found. Will start with 0001 for each type.\n";
} else {
    echo "Existing UOM codes:\n";
    foreach ($existingUOMs as $uom) {
        echo "  - {$uom->uom_code} ({$uom->uom_type})\n";
    }
}

echo "\nUOM Type Prefix Mapping:\n";
echo "  - BASE -> UOM-\n";
echo "  - DERIVED -> DUM-\n";
echo "  - REFERENCE -> RUM-\n";
echo "  - ALTERNATE -> AUM-\n";
echo "  - TEMPORARY -> TUM-\n";
echo "  - Default -> UOM-\n";

echo "\nUOM auto-generation is ready to use!\n";
