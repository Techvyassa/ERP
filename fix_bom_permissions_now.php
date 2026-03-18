<?php

/**
 * ONE-CLICK BOM PERMISSION FIX
 * 
 * This script will:
 * 1. Check current BOM permissions
 * 2. Add missing BOM permissions
 * 3. Clear permission cache
 * 4. Verify the fix
 * 
 * Run: php fix_bom_permissions_now.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Control\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         ONE-CLICK BOM PERMISSION FIX                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Configuration
$orgSlug = 'techvyassa';

// Step 1: Find organization
echo "Step 1: Finding organization...\n";
$org = Organization::where('org_slug', $orgSlug)->first();

if (!$org) {
    echo "❌ ERROR: Organization '{$orgSlug}' not found!\n";
    exit(1);
}

echo "✓ Found: {$org->org_name}\n";
echo "✓ Database: {$org->tenant_db_name}\n\n";

// Switch to tenant database
config(['database.connections.tenant.database' => $org->tenant_db_name]);
DB::purge('tenant');
DB::reconnect('tenant');

// Step 2: Check current state
echo "Step 2: Checking current BOM permissions...\n";

$roles = DB::connection('tenant')->table('role_master')->get();
$missingCount = 0;
$existingCount = 0;

foreach ($roles as $role) {
    $bomPermission = DB::connection('tenant')
        ->table('role_permissions')
        ->where('role_id', $role->id)
        ->where('module_code', 'BOM')
        ->first();
    
    if ($bomPermission) {
        $existingCount++;
    } else {
        $missingCount++;
    }
}

echo "  Roles with BOM permissions: {$existingCount}\n";
echo "  Roles missing BOM permissions: {$missingCount}\n\n";

// Step 3: Add BOM permissions
if ($missingCount > 0) {
    echo "Step 3: Adding BOM permissions...\n";
    
    $rolePermissions = [
        'ADMIN'           => [true, true, true, true, true],
        'PROC_EXE'        => [true, false, false, false, false],
        'PROC_MGR'        => [true, false, false, false, false],
        'SECURITY_GUARD'  => [false, false, false, false, false],
        'SECURITY_SUPVR'  => [false, false, false, false, false],
        'STOREKEEPER'     => [false, false, false, false, false],
        'STORE_MGR'       => [false, false, false, false, false],
        'QC_TECH'         => [false, false, false, false, false],
        'QC_MGR'          => [false, false, false, false, false],
        'AP_CLERK'        => [false, false, false, false, false],
        'FIN_MGR'         => [false, false, false, false, false],
        'CFO'             => [false, false, false, false, false],
        'PPC_USER'        => [true, true, true, false, false],
    ];
    
    $addedCount = 0;
    $updatedCount = 0;
    
    foreach ($rolePermissions as $roleCode => $permissions) {
        $role = DB::connection('tenant')
            ->table('role_master')
            ->where('role_code', $roleCode)
            ->first();
        
        if (!$role) {
            continue;
        }
        
        $existing = DB::connection('tenant')
            ->table('role_permissions')
            ->where('role_id', $role->id)
            ->where('module_code', 'BOM')
            ->first();
        
        if ($existing) {
            DB::connection('tenant')
                ->table('role_permissions')
                ->where('role_id', $role->id)
                ->where('module_code', 'BOM')
                ->update([
                    'can_view'    => $permissions[0],
                    'can_create'  => $permissions[1],
                    'can_edit'    => $permissions[2],
                    'can_approve' => $permissions[3],
                    'can_delete'  => $permissions[4],
                ]);
            $updatedCount++;
            echo "  ✓ Updated: {$roleCode}\n";
        } else {
            DB::connection('tenant')
                ->table('role_permissions')
                ->insert([
                    'role_id'     => $role->id,
                    'module_code' => 'BOM',
                    'can_view'    => $permissions[0],
                    'can_create'  => $permissions[1],
                    'can_edit'    => $permissions[2],
                    'can_approve' => $permissions[3],
                    'can_delete'  => $permissions[4],
                    'created_by'  => null,
                ]);
            $addedCount++;
            echo "  ✓ Added: {$roleCode}\n";
        }
    }
    
    echo "\n  Summary: Added {$addedCount}, Updated {$updatedCount}\n\n";
} else {
    echo "Step 3: All roles already have BOM permissions ✓\n\n";
}

// Step 4: Clear permission cache
echo "Step 4: Clearing permission cache...\n";

$users = DB::connection('tenant')->table('user_master')->get();
$clearedCount = 0;

foreach ($users as $user) {
    $cacheKey = "rbac:user:{$user->id}:permissions";
    if (Cache::has($cacheKey)) {
        Cache::forget($cacheKey);
        $clearedCount++;
    }
}

echo "  ✓ Cleared cache for {$clearedCount} users\n\n";

// Step 5: Verify
echo "Step 5: Verifying fix...\n";

$verifyCount = 0;
$adminHasBOM = false;

foreach ($roles as $role) {
    $bomPermission = DB::connection('tenant')
        ->table('role_permissions')
        ->where('role_id', $role->id)
        ->where('module_code', 'BOM')
        ->first();
    
    if ($bomPermission) {
        $verifyCount++;
        if ($role->role_code === 'ADMIN' && $bomPermission->can_view) {
            $adminHasBOM = true;
        }
    }
}

echo "  Roles with BOM permissions: {$verifyCount}/{$roles->count()}\n";

if ($adminHasBOM) {
    echo "  ✓ ADMIN role has BOM view permission\n";
} else {
    echo "  ⚠ WARNING: ADMIN role missing BOM permissions\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    FIX COMPLETE!                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Next steps:\n";
echo "1. Refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "2. Navigate to: http://127.0.0.1:8000/org/{$orgSlug}/bom-header\n";
echo "3. The 'Insufficient permissions' alert should be gone\n";
echo "\n";
echo "If you still see the error:\n";
echo "- Try logging out and back in\n";
echo "- Clear browser cookies\n";
echo "- Run: php artisan cache:clear\n";
echo "\n";
