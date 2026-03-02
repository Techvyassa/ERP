<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "All databases:\n";

$databases = DB::connection('control')->select('SHOW DATABASES');

foreach ($databases as $db) {
    $dbName = $db->Database;
    if (str_starts_with($dbName, 'erp_')) {
        echo "- {$dbName}\n";
    }
}
