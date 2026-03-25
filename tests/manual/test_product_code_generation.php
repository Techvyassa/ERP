<?php

// Test product code generation directly
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Tenant\Product;
use App\Http\Controllers\ProductController;

echo "Testing Product Code Generation\n";
echo "================================\n\n";

// Test the generateProductCode method
$controller = new \App\Http\Controllers\ProductController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateProductCode');
$method->setAccessible(true);

// Test different categories
$categories = ['ELECTRONICS', 'FOOD', 'BEVERAGES', 'CLOTHING', 'FURNITURE'];

foreach ($categories as $category) {
    $generatedCode = $method->invoke($controller, $category);
    echo "Product Category: {$category} -> Generated Code: {$generatedCode}\n";
}

echo "\nChecking existing products:\n";

// Check existing products
$existingProducts = Product::orderBy('product_code')->get(['product_code', 'product_category']);

if ($existingProducts->isEmpty()) {
    echo "No existing products found. Will start with 0001 for each category.\n";
} else {
    echo "Existing product codes:\n";
    foreach ($existingProducts as $product) {
        echo "  - {$product->product_code} ({$product->product_category})\n";
    }
}

echo "\nCategory Prefix Mapping:\n";
echo "  - ELECTRONICS -> ELEC-\n";
echo "  - CLOTHING -> CLO-\n";
echo "  - FOOD -> FD-\n";
echo "  - BEVERAGES -> BEV-\n";
echo "  - FURNITURE -> FUR-\n";
echo "  - TOYS -> TOY-\n";
echo "  - BOOKS -> BK-\n";
echo "  - SPORTS -> SP-\n";
echo "  - BEAUTY -> BEA-\n";
echo "  - AUTOMOTIVE -> AUTO-\n";
echo "  - Default -> PROD-\n";

echo "\nProduct auto-generation is ready to use!\n";
