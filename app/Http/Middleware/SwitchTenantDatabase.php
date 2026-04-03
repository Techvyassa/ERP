<?php

namespace App\Http\Middleware;

use App\Contracts\DatabaseConnectionRouter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwitchTenantDatabase
{
    public function handle(Request $request, Closure $next, ?string $connectionName = 'tenant'): Response
    {
        /** @var DatabaseConnectionRouter $router */
        $router = app(DatabaseConnectionRouter::class);

        $tenantDbName = $request->get('tenant_db_name');

        if ($tenantDbName) {
            $router->switchToTenant($tenantDbName);
        }

        try {
            return $next($request);
        } finally {
            $router->switchToControl();
        }
    }
}
