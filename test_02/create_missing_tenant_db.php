<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;

echo "=== Creating Missing Tenant Database ===\n\n";

// Get the organization
$org = Organization::where('org_slug', 'test-org')->first();

if (!$org) {
    echo "✗ Organization 'test-org' not found\n";
    exit(1);
}

$tenantDbName = $org->tenant_db_name;

echo "Organization: {$org->org_slug}\n";
echo "Tenant DB: {$tenantDbName}\n\n";

// Check if database exists
$result = DB::connection('control')
    ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenantDbName]);

if (!empty($result)) {
    echo "✓ Database already exists\n";
    exit(0);
}

echo "Creating database '{$tenantDbName}'...\n";

try {
    // Create the database
    DB::connection('control')->statement("CREATE DATABASE `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n\n";
    
    // Configure tenant connection for migrations
    echo "Configuring tenant connection...\n";
    \Illuminate\Support\Facades\Config::set('database.connections.tenant.database', $tenantDbName);
    DB::purge('tenant');
    
    // Run migrations
    echo "Running migrations...\n";
    $exitCode = 0;
    putenv("TENANT_DB_DATABASE={$tenantDbName}");
    passthru("php artisan migrate --database=tenant --path=database/migrations/tenant --force", $exitCode);
    
    if ($exitCode === 0) {
        echo "\n✓ Migrations completed\n\n";
    } else {
        echo "\n✗ Migrations failed\n";
        exit(1);
    }
    
    // Seed default data
    echo "Seeding default data...\n";
    
    // Configure tenant connection
    \Illuminate\Support\Facades\Config::set('database.connections.tenant', [
        'driver' => 'mysql',
        'host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
        'database' => $tenantDbName,
        'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    
    DB::purge('tenant');
    DB::reconnect('tenant');
    
    // Seed roles
    $roles = [
        ['role_code' => 'ADMIN', 'role_name' => 'Administrator', 'is_system_role' => true],
        ['role_code' => 'MANAGER', 'role_name' => 'Manager', 'is_system_role' => true],
        ['role_code' => 'USER', 'role_name' => 'User', 'is_system_role' => true],
        ['role_code' => 'VIEWER', 'role_name' => 'Viewer', 'is_system_role' => true],
    ];
    
    foreach ($roles as $role) {
        DB::connection('tenant')->table('role_master')->insert($role);
    }
    echo "✓ Roles seeded\n";
    
    // Seed role permissions
    $modules = ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT'];
    $roleIds = DB::connection('tenant')->table('role_master')->pluck('role_id', 'role_code');
    
    foreach ($modules as $module) {
        // ADMIN: all permissions
        DB::connection('tenant')->table('role_permissions')->insert([
            'role_id' => $roleIds['ADMIN'],
            'module_code' => $module,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_approve' => true,
            'can_delete' => true,
        ]);
        
        // VIEWER: only view
        DB::connection('tenant')->table('role_permissions')->insert([
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
    DB::connection('tenant')->table('department_master')->insert([
        'dept_code' => 'ROOT',
        'dept_name' => 'Root Department',
        'parent_dept_id' => null,
        'is_active' => true,
    ]);
    echo "✓ Root department seeded\n";
    
    // Seed admin user
    $deptId = DB::connection('tenant')->table('department_master')->where('dept_code', 'ROOT')->value('dept_id');
    
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
    echo "✓ Admin user seeded\n";
    
    echo "\n=== Tenant Database Setup Complete ===\n";
    echo "Admin credentials:\n";
    echo "  Email: admin@test-org.com\n";
    echo "  Password: TestPassword123!\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
