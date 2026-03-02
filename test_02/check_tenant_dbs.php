<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Available tenant databases:\n";

$orgs = DB::connection('control')
    ->table('organizations')
    ->where('registration_status', 'ACTIVE')
    ->get(['org_slug', 'tenant_db_name']);

foreach ($orgs as $org) {
    echo "- {$org->tenant_db_name} ({$org->org_slug})\n";
}

if ($orgs->isEmpty()) {
    echo "No active organizations found.\n";
}
