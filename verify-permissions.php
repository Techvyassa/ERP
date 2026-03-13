<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Set tenant database
$tenantDb = 'erp_amit-tech-solutions-pvt-ltd';

// Configure tenant connection
config(['database.connections.tenant.database' => $tenantDb]);

// Query permissions
$permissions = DB::connection('tenant')->table('role_permissions')
    ->where('role_id', 1)
    ->get(['module_code', 'can_view', 'can_create', 'can_edit', 'can_approve', 'can_delete']);

echo "Permissions for ADMIN role (role_id: 1):\n";
echo str_repeat('-', 80) . "\n";
printf("%-15s %-10s %-10s %-10s %-10s %-10s\n", 
    'Module', 'View', 'Create', 'Edit', 'Approve', 'Delete');
echo str_repeat('-', 80) . "\n";

foreach ($permissions as $perm) {
    printf("%-15s %-10s %-10s %-10s %-10s %-10s\n",
        $perm->module_code,
        $perm->can_view ? 'YES' : 'NO',
        $perm->can_create ? 'YES' : 'NO',
        $perm->can_edit ? 'YES' : 'NO',
        $perm->can_approve ? 'YES' : 'NO',
        $perm->can_delete ? 'YES' : 'NO'
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total permissions: " . count($permissions) . "\n";
