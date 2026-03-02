<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tenantDbName = 'erp_test_org';

echo "=== Seeding Tenant Database: {$tenantDbName} ===\n\n";

try {
    // Configure tenant connection
    \Illuminate\Support\Facades\Config::set('database.connections.tenant.database', $tenantDbName);
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    // Seed roles
    echo "Seeding roles...\n";
    $roles = [
        ['role_code' => 'ADMIN', 'role_name' => 'Administrator', 'is_system_role' => true],
        ['role_code' => 'MANAGER', 'role_name' => 'Manager', 'is_system_role' => true],
        ['role_code' => 'USER', 'role_name' => 'User', 'is_system_role' => true],
        ['role_code' => 'VIEWER', 'role_name' => 'Viewer', 'is_system_role' => true],
    ];
    
    foreach ($roles as $role) {
        DB::connection('tenant')->table('role_master')->insertOrIgnore($role);
    }
    echo "✓ Roles seeded\n";
    
    // Seed role permissions
    echo "Seeding role permissions...\n";
    $modules = ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT'];
    $roleIds = DB::connection('tenant')->table('role_master')->pluck('role_id', 'role_code');
    
    foreach ($modules as $module) {
        // ADMIN: all permissions
        DB::connection('tenant')->table('role_permissions')->insertOrIgnore([
            'role_id' => $roleIds['ADMIN'],
            'module_code' => $module,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_approve' => true,
            'can_delete' => true,
        ]);
        
        // VIEWER: only view
        DB::connection('tenant')->table('role_permissions')->insertOrIgnore([
            'role_id' => $roleIds['VIEWER'],
            'module_code' => $module,
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_approve' => false,
            'can_delete' => false,
        ]);
    }
    echo "✓ Role permissions seeded\n";
    
    // Seed root department
    echo "Seeding root department...\n";
    DB::connection('tenant')->table('department_master')->insertOrIgnore([
        'dept_code' => 'ROOT',
        'dept_name' => 'Root Department',
        'parent_dept_id' => null,
        'is_active' => true,
    ]);
    echo "✓ Root department seeded\n";
    
    // Seed admin user
    echo "Seeding admin user...\n";
    $deptId = DB::connection('tenant')->table('department_master')->where('dept_code', 'ROOT')->value('dept_id');
    
    $existingUser = DB::connection('tenant')->table('users')->where('email', 'admin@test-org.com')->first();
    
    if (!$existingUser) {
        DB::connection('tenant')->table('users')->insert([
            'employee_code' => 'ADMIN001',
            'email' => 'admin@test-org.com',
            'password_hash' => password_hash('TestPassword123!', PASSWORD_BCRYPT, ['cost' => 12]),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'dept_id' => $deptId,
            'role_id' => $roleIds['ADMIN'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✓ Admin user created\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    echo "\n=== Seeding Complete ===\n";
    echo "Admin credentials:\n";
    echo "  Email: admin@test-org.com\n";
    echo "  Password: TestPassword123!\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
