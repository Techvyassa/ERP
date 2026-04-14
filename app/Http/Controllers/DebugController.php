<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\User;
use App\Models\Tenant\RolePermission;

class DebugController extends Controller
{
    /**
     * GET /api/v1/debug/my-permissions
     * Returns the current user's permissions and clears their cache.
     * Dev/debug use only — should be removed or guarded in production.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $userId   = $request->input('auth_user_id');
        $tenantDb = $request->input('tenant_db_name');

        config(['database.connections.tenant.database' => $tenantDb]);

        $user        = User::with('role')->find($userId);
        $permissions = RolePermission::where('role_id', $user->role_id)->get();

        Cache::forget("rbac:user:{$tenantDb}:{$userId}:permissions");

        return response()->json([
            'success' => true,
            'data'    => [
                'user_id'            => $userId,
                'user_email'         => $user->email,
                'role_id'            => $user->role_id,
                'role_name'          => $user->role->role_name ?? 'N/A',
                'role_code'          => $user->role->role_code ?? 'N/A',
                'permissions_count'  => $permissions->count(),
                'permissions'        => $permissions->map(fn($p) => [
                    'module'  => $p->module_code,
                    'view'    => $p->can_view,
                    'create'  => $p->can_create,
                    'edit'    => $p->can_edit,
                    'approve' => $p->can_approve,
                    'delete'  => $p->can_delete,
                ]),
                'cache_cleared' => true,
            ],
            'message' => 'User permissions retrieved and cache cleared',
        ]);
    }
}
