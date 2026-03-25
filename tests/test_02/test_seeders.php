<?php

/**
 * Test script for database seeders
 * Tests both Control and Tenant database seeders
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Control\SubscriptionPlan;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use Database\Seeders\Control\SubscriptionPlanSeeder;
use Database\Seeders\Tenant\DefaultRoleSeeder;
use Database\Seeders\Tenant\DefaultRolePermissionSeeder;

echo "=== Testing Database Seeders ===\n\n";

// Test 1: Control Database - SubscriptionPlanSeeder
echo "Test 1: Seeding Subscription Plans (Control DB)\n";
echo str_repeat('-', 50) . "\n";

try {
    $seeder = new SubscriptionPlanSeeder();
    $seeder->run();
    
    // Verify plans were created
    $plans = SubscriptionPlan::all();
    echo "  Plans created: " . $plans->count() . "\n";
    
    foreach ($plans as $plan) {
        echo "  - {$plan->plan_code}: {$plan->plan_name}\n";
        echo "    Max Users: {$plan->max_users}, Modules: " . count($plan->modules_included) . "\n";
    }
    
    // Verify specific requirements
    $basicPlan = SubscriptionPlan::where('plan_code', 'BASIC')->first();
    $professionalPlan = SubscriptionPlan::where('plan_code', 'PROFESSIONAL')->first();
    $enterprisePlan = SubscriptionPlan::where('plan_code', 'ENTERPRISE')->first();
    
    assert($basicPlan !== null, 'BASIC plan should exist');
    assert($professionalPlan !== null, 'PROFESSIONAL plan should exist');
    assert($enterprisePlan !== null, 'ENTERPRISE plan should exist');
    
    assert(is_array($basicPlan->modules_included), 'modules_included should be array');
    assert(count($basicPlan->modules_included) > 0, 'BASIC should have modules');
    
    echo "  ✓ All subscription plan assertions passed\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 2: Tenant Database - DefaultRoleSeeder
echo "Test 2: Seeding Default Roles (Tenant DB)\n";
echo str_repeat('-', 50) . "\n";

try {
    $seeder = new DefaultRoleSeeder();
    $seeder->run();
    
    // Verify roles were created
    $roles = Role::all();
    echo "  Roles created: " . $roles->count() . "\n";
    
    foreach ($roles as $role) {
        echo "  - {$role->role_code}: {$role->role_name} (System: " . ($role->is_system_role ? 'Yes' : 'No') . ")\n";
    }
    
    // Verify specific requirements
    $adminRole = Role::where('role_code', 'ADMIN')->first();
    $managerRole = Role::where('role_code', 'MANAGER')->first();
    $userRole = Role::where('role_code', 'USER')->first();
    $viewerRole = Role::where('role_code', 'VIEWER')->first();
    
    assert($adminRole !== null, 'ADMIN role should exist');
    assert($managerRole !== null, 'MANAGER role should exist');
    assert($userRole !== null, 'USER role should exist');
    assert($viewerRole !== null, 'VIEWER role should exist');
    
    assert($adminRole->is_system_role === true, 'ADMIN should be system role');
    assert($viewerRole->is_system_role === true, 'VIEWER should be system role');
    
    echo "  ✓ All role assertions passed\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 3: Tenant Database - DefaultRolePermissionSeeder
echo "Test 3: Seeding Default Role Permissions (Tenant DB)\n";
echo str_repeat('-', 50) . "\n";

try {
    $seeder = new DefaultRolePermissionSeeder();
    $seeder->run();
    
    // Verify permissions were created
    $permissions = RolePermission::all();
    echo "  Permissions created: " . $permissions->count() . "\n";
    
    // Get roles
    $adminRole = Role::where('role_code', 'ADMIN')->first();
    $viewerRole = Role::where('role_code', 'VIEWER')->first();
    
    // Verify ADMIN permissions (all should be true)
    $adminPermissions = RolePermission::where('role_id', $adminRole->role_id)->get();
    echo "  ADMIN permissions: " . $adminPermissions->count() . " modules\n";
    
    foreach ($adminPermissions as $perm) {
        assert($perm->can_view === true, "ADMIN should have can_view for {$perm->module_code}");
        assert($perm->can_create === true, "ADMIN should have can_create for {$perm->module_code}");
        assert($perm->can_edit === true, "ADMIN should have can_edit for {$perm->module_code}");
        assert($perm->can_approve === true, "ADMIN should have can_approve for {$perm->module_code}");
        assert($perm->can_delete === true, "ADMIN should have can_delete for {$perm->module_code}");
    }
    echo "  ✓ ADMIN has all permissions for all modules\n";
    
    // Verify VIEWER permissions (only can_view should be true)
    $viewerPermissions = RolePermission::where('role_id', $viewerRole->role_id)->get();
    echo "  VIEWER permissions: " . $viewerPermissions->count() . " modules\n";
    
    foreach ($viewerPermissions as $perm) {
        assert($perm->can_view === true, "VIEWER should have can_view for {$perm->module_code}");
        assert($perm->can_create === false, "VIEWER should NOT have can_create for {$perm->module_code}");
        assert($perm->can_edit === false, "VIEWER should NOT have can_edit for {$perm->module_code}");
        assert($perm->can_approve === false, "VIEWER should NOT have can_approve for {$perm->module_code}");
        assert($perm->can_delete === false, "VIEWER should NOT have can_delete for {$perm->module_code}");
    }
    echo "  ✓ VIEWER has only can_view permission for all modules\n";
    
    // List some sample permissions
    echo "  Sample permissions:\n";
    $samplePerms = RolePermission::with('role')->limit(5)->get();
    foreach ($samplePerms as $perm) {
        echo "    - {$perm->role->role_code} / {$perm->module_code}: ";
        echo "V:" . ($perm->can_view ? '✓' : '✗') . " ";
        echo "C:" . ($perm->can_create ? '✓' : '✗') . " ";
        echo "E:" . ($perm->can_edit ? '✓' : '✗') . " ";
        echo "A:" . ($perm->can_approve ? '✓' : '✗') . " ";
        echo "D:" . ($perm->can_delete ? '✓' : '✗') . "\n";
    }
    
    echo "  ✓ All permission assertions passed\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 4: Idempotency - Run seeders again
echo "Test 4: Testing Idempotency (Running seeders again)\n";
echo str_repeat('-', 50) . "\n";

try {
    $planCountBefore = SubscriptionPlan::count();
    $roleCountBefore = Role::count();
    $permCountBefore = RolePermission::count();
    
    echo "  Before: Plans={$planCountBefore}, Roles={$roleCountBefore}, Permissions={$permCountBefore}\n";
    
    // Run seeders again
    (new SubscriptionPlanSeeder())->run();
    (new DefaultRoleSeeder())->run();
    (new DefaultRolePermissionSeeder())->run();
    
    $planCountAfter = SubscriptionPlan::count();
    $roleCountAfter = Role::count();
    $permCountAfter = RolePermission::count();
    
    echo "  After: Plans={$planCountAfter}, Roles={$roleCountAfter}, Permissions={$permCountAfter}\n";
    
    assert($planCountBefore === $planCountAfter, 'Plan count should not change');
    assert($roleCountBefore === $roleCountAfter, 'Role count should not change');
    assert($permCountBefore === $permCountAfter, 'Permission count should not change');
    
    echo "  ✓ Seeders are idempotent (safe to run multiple times)\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== All Seeder Tests Completed ===\n";
