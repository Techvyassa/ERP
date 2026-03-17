<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant\User;
use App\Models\Tenant\RolePermission;
use App\Contracts\DatabaseConnectionRouter;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * RBAC Middleware - CheckModulePermission
 * 
 * Switches to Tenant DB connection
 * Loads user's role_permissions by role_id
 * Checks can_view/can_create/can_edit/can_approve/can_delete flags
 * Caches permissions for 15 minutes in Redis
 * Logs permission denials
 * Returns 403 for insufficient permissions
 * 
 * Requirements: 9.1-9.10
 */
class CheckModulePermission
{
    protected DatabaseConnectionRouter $dbRouter;

    public function __construct(DatabaseConnectionRouter $dbRouter)
    {
        $this->dbRouter = $dbRouter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $moduleCode  The module code to check permissions for
     */
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        // Get user_id from request (set by ValidateJWT middleware)
        $userId = $request->input('auth_user_id');

        // Get tenant context (set by ResolveTenant middleware)
        $tenantDbName = $request->input('tenant_db_name');

        if (!$userId || !$tenantDbName) {
            return $this->errorResponse(
                'Authentication context required',
                'AUTH_CONTEXT_REQUIRED',
                400
            );
        }

        // Determine action from HTTP method
        $method = $request->method();
        $action = match ($method) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view'
        };

        try {
            // Switch to Tenant DB connection
            $this->dbRouter->switchToTenant($tenantDbName);

            // Requirement 9.8: Cache user permissions for 15 minutes
            $cacheKey = "rbac:user:{$userId}:permissions";
            $permissions = Cache::remember($cacheKey, 900, function () use ($userId) {
                return $this->loadUserPermissions($userId);
            });

            if ($permissions === null) {
                return $this->errorResponse(
                    'User not found',
                    'USER_NOT_FOUND',
                    404
                );
            }

            // Check if user has permission for the module
            if (!isset($permissions[$moduleCode])) {
                // No permissions defined for this module - deny by default
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            $modulePermissions = $permissions[$moduleCode];

            // Requirement 9.2, 9.3: Check can_view permission
            if ($action === 'view' && !$modulePermissions['can_view']) {
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            // Requirement 9.4: Check can_create permission
            if ($action === 'create' && !$modulePermissions['can_create']) {
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            // Requirement 9.5: Check can_edit permission
            if ($action === 'edit' && !$modulePermissions['can_edit']) {
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            // Requirement 9.6: Check can_approve permission
            if ($action === 'approve' && !$modulePermissions['can_approve']) {
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            // Requirement 9.7: Check can_delete permission
            if ($action === 'delete' && !$modulePermissions['can_delete']) {
                $this->logPermissionDenial($userId, $moduleCode, $action);

                return $this->errorResponse(
                    'Insufficient permissions',
                    'PERMISSION_DENIED',
                    403
                );
            }

            // Permission granted
            $request->merge([
                'user_permissions' => $permissions,
                'module_permissions' => $modulePermissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking module permissions', [
                'user_id' => $userId,
                'module_code' => $moduleCode,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Permission check failed',
                'PERMISSION_CHECK_ERROR',
                500
            );
        }

        return $next($request);
    }

    /**
     * Load user permissions from database
     * Requirement 9.1: Load permissions from role_permissions table
     */
    private function loadUserPermissions(int $userId): ?array
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        // Get all permissions for user's role
        $rolePermissions = RolePermission::where('role_id', $user->role_id)->get();

        // Transform to array keyed by module_code
        $permissions = [];
        foreach ($rolePermissions as $permission) {
            $permissions[$permission->module_code] = [
                'can_view' => $permission->can_view,
                'can_create' => $permission->can_create,
                'can_edit' => $permission->can_edit,
                'can_approve' => $permission->can_approve,
                'can_delete' => $permission->can_delete,
            ];
        }

        return $permissions;
    }

    /**
     * Requirement 9.10: Log permission denial events
     */
    private function logPermissionDenial(int $userId, string $moduleCode, string $action): void
    {
        // Get org_id from request context
        $orgId = request()->input('tenant_org_id');

        // Get user's role_id if available
        $user = User::find($userId);
        $roleId = $user ? $user->role_id : null;

        AuditLogger::logPermissionDenial($userId, $moduleCode, $action, $orgId, $roleId);
    }

    /**
     * Return consistent error response
     */
    private function errorResponse(string $message, string $code, int $status): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'details' => []
            ],
            'message' => $message,
            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String()
        ], $status);
    }
}
