<?php

namespace App\Services;

use App\Contracts\RBACPermissionService;
use App\Models\Tenant\User;
use App\Models\Tenant\RolePermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RBACPermissionServiceImpl implements RBACPermissionService
{
    /**
     * Cache TTL in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;
    
    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'rbac:user:';
    
    /**
     * Check if user has permission for module action
     * 
     * @param int $userId
     * @param string $moduleCode
     * @param string $action (view|create|edit|approve|delete)
     * @return bool
     */
    public function hasPermission(int $userId, string $moduleCode, string $action): bool
    {
        try {
            // Get all user permissions (from cache or database)
            $permissions = $this->getUserPermissions($userId);
            
            // Check if module exists in permissions
            if (!isset($permissions[$moduleCode])) {
                return false;
            }
            
            // Check if action is permitted
            return $permissions[$moduleCode][$action] ?? false;
            
        } catch (\Exception $e) {
            Log::error('RBAC permission check failed', [
                'user_id' => $userId,
                'module_code' => $moduleCode,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            // Fail closed: deny access on error
            return false;
        }
    }
    
    /**
     * Get all permissions for user
     * 
     * @param int $userId
     * @return array Keyed by module_code
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = $this->getCacheKey($userId);
        
        try {
            // Try to get from cache
            $permissions = Cache::get($cacheKey);
            
            if ($permissions !== null) {
                return $permissions;
            }
            
            // Load from database
            $permissions = $this->loadPermissionsFromDatabase($userId);
            
            // Cache for 15 minutes
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);
            
            return $permissions;
            
        } catch (\Exception $e) {
            // If cache fails, just load from database
            Log::warning('Cache unavailable, loading permissions from database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return $this->loadPermissionsFromDatabase($userId);
        }
    }
    
    /**
     * Update role permissions
     * 
     * @param int $roleId
     * @param string $moduleCode
     * @param array $permissions
     * @return bool
     */
    public function updateRolePermissions(int $roleId, string $moduleCode, array $permissions): bool
    {
        try {
            DB::connection('tenant')->beginTransaction();
            
            // Find or create role permission record
            $rolePermission = RolePermission::where('role_id', $roleId)
                ->where('module_code', $moduleCode)
                ->first();
            
            if (!$rolePermission) {
                $rolePermission = new RolePermission();
                $rolePermission->role_id = $roleId;
                $rolePermission->module_code = $moduleCode;
            }
            
            // Update permission flags
            $rolePermission->can_view = $permissions['can_view'] ?? false;
            $rolePermission->can_create = $permissions['can_create'] ?? false;
            $rolePermission->can_edit = $permissions['can_edit'] ?? false;
            $rolePermission->can_approve = $permissions['can_approve'] ?? false;
            $rolePermission->can_delete = $permissions['can_delete'] ?? false;
            
            if (isset($permissions['created_by'])) {
                $rolePermission->created_by = $permissions['created_by'];
            }
            
            $rolePermission->save();
            
            // Invalidate cache for all users with this role
            $this->invalidateCacheForRole($roleId);
            
            DB::connection('tenant')->commit();
            
            Log::info('Role permissions updated', [
                'role_id' => $roleId,
                'module_code' => $moduleCode,
                'permissions' => $permissions
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            Log::error('Failed to update role permissions', [
                'role_id' => $roleId,
                'module_code' => $moduleCode,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Invalidate permission cache for user
     * 
     * @param int $userId
     */
    public function invalidateCache(int $userId): void
    {
        try {
            $cacheKey = $this->getCacheKey($userId);
            Cache::forget($cacheKey);
            
            Log::debug('Permission cache invalidated', [
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get modules accessible by user
     * 
     * @param int $userId
     * @return array Module codes where can_view = true
     */
    public function getAccessibleModules(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId);
        
        $accessibleModules = [];
        
        foreach ($permissions as $moduleCode => $modulePermissions) {
            if ($modulePermissions['view'] ?? false) {
                $accessibleModules[] = $moduleCode;
            }
        }
        
        return $accessibleModules;
    }
    
    /**
     * Load permissions from database
     * 
     * @param int $userId
     * @return array
     */
    private function loadPermissionsFromDatabase(int $userId): array
    {
        // Get user with role
        $user = User::with('role')->find($userId);
        
        if (!$user || !$user->role_id) {
            return [];
        }
        
        // Get all role permissions
        $rolePermissions = RolePermission::where('role_id', $user->role_id)->get();
        
        $permissions = [];
        
        foreach ($rolePermissions as $permission) {
            $permissions[$permission->module_code] = [
                'view' => $permission->can_view,
                'create' => $permission->can_create,
                'edit' => $permission->can_edit,
                'approve' => $permission->can_approve,
                'delete' => $permission->can_delete,
            ];
        }
        
        return $permissions;
    }
    
    /**
     * Get cache key for user permissions
     * 
     * @param int $userId
     * @return string
     */
    private function getCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId . ':permissions';
    }
    
    /**
     * Invalidate cache for all users with a specific role
     * 
     * @param int $roleId
     */
    private function invalidateCacheForRole(int $roleId): void
    {
        // Get all users with this role
        $users = User::where('role_id', $roleId)->get();
        
        foreach ($users as $user) {
            $this->invalidateCache($user->id);
        }
        
        Log::debug('Permission cache invalidated for role', [
            'role_id' => $roleId,
            'affected_users' => $users->count()
        ]);
    }
}
