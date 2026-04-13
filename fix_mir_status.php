<?php

/**
 * Script to fix MIR status columns
 * Run this after applying the migrations
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\MIRLineItem;
use App\Models\Tenant\MaterialIssueRequest;

// Initialize Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MIR Status Fix Script ===\n\n";

// Check if status column exists
$hasStatus = DB::connection('tenant')->getSchemaBuilder()->hasColumn('mir_line_items', 'status');

if (!$hasStatus) {
    echo "❌ Status column not found in mir_line_items table.\n";
    echo "Please run the migrations first:\n";
    echo "  php artisan migrate\n";
    exit(1);
}

echo "✓ Status column found in mir_line_items table.\n\n";

// Update existing MIR line items with status
echo "Updating MIR line item statuses...\n";

$lines = MIRLineItem::all();
$updated = 0;

foreach ($lines as $line) {
    $oldStatus = $line->status ?? 'N/A';
    
    // Set status based on issued_qty
    if ($line->issued_qty > 0) {
        if ($line->issued_qty >= $line->required_qty) {
            $line->status = 'FULLY_PICKED';
        } else {
            $line->status = 'PARTIALLY_PICKED';
        }
    } else {
        $line->status = 'PENDING';
    }
    
    $line->save();
    $updated++;
    
    echo "  Line #{$line->id}: {$oldStatus} → {$line->status}\n";
}

echo "\n✓ Updated {$updated} MIR line items.\n\n";

// Update MIR header statuses
echo "Updating MIR header statuses...\n";

$mirs = MaterialIssueRequest::with('lines')->get();
$mirUpdated = 0;

foreach ($mirs as $mir) {
    $oldStatus = $mir->status;
    $newStatus = $mir->deriveHeaderStatus();
    
    if ($oldStatus !== $newStatus) {
        $mir->status = $newStatus;
        $mir->save();
        $mirUpdated++;
        
        echo "  MIR #{$mir->id}: {$oldStatus} → {$newStatus}\n";
    }
}

echo "\n✓ Updated {$mirUpdated} MIR header statuses.\n\n";

// Summary
echo "=== Summary ===\n";
echo "MIR Line Items Updated: {$updated}\n";
echo "MIR Headers Updated: {$mirUpdated}\n";
echo "\n✓ MIR status fix completed successfully!\n";