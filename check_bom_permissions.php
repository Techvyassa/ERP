<?php

/**
 * Quick diagnostic script to check BOM permissions
 * Run: php check_bom_permissions.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Control\Organization;
use Illuminate\Support\Facades\DB;

echo "=== BOM Permission Diagnostic ===\n\n";

// Get organization
$orgSlug = 'techvyassa';
$org = Organization::where('org_slug', $orgSlug)->first();

if (!$org) {
    echo "❌ Organization '{$orgSlug}' not found!\n";
    exit(1);
}

echo "✓ Organization: {$org->org_name}\n";
echo "✓ Database: {$org->tenant_db_name}\n\n";

// Switch to tenant database
config(['database.connections.tenant.database' => $org->tenant_db_name]);
DB::purge('tenant');
DB::reconnect('tenant');

echo "--- Checking BOM Permissions ---\n\n";

// Get all roles
$roles = DB::connection('tenant')->table('role_master')->get();

echo "Total Roles: " . $roles->count() . "\n\n";

foreach ($roles as $role) {
    // Check if BOM permission exists
    $bomPermission = DB::connection('tenant')
        ->table('role_permissions')
        ->where('role_id', $role->id)
        ->where('module_code', 'BOM')
        ->first();
    
    if ($bomPermission) {
        echo "✓ {$role->role_code}: ";
        $perms = [];
        if ($bomPermission->can_view) $perms[] = 'VIEW';
        if ($bomPermission->can_create) $perms[] = 'CREATE';
        if ($bomPermission->can_edit) $perms[] = 'EDIT';
        if ($bomPermission->can_approve) $perms[] = 'APPROVE';
        if ($bomPermission->can_delete) $perms[] = 'DELETE';
        echo implode(', ', $perms) ?: 'NO PERMISSIONS';
        echo "\n";
    } else {
        echo "❌ {$role->role_code}: BOM permission NOT FOUND\n";
    }
}

echo "\n--- Checking Users ---\n\n";

// Get all users
$users = DB::connection('tenant')->table('user_master')->get();

echo "Total Users: " . $users->count() . "\n\n";

foreach ($users as $user) {
    // Get user's role
    $role = DB::connection('tenant')
        ->table('role_master')
        ->where('id', $user->role_id)
        ->first();
    
    if (!$role) {
        echo "❌ User {$user->employee_code}: No role assigned\n";
        continue;
    }
    
    // Check BOM permission
    $bomPermission = DB::connection('tenant')
        ->table('role_permissions')
        ->where('role_id', $role->id)
        ->where('module_code', 'BOM')
        ->first();
    
    echo "User: {$user->employee_code} ({$user->email})\n";
    echo "  Role: {$role->role_code}\n";
    
    if ($bomPermission) {
        echo "  BOM Permissions: ";
        $perms = [];
        if ($bomPermission->can_view) $perms[] = 'VIEW';
        if ($bomPermission->can_create) $perms[] = 'CREATE';
        if ($bomPermission->can_edit) $perms[] = 'EDIT';
        if ($bomPermission->can_approve) $perms[] = 'APPROVE';
        if ($bomPermission->can_delete) $perms[] = 'DELETE';
        echo implode(', ', $perms) ?: 'NONE';
        echo "\n";
    } else {
        echo "  BOM Permissions: ❌ NOT FOUND\n";
    }
    echo "\n";
}

echo "=== Diagnostic Complete ===\n";
