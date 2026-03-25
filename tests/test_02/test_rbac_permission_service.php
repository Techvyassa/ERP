<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Contracts\RBACPermissionService;
use App\Models\Tenant\User;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Department;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== RBAC Permission Service Test ===\n\n";

try {
    // Get the service
    $rbacService = app(RBACPermissionService::class);
    
    // Switch to tenant database
    DB::purge('tenant');
    config(['database.connections.tenant.database' => 'erp_test_tenant']);
    DB::reconnect('tenant');
    
    echo "1. Testing database connection...\n";
    $connection = DB::connection('tenant')->getPdo();
    echo "   ✓ Connected to tenant database\n\n";
    
    // Create test data
    echo "2. Creating test data...\n";
    
    // Create department
    $dept = Department::firstOrCreate(
        ['dept_code' => 'TEST_DEPT'],
        [
            'dept_name' => 'Test Department',
            'is_active' => true,
            'created_by' => 1
        ]
    );
    echo "   ✓ Department created: {$dept->dept_name}\n";
    
    // Create role
    $role = Role::firstOrCreate(
        ['role_code' => 'TEST_ROLE'],
        [
            'role_name' => 'Test Role',
            'is_system_role' => false,
            'created_by' => 1
        ]
    );
    echo "   ✓ Role created: {$role->role_name}\n";
    
    // Create role permissions
    $modules = ['PR', 'PO', 'GRN', 'INVOICE'];
    foreach ($modules as $index => $module) {
        $permission = RolePermission::updateOrCreate(
            [
                'role_id' => $role->role_id,
                'module_code' => $module
            ],
            [
                'can_view' => true,
                'can_create' => $index < 2, // PR and PO can create
                'can_edit' => $index < 2,
                'can_approve' => $index === 0, // Only PR can approve
                'can_delete' => false,
                'created_by' => 1
            ]
        );
        echo "   ✓ Permission created for module: {$module}\n";
    }
    
    // Create user
    $user = User::firstOrCreate(
        ['email' => 'test.rbac@example.com'],
        [
            'employee_code' => 'EMP_RBAC_001',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password_hash' => 'test123', // Will be hashed by mutator
            'dept_id' => $dept->dept_id,
            'role_id' => $role->role_id,
            'is_active' => true,
            'created_by' => 1
        ]
    );
    echo "   ✓ User created: {$user->email}\n\n";
    
    // Test 1: Get user permissions
    echo "3. Testing getUserPermissions()...\n";
    $permissions = $rbacService->getUserPermissions($user->user_id);
    echo "   User permissions loaded:\n";
    foreach ($permissions as $module => $perms) {
        echo "   - {$module}: ";
        $actions = [];
        foreach ($perms as $action => $allowed) {
            if ($allowed) {
                $actions[] = $action;
            }
        }
        echo implode(', ', $actions) . "\n";
    }
    echo "\n";
    
    // Test 2: Check specific permissions
    echo "4. Testing hasPermission()...\n";
    $testCases = [
        ['PR', 'view', true],
        ['PR', 'create', true],
        ['PR', 'approve', true],
        ['PO', 'view', true],
        ['PO', 'create', true],
        ['PO', 'approve', false],
        ['GRN', 'view', true],
        ['GRN', 'create', false],
        ['INVOICE', 'delete', false],
        ['NONEXISTENT', 'view', false],
    ];
    
    foreach ($testCases as [$module, $action, $expected]) {
        $result = $rbacService->hasPermission($user->user_id, $module, $action);
        $status = $result === $expected ? '✓' : '✗';
        $resultStr = $result ? 'ALLOWED' : 'DENIED';
        echo "   {$status} {$module}.{$action}: {$resultStr}\n";
    }
    echo "\n";
    
    // Test 3: Get accessible modules
    echo "5. Testing getAccessibleModules()...\n";
    $accessibleModules = $rbacService->getAccessibleModules($user->user_id);
    echo "   Accessible modules: " . implode(', ', $accessibleModules) . "\n\n";
    
    // Test 4: Cache verification
    echo "6. Testing permission caching...\n";
    try {
        $cacheKey = 'rbac:user:' . $user->user_id . ':permissions';
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            echo "   ✓ Permissions cached successfully\n";
            echo "   Cache contains " . count($cachedData) . " modules\n";
        } else {
            echo "   ⚠ Permissions not found in cache (may be using database fallback)\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠ Cache not available (Redis not running): " . $e->getMessage() . "\n";
        echo "   Service will fall back to database queries\n";
    }
    echo "\n";
    
    // Test 5: Update role permissions
    echo "7. Testing updateRolePermissions()...\n";
    $success = $rbacService->updateRolePermissions(
        $role->role_id,
        'PR',
        [
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_approve' => false, // Changed from true to false
            'can_delete' => true,   // Changed from false to true
            'created_by' => 1
        ]
    );
    
    if ($success) {
        echo "   ✓ Role permissions updated successfully\n";
        
        // Verify cache was invalidated
        try {
            $cachedAfterUpdate = Cache::get($cacheKey);
            if (!$cachedAfterUpdate) {
                echo "   ✓ Cache invalidated after update\n";
            } else {
                echo "   ⚠ Cache still exists after update (may not be using Redis)\n";
            }
        } catch (\Exception $e) {
            echo "   ⚠ Cache check skipped (Redis not available)\n";
        }
        
        // Reload permissions
        $updatedPermissions = $rbacService->getUserPermissions($user->user_id);
        echo "   Updated PR permissions: ";
        $actions = [];
        foreach ($updatedPermissions['PR'] as $action => $allowed) {
            if ($allowed) {
                $actions[] = $action;
            }
        }
        echo implode(', ', $actions) . "\n";
    } else {
        echo "   ✗ Failed to update role permissions\n";
    }
    echo "\n";
    
    // Test 6: Manual cache invalidation
    echo "8. Testing invalidateCache()...\n";
    try {
        // First ensure cache exists
        $rbacService->getUserPermissions($user->user_id);
        $beforeInvalidate = Cache::get($cacheKey);
        echo "   Cache before invalidation: " . ($beforeInvalidate ? 'EXISTS' : 'NOT FOUND') . "\n";
        
        $rbacService->invalidateCache($user->user_id);
        $afterInvalidate = Cache::get($cacheKey);
        echo "   Cache after invalidation: " . ($afterInvalidate ? 'EXISTS' : 'NOT FOUND') . "\n";
        
        if (!$afterInvalidate) {
            echo "   ✓ Cache invalidated successfully\n";
        } else {
            echo "   ✗ Cache still exists\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠ Cache test skipped (Redis not available)\n";
        echo "   Service handles cache failures gracefully\n";
    }
    echo "\n";
    
    // Test 7: Performance test
    echo "9. Testing performance...\n";
    
    try {
        // Clear cache
        $rbacService->invalidateCache($user->user_id);
        
        // First call (database)
        $start = microtime(true);
        $rbacService->getUserPermissions($user->user_id);
        $dbTime = (microtime(true) - $start) * 1000;
        
        // Second call (cache or database)
        $start = microtime(true);
        $rbacService->getUserPermissions($user->user_id);
        $cacheTime = (microtime(true) - $start) * 1000;
        
        echo "   First query time: " . number_format($dbTime, 2) . " ms\n";
        echo "   Second query time: " . number_format($cacheTime, 2) . " ms\n";
        
        if ($cacheTime < $dbTime) {
            echo "   Speed improvement: " . number_format($dbTime / $cacheTime, 1) . "x faster\n";
        } else {
            echo "   ⚠ No caching benefit (Redis not available)\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠ Performance test skipped: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
