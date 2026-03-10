<?php

// Test material code generation directly
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Tenant\Material;
use App\Http\Controllers\MaterialController;

echo "Testing Material Code Generation\n";
echo "===================================\n\n";

// Test the generateMaterialCode method
$controller = new \App\Http\Controllers\MaterialController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateMaterialCode');
$method->setAccessible(true);

// Test PACKAGING type
$type = 'PACKAGING';
$generatedCode = $method->invoke($controller, $type);

echo "Material Type: {$type}\n";
echo "Generated Code: {$generatedCode}\n\n";

// Check existing materials
echo "Checking existing materials:\n";
$materials = Material::where('material_type', $type)
    ->orderBy('material_code', 'desc')
    ->limit(5)
    ->get(['material_code', 'material_type']);

if ($materials->isEmpty()) {
    echo "No existing PACKAGING materials found.\n";
} else {
    echo "Existing PACKAGING materials:\n";
    foreach ($materials as $material) {
        echo "  - {$material->material_code}\n";
    }
}

echo "\nTest completed successfully!\n";
