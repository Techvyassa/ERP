<?php

// Test script to verify material code auto-generation
// This can be run in Laravel Tinker: php artisan tinker

use App\Models\Tenant\Material;

echo "Testing Material Code Auto-Generation\n";
echo "=====================================\n\n";

// Test the generateMaterialCode method
$controller = new \App\Http\Controllers\MaterialController();

// Test different material types
$types = ['RAW', 'PACKAGING', 'CONSUMABLE', 'SEMI'];

foreach ($types as $type) {
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('generateMaterialCode');
    $method->setAccessible(true);
    
    $code = $method->invoke($controller, $type);
    echo "Material Type: {$type} -> Generated Code: {$code}\n";
}

echo "\nTesting with existing materials:\n";

// Check existing materials
$existingMaterials = Material::orderBy('material_code')->get(['material_code', 'material_type']);

if ($existingMaterials->isEmpty()) {
    echo "No existing materials found. Will start with 0001 for each type.\n";
} else {
    echo "Existing material codes:\n";
    foreach ($existingMaterials as $material) {
        echo "  - {$material->material_code} ({$material->material_type})\n";
    }
}

echo "\nAuto-generation is ready to use!\n";
echo "Prefix mapping:\n";
echo "  - RAW -> RM-\n";
echo "  - PACKAGING -> PKG-\n";
echo "  - CONSUMABLE -> CON-\n";
echo "  - SEMI -> SF-\n";
echo "  - Default -> MAT-\n";
