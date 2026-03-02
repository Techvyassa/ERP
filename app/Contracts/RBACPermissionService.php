<?php

namespace App\Contracts;

interface RBACPermissionService
{
    /**
     * Check if user has permission for module action
     * 
     * @param int $userId
     * @param string $moduleCode
     * @param string $action (view|create|edit|approve|delete)
     * @return bool
     */
    public function hasPermission(int $userId, string $moduleCode, string $action): bool;
    
    /**
     * Get all permissions for user
     * 
     * @param int $userId
     * @return array Keyed by module_code
     */
    public function getUserPermissions(int $userId): array;
    
    /**
     * Update role permissions
     * 
     * @param int $roleId
     * @param string $moduleCode
     * @param array $permissions
     * @return bool
     */
    public function updateRolePermissions(int $roleId, string $moduleCode, array $permissions): bool;
    
    /**
     * Invalidate permission cache for user
     * 
     * @param int $userId
     */
    public function invalidateCache(int $userId): void;
    
    /**
     * Get modules accessible by user
     * 
     * @param int $userId
     * @return array Module codes where can_view = true
     */
    public function getAccessibleModules(int $userId): array;
}
